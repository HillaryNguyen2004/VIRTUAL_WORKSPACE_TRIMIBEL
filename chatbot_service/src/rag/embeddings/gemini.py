from __future__ import annotations
from typing import List
import time, os
import google.generativeai as genai
from ..config import settings

genai.configure(api_key=settings.google_api_key)

EMBED_MODEL = settings.embed_model
if not EMBED_MODEL.startswith("models/"):
    EMBED_MODEL = f"models/{EMBED_MODEL}"

EMBED_DIM = int(os.getenv("EMBED_DIM", "768"))  # pin to your collection size

def _to_vector(resp) -> list[float]:
    emb = getattr(resp, "embedding", None)
    if emb is not None:
        if hasattr(emb, "values"):
            return list(emb.values)
        if isinstance(emb, list):
            return emb
        if isinstance(emb, dict) and "values" in emb:
            return emb["values"]
    if isinstance(resp, dict) and "embedding" in resp:
        emb = resp["embedding"]
        if isinstance(emb, list):
            return emb
        if isinstance(emb, dict) and "values" in emb:
            return emb["values"]
    raise ValueError(f"Unexpected embedding shape: {type(resp)} -> {resp}")

def _embed_one(text: str, task_type: str) -> list[float]:
    resp = genai.embed_content(
        model=EMBED_MODEL,
        content=text,
        task_type=task_type,
        output_dimensionality=EMBED_DIM,   # critical
    )
    return _to_vector(resp)

def embed_texts(texts: List[str], sleep: float = 0.0) -> List[List[float]]:
    return [_embed_one(t, "RETRIEVAL_DOCUMENT") for t in texts]

def embed_query(text: str) -> list[float]:
    return _embed_one(text, "RETRIEVAL_QUERY")
