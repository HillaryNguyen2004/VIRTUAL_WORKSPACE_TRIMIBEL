from __future__ import annotations
import logging
import re
import unicodedata
from concurrent.futures import ThreadPoolExecutor, as_completed, Future
from typing import List, Dict, Any, Callable, Optional

from .embeddings.ollama import embed_query, embed_texts
from .vectorstores.chroma_store import query_by_vector
from .config import settings
from .ollama_generate import GenerationCancelled

log = logging.getLogger(__name__)

# =========================
# TEXT NORMALIZATION
# =========================
def _normalize(text: str) -> str:
    text = text.lower().strip()
    text = unicodedata.normalize("NFD", text)
    text = "".join(ch for ch in text if unicodedata.category(ch) != "Mn")
    return re.sub(r"\s+", " ", text)


# =========================
# QUERY EXPANSION (paraphrase)
# =========================
_PARAPHRASE_PROMPT = """\
You are a search query assistant. Given a user question, generate {n} alternative phrasings \
that express the same intent using different words or structure.

Rules:
- Each alternative must preserve the original meaning.
- Use synonyms, different word orders, or simplified phrasing.
- Output ONLY a numbered list (1. ... 2. ... 3. ...), nothing else.
- Do NOT add explanations or extra text.

Original question: {question}
"""

def expand_query(
    question: str,
    n: int = 3,
    should_cancel: Optional[Callable[[], bool]] = None,
) -> List[str]:
    """
    Generate n paraphrase variants of the question using the local LLM.
    Falls back to the original question only if generation fails.
    """
    try:
        # Import here to avoid circular import
        from .ollama_generate import generate_answer

        if should_cancel and should_cancel():
            return [question]

        prompt = _PARAPHRASE_PROMPT.format(n=n, question=question)
        raw, _ = generate_answer(prompt, temperature=0.4, stream=False, should_cancel=should_cancel)

        variants: List[str] = []
        for line in raw.splitlines():
            line = line.strip()
            # strip leading "1. " / "- " / "* "
            cleaned = re.sub(r"^[\d]+[.)]\s*|^[-*]\s*", "", line).strip()
            if cleaned and cleaned.lower() != question.lower():
                variants.append(cleaned)

        if not variants:
            return [question]

        log.debug("Query expansion: %d variants for %r", len(variants), question)
        return variants[:n]

    except Exception as e:
        log.warning("Query expansion failed, using original: %s", e)
        return [question]


# =========================
# CROSS-LINGUAL TRANSLATION
# =========================
_TRANSLATE_PROMPT = """\
Translate the following search query into {target_lang}.
Output ONLY the translated query, no explanation, no extra text.

Query: {query}
"""

def _translate_query(
    query: str,
    src_lang: str,
    should_cancel: Optional[Callable[[], bool]] = None,
) -> Optional[str]:
    """
    Translate query to the other language (vi↔en).
    Returns the translated string, or None on failure.
    """
    if src_lang not in ("vi", "en"):
        return None

    target_lang = "English" if src_lang == "vi" else "Vietnamese"
    try:
        from .ollama_generate import generate_answer

        if should_cancel and should_cancel():
            return None

        prompt = _TRANSLATE_PROMPT.format(target_lang=target_lang, query=query)
        translated, _ = generate_answer(prompt, temperature=0.1, stream=False, should_cancel=should_cancel)
        translated = translated.strip()
        if translated and translated.lower() != query.lower():
            log.debug("Cross-lingual translation (%s→%s): %r → %r", src_lang, target_lang, query, translated)
            return translated
    except Exception as e:
        log.warning("Query translation failed: %s", e)
    return None


# =========================
# CONVERSATION-AWARE QUERY
# =========================
def build_context_query(
    question: str,
    history: List[Dict[str, str]],
    max_turns: int = 3,
) -> str:
    """
    Prepend the last N user turns from conversation history to the current
    question so that vague follow-up queries ("what about it?", "còn bước tiếp?")
    carry enough context for the embedding model.

    Only user turns are included (not assistant answers) to keep the query
    focused on what the user is asking, not the answers already given.

    Returns the enriched query string. Falls back to the original question
    if history is empty or all turns are non-user.
    """
    if not history:
        return question

    user_turns = [
        msg["content"].strip()
        for msg in history
        if msg.get("role") == "user" and msg.get("content", "").strip()
    ]

    # Take only the most recent max_turns turns (excluding the current question
    # which will be appended separately).
    recent = user_turns[-max_turns:]

    if not recent:
        return question

    # Join past turns with the current question, separated clearly.
    context_parts = recent + [question]
    enriched = " | ".join(context_parts)
    log.debug("Context-aware query (%d past turns): %r", len(recent), enriched[:120])
    return enriched

# =========================
# KEYWORD RERANK SCORE
# =========================
def _keyword_score(query: str, passage_text: str) -> float:
    """
    Token-overlap score between query tokens (len >= 2) and passage text.
    Returns 0.0–1.0.
    """
    tokens = [t for t in re.findall(r"\w+", _normalize(query)) if len(t) >= 2]
    if not tokens:
        return 0.0
    content = _normalize(passage_text or "")
    hits = sum(1 for t in set(tokens) if t in content)
    return hits / len(set(tokens))


