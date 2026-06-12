from __future__ import annotations
from dataclasses import dataclass
from typing import List, Dict, Any, Optional, Callable
import logging
import re
from .snapshot import get_latest_snapshot_date

from .retrieval import retrieve
from .vectorstores.chroma_store import get_chunks
from .prompting import build_rag_prompt, build_general_prompt, build_summary_prompt, build_chitchat_prompt
from .ollama_generate import generate_answer, stream_answer as ollama_stream_answer, GenerationCancelled
from .lang import detect_lang
from .memory import get_memory
from .utils import normalize_text, is_chitchat, is_analytics_query, is_summarize_query, is_aggregation_query, is_file_content_query

log = logging.getLogger(__name__)

def _log_final_response(
    active_logger: Any,
    mode: str,
    request_id: str,
    answer_text: str,
    citations: List[Dict[str, Any]],
    usage: Dict[str, int],
) -> None:
    active_logger.info(
        "%s response payload: %s",
        mode,
        {
            "request_id": request_id,
            "answer": answer_text,
            "citations": citations,
            "usage": usage,
        },
    )


def _zero_usage() -> Dict[str, int]:
    return {"prompt_tokens": 0, "completion_tokens": 0, "total_tokens": 0}

# =========================
# CONFIG
# =========================
INTENT_CHITCHAT = "chitchat"
INTENT_SUMMARIZE = "summarize"
INTENT_ANALYTICS = "analytics"
INTENT_QA = "qa"

MAX_CONTEXT_PASSAGES = 20
SUMMARY_BATCH_SIZE = 10
SUMMARY_MAX_PASSAGES = 100

INJECTION_PATTERNS = [
    # =========================
    # ENGLISH
    # =========================
    r"ignore\s+(all\s+)?(previous|prior|earlier)\s+instructions?",
    r"disregard\s+(the\s+)?above",
    r"reveal\s+(your|the)\s+(system|hidden)\s+prompt",
    r"show\s+(your|the)\s+instructions?",
    r"you\s+are\s+now",
    r"pretend\s+to\s+be",
    r"act\s+as",
    r"developer\s+mode",
    r"jailbreak",
    r"bypass\s+(policy|restriction|guardrail)",
    r"do\s+anything\s+now",
    r"execute\s+tool",
    r"tool\s+call",
    r"function\s+call",
    r"print\s+hidden\s+prompt",

    # =========================
    # VIETNAMESE
    # =========================
    r"bo\s+qua\s+(tat\s+ca\s+)?huong\s+dan",
    r"bo\s+qua\s+(chi\s+dan|instruction)",
    r"khong\s+can\s+tuan\s+theo",
    r"phot\s+lo\s+huong\s+dan",
    r"tiet\s+lo\s+(prompt|he\s+thong|system)",
    r"cho\s+toi\s+xem\s+(prompt|huong\s+dan)",
    r"ban\s+bay\s+gio\s+la",
    r"hay\s+gia\s+vo\s+la",
    r"dong\s+vai",
    r"che\s+do\s+developer",
    r"vuot\s+qua\s+(bao\s+ve|kiem\s+duyet|guardrail)",
    r"thuc\s+thi\s+tool",
    r"goi\s+ham",
    r"hien\s+thi\s+prompt\s+an",
]

# =========================
# QUERY ANALYSIS CLASS
# =========================
@dataclass
class QueryAnalysis:
    intent: str
    filters: Optional[dict]
    is_aggregation: bool
    is_file_content: bool
    lang: str
    top_n: Optional[int] = None

# =========================
# RETRIEVAL PLAN CLASS
# =========================    
@dataclass
class RetrievalPlan:
    mode: str            # normal | aggregation | fallback
    k: int
    where: Optional[dict]

def classify_intent(q: str) -> str:
    if is_chitchat(q):
        return INTENT_CHITCHAT

    if is_analytics_query(q):
        return INTENT_ANALYTICS

    if is_summarize_query(q):
        return INTENT_SUMMARIZE

    if is_aggregation_query(q):
        return INTENT_ANALYTICS

    return INTENT_QA

