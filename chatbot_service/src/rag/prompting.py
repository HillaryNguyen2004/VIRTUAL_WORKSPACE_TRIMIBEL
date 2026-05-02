from __future__ import annotations
from typing import List, Dict, Any

def _serialize_passages(passages: List[Dict[str, Any]]) -> str:
    """
    Format retrieved passages for injection into the RAG prompt.

    Each passage is rendered as:
        [N] source=X | section=Y | page=Z | type=T
        <chunk body>

    The chunk body already contains a contextual header injected at ingest
    time (by chunking.py:prepend_header), so the LLM sees provenance both
    in the passage label AND inside the text itself — double-grounding.
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

def build_rag_prompt(
    user_q: str,
    user_role: str,
    passages: List[Dict[str, Any]], 
    target_lang: str = "en", 
    history: str = ""
) -> str:
    kb = _serialize_passages(passages)
    
    # Inject conversation history if available
    history_block = f"""CONVERSATION HISTORY:
    <history>
    {history}
    </history>

    """ if history else ""
    
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

        ANSWERING STYLE:
        <style>
        - Go directly to the instructions without any opener.
        - Explain only the features / permissions that are supported by Knowledge AND allowed for the user's role.
        - If the user's role is not mentioned in Knowledge for this feature, do NOT assume access; say it is not covered.
        - Be clear and concise. Give short explanations where helpful.
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

def build_general_prompt(user_q: str, target_lang: str = "en", history: str = "",) -> str:
    """
    Fallback when we have no (or clearly insufficient) knowledge.
    """
    # Inject conversation history if available
    history_block = f"""CONVERSATION HISTORY:
    <history>
    {history}
    </history>

    """ if history else ""
    
    return f"""
        SYSTEM INSTRUCTIONS:
        <system>
        Your name is Bot Bot.
        You are a careful assistant. Answer using general knowledge only.
        You are Bot Bot.

        You can use:
        - Conversation history (for context like user name)
        - General knowledge

        Rules:
        - Prefer history for personal questions
        - If unsure, say briefly
        </system>

        {history_block}
        Answer in {target_lang}. Be concise, step-by-step when helpful.

        Question:
        {user_q}

        Answer:
    """
    
def build_chitchat_prompt(
    user_q: str,
    target_lang: str = "en",
    history: str = ""
) -> str:

    history_block = f"""CONVERSATION HISTORY:
    <history>
    {history}
    </history>

    """ if history else ""

    return f"""
        SYSTEM:
        <system>
        You are Bot Bot, a friendly and natural conversational assistant.

        You are NOT the user.
        Do NOT pretend to be the user.

        Guidelines:
        - Be natural, friendly, and concise
        - Do NOT mention employees, productivity, analytics
        - Use conversation history when relevant (e.g., user's name)
        - Respond like a human conversation (not formal documentation)
        - If the user introduces themselves, acknowledge it politely
        - Do NOT repeat the user's sentence as your identity
        - Do NOT mention being an AI model
        </system>

        {history_block}

        LANGUAGE:
        - Answer in {target_lang}

        User:
        {user_q}

        Assistant:
    """

def build_summary_prompt(passages: List[Dict[str, Any]], lang: str) -> str:
    """
    For summarization, we want to encourage more abstraction and insight extraction, rather than just listing facts.
    """
    
    context = "\n\n".join(
        (p.get("content") or p.get("document") or "").strip()
        for p in passages
        if (p.get("content") or p.get("document"))
    )

    return f"""
        You are a data analyst.

        Summarize the following knowledge.

        Requirements:
        - Extract key insights
        - Identify trends if any
        - Be concise
        - Do NOT list raw records

        Language: {lang}

        Knowledge:
        {context}

        Summary:
    """
