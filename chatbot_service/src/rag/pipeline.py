from __future__ import annotations
from typing import Tuple, List, Dict, Any, Optional
from .retrieval import retrieve
from .prompting import build_rag_prompt, build_general_prompt
from .generator import generate_answer
from .lang import detect_lang

MIN_DOCS_FOR_CONFIDENT_RAG = 2        # require at least 2 docs
MIN_TOP_SCORE = 0.35                  # adjust to your store's scale (see note below)

def _score(p: Dict[str, Any]) -> float:
    # Expecting a numeric similarity or distance converted to similarity.
    # Put your real key here (e.g., p["score"] or p["metadata"]["score"]).
    return float(p.get("score") or (p.get("metadata", {})).get("score") or 0.0)

def answer(user_q: str, k: Optional[int] = None, lang_hint: Optional[str] = None) -> Tuple[str, List[Dict[str, Any]]]:
    target_lang = (lang_hint or detect_lang(user_q) or "en")
    passages = retrieve(user_q, k)

    # No hits -> general
    if not passages:
        text = generate_answer(build_general_prompt(user_q, target_lang), temperature=0.2)
        return text, []

    # Heuristic sufficiency gate
    top = passages[0]
    enough_docs = len(passages) >= MIN_DOCS_FOR_CONFIDENT_RAG
    strong_top = _score(top) >= MIN_TOP_SCORE if _score(top) else False
    use_docs = enough_docs or strong_top

    if not use_docs:
        # Fall back even though we have 1 weak passage
        text = generate_answer(build_general_prompt(user_q, target_lang), temperature=0.2)
        return text, []

    # RAG path (use your existing prompt that cites Knowledge)
    prompt = build_rag_prompt(user_q, passages, target_lang)
    text = generate_answer(prompt, temperature=0.2)

    citations = [
        {"rank": i + 1, "id": p.get("id"), "source": (p.get("metadata") or {}).get("source")}
        for i, p in enumerate(passages)
    ]
    return text, citations