# =========================
# FILTER EXTRACTION
# =========================
def extract_productivity_filters(q: str, workspace_id: str = None) -> dict | None:
    if workspace_id != "productivity":
        return None
    
    text = normalize_text(q)

    trends = []
    levels = []    
    filters = [{"record_type": {"$eq": "employee_snapshot"}}]
    
    # overview
    if re.search(r"\b(overview|summary|summarize|tong quan|tong quat)\b", text):
        summary_filters = [{"record_type": {"$eq": "team_summary"}}]
        latest_snapshot = get_latest_snapshot_date(workspace_id)
        if latest_snapshot:
            summary_filters.append({"snapshot_date": {"$eq": latest_snapshot}})

        if len(summary_filters) == 1:
            return summary_filters[0]
        return {"$and": summary_filters}
    
    latest_snapshot = get_latest_snapshot_date(workspace_id)
    if latest_snapshot:
        filters.append({"snapshot_date": {"$eq": latest_snapshot}})

    # trends (multi-value)
    if re.search(r"\b(declining|giam|sut giam|decrease)\b", text):
        trends.append("declining")

    if re.search(r"\b(improving|tang|cai thien|increase)\b", text):
        trends.append("improving")

    if re.search(r"\b(stable|on dinh|duy tri)\b", text):
        trends.append("stable")

    if trends:
        if len(trends) == 1:
            filters.append({"trend": {"$eq": trends[0]}})
        else:
            filters.append({"trend": {"$in": trends}})

    # predicted levels (multi-value) — field is predicted_level, values are High/Medium/Low
    if re.search(r"\b(high|cao|excellent|xuat sac|top performer)\b", text):
        levels.append("High")

    if re.search(r"\b(medium|trung binh|average|kha)\b", text):
        levels.append("Medium")

    if re.search(r"\b(low|thap|urgent|khan cap|can thiep|needs urgent|needs attention)\b", text):
        levels.append("Low")

    if levels:
        if len(levels) == 1:
            filters.append({"predicted_level": {"$eq": levels[0]}})
        else:
            filters.append({"predicted_level": {"$in": levels}})

    # at-risk = declining trend (model infers active alerts from chunk content)
    if re.search(r"\b(risk|at.risk|nguy co|rui ro)\b", text):
        if not trends:  # avoid duplicate trend filter
            filters.append({"trend": {"$eq": "declining"}})

    if not filters:
        return None

    if len(filters) == 1:
        return filters[0]

    return {"$and": filters}

def extract_file_filter(q: str) -> dict | None:
    """If the query mentions a specific filename, filter retrieval to that file."""
    # Match common file extensions
    match = re.search(
        r"([\w\-]+\.(pdf|docx|xlsx|csv|txt|md))",
        q,
        re.IGNORECASE
    )
    if match:
        filename = match.group(1)
        return {"source": {"$eq": filename}}
    return None

# =========================
# TOP-N EXTRACTION
# =========================
def extract_top_n(q: str) -> Optional[int]:
    text = normalize_text(q)

    patterns = [
        r"\btop\s*(\d+)\b",
        r"\b(\d+)\s*(best|top|highest|lowest)\b",
        r"\b(\d+)\s*(employees|people)\b",
        r"\b(\d+)\s*(nhan vien|nguoi)\b",
    ]

    for pattern in patterns:
        match = re.search(pattern, text)
        if match:
            return int(match.group(1))

    return None

# =========================
# QUERY ANALYSIS
# =========================
def analyze_query(user_q: str, lang_hint: Optional[str], workspace_id: str = None) -> QueryAnalysis:
    lang = lang_hint or detect_lang(user_q) or "en"

    intent = classify_intent(user_q)
    filters = extract_file_filter(user_q) or extract_productivity_filters(user_q, workspace_id)
    is_agg = is_aggregation_query(user_q)
    top_n = extract_top_n(user_q)

    return QueryAnalysis(
        intent=intent,
        filters=filters,
        is_aggregation=is_agg,
        is_file_content=is_file_content_query(user_q),
        lang=lang,
        top_n=top_n,
    )

# =========================
# BUILD RETRIEVAL PLAN
# =========================
def build_retrieval_plan(
    analysis: QueryAnalysis,
    k: Optional[int],
    workspace_id: Optional[str] = None,
) -> RetrievalPlan:
    # =========================
    # CHITCHAT → no retrieval
    # =========================
    if analysis.intent == INTENT_CHITCHAT:
        return RetrievalPlan("none", 0, None)
        
    base_k = k if k is not None else 5

    filters = analysis.filters if workspace_id == "productivity" else None
    
    # If the query explicitly asks for top N items, prioritize that over intent-based heuristics for k and retrieval mode
    if analysis.top_n:
        return RetrievalPlan(
            "aggregation",
            max(analysis.top_n * 3, 20),  # buffer
            filters,
        )

    # summarize = full scan (only for explicit summarize queries, not file-content)
    if analysis.intent == INTENT_SUMMARIZE:
        return RetrievalPlan("summarize", SUMMARY_MAX_PASSAGES, filters)

    # analytics = aggregation (list-all style questions need more passages)
    if analysis.is_aggregation:
        return RetrievalPlan("aggregation", 100, filters)

    # file content queries get more passages but stream normally
    if analysis.is_file_content:
        return RetrievalPlan("normal", 20, filters)

    return RetrievalPlan("normal", base_k, filters)

