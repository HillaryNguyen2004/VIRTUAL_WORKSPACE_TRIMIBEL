from __future__ import annotations
from dataclasses import dataclass
from typing import List, Dict, Any, Optional, Callable
import logging
import re
import unicodedata
from .snapshot import get_latest_snapshot_date

from .retrieval import retrieve
from .vectorstores.chroma_store import get_chunks
from .prompting import build_rag_prompt, build_general_prompt, build_summary_prompt, build_chitchat_prompt
from .ollama_generate import generate_answer, stream_answer as ollama_stream_answer, GenerationCancelled
from .lang import detect_lang
from .memory import get_memory

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

# =========================
# RETRIEVAL PLAN CLASS
# =========================    
@dataclass
class RetrievalPlan:
    mode: str            # normal | aggregation | fallback
    k: int
    where: Optional[dict]

# =========================
# NORMALIZATION
# =========================
def normalize_text(text: str) -> str:
    text = text.lower().strip()
    text = unicodedata.normalize("NFD", text)
    text = "".join(ch for ch in text if unicodedata.category(ch) != "Mn")
    return re.sub(r"\s+", " ", text)

# =========================
# INTENT DETECTION
# =========================
def is_chitchat(q: str) -> bool:
    text = normalize_text(q)

    if re.search(r"\b(employee|productivity|nhan vien)\b", text):
        return False

    # greeting
    if re.search(r"\b(hi|hello|hey|xin chao|chao)\b", text):
        return True

    # identity / capability
    if re.search(r"\b(who are you|ban la ai)\b", text):
        return True

    # capability-specific
    if re.search(r"\b(what can you do|ban lam duoc gi|your capabilities)\b", text):
        return True

    # small talk
    if re.search(r"\b(how are you|khoe khong)\b", text):
        return True
    
    if re.search(r"\b(thank you|cam on|thanks)\b", text):
        return True
    
    if re.search(r"\b(sorry|xin loi)\b", text):
        return True
    
    if re.search(r"\b(nice to meet you)\b", text):
        return True

    return False

def is_analytics_query(q: str) -> bool:
    return bool(re.search(r"\b(overview|tinh hinh|tong the)\b", normalize_text(q)))

def is_summarize_query(q: str) -> bool:
    return bool(re.search(r"\b(summarize|summary|tom tat)\b", normalize_text(q)))

def is_comparison_query(q: str) -> bool:
    return bool(re.search(
        r"\b(larger|more|less|compare|which group|higher|lower)\b",
        normalize_text(q)
    ))
    
def is_file_content_query(q: str) -> bool:
    text = normalize_text(q)
    return bool(re.search(
        r"\b(content of|all content|full content|noi dung|toan bo noi dung)\b",
        text
    ))

def is_aggregation_query(q: str) -> bool:
    text = normalize_text(q)

    if is_chitchat(q):
        return False
    
    if is_summarize_query(q):
        return False
    
    if is_comparison_query(q):
        return False
    
    if is_file_content_query(q):
        return False

    return bool(re.search(
        r"\b(list|all|enumerate|find all|nhung nhan vien|cac nhan vien)\b",
        text
    ))

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
    if re.search(r"\b(declining|giam|sut giam)\b", text):
        trends.append("declining")

    if re.search(r"\b(improving|tang|cai thien)\b", text):
        trends.append("improving")

    if trends:
        if len(trends) == 1:
            filters.append({"trend": {"$eq": trends[0]}})
        else:
            filters.append({"trend": {"$in": trends}})

    # levels (multi-value)
    if re.search(r"\b(excellent|xuat sac|top)\b", text):
        levels.append("Excellent")

    if re.search(r"\b(good|kha|tot)\b", text):
        levels.append("Good")

    if re.search(r"\b(average|trung binh)\b", text):
        levels.append("Average")

    if levels:
        if len(levels) == 1:
            filters.append({"level": {"$eq": levels[0]}})
        else:
            filters.append({"level": {"$in": levels}})

    # risk
    if re.search(r"\b(risk|intervention|nguy co)\b", text):
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
# QUERY ANALYSIS
# =========================
def analyze_query(user_q: str, lang_hint: Optional[str], workspace_id: str = None) -> QueryAnalysis:
    lang = lang_hint or detect_lang(user_q) or "en"

    intent = classify_intent(user_q)
    filters = extract_file_filter(user_q) or extract_productivity_filters(user_q, workspace_id)
    is_agg = is_aggregation_query(user_q)

    return QueryAnalysis(
        intent=intent,
        filters=filters,
        is_aggregation=is_agg,
        is_file_content=is_file_content_query(user_q),
        lang=lang,
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

    # summarize = full scan (only for explicit summarize queries, not file-content)
    if analysis.intent == INTENT_SUMMARIZE:
        return RetrievalPlan("summarize", SUMMARY_MAX_PASSAGES, filters)

    # analytics = aggregation
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
    src_lang: Optional[str] = None,
    history: Optional[List[Dict[str, Any]]] = None,
) -> List[Dict[str, Any]]:
    if plan.mode == "none":
        return []

    if plan.mode == "aggregation":
        return get_chunks(plan.k, workspace_id=workspace_id, where=plan.where or None)

    return retrieve(
        user_q,
        k=plan.k,
        workspace_id=workspace_id,
        where=plan.where or {},
        should_cancel=should_cancel,
        expand=True,
        src_lang=src_lang,
        history=history or [],
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

        memory.add("user", user_q)
        memory.add("assistant", accumulated_text)
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
        src_lang=analysis.lang,
        history=memory.get_history(),
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
        
        memory.add("user", user_q)
        memory.add("assistant", accumulated_text)
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
    # 5. SUMMARIZE MODE
    # =========================
    if plan.mode == "summarize":
        active_logger.info("STREAM Summarize mode with %d passages", len(passages))
        text, usage = summarize_passages(passages, analysis.lang, should_cancel=should_cancel)

        memory.add("user", user_q)
        memory.add("assistant", text)
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
    if plan.k:
        passages = passages[:plan.k]
    
    active_logger.info(
        "STREAM RAG mode with %d passages",
        len(passages),
    )
    
    prompt = build_rag_prompt(
        user_q,
        user_role,
        passages,
        target_lang,
        history=memory.get_context_text(),
    )

    accumulated_text = ""
    usage = _zero_usage()
    for chunk in ollama_stream_answer(prompt, should_cancel=should_cancel, on_usage=usage.update):
        accumulated_text += chunk
        yield chunk

    memory.add("user", user_q)
    memory.add("assistant", accumulated_text)

    citations = [
        {
            "rank": i + 1,
            "id": p.get("id"),
            "source": (p.get("metadata") or {}).get("source", "unknown"),
        }
        for i, p in enumerate(passages)
    ]

    _log_final_response(
        active_logger,
        "STREAM",
        f"{user_id}_{workspace_id}",
        accumulated_text,
        citations,
        usage,
    )