from __future__ import annotations
from typing import List, Dict, Any, Callable, Optional
# from .embeddings.gemini import embed_query
from .embeddings.ollama import embed_query
from .vectorstores.chroma_store import query_by_vector
from .config import settings
from .ollama_generate import GenerationCancelled

# =========================
# RETRIEVAL
# =========================
def retrieve(
    query_text: str,
    k: int | None = None,
    workspace_id: str | None = None,
    where: dict | None = None,
    should_cancel: Optional[Callable[[], bool]] = None,
) -> List[Dict[str, Any]]:
    k = k or settings.top_k
    if should_cancel and should_cancel():
        raise GenerationCancelled("Retrieval canceled before embedding")
    # Embeds the user query
    qv = embed_query(query_text, should_cancel=should_cancel)          # 768-dim

    if should_cancel and should_cancel():
        raise GenerationCancelled("Retrieval canceled before vector query")
    
    raw = query_by_vector(
        qv,
        k * 3,   # overfetch
        workspace_id=workspace_id,
        where=where
    )

    if should_cancel and should_cancel():
        raise GenerationCancelled("Retrieval canceled after vector query")

    # get top-K passages
    # Return shape: list of {id, content, metadata}
    return raw[:k]
