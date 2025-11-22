from __future__ import annotations
from typing import List, Dict, Any

def _serialize_passages(passages: List[Dict[str, Any]]) -> str:
    # passages: [{ id, content, metadata }]
    blocks = []
    for i, p in enumerate(passages, start=1):
        src = (p.get("metadata") or {}).get("source", "unknown")
        title = (p.get("metadata") or {}).get("title", "")
        head = f"[{i}] source={src}" + (f" | title={title}" if title else "")
        blocks.append(f"{head}\n{p['content'].strip()}")
    return "\n\n".join(blocks)

def build_rag_prompt(user_q: str, passages: List[Dict[str, Any]], target_lang: str = "en") -> str:
    """
    Strong “prefer my docs” instruction + inline citation style.
    """
    kb = _serialize_passages(passages)
    return f"""System:
        You are a retrieval-augmented assistant. Always prefer the Knowledge blocks over prior knowledge.
        If something is missing from Knowledge, say briefly that it's not covered.

        Answer in {target_lang}. Be concise, step-by-step when helpful.

        Question:
        {user_q}

        Knowledge:
        {kb}

        Answer:
        """

def build_general_prompt(user_q: str, target_lang: str = "en") -> str:
    """
    Fallback when we have no (or clearly insufficient) knowledge.
    """
    return f"""System:
        You are a careful assistant. Answer using general knowledge only.
        If you are unsure, say so briefly.

        Answer in {target_lang}. Be concise, step-by-step when helpful.

        Question:
        {user_q}

        Answer:
        """
