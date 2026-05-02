from __future__ import annotations
from pathlib import Path
from fastapi import FastAPI, HTTPException
from .schemas import ChatRequest, ChatResponse, Citation, Confidence, IngestRequest, IngestResponse, DeleteRequest, DeleteResponse
from src.rag.pipeline import answer as rag_answer
from src.rag.config import settings
from src.rag.chunking import make_splitter, chunk_file
from src.rag.embeddings.ollama import embed_texts
from src.rag.vectorstores.chroma_store import add_chunks, get_collection

SUPPORTED_EXTENSIONS = {".pdf", ".txt", ".md", ".docx", ".xlsx"}

app = FastAPI(title="Gemini RAG API", version="1.0")

@app.get("/healthz")
def healthz():
    return {"status": "ok"}

@app.post("/chat", response_model=ChatResponse)
def chat(req: ChatRequest):
    try:
        text, cits, confidence = rag_answer(req.message, req.k, req.lang, req.user_id, req.user_role)
        return ChatResponse(
            answer=text,
            citations=[Citation(**c) for c in cits],
            confidence=Confidence(**confidence),
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"error: {e}")

@app.post("/ingest", response_model=IngestResponse)
def ingest(req: IngestRequest):
    path = Path(req.path)
    if not path.exists() or not path.is_file():
        raise HTTPException(status_code=404, detail=f"File not found: {req.path}")
    if path.suffix.lower() not in SUPPORTED_EXTENSIONS:
        raise HTTPException(status_code=422, detail=f"Unsupported file type: {path.suffix}")

    try:
        splitter = make_splitter(settings.chunk_size, settings.chunk_overlap)
        chunks, metas = chunk_file(path, splitter)
        if not chunks:
            return IngestResponse(status="skipped", chunks=0, path=str(path))

        doc_id = req.doc_id or path.stem
        for m in metas:
            m["source_type"] = req.source_type
            m["doc_id"] = doc_id

        vectors = embed_texts(chunks)
        ids = [f"{req.source_type}-{doc_id}-{path.stem}-{i}" for i in range(len(chunks))]
        add_chunks(ids=ids, docs=chunks, metas=metas, embeddings=vectors)

        return IngestResponse(status="ok", chunks=len(chunks), path=str(path))
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Ingest error: {e}")

@app.post("/delete", response_model=DeleteResponse)
def delete_doc(req: DeleteRequest):
    try:
        coll = get_collection()
        prefix = f"{req.source_type}-{req.doc_id}-"
        results = coll.get(where={"doc_id": req.doc_id})
        ids_to_delete = [id_ for id_ in (results.get("ids") or []) if id_.startswith(prefix)]
        if ids_to_delete:
            coll.delete(ids=ids_to_delete)
        return DeleteResponse(status="ok", deleted=len(ids_to_delete))
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Delete error: {e}")
