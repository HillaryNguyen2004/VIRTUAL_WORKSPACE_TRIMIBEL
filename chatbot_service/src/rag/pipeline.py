from __future__ import annotations
from typing import Tuple, List, Dict, Any, Optional
import re

from .retrieval import retrieve
from .prompting import build_rag_prompt, build_general_prompt
# from .gemini_generator import generate_answer
from .ollama_generate import generate_answer
from .lang import detect_lang

MIN_DOCS_FOR_CONFIDENT_RAG = 2
MIN_TOP_SCORE = 0.35

def is_chitchat(user_q: str) -> bool:
    text = user_q.lower().strip()
    words = text.split()

    # Very short trivial messages (1–3 words)
    # Greetings
    greetings = [
        "hi", "hello", "hey", "heyy", "yo",
        "good morning", "good afternoon", "good evening",
    ]

    if len(words) <= 3 and any(g == text for g in greetings):
        return True

    # Simple acknowledgements / thanks / closings
    short_ack = {
        "ok", "okay", "okk", "okie",
        "thanks", "thank you", "thx", "tks",
        "cam on", "cảm ơn",
        "bye", "goodbye", "bye bye", "see you",
        "got it", "roger", "noted",
    }
    if len(words) <= 4 and text in short_ack:
        return True

    # Classic small-talk / social phrases
    small_talk_phrases = [
        "what's up",
        "whats up",
        "sup",
        "how's it going",
        "hows it going",
        "how is it going",
        "how's your day",
        "hows your day",
        "how is your day",
        "tell me a joke",
        "make me laugh",
        "i'm bored",
        "im bored",
        "talk to me",
        "chat with me",
        "keep me company",
    ]
    if any(phrase in text for phrase in small_talk_phrases):
        return True

    # Meta questions about the bot itself
    patterns: List[str] = [
        r"\bwhat('?s| is) your name\b",
        r"\bwho are you\b",
        r"\bwhat can you do\b",
        r"\bhow are you\b",
        r"\bnice to meet you\b",

        r"\bare you (a )?robot\b",
        r"\bare you (a )?human\b",
        r"\bare you real\b",
        r"\bdo you have feelings\b",
        r"\bwhere are you from\b",
        r"\bhow old are you\b",
        r"\bdo you sleep\b",
        r"\bdo you (speak|know) (vietnamese|english)\b",
        r"\bwho made you\b",
        r"\bwho created you\b",
    ]
    if any(re.search(p, text) for p in patterns):
        return True

    # Emoji or laugh-only messages
    laugh_tokens = {"haha", "hahaha", "lol", "lmao", "rofl", "hahaah", "hihi"}
    if len(words) <= 3 and (
        text in laugh_tokens
        or all(ch in "😄😂🤣😅😉😊😍❤️👍👌🙏🤭🤔🥲🥹🥰" for ch in text if not ch.isspace())
    ):
        return True

    return False

# def is_db_question(user_q: str) -> bool:
#     text = user_q.lower()

#     asks_how_to = any(
#         phrase in text
#         for phrase in [
#             "how to",
#             "how do i",
#             "where can i",
#             "how can i",
#         ]
#     )
#     if asks_how_to:
#         return False

#     # Domain flags
#     about_attendance = any(
#         k in text
#         for k in [
#             "check in",
#             "check-in",
#             "check out",
#             "checkout",
#             "attendance",
#         ]
#     )
#     about_tasks = any(
#         k in text
#         for k in [
#             "task",
#             "tasks",
#             "assigned task",
#             "assigned tasks",
#         ]
#     )
#     about_requests = any(
#         k in text
#         for k in [
#             "day off",
#             "day-off",
#             "leave request",
#             "leave requests",
#             "time off",
#         ]
#     )
#     about_users = any(
#         k in text
#         for k in [
#             "user list",
#             "list of users",
#             "all users",
#         ]
#     )
#     # NEW: team / leader domain
#     about_team = any(
#         k in text
#         for k in [
#             "team leader",
#             "team lead",
#             "leader of my team",
#             "my team members",
#             "team members",
#             "my team",
#         ]
#     )

#     mentions_me = any(
#         k in text
#         for k in [
#             " my ",
#             " for me",
#             " assigned to me",
#             " my task",
#             " my tasks",
#             " my request",
#             " my requests",
#         ]
#     ) or text.startswith("my ")

#     asks_stats = any(
#         k in text
#         for k in [
#             "how many",
#             "count",
#             "total",
#             "list all",
#             "show me all",
#             "show all",
#         ]
#     )

#     domain = about_attendance or about_tasks or about_requests or about_users or about_team

#     if domain and (mentions_me or asks_stats or about_team):
#         return True

#     return False


