from __future__ import annotations
from typing import List, Dict, Any

# =========================
# PASSAGE SERIALISER
# =========================
def _serialize_passages(passages: List[Dict[str, Any]]) -> str:
    """
    Format retrieved passages for injection into the RAG prompt.

    Each passage is rendered as:
        [N] source=X | section=Y | page=Z | type=T
        <chunk body>

    The chunk body already contains a contextual header injected at ingest
    time (chunking.py:prepend_header), so the LLM sees provenance both
    in the label AND inside the text itself — double-grounded.
    """
    blocks = []
    for i, p in enumerate(passages, start=1):
        meta = p.get("metadata") or {}

        # Build label parts — include every non-empty provenance field
        label_parts = []
        src = meta.get("source") or meta.get("file_name") or "unknown"
        label_parts.append(f"source={src}")

        section = meta.get("section") or ""
        if section:
            label_parts.append(f"section={section[:60]}")

        page = meta.get("page")
        if page is not None:
            label_parts.append(f"page={page}")

        sheet = meta.get("sheet") or ""
        if sheet:
            label_parts.append(f"sheet={sheet}")

        headers = meta.get("headers") or {}
        if headers:
            h_str = " > ".join(str(v) for v in headers.values() if v)
            if h_str:
                label_parts.append(f"heading={h_str}")

        doc_type = meta.get("type") or ""
        if doc_type:
            label_parts.append(f"type={doc_type}")

        label = f"[{i}] " + " | ".join(label_parts)
        body = (p.get("document") or p.get("content") or "").strip()
        blocks.append(f"{label}\n{body}")

    return "\n\n".join(blocks)


def _confidence_band(passages: List[Dict[str, Any]]) -> str:
    """
    Derive a simple confidence label from passage scores so the LLM can
    calibrate its hedging language.
      high   → top passage _final_score ≥ 0.3
      medium → top passage _final_score ≥ 0.1
      low    → below that
    """
    if not passages:
        return "low"
    top = passages[0].get("_final_score", 0.0)
    if top >= 0.3:
        return "high"
    if top >= 0.1:
        return "medium"
    return "low"


# =========================
# RAG PROMPT
# =========================
def build_rag_prompt(
    user_q: str,
    user_role: str,
    passages: List[Dict[str, Any]],
    target_lang: str = "en",
    history: str = "",
) -> str:
    kb = _serialize_passages(passages)
    
    # Inject conversation history if available
    history_block = f"""CONVERSATION HISTORY:
    <history>
    {history}
    </history>

    """ if history else ""

    confidence = _confidence_band(passages)

    confidence_instruction = {
        "high": (
            "The retrieved Knowledge strongly supports this topic. "
            "Answer confidently and thoroughly."
        ),
        "medium": (
            "The retrieved Knowledge partially covers this topic. "
            "Answer what is clearly supported; flag anything uncertain."
        ),
        "low": (
            "The retrieved Knowledge has limited coverage. "
            "Only state what is explicitly in the text; do not infer. "
            "If the answer is not present, say so clearly."
        ),
    }[confidence]
    
    return f"""
        SYSTEM INSTRUCTIONS:
        <system>
        You are Bot Bot.
        You are Bot Bot, a retrieval-augmented assistant.

        You can use BOTH:
        1. Knowledge (retrieved documents)
        2. Conversation History

        Priority rules:
        - Use Knowledge for factual/system/domain questions
        - Use Conversation History for personal context (e.g., user's name)
        - If neither contains the answer, say it is not covered

        Do NOT ignore Conversation History.
        </system>

        USER ROLE:
        <role>
        - The user is logged in with the role: "{user_role}".
        - You MUST always assume the user has this role.
        - When describing what the user can do in the system, describe ONLY the capabilities available to this role.
        - Do NOT say things like "it depends on your role" because you already know the role.
        - Do NOT claim the user can perform admin/staff actions if those are not clearly available to "{user_role}".
        - Do NOT mention being a language model, AI model, or being trained by Google/OpenAI.
        - Always refer to yourself only as "Bot Bot".
        </role>

        KNOWLEDGE USAGE:
        <knowledge>
        - Use ONLY the information in the Knowledge section below.
        - If the answer is not covered in Knowledge, explicitly say that it is not covered.
        - Do NOT invent features or policies that are not supported by the Knowledge text.
        </knowledge>

        LANGUAGE:
        <language>
        - Answer in {target_lang}.
        </language>

        RETRIEVAL CONFIDENCE: {confidence.upper()}
        <confidence>
        {confidence_instruction}
        </confidence>

        PROFESSIONAL ANSWER STYLE:
        <style>
        - Begin directly with the answer — no filler openers ("Sure!", "Of course!", "Great question!").
        - Structure complex answers with numbered steps or bullet points.
        - For procedures, use numbered steps in order.
        - For feature lists, use bullet points.
        - For comparisons, use a short table or paired bullets.
        - End with a short closing sentence only if the user's question implies they need next steps.
        - Keep answers concise: prioritise depth over length. Do not pad.
        </style>

        FORMATTING RULES (VERY IMPORTANT):
        <formatting>
        - Use clear paragraphs or numbered / bulleted lists.
        - Each list item MUST be on its own line.
        - Use line breaks (\\n) between items.
        - Do NOT write bullets inline like: "You can do X: * item1 * item2".
        - If you list features or steps, use numbered lists or bullet points with line breaks
        - Instead, format like:
            You can do X in a few ways:
            1. First way
            2. Second way
        - Do NOT mention "source", "sources", "citations", or reference markers like "[1]" or "[2]" in your answer.
        </formatting>

        {history_block}
        Question:
        {user_q}

        Knowledge:
        {kb}

        Answer:
    """


