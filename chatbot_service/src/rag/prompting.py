from __future__ import annotations
from typing import List, Dict, Any


def _serialize_passages(passages: List[Dict[str, Any]]) -> str:
    blocks = []
    for i, p in enumerate(passages, start=1):
        meta = p.get("metadata") or {}
        src = meta.get("source", "unknown")
        page = meta.get("page")
        loc = f" (page {page})" if page else ""
        blocks.append(f"[{i}] {src}{loc}\n{p['content'].strip()}")
    return "\n\n".join(blocks)


def build_rag_prompt(user_q: str, user_role: str, passages: List[Dict[str, Any]], target_lang: str = "en") -> str:
    kb = _serialize_passages(passages)

    no_context_note = (
        "No relevant documents were found. Answer from general knowledge if possible, "
        "or say you don't have enough information."
        if not passages else ""
    )

    lang_instruction = (
        "Respond in Vietnamese." if target_lang in ("vi", "vi-VN")
        else "Respond in English."
    )

    role_note = f"The user's role is: {user_role}." if user_role else ""

    return f"""You are Bot Bot, a helpful document assistant.
{role_note}
{lang_instruction}
{no_context_note}

RULES:
- Answer ONLY from the documents below. Do NOT invent facts.
- If the answer is not in the documents, say: "I could not find this in the available documents."
- Be concise and direct. No filler phrases like "Of course!" or "Certainly!".
- Use bullet points or numbered lists when listing steps or items.
- Do NOT mention source filenames, page numbers, or citation markers in your answer.
- Do NOT say you are an AI or mention any AI company.

DOCUMENTS:
{kb}

QUESTION: {user_q}

ANSWER:"""


def build_general_prompt(user_q: str, target_lang: str = "en") -> str:
    lang_instruction = (
        "Respond in Vietnamese." if target_lang in ("vi", "vi-VN")
        else "Respond in English."
    )
    return f"""You are Bot Bot, a helpful assistant.
{lang_instruction}
Be concise and direct. If unsure, say so briefly.

QUESTION: {user_q}

ANSWER:"""