# def _score(p: Dict[str, Any]) -> float:
#     meta = p.get("metadata") or {}
#     raw = p.get("score", meta.get("score", 0.0))
#     try:
#         return float(raw)
#     except (TypeError, ValueError):
#         return 0.0


def _score_passage_relevance(user_q: str, passage_text: str) -> float:
    query_tokens = [t for t in re.findall(r"\w+", user_q.lower()) if len(t) >= 3]
    if not query_tokens:
        return 0.0

    content = (passage_text or "").lower()
    hits = sum(1 for token in set(query_tokens) if token in content)
    return hits / max(1, len(set(query_tokens)))


def _compute_confidence(
    user_q: str,
    passages: List[Dict[str, Any]],
    answer_text: str,
) -> Dict[str, Any]:
    if not passages:
        return {
            "level": "low",
            "score": 0.0,
            "reason": "No relevant context was retrieved for this question.",
        }

    relevance_scores = [
        _score_passage_relevance(user_q, str(p.get("content", "")))
        for p in passages
    ]

    top_relevance = max(relevance_scores) if relevance_scores else 0.0
    avg_relevance = (sum(relevance_scores) / len(relevance_scores)) if relevance_scores else 0.0
    coverage_bonus = min(0.2, len(passages) * 0.05)
    answer_non_empty = 0.1 if (answer_text or "").strip() else 0.0

    raw = 0.55 * top_relevance + 0.35 * avg_relevance + coverage_bonus + answer_non_empty
    score = max(0.0, min(1.0, raw))

    if score >= 0.75:
        level = "high"
        reason = "The answer is supported by multiple relevant context chunks."
    elif score >= 0.45:
        level = "medium"
        reason = "The answer is partially grounded, but context relevance is moderate."
    else:
        level = "low"
        reason = "Retrieved context has weak overlap with the question; verify before using."

    return {
        "level": level,
        "score": round(score, 3),
        "reason": reason,
    }


def answer(
    user_q: str,
    k: Optional[int] = None,
    lang_hint: Optional[str] = None,
    user_id: Optional[str] = None,
    user_role: Optional[str] = None,
) -> Tuple[str, List[Dict[str, Any]], Dict[str, Any]]:

    target_lang = lang_hint or detect_lang(user_q) or "en"

    # Handle chit-chat first (no retrieval, no citations, no DB)
    if is_chitchat(user_q):
        prompt = f"""System:
            Your name is Bot Bot.
            You are a friendly, concise assistant having a casual conversation with the user. You can help user to answer about their works or something related to them
            Do NOT mention documents, retrieval, or knowledge bases.
            
            
            If you do not know the answer or are not sure:
            - say so politely, without inventing information, and
            - then ask a short follow-up question about the system or the user's use case
            that could help you answer better.

            Answer in {target_lang}.

            User: {user_q}
            Assistant:
        """
        text = generate_answer(prompt, temperature=0.6)
        confidence = {
            "level": "high",
            "score": 0.9,
            "reason": "This is a conversational response and does not require document grounding.",
        }
        return text, [], confidence
    
    # DB agent here
    # text = answer_from_db(user_q, target_lang=target_lang, user_id=user_id,)
    # return text, []

    # Retrieve candidate passages from the knowledge base (RAG)
    passages = retrieve(user_q, k)

    # Filter out passages with very low keyword overlap to reduce noise for small models
    if passages:
        passages = [p for p in passages if _score_passage_relevance(user_q, str(p.get("content", ""))) > 0.02]

    # Cap at 6 passages
    passages = passages[:6]

    prompt = build_rag_prompt(user_q, user_role, passages, target_lang)
    text = generate_answer(prompt, temperature=0.2)

    citations: List[Dict[str, Any]] = []
    seen_sources: set = set()
    for i, p in enumerate(passages):
        meta = p.get("metadata") or {}
        source = str(meta.get("source", p.get("id", "")))
        # Deduplicate by source so the UI doesn't show the same file multiple times
        if source in seen_sources:
            continue
        seen_sources.add(source)
        page = meta.get("page")
        line = meta.get("line")
        source_type = meta.get("source_type", "doc" if source.lstrip("-").isdigit() else "file")
        citations.append({
            "rank": len(citations) + 1,
            "id": str(p.get("id", "")),
            "source": source,
            "page": int(page) if page is not None else None,
            "line": int(line) if line is not None else None,
            "source_type": source_type,
        })

    confidence = _compute_confidence(user_q, passages, text)
    return text, citations, confidence

    # Fallback: general knowledge (no good docs, not a DB query)
    # prompt = build_general_prompt(user_q, target_lang)
    # text = generate_answer(prompt, temperature=0.2)
    # return text, []