# =========================
# RETRIEVAL STRATEGY
# =========================
def retrieve_passages(
    user_q: str,
    plan: RetrievalPlan,
    workspace_id: Optional[str],
    should_cancel: Optional[Callable[[], bool]] = None,
) -> List[Dict[str, Any]]:
    if plan.mode == "none":
        return []

    # if plan.mode == "aggregation":
    #     # return get_chunks(plan.k, workspace_id=workspace_id, where=plan.where or None)
    #     return retrieve(
    #         user_q,
    #         k=plan.k,
    #         workspace_id=workspace_id,
    #         where=plan.where or {},
    #         should_cancel=should_cancel,
    #     )

    return retrieve(
        user_q,
        k=plan.k,
        workspace_id=workspace_id,
        where=plan.where or {},
        should_cancel=should_cancel
    )

def summarize_passages(passages, lang, should_cancel: Optional[Callable[[], bool]] = None):
    passages = passages[:SUMMARY_MAX_PASSAGES]

    partial = []

    for i in range(0, len(passages), SUMMARY_BATCH_SIZE):
        if should_cancel and should_cancel():
            raise GenerationCancelled("Request canceled")

        batch = passages[i:i+SUMMARY_BATCH_SIZE]

        prompt = build_summary_prompt(batch, lang)
        text, _ = generate_answer(prompt, should_cancel=should_cancel)

        partial.append(text)

    final_prompt = build_summary_prompt(
        [{"content": s} for s in partial],
        lang
    )

    return generate_answer(final_prompt, should_cancel=should_cancel)

def sort_passages(passages: List[Dict[str, Any]], key: str, reverse: bool = True):
    def get_value(p):
        return (p.get("metadata") or {}).get(key, 0)

    return sorted(passages, key=get_value, reverse=reverse)

def detect_sort_key(q: str) -> str:
    text = normalize_text(q)

    if re.search(r"\b(productivity|hieu suat)\b", text):
        return "current_productivity"

    if re.search(r"\b(predicted|du doan)\b", text):
        return "predicted_productivity"

    return None

def sanitize_retrieved_context(text: str) -> str:
    sanitized = text

    for pattern in INJECTION_PATTERNS:
        sanitized = re.sub(
            pattern,
            "[BLOCKED_PROMPT_INJECTION]",
            sanitized,
            flags=re.IGNORECASE,
        )

    return sanitized

# =========================
# MAIN ANSWER
# =========================
# def answer(
#     user_q: str,
#     k: Optional[int] = None,
#     lang_hint: Optional[str] = None,
#     user_id: Optional[str] = None,
#     user_role: Optional[str] = None,
#     workspace_id: Optional[str] = None,
#     logger: Optional[Any] = None,
#     should_cancel: Optional[Callable[[], bool]] = None,
# ) -> Tuple[str, List[Dict[str, Any]], Dict[str, int]]:

#     target_lang = lang_hint or detect_lang(user_q) or "en"
#     active_logger = logger or log

#     def ensure_not_cancelled() -> None:
#         if should_cancel and should_cancel():
#             raise GenerationCancelled("Request canceled")

#     ensure_not_cancelled()

#     # =========================
#     # 1. ANALYZE
#     # =========================
#     analysis = analyze_query(user_q, lang_hint)
#     ensure_not_cancelled()
    
#     active_logger.info(
#         "Query analysis: intent=%s aggregation=%s filters=%s lang=%s",
#         analysis.intent,
#         analysis.is_aggregation,
#         analysis.filters,
#         analysis.lang,
#     )

#     memory = get_memory(f"{user_id}_{workspace_id}", max_turns=10)
#     ensure_not_cancelled()

