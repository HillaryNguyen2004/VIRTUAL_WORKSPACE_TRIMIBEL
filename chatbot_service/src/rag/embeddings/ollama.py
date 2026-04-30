from __future__ import annotations
from typing import List, Callable, Optional
from src.rag.config import settings
import os
import httpx

# env variables
OLLAMA_BASE_URL = settings.ollama_url
EMBED_MODEL = settings.embed_model
EMBED_DIM = settings.embed_dim

_client = httpx.Client(timeout=httpx.Timeout(connect=10, read=120, write=30, pool=120))

def _embed(inputs: List[str], should_cancel: Optional[Callable[[], bool]] = None) -> List[List[float]]:
    """
    Ollama embeddings.
    POST /api/embed
    Body: { model, input: [..], dimensions, truncate }
    Returns: { embeddings: [[...], ...], model: ..., ... }
    """
    payload = {
        "model": EMBED_MODEL,
        "input": inputs,
        "truncate": True,
        "dimensions": EMBED_DIM,
    }
    if should_cancel and should_cancel():
        raise RuntimeError("Embedding canceled before request dispatch")
    r = _client.post(f"{OLLAMA_BASE_URL}/api/embed", json=payload)
    r.raise_for_status()
    data = r.json()
    embs = data.get("embeddings")
    if not isinstance(embs, list) or not embs:
        raise ValueError(f"Unexpected embed response: {data}")
    return embs

def embed_texts(texts: List[str], sleep: float = 0.0, should_cancel: Optional[Callable[[], bool]] = None) -> List[List[float]]:
    # sleep ignored (kept for compatibility)
    return _embed(texts, should_cancel=should_cancel)

def embed_query(text: str, should_cancel: Optional[Callable[[], bool]] = None) -> List[float]:
    return _embed([text], should_cancel=should_cancel)[0]