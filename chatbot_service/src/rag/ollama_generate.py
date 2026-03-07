from __future__ import annotations
import os
import httpx
from .config import settings

# Env defaults from config.py
OLLAMA_BASE_URL = settings.ollama_url
OLLAMA_MODEL = settings.ollama_model

# Optional system prompt for RAG
DEFAULT_SYSTEM = os.getenv(
    "SYSTEM_PROMPT",
    "You are a helpful assistant. Answer based on the provided context."
)

_client = httpx.Client(timeout=httpx.Timeout(connect=10, read=120, write=30, pool=120))

def generate_answer(prompt: str, temperature: float = 0.2) -> str:
    """
    Ollama chat endpoint. Returns plain text.
    For structured output, enforce JSON in the prompt.
    """
    payload = {
        "model": OLLAMA_MODEL,
        "messages": [
            {"role": "system", "content": DEFAULT_SYSTEM},
            {"role": "user", "content": prompt},
        ],
        "options": {
            "temperature": temperature,
            "num_predict": 256,
        },
        "stream": False,
        "keep_alive": "10m",
    }

    r = _client.post(f"{OLLAMA_BASE_URL}/api/chat", json=payload)
    r.raise_for_status()
    data = r.json()

    # Ollama returns: {"message": {"role": "assistant", "content": "..."}, ...}
    return (data.get("message", {}).get("content") or "").strip()