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
    kb = _serialize_passages(passages)
    return f"""System:
        You are a retrieval-augmented assistant. Use ONLY the "Knowledge" text below. If the answer is not in Knowledge, say that it is not covered.

        Answer in {target_lang}.

        Formatting rules (very important):
        - Use clear and full information paragraphs or numbered / bulleted lists.
        - Each item MUST be on its own line.
        - Use line breaks (\\n) between items.
        - Do NOT write bullets inline like "You can do X: * item1 * item2".
        - Instead, format like:
        You can do X in a few ways:
        1. First way
        2. Second way

        - Do NOT mention "source", "sources", "citations", or "[1]", "[2]" in your answer.

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
