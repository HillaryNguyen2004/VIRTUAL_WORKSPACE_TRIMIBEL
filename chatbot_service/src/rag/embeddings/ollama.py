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
    Ollama embeddings — calls /api/embeddings once per input (legacy endpoint,
    compatible with all Ollama versions). Falls back to /api/embed batch if the
    legacy endpoint returns an unexpected shape.
    """
    if should_cancel and should_cancel():
        raise RuntimeError("Embedding canceled before request dispatch")

    # Try legacy /api/embeddings (prompt per request) — works on all Ollama versions
    results: List[List[float]] = []
    for text in inputs:
        r = _client.post(f"{OLLAMA_BASE_URL}/api/embeddings", json={"model": EMBED_MODEL, "prompt": text})
        r.raise_for_status()
        data = r.json()
        emb = data.get("embedding")
        if not isinstance(emb, list) or not emb:
            raise ValueError(f"Unexpected embed response: {data}")
        results.append(emb)
    return results

def embed_texts(texts: List[str], sleep: float = 0.0, should_cancel: Optional[Callable[[], bool]] = None) -> List[List[float]]:
    # sleep ignored (kept for compatibility)
    return _embed(texts, should_cancel=should_cancel)

def embed_query(text: str, should_cancel: Optional[Callable[[], bool]] = None) -> List[float]:
    return _embed([text], should_cancel=should_cancel)[0]