from __future__ import annotations
import logging
import math
import re
import torch
from typing import List, Dict, Any, Callable, Optional

from .embeddings.ollama import embed_query
from .vectorstores.chroma_store import query_by_vector
from .bm25_store import bm25_search
from .config import settings
from .ollama_generate import GenerationCancelled
from sentence_transformers import CrossEncoder

log = logging.getLogger(__name__)

_cross_encoder = None
_cross_encoder_ready = False

# RRF constant — higher k dampens rank differences (60 is standard)
_RRF_K = 60


def _get_cross_encoder():
    """
    Lazy-init cross-encoder so service startup stays fast and we can
    gracefully fallback if optional deps are unavailable.
    """
    global _cross_encoder, _cross_encoder_ready
    if _cross_encoder_ready:
        return _cross_encoder

    try:
        device = "cuda" if torch.cuda.is_available() else "cpu"
        _cross_encoder = CrossEncoder(
            "cross-encoder/ms-marco-TinyBERT-L-2",
            device=device,
        )
        log.info("Cross-encoder initialized on device=%s", device)
    except Exception as e:
        _cross_encoder = None
        log.warning("Cross-encoder unavailable, fallback to heuristic rerank: %s", e)

    _cross_encoder_ready = True
    return _cross_encoder


def _rerank_with_cross_encoder(query: str, docs: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
    """Rerank candidates with sentence-transformers cross-encoder."""
    ce = _get_cross_encoder()
    if ce is None or not docs:
        return docs

    pairs = [(query, str(d.get("content") or d.get("document") or "")) for d in docs]

    try:
        scores = ce.predict(pairs)
    except Exception as e:
        log.warning("Cross-encoder predict failed, fallback to heuristic rerank: %s", e)
        return docs

    for d, s in zip(docs, scores):
        d["_ce_score"] = float(s)

    docs.sort(key=lambda d: d.get("_ce_score", float("-inf")), reverse=True)
    return docs


def _keyword_score(query: str, passage_text: str) -> float:
    """Token-overlap score — lowercase only, preserve diacritics."""
    def _soft(text: str) -> str:
        return re.sub(r"\s+", " ", text.lower().strip())

    tokens = [t for t in re.findall(r"\w+", _soft(query)) if len(t) >= 2]
    if not tokens:
        return 0.0
    content = _soft(passage_text or "")
    hits = sum(1 for t in set(tokens) if t in content)
    return hits / len(set(tokens))


def _rrf_merge(
    dense_results: List[Dict[str, Any]],
    sparse_results: List[Dict[str, Any]],
    k: int = _RRF_K,
) -> List[Dict[str, Any]]:
    """
    Reciprocal Rank Fusion of dense + sparse ranked lists.

    score(d) = Σ 1 / (k + rank(d))  across both lists.

    Docs are identified by their 'id' field.  All unique docs from both
    lists are included; missing from one list → no contribution from that list.
    """
    scores: Dict[str, float] = {}
    doc_map: Dict[str, Dict[str, Any]] = {}

    for rank, doc in enumerate(dense_results, start=1):
        doc_id = doc["id"]
        scores[doc_id] = scores.get(doc_id, 0.0) + 1.0 / (k + rank)
        doc_map[doc_id] = doc

    for rank, doc in enumerate(sparse_results, start=1):
        doc_id = doc["id"]
        scores[doc_id] = scores.get(doc_id, 0.0) + 1.0 / (k + rank)
        if doc_id not in doc_map:
            doc_map[doc_id] = doc

    merged = []
    for doc_id, rrf_score in sorted(scores.items(), key=lambda x: x[1], reverse=True):
        entry = dict(doc_map[doc_id])
        entry["rrf_score"] = rrf_score
        merged.append(entry)

    return merged


def retrieve(
    query_text: str,
    k: int | None = None,
    workspace_id: str | None = None,
    user_id: str | int | None = None,
    where: dict | None = None,
    should_cancel: Optional[Callable[[], bool]] = None,
    # Legacy kwargs accepted by search_agent callers — unused in hybrid pipeline
    expand: bool = False,  # noqa: ARG001
    src_lang: Optional[str] = None,  # noqa: ARG001
    history: Optional[List[Dict[str, str]]] = None,  # noqa: ARG001
) -> List[Dict[str, Any]]:
    """
    Hybrid retrieval: Dense (ChromaDB HNSW) + Sparse (BM25), merged via RRF,
    then reranked with a Cross-Encoder.

    Steps
    -----
    1. Embed query → Dense retrieval (top fetch_k candidates)
    2. BM25 keyword retrieval (top fetch_k candidates)
    3. RRF merge of both ranked lists
    4. Cross-Encoder rerank on merged candidates (fallback: distance×keyword)
    5. Reverse repacking: return top-k in reverse order so the most-relevant
       passage appears last (closest to the LLM's attention window)
    """
    base_k = k or settings.top_k
    fetch_k = base_k * 3  # Fetch extra candidates before reranking

    if not query_text.strip():
        return []

    if should_cancel and should_cancel():
        raise GenerationCancelled("Retrieval canceled")

    # ── 1. Dense retrieval ────────────────────────────────────────────────────
    qv = embed_query(query_text, should_cancel=should_cancel)

    if should_cancel and should_cancel():
        raise GenerationCancelled("Retrieval canceled after embed")

    dense_raw = query_by_vector(
        qv, fetch_k, workspace_id=workspace_id, user_id=user_id, where=where
    )

    # ── 2. BM25 sparse retrieval ──────────────────────────────────────────────
    try:
        sparse_raw = bm25_search(
            query=query_text,
            workspace_id=workspace_id,
            user_id=user_id,
            k=fetch_k,
        )
    except Exception as e:
        log.warning("retrieve: BM25 search failed, skipping sparse leg: %s", e)
        sparse_raw = []

    if should_cancel and should_cancel():
        raise GenerationCancelled("Retrieval canceled after BM25")

    # ── 3. RRF merge ──────────────────────────────────────────────────────────
    if sparse_raw:
        merged = _rrf_merge(dense_raw, sparse_raw)
        log.debug(
            "retrieve: dense=%d sparse=%d merged=%d",
            len(dense_raw), len(sparse_raw), len(merged),
        )
    else:
        # BM25 unavailable or empty — fall back to dense only
        merged = dense_raw
        log.debug("retrieve: dense only, %d candidates", len(merged))

    if not merged:
        return []

    # ── 4. Cross-Encoder rerank ───────────────────────────────────────────────
    reranked = _rerank_with_cross_encoder(query_text, merged)
    if reranked is merged and merged and "_ce_score" not in merged[0]:
        # CE unavailable — heuristic: distance × keyword overlap
        for doc in merged:
            kw = _keyword_score(query_text, str(doc.get("content", "")))
            dist = doc.get("distance", 1.0)
            similarity = 1.0 - min(dist, 1.0)
            rrf = doc.get("rrf_score", 0.0)
            doc["_final_score"] = (similarity + rrf) * (1.0 + kw)
        merged.sort(key=lambda d: d["_final_score"], reverse=True)
        top = merged[:base_k]
    else:
        # Normalise CE raw logit into [0, 1] via sigmoid, then store as _final_score
        for doc in reranked:
            raw = doc.get("_ce_score", 0.0)
            doc["_final_score"] = 1.0 / (1.0 + math.exp(-raw))
        top = reranked[:base_k]

    # ── 5. Reverse repacking ──────────────────────────────────────────────────
    # Most-relevant passage placed last so it sits closest to the query in the
    # prompt context window (per RAG best practices: Lost in the Middle effect).
    # The original order (best-first) is preserved in _final_score; callers that
    # need ranked display should sort by _final_score descending.
    top_reversed = list(reversed(top))

    log.debug("retrieve: returning %d passages (reverse-packed)", len(top_reversed))
    return top_reversed
