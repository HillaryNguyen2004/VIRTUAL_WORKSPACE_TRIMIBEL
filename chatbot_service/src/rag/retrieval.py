from __future__ import annotations
import logging
import re
from typing import List, Dict, Any, Callable, Optional

from .embeddings.ollama import embed_query
from .vectorstores.chroma_store import query_by_vector
from .config import settings
from .ollama_generate import GenerationCancelled

log = logging.getLogger(__name__)

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
    **kwargs,                          # absorb old expand/src_lang/history args
) -> List[Dict[str, Any]]:
    """
    Simple single-query retrieval with keyword rerank.

    Steps:
    1. Embed the query as-is (Qwen3-Embedding handles multilingual natively)
    2. Fetch top k*3 candidates from ChromaDB
    3. Rerank by distance * keyword overlap
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

    # 3. Rerank by distance score + keyword overlap
    for doc in raw:
        kw = _keyword_score(query_text, str(doc.get("content", "")))
        dist = doc.get("distance", 1.0)
        # Lower distance = better. Convert to similarity score then boost with keyword.
        similarity = 1.0 - min(dist, 1.0)
        final = similarity * (1.0 + kw)
        doc["rrf_score"] = round(similarity, 6)
        doc["_final_score"] = final

    raw.sort(key=lambda d: d["_final_score"], reverse=True)

    log.debug("retrieve: %d candidates → returning top %d", len(raw), base_k)
    return raw[:base_k]