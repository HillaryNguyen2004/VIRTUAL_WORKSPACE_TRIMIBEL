from __future__ import annotations
from fastapi import FastAPI, HTTPException
from .schemas import ChatRequest, ChatResponse, Citation
from src.rag.pipeline import answer as rag_answer

app = FastAPI(title="Gemini RAG API", version="1.0")

@app.get("/healthz")
def healthz():
    return {"status": "ok"}

@app.post("/chat", response_model=ChatResponse)
def chat(req: ChatRequest):
    try:
        text, cits = rag_answer(req.message, req.k, req.lang)
        return ChatResponse(
            answer=text,
            citations=[Citation(**c) for c in cits]
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"error: {e}")
