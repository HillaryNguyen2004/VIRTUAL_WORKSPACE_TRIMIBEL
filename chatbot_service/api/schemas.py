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


class Usage(BaseModel):
    prompt_tokens: int = 0
    completion_tokens: int = 0
    total_tokens: int = 0

class ChatResponse(BaseModel):
    answer: str
    citations: List[Citation]
    usage: Usage | None = None
