"""
summary_agent.py
================
Map-reduce summarisation agent with per-file grouping and rich citations.

Pipeline for summarize_workspace / summarize_s3_document
---------------------------------------------------------
1. Load all chunks + stored embeddings from ChromaDB.
2. Group chunks by source file (metadata["storage_file"]).
3. For each file:
   a. Run K-Means → pick one representative chunk per cluster (MAP phase).
   b. Summarise those representatives with the LLM → one file-level summary.
4. Combine all file-level summaries into a final REDUCE call to the LLM.
5. Return the final summary plus rich per-file citations.

Benefits over the old single-pass approach
------------------------------------------
- Larger documents get proper coverage: each file's content is summarised
  independently before being merged, so no file drowns out the others.
- Citations now include filename, page, and section pulled from chunk metadata.
- Auto k: k is derived from each file's chunk count so the caller rarely
  needs to tune n_clusters.

For raw text / chat messages (not yet in ChromaDB) the pipeline falls back
to the original embed-on-the-fly → K-Means → single LLM call.

Public API
----------
summarize_workspace(workspace_id, user_id, lang, style, n_clusters, should_cancel)
summarize_text(text, lang, style, n_clusters, should_cancel)
summarize_s3_document(s3_key, workspace_id, lang, style, n_clusters, should_cancel)
summarize_messages(messages, lang, style, n_clusters, should_cancel)
"""
from __future__ import annotations

import logging
from collections import defaultdict
from typing import Callable, Dict, Iterator, List, Optional, Tuple

import numpy as np

from .embeddings.ollama import embed_texts
from .ollama_generate import generate_answer, stream_answer, GenerationCancelled
from .vectorstores.chroma_store import get_collection, normalize_workspace_id

log = logging.getLogger(__name__)

# ---------------------------------------------------------------------------
# Constants
# ---------------------------------------------------------------------------
_CHUNK_SIZE         = 800
_CHUNK_OVERLAP      = 100
_MAX_CLUSTERS       = 20
_MIN_CLUSTERS       = 3
_MAX_CHUNK_CHARS    = 1800  # chars per excerpt sent to LLM
_MAX_FILE_SUMMARY_CHARS = 4000  # cap each file-level summary fed into reduce
_MIN_CHUNK_CHARS    = 40    # skip chunks that are too short to carry meaning

_SYSTEM_PROMPT = """\
You are a senior analyst writing a professional document summary.

Rules you must follow:
1. Read the excerpts carefully, understand the subject matter, then write a genuine summary.
2. Choose section headings that match the ACTUAL content of this specific document (e.g. for a marketing lecture: **Overview**, **Product Classifications**, **Service Marketing**; for a financial report: **Revenue**, **Expenses**, **Key Metrics**). Do NOT use generic headings like "Key Figures", "Action Items", or "Important Dates" unless the document actually contains those things.
3. Under each section, write concise bullet points in your own words. Each bullet must begin with the most important concept or fact.
4. Extract and preserve ALL specific facts: numbers, percentages, names, dates, amounts, and identifiers. Never paraphrase a number away.
5. Do NOT copy or repeat the excerpt labels ([Excerpt N]) in your output. Do NOT list citations as bullet points. Use [Excerpt N] only as a brief inline reference after a specific fact, e.g. "Revenue grew 12% [Excerpt 2]."
6. Do NOT hallucinate any fact, name, number, or date that is not present in the excerpts.
7. Do NOT write generic filler phrases like "the document discusses" or "as mentioned above".
8. Write in the language specified by the instruction below.\
"""

_REDUCE_SYSTEM_PROMPT = """\
You are a senior analyst synthesising multiple document summaries into one final report.

Rules you must follow:
1. Read all document summaries, understand their shared subject, then produce a unified summary with section headings that match the ACTUAL content (e.g. for a product management course: **Overview**, **Product Classifications**, **Decisions & Strategies**; not generic headings).
2. Under each section, write concise bullet points in your own words, starting with the most important concept.
3. When citing a fact from a specific document, use [Document N] only as a brief inline reference after the fact.
4. Explicitly call out any contradictions or gaps between documents.
5. Preserve ALL specific numbers, names, dates, and identifiers — never round or paraphrase them away.
6. Do NOT hallucinate. If something is uncertain, say so.
7. Do NOT write generic filler phrases.
8. Write in the language specified by the instruction below.\
"""


# ---------------------------------------------------------------------------
# K-Means (pure numpy, K-Means++ initialisation)
# ---------------------------------------------------------------------------