#     # =========================
#     # 2. CHITCHAT
#     # =========================
#     if analysis.intent == INTENT_CHITCHAT:
#         active_logger.info("Chitchat path selected for query=%r", user_q)
#         prompt = build_chitchat_prompt(user_q, analysis.lang, history=memory.get_context_text())
#         text, usage = generate_answer(prompt, should_cancel=should_cancel)

#         memory.add("user", user_q)
#         memory.add("assistant", text)

#         _log_final_response(
#             active_logger,
#             "CHAT",
#             f"{user_id}_{workspace_id}",
#             text,
#             [],
#             usage,
#         )

#         return text, [], usage

#     # =========================
#     # 3. BUILD RETRIEVAL PLAN
#     # =========================
#     plan = build_retrieval_plan(analysis, k)
#     ensure_not_cancelled()

#     log.info("Retrieval plan: mode=%s k=%s where=%s", plan.mode, plan.k, plan.where)

#     # =========================
#     # 4. RETRIEVAL
#     # =========================
#     passages = retrieve_passages(
#         user_q,
#         plan,
#         workspace_id,
#         user_role,
#         should_cancel=should_cancel,
#     )
#     ensure_not_cancelled()

#     if not passages:
#         prompt = build_general_prompt(
#             user_q,
#             analysis.lang,
#             history=memory.get_context_text(),
#         )
#         text, usage = generate_answer(prompt, should_cancel=should_cancel)
#         ensure_not_cancelled()

#         _log_final_response(
#             active_logger,
#             "CHAT",
#             f"{user_id}_{workspace_id}",
#             text,
#             [],
#             usage,
#         )

#         return text, [], usage
    
#     # =========================
#     # 5. SUMMARIZE MODE
#     # =========================
#     if plan.mode == "summarize":
#         text, usage = summarize_passages(passages, analysis.lang, should_cancel=should_cancel)

#         memory.add("user", user_q)
#         memory.add("assistant", text)

#         _log_final_response(
#             active_logger,
#             "CHAT",
#             f"{user_id}_{workspace_id}",
#             text,
#             [],
#             usage,
#         )
        
#         return text, [], usage

#     # =========================
#     # 6. NORMAL RAG
#     # =========================
#     if plan.k:
#         passages = passages[:plan.k]
    
#     prompt = build_rag_prompt(
#         user_q,
#         user_role,
#         passages,
#         target_lang,
#         history=memory.get_context_text(),
#     )

#     text, usage = generate_answer(prompt, should_cancel=should_cancel)
#     ensure_not_cancelled()

#     memory.add("user", user_q)
#     memory.add("assistant", text)

#     citations = [
#         {
#             "rank": i + 1,
#             "id": p.get("id"),
#             "source": (p.get("metadata") or {}).get("source", "unknown"),
#         }
#         for i, p in enumerate(passages)
#     ]

#     _log_final_response(
#         active_logger,
#         "CHAT",
#         f"{user_id}_{workspace_id}",
#         text,
#         citations,
#         usage,
#     )

#     return text, citations, usage

