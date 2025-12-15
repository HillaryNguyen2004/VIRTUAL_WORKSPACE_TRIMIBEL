from __future__ import annotations
import google.generativeai as genai
from .config import settings

genai.configure(api_key=settings.google_api_key)

def _normalize_gen_model(name: str) -> str:
    # Chat models should NOT have "models/" prefix
    return name.split("models/")[-1]

def _pick_model(name: str) -> str:
    target = _normalize_gen_model(name)
    try:
        # Check model availability + supported methods
        for m in genai.list_models():
            mid = getattr(m, "name", "")
            simple = mid.split("models/")[-1]
            methods = set(getattr(m, "supported_generation_methods", []) or [])
            if simple == target and ("generateContent" in methods or "generate_content" in methods):
                return simple
    except Exception:
        # If listing fails, just try the target
        return target
    # Fallback to flash if pro not found
    return "gemini-2.5-flash" if target != "gemini-2.5-flash" else target

_GEN_MODEL = _pick_model(settings.gen_model)
_llm = genai.GenerativeModel(_GEN_MODEL)

def generate_answer(prompt: str, temperature: float = 0.2) -> str:
    try:
        resp = _llm.generate_content(
            [prompt],
            generation_config={"temperature": temperature}
        )
        return (resp.text or "").strip()
    except Exception:
        # last-ditch: try flash once if pro failed
        if _GEN_MODEL != "gemini-2.5-flash":
            alt = genai.GenerativeModel("gemini-2.5-flash")
            resp = alt.generate_content([prompt], generation_config={"temperature": temperature})
            return (resp.text or "").strip()
        raise
