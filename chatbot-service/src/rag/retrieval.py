from __future__ import annotations
from typing import List, Dict, Any
from .embeddings.gemini import embed_query
from .vectorstores.chroma_store import query_by_vector
from .config import settings

def retrieve(query_text: str, k: int | None = None) -> List[Dict[str, Any]]:
    k = k or settings.top_k
    # Embeds the user query
    qv = embed_query(query_text)          # 768-dim
    # get top-K passages
    # Return shape: list of {id, content, metadata}
    return query_by_vector(qv, k)         # matches collection