# =========================
# STREAMING ANSWER
# =========================
def answer(
    user_q: str,
    k: Optional[int] = None,
    lang_hint: Optional[str] = None,
    user_id: Optional[str] = None,
    user_role: Optional[str] = None,
    workspace_id: Optional[str] = None,
    logger: Optional[Any] = None,
    should_cancel: Optional[Callable[[], bool]] = None,
):
    """Stream answer with logging similar to answer() function."""
    target_lang = lang_hint or detect_lang(user_q) or "en"
    active_logger = logger or log

    def ensure_not_cancelled() -> None:
        if should_cancel and should_cancel():
            raise GenerationCancelled("Request canceled")

    ensure_not_cancelled()

    # =========================
    # 1. ANALYZE
    # =========================
    analysis = analyze_query(user_q, lang_hint, workspace_id)
    ensure_not_cancelled()
    
    active_logger.info(
        "STREAM Query analysis: intent=%s aggregation=%s filters=%s lang=%s",
        analysis.intent,
        analysis.is_aggregation,
        analysis.filters,
        analysis.lang,
    )

    memory = get_memory(f"{user_id}_{workspace_id}", max_turns=10)
    ensure_not_cancelled()

    # =========================
    # 2. CHITCHAT
    # =========================
    if analysis.intent == INTENT_CHITCHAT:
        active_logger.info("STREAM Chitchat path selected for query=%r", user_q)
        prompt = build_chitchat_prompt(user_q, analysis.lang, history=memory.get_context_text())
        accumulated_text = ""
        usage: Dict[str, int] = _zero_usage()
        for chunk in ollama_stream_answer(prompt, should_cancel=should_cancel, on_usage=usage.update):
            accumulated_text += chunk
            yield chunk
            
        safe_user_q = sanitize_retrieved_context(user_q)
        safe_answer = sanitize_retrieved_context(accumulated_text)

        memory.add("user", safe_user_q)
        memory.add("assistant", safe_answer)
        _log_final_response(
            active_logger,
            "STREAM",
            f"{user_id}_{workspace_id}",
            accumulated_text,
            [],
            usage,
        )
        return

    # =========================
    # 3. BUILD RETRIEVAL PLAN
    # =========================
    plan = build_retrieval_plan(analysis, k, workspace_id)
    ensure_not_cancelled()

    active_logger.info("STREAM Retrieval plan: mode=%s k=%s where=%s", plan.mode, plan.k, plan.where)

    # =========================
    # 4. RETRIEVAL
    # =========================
    passages = retrieve_passages(
        user_q,
        plan,
        workspace_id,
        should_cancel=should_cancel,
    )
    ensure_not_cancelled()

    if not passages:
        active_logger.info("STREAM No passages found, using general prompt")
        prompt = build_general_prompt(
            user_q,
            analysis.lang,
            history=memory.get_context_text(),
        )
        accumulated_text = ""
        usage = _zero_usage()
        for chunk in ollama_stream_answer(prompt, should_cancel=should_cancel, on_usage=usage.update):
            accumulated_text += chunk
            yield chunk
            
        safe_user_q = sanitize_retrieved_context(user_q)
        safe_answer = sanitize_retrieved_context(accumulated_text)
        
        memory.add("user", safe_user_q)
        memory.add("assistant", safe_answer)
        _log_final_response(
            active_logger,
            "STREAM",
            f"{user_id}_{workspace_id}",
            accumulated_text,
            [],
            usage,
        )
        return
    
    safe_passages = []

    for p in passages:
        safe_p = dict(p)

        content = p.get("content", "")
        safe_p["content"] = sanitize_retrieved_context(content)

        safe_passages.append(safe_p)
    
    # =========================
    # 5. SUMMARIZE MODE
    # =========================
    if plan.mode == "summarize":
        active_logger.info("STREAM Summarize mode with %d passages", len(safe_passages))
        text, usage = summarize_passages(safe_passages, analysis.lang, should_cancel=should_cancel)

        safe_user_q = sanitize_retrieved_context(user_q)
        safe_text = sanitize_retrieved_context(text)

        memory.add("user", safe_user_q)
        memory.add("assistant", safe_text)
        _log_final_response(
            active_logger,
            "STREAM",
            f"{user_id}_{workspace_id}",
            text,
            [],
            usage,
        )
        
        yield text
        return

    # =========================
    # 6. NORMAL RAG
    # =========================
    if analysis.top_n:
        sort_key = detect_sort_key(user_q)

        if sort_key:
            safe_passages = sort_passages(safe_passages, key=sort_key)

        safe_passages = safe_passages[:analysis.top_n]
    else:
        if plan.k:
            safe_passages = safe_passages[:plan.k]
    
    active_logger.info(
        "STREAM RAG mode with %d passages",
        len(safe_passages),
    )
    
    prompt = build_rag_prompt(
        user_q,
        user_role,
        safe_passages,
        target_lang,
        history=memory.get_context_text(),
        include_confidence=False,
    )

    accumulated_text = ""
    usage = _zero_usage()
    for chunk in ollama_stream_answer(prompt, should_cancel=should_cancel, on_usage=usage.update):
        accumulated_text += chunk
        yield chunk

    safe_user_q = sanitize_retrieved_context(user_q)
    safe_answer = sanitize_retrieved_context(accumulated_text)

    memory.add("user", safe_user_q)
    memory.add("assistant", safe_answer)

    citations = [
        {
            "rank": i + 1,
            "id": p.get("id"),
            "source": (p.get("metadata") or {}).get("source", "unknown"),
            "content": sanitize_retrieved_context(
                str(p.get("content") or p.get("document") or "")
            ),
        }
        for i, p in enumerate(safe_passages)
    ]

    _log_final_response(
        active_logger,
        "STREAM",
        f"{user_id}_{workspace_id}",
        safe_answer,
        citations,
        usage,
    )