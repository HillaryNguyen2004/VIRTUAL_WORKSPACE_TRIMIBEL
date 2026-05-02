from __future__ import annotations
from typing import List
from src.rag.config import settings
import os
import httpx

# env variables
OLLAMA_BASE_URL = settings.ollama_url
EMBED_MODEL = settings.embed_model
EMBED_DIM = settings.embed_dim

_client = httpx.Client(timeout=httpx.Timeout(connect=10, read=300, write=60, pool=300))

def _embed(inputs: List[str]) -> List[List[float]]:
    """
    Ollama embeddings.
    POST /api/embed
    Body: { model, input: [..], truncate }
    Returns: { embeddings: [[...], ...], model: ..., ... }
    """
    payload = {
        "model": EMBED_MODEL,
        "input": inputs,
        "truncate": True,
    }
    r = _client.post(f"{OLLAMA_BASE_URL}/api/embed", json=payload)
    r.raise_for_status()
    data = r.json()
    embs = data.get("embeddings")
    if not isinstance(embs, list) or not embs:
        raise ValueError(f"Unexpected embed response: {data}")
    return embs

def embed_texts(texts: List[str], sleep: float = 0.0, batch_size: int = 16) -> List[List[float]]:
    results: List[List[float]] = []
    for i in range(0, len(texts), batch_size):
        results.extend(_embed(texts[i:i + batch_size]))
    return results

def embed_query(text: str) -> List[float]:
    return _embed([text])[0]