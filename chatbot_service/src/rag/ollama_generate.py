# from __future__ import annotations
# import os
# import httpx
# from typing import Dict, Any, Tuple
# from .config import settings

# # Env defaults from config.py
# OLLAMA_BASE_URL = settings.ollama_url
# OLLAMA_MODEL = settings.ollama_model

# # Optional system prompt for RAG
# DEFAULT_SYSTEM = os.getenv(
#     "SYSTEM_PROMPT",
#     "You are a helpful assistant. Answer based on the provided context."
# )

# _client = httpx.Client(timeout=httpx.Timeout(connect=10, read=120, write=30, pool=120))

# def _extract_usage(data: Dict[str, Any]) -> Dict[str, int]:
#     prompt_tokens = int(data.get("prompt_eval_count") or 0)
#     completion_tokens = int(data.get("eval_count") or 0)
#     return {
#         "prompt_tokens": prompt_tokens,
#         "completion_tokens": completion_tokens,
#         "total_tokens": prompt_tokens + completion_tokens,
#     }


# def generate_answer(prompt: str, temperature: float = 0.2) -> Tuple[str, Dict[str, int]]:
#     """
#     Ollama chat endpoint. Returns plain text.
#     For structured output, enforce JSON in the prompt.
#     """
#     payload = {
#         "model": OLLAMA_MODEL,
#         "messages": [
#             {"role": "system", "content": DEFAULT_SYSTEM},
#             {"role": "user", "content": prompt},
#         ],
#         "options": {
#             "temperature": temperature,
#             "num_predict": settings.gen_max_tokens,
#         },
#         "stream": False,
#         "keep_alive": "10m",
#     }

#     r = _client.post(f"{OLLAMA_BASE_URL}/api/chat", json=payload)
#     r.raise_for_status()
#     data = r.json()

#     # Ollama returns: {"message": {"role": "assistant", "content": "..."}, ...}
#     text = (data.get("message", {}).get("content") or "").strip()
#     usage = _extract_usage(data)
#     return text, usage

from __future__ import annotations
import os
import httpx
import json
import logging
from typing import Dict, Any, Tuple, Optional, Callable, Iterator
from .config import settings

logger = logging.getLogger(__name__)

OLLAMA_BASE_URL = settings.ollama_url
OLLAMA_MODEL = settings.ollama_model

DEFAULT_SYSTEM_PROMPT = """You are an expert AI assistant with strong analytical and reasoning skills.
Always think step-by-step (Chain-of-Thought) before answering.
Be concise, accurate, logical, and data-driven.
If the context is provided, use it. If not, say you don't know instead of hallucinating."""

_client = httpx.Client(timeout=httpx.Timeout(connect=10, read=600, write=60, pool=600))


class GenerationCancelled(Exception):
    pass

def _extract_usage(data: Dict[str, Any]) -> Dict[str, int]:
    prompt_tokens = int(data.get("prompt_eval_count") or 0)
    completion_tokens = int(data.get("eval_count") or 0)
    return {
        "prompt_tokens": prompt_tokens,
        "completion_tokens": completion_tokens,
        "total_tokens": prompt_tokens + completion_tokens,
    }

def generate_answer(
    prompt: str,
    temperature: float = 0.6,
    system_prompt: Optional[str] = None,
    stream: bool = True,
    model: Optional[str] = None,
    should_cancel: Optional[Callable[[], bool]] = None,
) -> Tuple[str, Dict[str, int]]:
    final_system = system_prompt or DEFAULT_SYSTEM_PROMPT
    final_model = model or OLLAMA_MODEL

    if should_cancel and should_cancel():
        raise GenerationCancelled("Generation canceled before request dispatch")

    payload = {
        "model": final_model,
        "messages": [
            {"role": "system", "content": final_system},
            {"role": "user", "content": prompt},
        ],
        "options": {
            "temperature": temperature,
            "num_predict": settings.gen_max_tokens,
            "num_ctx": 8192,
        },
        "stream": stream,
        "keep_alive": "10m",
    }

    try:
        if stream:
            chunks: list[str] = []
            last_data: Dict[str, Any] = {}

            with _client.stream("POST", f"{OLLAMA_BASE_URL}/api/chat", json=payload) as r:
                r.raise_for_status()

                for line in r.iter_lines():
                    if should_cancel and should_cancel():
                        r.close()
                        raise GenerationCancelled("Generation canceled while streaming")

                    if not line:
                        continue

                    if isinstance(line, bytes):
                        line = line.decode("utf-8", errors="ignore")

                    try:
                        data = json.loads(line)
                    except json.JSONDecodeError:
                        continue

                    last_data = data
                    content = (data.get("message") or {}).get("content")
                    if content:
                        chunks.append(content)

                    if data.get("done"):
                        break

            text = "".join(chunks).strip()
            usage = _extract_usage(last_data)
            return text, usage

        r = _client.post(f"{OLLAMA_BASE_URL}/api/chat", json=payload)
        r.raise_for_status()
        data = r.json()
        text = (data.get("message", {}).get("content") or "").strip()
        usage = _extract_usage(data)
        return text, usage

    except GenerationCancelled:
        raise
    except Exception as e:
        logger.error(f"Ollama error: {e}")
        return "Sorry, I encountered an error while generating the answer. Please try again.", {"prompt_tokens": 0, "completion_tokens": 0, "total_tokens": 0}


def stream_answer(
    prompt: str,
    temperature: float = 0.6,
    system_prompt: Optional[str] = None,
    model: Optional[str] = None,
    should_cancel: Optional[Callable[[], bool]] = None,
    on_usage: Optional[Callable[[Dict[str, int]], None]] = None,
) -> Iterator[str]:
    final_system = system_prompt or DEFAULT_SYSTEM_PROMPT
    final_model = model or OLLAMA_MODEL

    if should_cancel and should_cancel():
        raise GenerationCancelled("Generation canceled before request dispatch")

    payload = {
        "model": final_model,
        "messages": [
            {"role": "system", "content": final_system},
            {"role": "user", "content": prompt},
        ],
        "options": {
            "temperature": temperature,
            "num_predict": settings.gen_max_tokens,
            "num_ctx": 8192,
        },
        "stream": True,
        "keep_alive": "10m",
    }

    with _client.stream("POST", f"{OLLAMA_BASE_URL}/api/chat", json=payload) as r:
        r.raise_for_status()

        last_data: Dict[str, Any] = {}
        for line in r.iter_lines():
            if should_cancel and should_cancel():
                r.close()
                raise GenerationCancelled("Generation canceled while streaming")

            if not line:
                continue

            if isinstance(line, bytes):
                line = line.decode("utf-8", errors="ignore")

            try:
                data = json.loads(line)
            except json.JSONDecodeError:
                continue

            last_data = data

            content = (data.get("message") or {}).get("content")
            if content:
                yield content

            if data.get("done"):
                if on_usage is not None:
                    on_usage(_extract_usage(last_data))
                break