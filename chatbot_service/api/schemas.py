from __future__ import annotations
from typing import Any, Dict, List
from pydantic import BaseModel, Field

class ChatRequest(BaseModel):
    message: str = Field(..., min_length=1)
    k: int | None = Field(default=None, gt=0, le=50)
    lang: str | None = None
    user_id: str | None = None
    user_role: str | None = None

class Citation(BaseModel):
    rank: int
    id: str
    source: str
    page: int | None = None
    line: int | None = None

class Confidence(BaseModel):
    level: str
    score: float
    reason: str

class ChatResponse(BaseModel):
    answer: str
    citations: List[Citation]
    confidence: Confidence

class IngestRequest(BaseModel):
    path: str = Field(..., min_length=1)
    doc_id: str | None = None
    source_type: str = "online_doc"

class IngestResponse(BaseModel):
    status: str
    chunks: int
    path: str

class DeleteRequest(BaseModel):
    doc_id: str
    source_type: str = "online_doc"

class DeleteResponse(BaseModel):
    status: str
    deleted: int
