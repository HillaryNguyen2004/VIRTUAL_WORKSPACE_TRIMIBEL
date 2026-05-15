from __future__ import annotations
import logging
import re
import torch
from typing import List, Dict, Any, Callable, Optional

from .embeddings.ollama import embed_query
from .vectorstores.chroma_store import query_by_vector
from .config import settings
from .ollama_generate import GenerationCancelled
from sentence_transformers import CrossEncoder

log = logging.getLogger(__name__)

_cross_encoder = None
_cross_encoder_ready = False


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

    pairs = []
    for d in docs:
        text = str(d.get("content") or d.get("document") or "")
        pairs.append((query, text))

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

def retrieve(
    query_text: str,
    k: int | None = None,
    workspace_id: str | None = None,
    user_id: str | int | None = None,
    where: dict | None = None,
    should_cancel: Optional[Callable[[], bool]] = None,
) -> List[Dict[str, Any]]:
    """
    Single-query retrieval with cross-encoder rerank (fallback to heuristic).

    Steps:
    1. Embed the query as-is (Qwen3-Embedding handles multilingual natively)
    2. Fetch top k*3 candidates from ChromaDB
     3. Rerank by Cross-Encoder if available
         (fallback to distance * keyword overlap)
    4. Return top k
    """
    base_k = k or settings.top_k
    fetch_k = base_k * 3

    if not query_text.strip():
        return []

    if should_cancel and should_cancel():
        raise GenerationCancelled("Retrieval canceled")

    # 1. Embed
    qv = embed_query(query_text, should_cancel=should_cancel)

    if should_cancel and should_cancel():
        raise GenerationCancelled("Retrieval canceled after embed")

    # 2. Fetch candidates
    raw = query_by_vector(qv, fetch_k, workspace_id=workspace_id, user_id=user_id, where=where)

    if not raw:
        return []

    # 3. Rerank by cross-encoder; fallback to previous heuristic scoring.
    reranked = _rerank_with_cross_encoder(query_text, raw)
    if reranked is raw and raw and "_ce_score" not in raw[0]:
        for doc in raw:
            kw = _keyword_score(query_text, str(doc.get("content", "")))
            dist = doc.get("distance", 1.0)
            # Lower distance = better. Convert to similarity score then boost with keyword.
            similarity = 1.0 - min(dist, 1.0)
            doc["_final_score"] = similarity * (1.0 + kw)
        raw.sort(key=lambda d: d["_final_score"], reverse=True)
    else:
        raw = reranked

    log.debug("retrieve: %d candidates → returning top %d", len(raw), base_k)
    return raw[:base_k]