# =========================
# GENERAL FALLBACK PROMPT
# =========================
def build_general_prompt(
    user_q: str,
    target_lang: str = "en",
    history: str = "",
) -> str:
    """Used when no relevant passages were retrieved."""
    history_block = (
        f"CONVERSATION HISTORY:\n<history>\n{history}\n</history>\n\n"
        if history else ""
    )

    return f"""\
        SYSTEM:
        <system>
        You are Bot Bot, a professional assistant.
        No document Knowledge is available for this question.

        Rules:
        - Answer using only general knowledge or the Conversation History.
        - Use Conversation History for personal context (e.g. user's name).
        - If the question requires specific system/policy knowledge you do not have,
        say clearly that you do not have that information and suggest the user
        consult the relevant documentation or contact support.
        - Never hallucinate facts, policies, or features.
        - You are Bot Bot. Do NOT mention being an AI model or being trained by any company.
        </system>

        {history_block}\
        Answer in {target_lang}. Be concise and professional.

        Question:
        {user_q}

        Answer:
"""


# =========================
# CHITCHAT PROMPT
# =========================
def build_chitchat_prompt(
    user_q: str,
    target_lang: str = "en",
    history: str = "",
) -> str:

    history_block = f"""CONVERSATION HISTORY:
    <history>
    {history}
    </history>

    """ if history else ""

    return f"""
        SYSTEM:
        <system>
        You are Bot Bot, a friendly and professional conversational assistant.

        Guidelines:
        - Be warm, natural, and concise — like a knowledgeable colleague, not a chatbot.
        - Use Conversation History when relevant (e.g. the user's name).
        - Do NOT mention employees, productivity metrics, or analytics unless the user asks.
        - Do NOT pretend to be the user or repeat their words as your own identity.
        - Do NOT mention being an AI model, a language model, or being trained by any company.
        - If the user introduces themselves, acknowledge them naturally and remember their name.
        </system>

        {history_block}\
        Language: {target_lang}

        User: {user_q}

        Bot Bot:
"""


# =========================
# SUMMARY PROMPT
# =========================
def build_summary_prompt(passages: List[Dict[str, Any]], lang: str) -> str:
    """
    Multi-batch summarisation prompt. Encourages insight extraction,
    not just fact listing.
    """
    
    context = "\n\n".join(
        (p.get("content") or p.get("document") or "").strip()
        for p in passages
        if (p.get("content") or p.get("document"))
    )

    return f"""\
        SYSTEM:
        You are a professional analyst. Your task is to summarise the following knowledge
        into a clear, structured executive summary.

        Requirements:
        - Lead with the most important insight or conclusion.
        - Use bullet points for supporting facts.
        - Identify trends, patterns, or anomalies if present.
        - Do NOT copy raw records verbatim — paraphrase and synthesise.
        - Do NOT fabricate data not present in the text.
        - Be concise: aim for 150–300 words.

        Language: {lang}

        Knowledge:
        {context}

        Summary:
"""
