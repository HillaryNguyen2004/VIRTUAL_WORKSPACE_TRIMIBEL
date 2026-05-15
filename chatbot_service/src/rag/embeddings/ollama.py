from __future__ import annotations
from typing import List, Callable, Optional
from src.rag.config import settings
import os
import json
from urllib import request as urlrequest
from urllib import error as urlerror

try:
    import httpx
    _HTTPX_AVAILABLE = True
except Exception:
    httpx = None
    _HTTPX_AVAILABLE = False

# env variables
OLLAMA_BASE_URL = settings.ollama_url
EMBED_MODEL = settings.embed_model
EMBED_DIM = settings.embed_dim

_EMBED_TIMEOUT = int(os.getenv("EMBED_TIMEOUT", "600"))
_EMBED_BATCH_SIZE = int(os.getenv("EMBED_BATCH_SIZE", "32"))

_client = None
if _HTTPX_AVAILABLE:
    _client = httpx.Client(
        timeout=httpx.Timeout(connect=10, read=_EMBED_TIMEOUT, write=60, pool=_EMBED_TIMEOUT)
    )

def _embed_via_urllib(text: str) -> List[float]:
    payload = {
        "model": EMBED_MODEL,
        "prompt": text,
        "keep_alive": "0",
    }
    data = json.dumps(payload).encode("utf-8")
    req = urlrequest.Request(
        url=f"{OLLAMA_BASE_URL}/api/embeddings",
        data=data,
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    try:
        with urlrequest.urlopen(req, timeout=_EMBED_TIMEOUT) as resp:
            raw = resp.read().decode("utf-8")
        parsed = json.loads(raw)
    except urlerror.HTTPError as exc:
        body = exc.read().decode("utf-8", errors="ignore")
        raise RuntimeError(f"Ollama embedding HTTP error: {exc.code} {body}")
    except Exception as exc:
        raise RuntimeError(f"Ollama embedding error: {exc}")

    emb = parsed.get("embedding")
    if not isinstance(emb, list) or not emb:
        raise ValueError(f"Unexpected embed response: {parsed}")
    return emb

def _embed_batch_via_api(inputs: List[str]) -> List[List[float]]:
    """Try the newer /api/embed endpoint that accepts a list of inputs in one call."""
    if _client is None:
        raise RuntimeError("httpx not available")
    r = _client.post(
        f"{OLLAMA_BASE_URL}/api/embed",
        json={"model": EMBED_MODEL, "input": inputs},
    )
    r.raise_for_status()
    data = r.json()
    embeddings = data.get("embeddings")
    if not isinstance(embeddings, list) or len(embeddings) != len(inputs):
        raise ValueError(f"Unexpected batch embed response shape: {list(data.keys())}")
    return embeddings

def _embed(inputs: List[str], should_cancel: Optional[Callable[[], bool]] = None) -> List[List[float]]:
    """
    Embed texts in batches. Tries /api/embed (batch) first, falls back to
    /api/embeddings (one per request) for older Ollama versions.
    """
    if should_cancel and should_cancel():
        raise RuntimeError("Embedding canceled before request dispatch")

    results: List[List[float]] = []

    for batch_start in range(0, len(inputs), _EMBED_BATCH_SIZE):
        batch = inputs[batch_start: batch_start + _EMBED_BATCH_SIZE]

        if should_cancel and should_cancel():
            raise RuntimeError("Embedding canceled mid-batch")

        # Try batch endpoint first
        try:
            batch_results = _embed_batch_via_api(batch)
            results.extend(batch_results)
            continue
        except Exception:
            pass

        # Fall back: one request per text
        for text in batch:
            if _client is not None:
                r = _client.post(
                    f"{OLLAMA_BASE_URL}/api/embeddings",
                    json={"model": EMBED_MODEL, "prompt": text, "keep_alive": "0"},
                )
                r.raise_for_status()
                data = r.json()
                emb = data.get("embedding")
                if not isinstance(emb, list) or not emb:
                    raise ValueError(f"Unexpected embed response: {data}")
                results.append(emb)
            else:
                results.append(_embed_via_urllib(text))

    return results

def embed_texts(texts: List[str], sleep: float = 0.0, should_cancel: Optional[Callable[[], bool]] = None) -> List[List[float]]:
    # sleep ignored (kept for compatibility)
    return _embed(texts, should_cancel=should_cancel)

def embed_query(text: str, should_cancel: Optional[Callable[[], bool]] = None) -> List[float]:
    return _embed([text], should_cancel=should_cancel)[0]