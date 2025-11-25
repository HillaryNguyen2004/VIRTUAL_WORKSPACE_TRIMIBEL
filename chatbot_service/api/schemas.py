from __future__ import annotations
from typing import Any, Dict, List
from pydantic import BaseModel, Field

class ChatRequest(BaseModel):
    message: str = Field(..., min_length=1)
    k: int | None = Field(default=None, gt=0, le=50)
    lang: str | None = None

class Citation(BaseModel):
    rank: int
    id: str
    source: str

class ChatResponse(BaseModel):
    answer: str
    citations: List[Citation]