def _kmeans(vectors: np.ndarray, k: int, max_iter: int = 100, seed: int = 42) -> np.ndarray:
    """
    Cluster `vectors` into `k` groups.
    Returns label array of shape (n,) where label[i] is the cluster index for vectors[i].

    Uses K-Means++ initialisation so the starting centroids are spread out,
    which gives faster convergence and avoids degenerate clusters.
    """
    rng = np.random.default_rng(seed)
    n   = len(vectors)
    k   = min(k, n)

    # K-Means++ init: first centroid random, each subsequent one chosen
    # with probability proportional to squared distance from nearest existing centroid.
    centroids = [vectors[rng.integers(n)]]
    for _ in range(k - 1):
        # squared distance from each point to its nearest centroid so far
        dists = np.array([
            min(float(np.dot(v - c, v - c)) for c in centroids)
            for v in vectors
        ])
        total = dists.sum()
        probs = dists / total if total > 0 else np.ones(n) / n
        centroids.append(vectors[rng.choice(n, p=probs)])
    centroids = np.array(centroids, dtype=np.float32)

    labels = np.zeros(n, dtype=np.int32)
    for _ in range(max_iter):
        # Assign each point to the nearest centroid
        diffs      = vectors[:, None, :] - centroids[None, :, :]   # (n, k, d)
        dists2     = (diffs ** 2).sum(axis=2)                       # (n, k)
        new_labels = dists2.argmin(axis=1)                          # (n,)
        if np.array_equal(new_labels, labels):
            break
        labels = new_labels
        # Recompute centroids as the mean of their member vectors
        for j in range(k):
            members = vectors[labels == j]
            if len(members):
                centroids[j] = members.mean(axis=0)

    return labels


