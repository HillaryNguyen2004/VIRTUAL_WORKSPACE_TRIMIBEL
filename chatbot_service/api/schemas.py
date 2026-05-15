from __future__ import annotations
from typing import Any, Dict, List
from pydantic import BaseModel, Field

class ChatRequest(BaseModel):
    message: str = Field(..., min_length=1)
    k: int | None = Field(default=None, gt=0, le=50)
    lang: str | None = None
    user_id: str | None = None
    user_role: str | None = None
    workspace_id: str | None = None
    request_id: str | None = Field(default=None, min_length=1, max_length=128)


class CancelRequest(BaseModel):
    request_id: str = Field(..., min_length=1, max_length=128)

class Citation(BaseModel):
    rank: int
    id: str
    source: str
    location: str = ""


class Usage(BaseModel):
    prompt_tokens: int = 0
    completion_tokens: int = 0
    total_tokens: int = 0

class Confidence(BaseModel):
    level: str = "low"       # "high" | "medium" | "low"
    score: float = 0.0
    reason: str = ""

class ChatResponse(BaseModel):
    answer: str
    citations: List[Citation]
    usage: Usage | None = None
    confidence: Confidence | None = None

class IngestS3Request(BaseModel):
    s3_key: str = Field(..., min_length=1)
    workspace_id: str = Field(..., min_length=1)
    original_name: str | None = None
    storage_file_name: str | None = None
    user_id: str | None = None

class DeleteChunksRequest(BaseModel):
    storage_file: str = Field(..., min_length=1)
    workspace_id: str = Field(..., min_length=1)

class IngestResult(BaseModel):
    success: bool
    total_chunks: int = 0
    error: str | None = None


class SearchRequest(BaseModel):
    query: str = Field(..., min_length=1)
    workspace_id: str = Field(..., min_length=1)
    k: int = Field(default=5, gt=0, le=50)
    lang: str | None = None
    history: List[Dict[str, str]] | None = None
    where: Dict[str, Any] | None = None
    user_id: str | None = None


class PassageResult(BaseModel):
    id: str
    content: str
    metadata: Dict[str, Any]
    rrf_score: float = 0.0
    final_score: float = 0.0


class SearchResponse(BaseModel):
    passages: List[PassageResult]
    total: int


class MultiSearchRequest(BaseModel):
    query: str = Field(..., min_length=1)
    workspace_ids: List[str] = Field(..., min_items=1)
    user_role: str = "user"
    k: int = Field(default=5, gt=0, le=50)
    lang: str | None = None
    history: List[Dict[str, str]] | None = None
    history_text: str = ""
    user_id: str | None = None
    request_id: str | None = Field(default=None, min_length=1, max_length=128)


class AgentAnswerRequest(BaseModel):
    query: str = Field(..., min_length=1)
    workspace_id: str = Field(..., min_length=1)
    user_role: str = "user"
    k: int = Field(default=5, gt=0, le=50)
    lang: str | None = None
    history: List[Dict[str, str]] | None = None
    history_text: str = ""
    where: Dict[str, Any] | None = None
    request_id: str | None = Field(default=None, min_length=1, max_length=128)
    user_id: str | None = None


class AgentAnswerResponse(BaseModel):
    answer: str
    passages: List[PassageResult]


class BatchIngestRequest(BaseModel):
    items: List[Dict[str, str]] = Field(..., min_length=1)
    workspace_id: str = Field(..., min_length=1)
    user_id: str | None = None


class BatchIngestResult(BaseModel):
    total_chunks: int
    success: List[str]
    failed: List[Dict[str, str]]


# ---------------------------------------------------------------------------
# Summary agent
# ---------------------------------------------------------------------------

class SummaryTextRequest(BaseModel):
    text: str = Field(..., min_length=1)
    lang: str = Field(default="auto", pattern=r"^(auto|vi|en)$")
    style: str = Field(default="bullet", pattern=r"^(bullet|paragraph|short)$")
    n_clusters: int = Field(default=10, ge=3, le=20)
    request_id: str | None = Field(default=None, min_length=1, max_length=128)


class SummaryDocumentRequest(BaseModel):
    s3_key: str = Field(..., min_length=1)
    workspace_id: str | None = None
    lang: str = Field(default="auto", pattern=r"^(auto|vi|en)$")
    style: str = Field(default="bullet", pattern=r"^(bullet|paragraph|short)$")
    n_clusters: int = Field(default=10, ge=3, le=20)
    request_id: str | None = Field(default=None, min_length=1, max_length=128)


class SummaryWorkspaceRequest(BaseModel):
    workspace_id: str = Field(..., min_length=1)
    user_id: str | None = None
    lang: str = Field(default="auto", pattern=r"^(auto|vi|en)$")
    style: str = Field(default="bullet", pattern=r"^(bullet|paragraph|short)$")
    n_clusters: int = Field(default=10, ge=3, le=20)
    request_id: str | None = Field(default=None, min_length=1, max_length=128)


class SummaryMessagesRequest(BaseModel):
    messages: List[Dict[str, str]] = Field(..., min_length=1)
    lang: str = Field(default="auto", pattern=r"^(auto|vi|en)$")
    style: str = Field(default="bullet", pattern=r"^(bullet|paragraph|short)$")
    n_clusters: int = Field(default=5, ge=3, le=20)
    request_id: str | None = Field(default=None, min_length=1, max_length=128)


class SummaryResponse(BaseModel):
    summary: str
    truncated: bool = False
    n_clusters: int = 0
    total_chunks: int = 0
    citations: List[Citation] = Field(default_factory=list)
    source: str | None = None
    file_name: str | None = None
    message_count: int | None = None
    error: str | None = None
    usage: Dict[str, Any] = Field(default_factory=dict)
    file_summaries: List[Dict[str, Any]] = Field(default_factory=list)
