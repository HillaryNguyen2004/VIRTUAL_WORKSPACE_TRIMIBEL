from __future__ import annotations
import logging
from threading import Event, Lock
from uuid import uuid4
from fastapi import FastAPI
from fastapi.responses import StreamingResponse
from .schemas import ChatRequest, CancelRequest
from src.rag.pipeline import answer
from src.rag.ollama_generate import GenerationCancelled
from src.rag.vectorstores.chroma_store import reload_chroma_clients

app = FastAPI(title="OLLAMA RAG API", version="1.0")
logger = logging.getLogger("uvicorn.error")
_cancel_lock = Lock()
_active_cancel_flags: dict[str, Event] = {}
_pre_canceled_request_ids: set[str] = set()

def _register_request(request_id: str) -> Event:
    with _cancel_lock:
        flag = _active_cancel_flags.get(request_id)
        if flag is None:
            flag = Event()
            _active_cancel_flags[request_id] = flag

        if request_id in _pre_canceled_request_ids:
            flag.set()
            _pre_canceled_request_ids.discard(request_id)

        return flag


def _cancel_request(request_id: str) -> bool:
    with _cancel_lock:
        flag = _active_cancel_flags.get(request_id)
        if flag is not None:
            flag.set()
            return True

        # Handle race where cancel arrives slightly before /chat registration.
        _pre_canceled_request_ids.add(request_id)
        return False


def _unregister_request(request_id: str) -> None:
    with _cancel_lock:
        _active_cancel_flags.pop(request_id, None)
        _pre_canceled_request_ids.discard(request_id)


@app.on_event("startup")
def configure_logging() -> None:
    logging.getLogger().setLevel(logging.DEBUG)
    logging.getLogger("uvicorn.error").setLevel(logging.DEBUG)
    logging.getLogger("uvicorn.access").setLevel(logging.DEBUG)
    logging.getLogger("src.rag.pipeline").setLevel(logging.DEBUG)
    logging.getLogger("src.rag.ollama_generate").setLevel(logging.DEBUG)
    logger.debug("Debug logging enabled for RAG pipeline")

@app.get("/health")
def healthz():
    return {"status": "ok"}

@app.post("/reload-chroma")
def reload_chroma():
    """Clear cached ChromaDB clients so the next query reloads from disk.
    Call this after ingest_workspace.py finishes to make new documents visible."""
    reload_chroma_clients()
    return {"ok": True, "message": "ChromaDB client cache cleared"}

@app.post("/chat/cancel")
def cancel_chat(req: CancelRequest):
    active = _cancel_request(req.request_id)
    return {
        "ok": True,
        "request_id": req.request_id,
        "active_request_found": active,
    }

# @app.post("/chat", response_model=ChatResponse)
# def chat(req: ChatRequest):
#     request_id = (req.request_id or str(uuid4())).strip()
#     cancel_flag = _register_request(request_id)

#     try:
#         logger.debug(
#             "CHAT request_id=%s message=%r k=%s lang=%s user_id=%s user_role=%s workspace_id=%s",
#             request_id,
#             req.message,
#             req.k,
#             req.lang,
#             req.user_id,
#             req.user_role,
#             req.workspace_id,
#         )
#         text, cits, usage = rag_answer(
#             req.message, 
#             req.k, 
#             req.lang, 
#             req.user_id, 
#             req.user_role, 
#             req.workspace_id,
#             logger=logger,
#             should_cancel=cancel_flag.is_set,
#         )

#         if cancel_flag.is_set():
#             raise GenerationCancelled("Request canceled")

#         prompt_tokens = int(usage.get("prompt_tokens", 0))
#         completion_tokens = int(usage.get("completion_tokens", 0))
#         total_tokens = int(usage.get("total_tokens", 0))
#         preview = " ".join((text or "").split())
#         if len(preview) > 220:
#             preview = preview[:220] + "..."

#         logger.info(
#             "CHAT response=%s | usage total=%d prompt=%d output=%d",
#             preview,
#             total_tokens,
#             prompt_tokens,
#             completion_tokens,
#         )

#         return ChatResponse(
#             answer=text,
#             citations=[Citation(**c) for c in cits],
#             usage=Usage(**usage)
#         )
#     except GenerationCancelled:
#         logger.info("CHAT canceled request_id=%s", request_id)
#         raise HTTPException(status_code=499, detail="request canceled")
#     except Exception as e:
#         raise HTTPException(status_code=500, detail=f"error: {e}")
#     finally:
#         _unregister_request(request_id)

@app.post("/chat/stream")
def chat_stream(req: ChatRequest):
    request_id = (req.request_id or str(uuid4())).strip()
    cancel_flag = _register_request(request_id)

    def event_stream():
        try:
            logger.debug(
                "CHAT STREAM request_id=%s message=%r k=%s lang=%s user_id=%s user_role=%s workspace_id=%s",
                request_id,
                req.message,
                req.k,
                req.lang,
                req.user_id,
                req.user_role,
                req.workspace_id,
            )

            for chunk in answer(
                req.message,
                k=req.k,
                lang_hint=req.lang,
                user_id=req.user_id,
                user_role=req.user_role,
                workspace_id=req.workspace_id,
                logger=logger,
                should_cancel=cancel_flag.is_set,
            ):
                yield chunk

        except GenerationCancelled:
            logger.info("CHAT STREAM canceled request_id=%s", request_id)
        finally:
            _unregister_request(request_id)

    return StreamingResponse(
        event_stream(),
        media_type="text/plain; charset=utf-8",
        headers={
            "Cache-Control": "no-cache",
            "X-Accel-Buffering": "no",
        },
    )