def _auto_k(n_chunks: int, requested_k: int) -> int:
    """
    Derive a sensible cluster count from corpus size.
    For small docs (< 20 chunks) we use n_chunks // 2 so coverage is dense.
    For larger docs: clamp(n_chunks // 5, _MIN_CLUSTERS, _MAX_CLUSTERS).
    """
    if n_chunks <= 20:
        auto = max(_MIN_CLUSTERS, n_chunks // 2)
    else:
        auto = max(_MIN_CLUSTERS, min(n_chunks // 5, _MAX_CLUSTERS))
    return min(requested_k, auto, n_chunks)


# ---------------------------------------------------------------------------
# MMR (Maximal Marginal Relevance, pure numpy)
# ---------------------------------------------------------------------------

# How many candidates K-Means nominates per cluster before MMR filters them.
_MMR_CANDIDATES_PER_CLUSTER = 3
# Trade-off between relevance (1.0) and diversity (0.0).
_MMR_LAMBDA = 0.6


def _cosine_sim(a: np.ndarray, b: np.ndarray) -> float:
    """Cosine similarity between two already-normalised vectors."""
    return float(np.dot(a, b))


def _mmr(
    candidate_indices: List[int],
    vectors: np.ndarray,
    query_vector: np.ndarray,
    k: int,
    lam: float = _MMR_LAMBDA,
) -> List[int]:
    """
    Maximal Marginal Relevance selection.

    From `candidate_indices`, pick `k` indices that maximise:
        score(i) = λ · sim(i, query) − (1−λ) · max_j∈selected sim(i, j)

    - λ close to 1.0  → favour relevance to the query centroid
    - λ close to 0.0  → favour diversity (spread) among selected items

    Vectors are assumed to already be L2-normalised, so dot-product == cosine sim.
    Returns a list of chosen indices (original positions in `vectors`).
    """
    if not candidate_indices:
        return []

    k = min(k, len(candidate_indices))

    # Relevance of each candidate to the global query (document centroid)
    relevance = {i: _cosine_sim(vectors[i], query_vector) for i in candidate_indices}

    selected:   List[int] = []
    remaining:  List[int] = list(candidate_indices)

    while len(selected) < k and remaining:
        if not selected:
            # First pick: highest relevance to query
            best = max(remaining, key=lambda i: relevance[i])
        else:
            # Subsequent picks: MMR score
            sel_vecs = vectors[selected]  # (s, d)
            best, best_score = remaining[0], -np.inf
            for i in remaining:
                # max similarity to any already-selected excerpt
                max_sim_to_selected = float(np.max(sel_vecs @ vectors[i]))
                score = lam * relevance[i] - (1.0 - lam) * max_sim_to_selected
                if score > best_score:
                    best, best_score = i, score

        selected.append(best)
        remaining.remove(best)

    return selected


# ---------------------------------------------------------------------------
# Representative selection  (K-Means → candidate pool → MMR)
# ---------------------------------------------------------------------------

def _pick_representatives(
    texts: List[str],
    vectors: np.ndarray,
    metas: List[dict],
    n_clusters: int,
) -> List[Tuple[int, str, dict]]:
    """
    Two-stage selection pipeline:

    Stage 1 — K-Means clustering
        Divide all chunks into `k` semantic clusters.
        From each cluster, nominate the top-C candidates closest to the
        cluster centroid (not just 1). This gives a diverse candidate pool
        that still covers every region of the document.

    Stage 2 — MMR filtering
        From the full candidate pool, MMR picks exactly `k` final excerpts
        that are both relevant (close to the document centroid) and
        mutually diverse (not too similar to each other).

    Returns list of (cluster_idx, text, meta) sorted by cluster index
    so the LLM receives excerpts in document order.
    """
    k = min(n_clusters, len(texts))
    labels = _kmeans(vectors, k)

    # Document centroid = mean of all vectors (used as the MMR "query")
    doc_centroid = vectors.mean(axis=0)
    norm = np.linalg.norm(doc_centroid)
    if norm > 0:
        doc_centroid = doc_centroid / norm

    # Stage 1: collect top-C candidates per cluster
    candidates: List[int] = []   # original indices into texts/vectors/metas
    cluster_of: dict       = {}  # original_idx → cluster_idx (for output labelling)

    for ci in range(k):
        idx = np.where(labels == ci)[0]
        if len(idx) == 0:
            continue
        centroid  = vectors[idx].mean(axis=0)
        # Squared distance from each member to its cluster centroid
        dists     = ((vectors[idx] - centroid) ** 2).sum(axis=1)
        # Take up to _MMR_CANDIDATES_PER_CLUSTER closest members
        n_cands   = min(_MMR_CANDIDATES_PER_CLUSTER, len(idx))
        top_local = idx[np.argsort(dists)[:n_cands]]
        for orig_idx in top_local:
            candidates.append(int(orig_idx))
            cluster_of[int(orig_idx)] = ci

    # Stage 2: MMR selects k final excerpts from the candidate pool
    chosen_indices = _mmr(candidates, vectors, doc_centroid, k=k)

    # Build output list — use the cluster index the chunk came from for ordering
    selected: List[Tuple[int, str, dict]] = [
        (cluster_of[i], texts[i], metas[i] if metas else {})
        for i in chosen_indices
    ]

    # Sort by cluster index so excerpts arrive in rough document order
    selected.sort(key=lambda x: x[0])
    return selected


# ---------------------------------------------------------------------------
# Text splitting
# ---------------------------------------------------------------------------

def _split_text(text: str, chunk_size: int = _CHUNK_SIZE, overlap: int = _CHUNK_OVERLAP) -> List[str]:
    chunks, start = [], 0
    while start < len(text):
        end = min(start + chunk_size, len(text))
        chunks.append(text[start:end])
        start += chunk_size - overlap
    return [c for c in chunks if c.strip()]


# ---------------------------------------------------------------------------
# Prompt builders
# ---------------------------------------------------------------------------

def _style_and_lang(lang: str, style: str) -> str:
    lang_instr = (
        "Write in Vietnamese." if lang == "vi"
        else "Write in English." if lang == "en"
        else "Write in the same language as the source material."
    )
    style_instr = {
        "bullet":    (
            "Use thematic bullet-point sections with bold headings. "
            "Each bullet must start with a key word or phrase, not a weak verb."
        ),
        "paragraph": (
            "Write in short, precise paragraphs (3–5 sentences each). "
            "Use bold thematic headings to separate sections."
        ),
        "short":     (
            "Write a single crisp paragraph of at most 60 words covering "
            "the most important point, key figure, and main outcome."
        ),
    }.get(style, "Use thematic bullet-point sections with bold headings.")
    return f"Language instruction: {lang_instr}\nFormat instruction: {style_instr}"


def _build_map_prompt(reps: List[Tuple[int, str, dict]], lang: str, style: str) -> str:
    """Prompt for the MAP phase: summarise one file's representative chunks."""
    style_lang = _style_and_lang(lang, style)
    source = reps[0][2].get("source") or reps[0][2].get("file_name") or "" if reps else ""
    source_line = f"Source file: {source}\n" if source else ""
    parts = []
    for i, (_, text, _meta) in enumerate(reps, 1):
        # Use a plain separator — no "[Excerpt N]" label to avoid the LLM copying it verbatim
        parts.append(f"--- passage {i} ---\n{text[:_MAX_CHUNK_CHARS]}")
    excerpts = "\n\n".join(parts)
    return (
        f"{style_lang}\n\n"
        f"{source_line}"
        f"The following {len(reps)} passages are representative sections of the document, "
        f"presented in document order.\n\n"
        f"{excerpts}\n\n"
        f"Task: Write a comprehensive, well-structured summary covering ALL major topics "
        f"in the passages above. Use bold thematic section headings that match the actual "
        f"content. Under each heading write detailed bullet points — include specific terms, "
        f"definitions, examples, and any numbers or names mentioned. "
        f"Do NOT mention 'passage N' or cite passage numbers in your output. "
        f"Do not invent any detail not present in the passages."
    )


def _build_reduce_prompt(file_summaries: List[Tuple[str, str]], lang: str, style: str) -> str:
    """Prompt for the REDUCE phase: merge per-file summaries into one final summary."""
    style_lang = _style_and_lang(lang, style)
    parts = []
    for i, (fname, fsummary) in enumerate(file_summaries, 1):
        parts.append(
            f"[Document {i}: {fname}]\n"
            f"{fsummary[:_MAX_FILE_SUMMARY_CHARS]}"
        )
    combined = "\n\n---\n\n".join(parts)
    return (
        f"{style_lang}\n\n"
        f"You have {len(file_summaries)} document summary(ies) below. "
        f"Each is labelled [Document N: filename].\n\n"
        f"{combined}\n\n"
        f"Task: Synthesise these into one final, comprehensive summary. "
        f"Cite sources as [Document N]. Highlight shared themes, key figures, "
        f"important dates, and any contradictions between documents. "
        f"Do not invent any detail not present in the summaries above."
    )


def _build_single_prompt(reps: List[Tuple[int, str, dict]], lang: str, style: str) -> str:
    """Single-pass prompt (used for raw text / messages)."""
    return _build_map_prompt(reps, lang, style)


# ---------------------------------------------------------------------------
# Citation helpers
# ---------------------------------------------------------------------------

def _format_location(meta: dict) -> str:
    """Build a short human-readable location string from chunk metadata."""
    parts = []
    page    = meta.get("page")
    section = meta.get("section") or ""
    sheet   = meta.get("sheet") or ""
    if page is not None:
        parts.append(f"p.{page}")
    if section:
        parts.append(section[:50])
    if sheet and sheet not in ("summary",):
        parts.append(sheet)
    return ", ".join(parts)


def _build_rich_citations(reps: List[Tuple[int, str, dict]]) -> List[Dict]:
    citations = []
    for i, (_, _, meta) in enumerate(reps, 1):
        source   = meta.get("source") or meta.get("file_name") or "excerpt"
        location = _format_location(meta)
        citations.append({
            "rank":     i,
            "id":       f"Excerpt {i}",
            "source":   source,
            "location": location,
        })
    return citations


def _merge_citations(file_citations: List[List[Dict]], file_names: List[str]) -> List[Dict]:
    """Flatten per-file citation lists, adding file context."""
    merged, rank = [], 1
    for fname, cits in zip(file_names, file_citations):
        for c in cits:
            merged.append({
                "rank":     rank,
                "id":       f"Excerpt {rank}",
                "source":   fname,
                "location": c.get("location", ""),
            })
            rank += 1
    return merged


# ---------------------------------------------------------------------------
# Embedding validity
# ---------------------------------------------------------------------------

def _is_valid_embedding(embedding) -> bool:
    if embedding is None:
        return False
    try:
        arr = np.asarray(embedding, dtype=np.float32)
        return arr.ndim >= 1 and arr.shape[0] > 0
    except Exception:
        return False


def _is_meaningful_chunk(text: str) -> bool:
    """Return False for chunks that are too short or purely structural (headers, whitespace)."""
    stripped = text.strip()
    if len(stripped) < _MIN_CHUNK_CHARS:
        return False
    # Skip chunks that are almost entirely non-alphabetic (e.g. table separators, page numbers)
    alpha_ratio = sum(c.isalpha() for c in stripped) / max(len(stripped), 1)
    return alpha_ratio >= 0.25


def _normalise(vectors: np.ndarray) -> np.ndarray:
    norms = np.linalg.norm(vectors, axis=1, keepdims=True)
    norms = np.where(norms == 0, 1.0, norms)
    return vectors / norms


# ---------------------------------------------------------------------------
# MAP phase: summarise one group of chunks
# ---------------------------------------------------------------------------

def _map_summarise(
    texts: List[str],
    vectors: np.ndarray,
    metas: List[dict],
    lang: str,
    style: str,
    n_clusters: int,
    should_cancel: Optional[Callable[[], bool]],
) -> Tuple[str, List[Dict], int]:
    """
    Summarise one file's chunks. Returns (summary_text, citations, k_used).
    """
    k    = _auto_k(len(texts), n_clusters)
    reps = _pick_representatives(texts, vectors, metas, k)

    if should_cancel and should_cancel():
        raise GenerationCancelled("Cancelled in map phase")

    prompt         = _build_map_prompt(reps, lang, style)
    summary, _ = generate_answer(prompt, system_prompt=_SYSTEM_PROMPT,
                                 should_cancel=should_cancel)
    citations  = _build_rich_citations(reps)
    return summary, citations, k


# ---------------------------------------------------------------------------
# Core pipeline: embed → cluster → map-reduce summarise
# ---------------------------------------------------------------------------

def _map_reduce_summarise(
    grouped: Dict[str, Tuple[List[str], np.ndarray, List[dict]]],
    lang: str,
    style: str,
    n_clusters: int,
    should_cancel: Optional[Callable[[], bool]],
) -> Dict:
    """
    grouped: {filename: (texts, normalised_vectors, metas)}
    Returns the full SummaryResponse-compatible dict.
    """
    file_summaries: List[Tuple[str, str]] = []
    all_citations:  List[List[Dict]]      = []
    all_file_names: List[str]             = []
    total_chunks = 0
    total_k      = 0

    for fname, (texts, vectors, metas) in grouped.items():
        if should_cancel and should_cancel():
            raise GenerationCancelled("Cancelled between map calls")

        log.info("summary_agent: MAP file=%s chunks=%d", fname, len(texts))
        fsummary, fcitations, fk = _map_summarise(
            texts, vectors, metas, lang, style, n_clusters, should_cancel
        )
        file_summaries.append((fname, fsummary))
        all_citations.append(fcitations)
        all_file_names.append(fname)
        total_chunks += len(texts)
        total_k      += fk

    if should_cancel and should_cancel():
        raise GenerationCancelled("Cancelled before reduce")

    # Single file → skip reduce, return map result directly
    if len(file_summaries) == 1:
        fname, fsummary = file_summaries[0]
        return {
            "summary":      fsummary,
            "truncated":    False,
            "n_clusters":   total_k,
            "total_chunks": total_chunks,
            "citations":    all_citations[0],
            "file_name":    fname,
            "source":       "map_reduce",
        }

    # Multiple files → REDUCE
    log.info("summary_agent: REDUCE %d file summaries", len(file_summaries))
    reduce_prompt   = _build_reduce_prompt(file_summaries, lang, style)
    final_summary, usage = generate_answer(
        reduce_prompt, system_prompt=_REDUCE_SYSTEM_PROMPT, should_cancel=should_cancel
    )

    return {
        "summary":      final_summary,
        "truncated":    False,
        "n_clusters":   total_k,
        "total_chunks": total_chunks,
        "citations":    _merge_citations(all_citations, all_file_names),
        "file_name":    ", ".join(all_file_names),
        "source":       "map_reduce",
        "usage":        usage,
    }


# ---------------------------------------------------------------------------
# Fallback: embed on the fly → single-pass (for raw text / messages)
# ---------------------------------------------------------------------------

def _embed_and_summarise(
    chunks: List[str],
    lang: str,
    style: str,
    n_clusters: int,
    should_cancel: Optional[Callable[[], bool]],
    source_label: str = "",
    metas: Optional[List[dict]] = None,
) -> Dict:
    if not chunks:
        return {"summary": "", "truncated": False, "n_clusters": 0,
                "total_chunks": 0, "citations": []}

    if should_cancel and should_cancel():
        raise GenerationCancelled("Cancelled before embedding")

    log.info("summary_agent: embedding %d chunks for %s", len(chunks), source_label or "text")
    raw_vectors = embed_texts(chunks)
    vectors     = _normalise(np.array(raw_vectors, dtype=np.float32))
    metas       = metas or [{} for _ in chunks]

    k    = _auto_k(len(chunks), n_clusters)
    reps = _pick_representatives(chunks, vectors, metas, k)

    if should_cancel and should_cancel():
        raise GenerationCancelled("Cancelled before generation")

    prompt          = _build_single_prompt(reps, lang, style)
    summary, usage  = generate_answer(prompt, system_prompt=_SYSTEM_PROMPT,
                                      should_cancel=should_cancel)
    return {
        "summary":      summary,
        "truncated":    False,
        "n_clusters":   k,
        "total_chunks": len(chunks),
        "citations":    _build_rich_citations(reps),
        "usage":        usage,
    }


# ---------------------------------------------------------------------------
# Public API
# ---------------------------------------------------------------------------

def summarize_workspace(
    workspace_id: str,
    user_id: Optional[str] = None,
    lang: str = "auto",
    style: str = "bullet",
    n_clusters: int = 10,
    should_cancel: Optional[Callable[[], bool]] = None,
) -> Dict:
    """
    Summarise all content ingested into a ChromaDB workspace using map-reduce.
    Groups chunks by source file, summarises each independently, then merges.
    """
    scope = normalize_workspace_id(workspace_id)
    log.info("summary_agent.summarize_workspace: workspace=%s user=%s", scope, user_id)

    coll  = get_collection(workspace_id=scope, user_id=user_id)
    total = coll.count()
    if total == 0:
        return {"summary": "", "truncated": False, "n_clusters": 0, "total_chunks": 0,
                "citations": [], "error": "No documents ingested into this workspace yet."}

    res        = coll.get(include=["documents", "embeddings", "metadatas"])
    docs       = res.get("documents") or []
    raw_embeds = res.get("embeddings")
    raw_metas  = res.get("metadatas") or [{} for _ in docs]

    if raw_embeds is None:
        embeds = []
    elif hasattr(raw_embeds, "tolist"):
        embeds = raw_embeds.tolist()
    else:
        embeds = list(raw_embeds)

    triples = [
        (d, e, m)
        for d, e, m in zip(docs, embeds, raw_metas)
        if bool(d) and _is_meaningful_chunk(d) and _is_valid_embedding(e)
    ]
    if not triples:
        return {"summary": "", "truncated": False, "n_clusters": 0, "total_chunks": 0,
                "citations": [], "error": "Chunks found but embeddings missing — please re-ingest."}

    # Group by source file
    groups: Dict[str, Tuple[List[str], List, List[dict]]] = defaultdict(
        lambda: ([], [], [])
    )
    for text, emb, meta in triples:
        fname = (
            meta.get("storage_file")
            or meta.get("source")
            or meta.get("file_name")
            or "unknown"
        )
        groups[fname][0].append(text)
        groups[fname][1].append(emb)
        groups[fname][2].append(meta)

    # Convert embedding lists → normalised numpy arrays
    grouped: Dict[str, Tuple[List[str], np.ndarray, List[dict]]] = {}
    for fname, (texts, embs, metas) in groups.items():
        vectors = _normalise(np.array(embs, dtype=np.float32))
        grouped[fname] = (texts, vectors, metas)

    log.info("summary_agent.summarize_workspace: %d files, %d total chunks",
             len(grouped), len(triples))

    return _map_reduce_summarise(grouped, lang, style, n_clusters, should_cancel)


def summarize_workspace_stream(
    workspace_id: str,
    user_id: Optional[str] = None,
    lang: str = "auto",
    style: str = "bullet",
    n_clusters: int = 10,
    should_cancel: Optional[Callable[[], bool]] = None,
) -> Iterator[str]:
    """
    Streaming variant of summarize_workspace.
    Yields newline-delimited JSON events:
      {"type":"progress","file":"x.docx","current":1,"total":3}
      {"type":"token","text":"..."}
      {"type":"done","n_clusters":10,"total_chunks":60,"citations":[...],"file_name":"...","source":"map_reduce"}
      {"type":"error","message":"..."}
    """
    import json as _json

    def _emit(obj: dict) -> str:
        return _json.dumps(obj, ensure_ascii=False) + "\n"

    scope = normalize_workspace_id(workspace_id)
    log.info("summary_agent.summarize_workspace_stream: workspace=%s user=%s", scope, user_id)

    coll  = get_collection(workspace_id=scope, user_id=user_id)
    total = coll.count()
    if total == 0:
        yield _emit({"type": "error", "message": "No documents ingested into this workspace yet."})
        return

    res        = coll.get(include=["documents", "embeddings", "metadatas"])
    docs       = res.get("documents") or []
    raw_embeds = res.get("embeddings")
    raw_metas  = res.get("metadatas") or [{} for _ in docs]

    if raw_embeds is None:
        embeds = []
    elif hasattr(raw_embeds, "tolist"):
        embeds = raw_embeds.tolist()
    else:
        embeds = list(raw_embeds)

    triples = [
        (d, e, m)
        for d, e, m in zip(docs, embeds, raw_metas)
        if bool(d) and _is_meaningful_chunk(d) and _is_valid_embedding(e)
    ]
    if not triples:
        yield _emit({"type": "error", "message": "Chunks found but embeddings missing — please re-ingest."})
        return

    # Group by source file
    groups: Dict[str, Tuple[List[str], List, List[dict]]] = defaultdict(
        lambda: ([], [], [])
    )
    for text, emb, meta in triples:
        fname = (
            meta.get("storage_file")
            or meta.get("source")
            or meta.get("file_name")
            or "unknown"
        )
        groups[fname][0].append(text)
        groups[fname][1].append(emb)
        groups[fname][2].append(meta)

    grouped: Dict[str, Tuple[List[str], np.ndarray, List[dict]]] = {}
    for fname, (texts, embs, metas) in groups.items():
        vectors = _normalise(np.array(embs, dtype=np.float32))
        grouped[fname] = (texts, vectors, metas)

    file_names  = list(grouped.keys())
    total_files = len(file_names)
    total_chunks = len(triples)

    # --- MAP phase (non-streaming per file, these are intermediate results) ---
    file_summaries: List[Tuple[str, str]] = []
    all_citations:  List[List[Dict]]      = []
    total_k = 0

    for idx, fname in enumerate(file_names):
        if should_cancel and should_cancel():
            yield _emit({"type": "error", "message": "Cancelled."})
            return

        yield _emit({"type": "progress", "file": fname, "current": idx + 1, "total": total_files})

        texts, vectors, metas = grouped[fname]
        log.info("summary_agent.stream: MAP file=%s chunks=%d", fname, len(texts))
        fsummary, fcitations, fk = _map_summarise(
            texts, vectors, metas, lang, style, n_clusters, should_cancel
        )
        file_summaries.append((fname, fsummary))
        all_citations.append(fcitations)
        total_k += fk

    if should_cancel and should_cancel():
        yield _emit({"type": "error", "message": "Cancelled."})
        return

    # --- REDUCE / final streaming phase ---
    if total_files == 1:
        fname, _ = file_summaries[0]
        # Re-stream the single-file map summary token by token
        texts, vectors, metas = grouped[fname]
        k    = _auto_k(len(texts), n_clusters)
        reps = _pick_representatives(texts, vectors, metas, k)
        prompt = _build_map_prompt(reps, lang, style)
        citations = all_citations[0]
    else:
        prompt    = _build_reduce_prompt(file_summaries, lang, style)
        citations = _merge_citations(all_citations, file_names)
        fname     = ", ".join(file_names)

    system = _SYSTEM_PROMPT if total_files == 1 else _REDUCE_SYSTEM_PROMPT
    for token in stream_answer(prompt, system_prompt=system, should_cancel=should_cancel):
        yield _emit({"type": "token", "text": token})

    yield _emit({
        "type":         "done",
        "n_clusters":   total_k,
        "total_chunks": total_chunks,
        "citations":    citations,
        "file_name":    fname,
        "source":       "map_reduce",
    })


def summarize_text(
    text: str,
    lang: str = "auto",
    style: str = "bullet",
    n_clusters: int = 10,
    should_cancel: Optional[Callable[[], bool]] = None,
) -> Dict:
    """Summarise a raw text string (single-pass)."""
    if not text.strip():
        return {"summary": "", "truncated": False, "n_clusters": 0,
                "total_chunks": 0, "citations": []}
    chunks = _split_text(text)
    return _embed_and_summarise(chunks, lang, style, n_clusters, should_cancel,
                                source_label="raw_text")


def summarize_s3_document(
    s3_key: str,
    workspace_id: Optional[str] = None,
    lang: str = "auto",
    style: str = "bullet",
    n_clusters: int = 10,
    should_cancel: Optional[Callable[[], bool]] = None,
) -> Dict:
    """
    Summarise one S3 document using map-reduce if already ingested,
    otherwise falls back to download → extract → single-pass.
    """
    from pathlib import Path

    if workspace_id:
        scope        = normalize_workspace_id(workspace_id)
        storage_file = Path(s3_key).name
        try:
            coll  = get_collection(workspace_id=scope)
            if coll.count() > 0:
                res        = coll.get(
                    where={"storage_file": {"$eq": storage_file}},
                    include=["documents", "embeddings", "metadatas"],
                )
                docs       = res.get("documents") or []
                raw_embeds = res.get("embeddings")
                raw_metas  = res.get("metadatas") or [{} for _ in docs]

                if raw_embeds is None:
                    embeds = []
                elif hasattr(raw_embeds, "tolist"):
                    embeds = raw_embeds.tolist()
                else:
                    embeds = list(raw_embeds)

                triples = [
                    (d, e, m)
                    for d, e, m in zip(docs, embeds, raw_metas)
                    if bool(d) and _is_valid_embedding(e)
                ]
                if triples:
                    log.info("summary_agent.summarize_s3_document: %d stored chunks for %s",
                             len(triples), storage_file)
                    texts   = [t[0] for t in triples]
                    vectors = _normalise(np.array([t[1] for t in triples], dtype=np.float32))
                    metas   = [t[2] for t in triples]
                    k       = _auto_k(len(texts), n_clusters)
                    reps    = _pick_representatives(texts, vectors, metas, k)

                    if should_cancel and should_cancel():
                        raise GenerationCancelled("Cancelled before generation")

                    prompt          = _build_map_prompt(reps, lang, style)
                    summary, usage  = generate_answer(prompt, system_prompt=_SYSTEM_PROMPT,
                                                      should_cancel=should_cancel)
                    return {
                        "summary":      summary,
                        "truncated":    False,
                        "file_name":    storage_file,
                        "n_clusters":   k,
                        "total_chunks": len(texts),
                        "citations":    _build_rich_citations(reps),
                        "source":       "chromadb",
                        "usage":        usage,
                    }
        except GenerationCancelled:
            raise
        except Exception as e:
            log.warning("summary_agent: ChromaDB lookup failed (%s), falling back to S3", e)

    from .s3_storage import download_s3_file
    log.info("summary_agent.summarize_s3_document: downloading s3_key=%s", s3_key)
    with download_s3_file(s3_key) as local_path:
        text      = _extract_file_text(local_path)
        file_name = Path(s3_key).name

    if not text.strip():
        return {"summary": "", "truncated": False, "file_name": file_name,
                "n_clusters": 0, "total_chunks": 0, "citations": [],
                "error": "Could not extract text from file."}

    result             = summarize_text(text, lang=lang, style=style,
                                        n_clusters=n_clusters, should_cancel=should_cancel)
    result["file_name"] = file_name
    result["source"]    = "s3_extract"
    return result


def summarize_messages(
    messages: List[Dict[str, str]],
    lang: str = "auto",
    style: str = "bullet",
    n_clusters: int = 5,
    should_cancel: Optional[Callable[[], bool]] = None,
) -> Dict:
    """Summarise a chat history (single-pass, unchanged behaviour)."""
    if not messages:
        return {"summary": "", "truncated": False, "n_clusters": 0,
                "message_count": 0, "total_chunks": 0, "citations": []}

    lines = [
        (m.get("content") or "").strip()
        for m in messages
        if (m.get("content") or "").strip()
    ]
    if not lines:
        return {"summary": "", "truncated": False, "n_clusters": 0,
                "message_count": len(messages), "total_chunks": 0, "citations": []}

    if len(lines) <= _MIN_CLUSTERS:
        reps            = [(i, t, {}) for i, t in enumerate(lines)]
        prompt          = _build_single_prompt(reps, lang, style)
        summary, usage  = generate_answer(prompt, system_prompt=_SYSTEM_PROMPT,
                                          should_cancel=should_cancel)
        return {
            "summary":       summary,
            "truncated":     False,
            "n_clusters":    len(lines),
            "message_count": len(messages),
            "total_chunks":  len(lines),
            "citations":     _build_rich_citations(reps),
            "usage":         usage,
        }

    result                  = _embed_and_summarise(lines, lang, style, n_clusters,
                                                   should_cancel, source_label="messages")
    result["message_count"] = len(messages)
    return result


# ---------------------------------------------------------------------------
# File text extraction
# ---------------------------------------------------------------------------

def _extract_file_text(local_path) -> str:
    from pathlib import Path
    path = Path(local_path)
    ext  = path.suffix.lower()

    if ext == ".pdf":
        try:
            from pypdf import PdfReader
            reader = PdfReader(str(path))
            return "\n".join(page.extract_text() or "" for page in reader.pages)
        except Exception as e:
            log.warning("PDF extraction failed: %s", e)
            return ""

    if ext == ".docx":
        try:
            from zipfile import ZipFile
            from xml.etree import ElementTree
            NS = {"w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main"}
            with ZipFile(path) as z:
                root = ElementTree.parse(z.open("word/document.xml")).getroot()
            lines = []
            for p in root.findall(".//w:p", NS):
                line = "".join(t.text or "" for t in p.findall(".//w:t", NS)).strip()
                if line:
                    lines.append(line)
            return "\n".join(lines)
        except Exception as e:
            log.warning("DOCX extraction failed: %s", e)
            return ""

    if ext in (".txt", ".md", ".csv"):
        return path.read_text(encoding="utf-8", errors="replace")

    return ""
