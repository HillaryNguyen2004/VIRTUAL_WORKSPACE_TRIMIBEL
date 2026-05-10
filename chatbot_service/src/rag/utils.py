from __future__ import annotations

import re
import unicodedata

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

    # return bool(re.search(
    #     r"\b(list|all|enumerate|find all|nhung nhan vien|cac nhan vien)\b",
    #     text
    # ))
    
    # Keywords indicating full list or ranking retrieval needed
    list_keywords = (
        r"\b(list|all|enumerate|find all|who are|which\s+employees|nhung nhan vien|tat ca"
        r"|cac nhan vien|nhan vien nao|nhan vien gi|top\s*\d+|top\s+(?:employees|nhan vien))\b"
    )
    return bool(re.search(list_keywords, text))