# =========================
# RECIPROCAL RANK FUSION
# =========================
def _rrf_merge(
    ranked_lists: List[List[Dict[str, Any]]],
    k: int = 60,
) -> List[Dict[str, Any]]:
    """
    Merge multiple ranked lists with Reciprocal Rank Fusion.
    Higher RRF score = better combined rank.
    """
    scores: Dict[str, float] = {}
    docs: Dict[str, Dict[str, Any]] = {}

    for ranked in ranked_lists:
        for rank, doc in enumerate(ranked, start=1):
            doc_id = doc["id"]
            scores[doc_id] = scores.get(doc_id, 0.0) + 1.0 / (k + rank)
            docs[doc_id] = doc

    merged = sorted(docs.values(), key=lambda d: scores[d["id"]], reverse=True)

    # Attach rrf_score for downstream logging
    for doc in merged:
        doc["rrf_score"] = round(scores[doc["id"]], 6)

    return merged

# =========================
# SINGLE-QUERY FETCH
# =========================
def _fetch_one(
    query_text: str,
    k: int,
    workspace_id: Optional[str],
    where: Optional[dict],
    should_cancel: Optional[Callable[[], bool]],
) -> List[Dict[str, Any]]:
    if should_cancel and should_cancel():
        raise GenerationCancelled("Retrieval canceled before embedding")

    qv = embed_query(query_text, should_cancel=should_cancel)

    if should_cancel and should_cancel():
        raise GenerationCancelled("Retrieval canceled before vector query")

    raw = query_by_vector(qv, k, workspace_id=workspace_id, where=where)

    if should_cancel and should_cancel():
        raise GenerationCancelled("Retrieval canceled after vector query")

    return raw


# =========================
# MAIN RETRIEVE
# =========================
def retrieve(
    query_text: str,
    k: int | None = None,
    workspace_id: str | None = None,
    where: dict | None = None,
    should_cancel: Optional[Callable[[], bool]] = None,
    expand: bool = True,
    src_lang: str | None = None,
    history: Optional[List[Dict[str, str]]] = None,
) -> List[Dict[str, Any]]:
    """
    Multi-query retrieval with RRF reranking + cross-lingual + conversation-aware support.

    Steps:
    1. Build context-enriched query from conversation history + current question.
    2. Expand the context query into paraphrase variants (same language).
    3. Translate the original question into the other language (vi↔en).
    4. Embed and fetch candidates for every query string in parallel (overfetch x3).
    5. Merge all candidate lists with Reciprocal Rank Fusion.
    6. Rerank the merged list by combined RRF + keyword score (against original question).
    7. Return top-k.
    """
    base_k = k or settings.top_k
    fetch_k = base_k * 3   # overfetch per variant

    # --- 1. Conversation-aware query enrichment ---
    # Use enriched query for embedding/expansion so vague follow-ups carry context.
    # Keep query_text (the raw question) for keyword scoring to avoid noise.
    context_query = build_context_query(query_text, history or [], max_turns=3)

    # --- 2. Query expansion (same language paraphrases, on enriched query) ---
    if expand and context_query.strip():
        variants = expand_query(context_query, n=3, should_cancel=should_cancel)
        # Always include the context-enriched query first
        all_queries = [context_query] + [v for v in variants if v != context_query]
    else:
        all_queries = [context_query] if context_query.strip() else []

    # --- 3. Cross-lingual: translate the original question (not the enriched one) ---
    # We translate query_text (not context_query) to keep the translation clean.
    if expand and query_text.strip() and src_lang in ("vi", "en"):
        translated = _translate_query(query_text, src_lang, should_cancel=should_cancel)
        if translated and translated not in all_queries:
            all_queries.append(translated)

    if not all_queries:
        return []

    log.debug("retrieve: %d queries for %r", len(all_queries), query_text)

    # --- 4. Parallel fetch per variant ---
    # Each variant is independent — run them concurrently to cut latency.
    # Cap at 8 workers so we don't overwhelm the Ollama embed endpoint.
    ranked_lists: List[List[Dict[str, Any]]] = []
    _cancelled_from_thread: list[bool] = [False]

    def _fetch_safe(q: str) -> List[Dict[str, Any]]:
        try:
            return _fetch_one(q, fetch_k, workspace_id, where, should_cancel)
        except GenerationCancelled:
            _cancelled_from_thread[0] = True
            return []
        except Exception as e:
            log.warning("Fetch failed for query variant %r: %s", q, e)
            return []

    n_workers = min(len(all_queries), 8)
    with ThreadPoolExecutor(max_workers=n_workers) as pool:
        futures: List[Future] = [pool.submit(_fetch_safe, q) for q in all_queries]
        for fut in as_completed(futures):
            if _cancelled_from_thread[0]:
                pool.shutdown(wait=False, cancel_futures=True)
                raise GenerationCancelled("Retrieval canceled during parallel fetch")
            results = fut.result()
            if results:
                ranked_lists.append(results)

    if not ranked_lists:
        return []

    # --- 5. RRF merge ---
    merged = _rrf_merge(ranked_lists)

    # --- 6. Rerank: RRF score * (1 + keyword_score) ---
    for doc in merged:
        kw = _keyword_score(query_text, str(doc.get("content", "")))
        doc["_final_score"] = doc["rrf_score"] * (1.0 + kw)

    merged.sort(key=lambda d: d["_final_score"], reverse=True)

    log.debug(
        "retrieve: merged %d unique docs → returning top %d",
        len(merged),
        base_k,
    )

    return merged[:base_k]
