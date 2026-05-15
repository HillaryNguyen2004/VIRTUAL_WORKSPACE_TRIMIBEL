from __future__ import annotations
import logging
from threading import Event, Lock
from uuid import uuid4
from fastapi import FastAPI, HTTPException
from fastapi.responses import StreamingResponse
from .schemas import (
    ChatRequest, CancelRequest, IngestS3Request, DeleteChunksRequest,
    IngestResult, ChatResponse, Citation, Usage, Confidence,
    SearchRequest, SearchResponse, PassageResult,
    MultiSearchRequest,
    AgentAnswerRequest, AgentAnswerResponse,
    BatchIngestRequest, BatchIngestResult,
    SummaryTextRequest, SummaryDocumentRequest, SummaryWorkspaceRequest,
    SummaryMessagesRequest, SummaryResponse,
)
from src.rag.pipeline import answer
from src.rag.ollama_generate import GenerationCancelled
from src.rag.vectorstores.chroma_store import reload_chroma_clients, delete_by_storage_file
from src.rag.search_agent import (
    index_s3_document,
    index_documents_batch,
    remove_document,
    search as agent_search,
    answer as agent_answer,
)
from src.rag.summary_agent import (
    summarize_text,
    summarize_s3_document,
    summarize_messages,
    summarize_workspace,
    summarize_workspace_stream,
)

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


@app.post("/ingest-s3", response_model=IngestResult)
def ingest_s3(req: IngestS3Request):
    """
    Download a file from S3 and index it into ChromaDB.
    Laravel calls this instead of copying the file locally.
    """
    try:
        total_chunks = index_s3_document(
            s3_key=req.s3_key,
            workspace_id=req.workspace_id,
            original_name=req.original_name,
            storage_file_name=req.storage_file_name,
            user_id=req.user_id,
        )
        reload_chroma_clients()
        return IngestResult(success=True, total_chunks=total_chunks)
    except Exception as e:
        logger.error("ingest-s3 failed: %s", e)
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/delete-chunks")
def delete_chunks(req: DeleteChunksRequest):
    """
    Remove all ChromaDB chunks for a specific file (by storage_file metadata key).
    """
    count = delete_by_storage_file(req.storage_file, workspace_id=req.workspace_id)
    reload_chroma_clients()
    return {"ok": True, "deleted": count}

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

@app.post("/agent/search", response_model=SearchResponse)
def agent_search_endpoint(req: SearchRequest):
    try:
        passages = agent_search(
            query=req.query,
            workspace_id=req.workspace_id,
            k=req.k,
            src_lang=req.lang,
            history=req.history,
            where=req.where,
            user_id=req.user_id,
        )
        results = [
            PassageResult(
                id=p["id"],
                content=p["content"],
                metadata=p.get("metadata", {}),
                rrf_score=p.get("rrf_score", 0.0),
                final_score=p.get("_final_score", 0.0),
            )
            for p in passages
        ]
        return SearchResponse(passages=results, total=len(results))
    except Exception as e:
        logger.error("agent/search failed: %s", e)
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/agent/answer/multi", response_model=AgentAnswerResponse)
def agent_answer_multi(req: MultiSearchRequest):
    """
    Search across multiple workspace IDs, merge results by score, then generate
    one grounded answer. Used by the Online Docs search agent which stores each
    document in its own isolated workspace (online_doc_{id}).
    """
    from src.rag.vectorstores.chroma_store import normalize_workspace_id
    from src.rag.retrieval import retrieve
    from src.rag.prompting import build_rag_prompt, build_general_prompt
    from src.rag.ollama_generate import generate_answer
    from src.rag.lang import detect_lang

    request_id = (req.request_id or str(uuid4())).strip()
    cancel_flag = _register_request(request_id)

    try:
        target_lang = req.lang or detect_lang(req.query) or "en"

        # 1. Retrieve from every workspace and merge by _final_score
        all_passages: list = []
        per_ws_k = max(req.k, 3)   # fetch at least 3 per workspace so merging is meaningful

        for ws_id in req.workspace_ids:
            if cancel_flag.is_set():
                raise GenerationCancelled("Request canceled")
            ws_scope = normalize_workspace_id(ws_id)
            try:
                hits = retrieve(
                    query_text=req.query,
                    k=per_ws_k,
                    workspace_id=ws_scope,
                    user_id=req.user_id,
                    where={},
                    should_cancel=cancel_flag.is_set,
                )
                all_passages.extend(hits)
            except GenerationCancelled:
                raise
            except Exception as exc:
                logger.warning("agent/answer/multi: workspace=%s failed: %s", ws_scope, exc)

        # 2. Deduplicate by chunk id, keep highest score, re-sort
        seen: dict = {}
        for p in all_passages:
            pid = p.get("id", "")
            if pid not in seen or p.get("_final_score", 0.0) > seen[pid].get("_final_score", 0.0):
                seen[pid] = p
        merged = sorted(seen.values(), key=lambda x: x.get("_final_score", 0.0), reverse=True)[:req.k]

        # 3. Build prompt and generate
        if merged:
            prompt = build_rag_prompt(
                user_q=req.query,
                user_role=req.user_role,
                passages=merged,
                target_lang=target_lang,
                history=req.history_text,
            )
        else:
            prompt = build_general_prompt(
                user_q=req.query,
                target_lang=target_lang,
                history=req.history_text,
            )

        if cancel_flag.is_set():
            raise GenerationCancelled("Request canceled")

        text, _ = generate_answer(prompt, should_cancel=cancel_flag.is_set)

        passages_out = [
            PassageResult(
                id=p["id"],
                content=p["content"],
                metadata=p.get("metadata", {}),
                rrf_score=p.get("rrf_score", 0.0),
                final_score=p.get("_final_score", 0.0),
            )
            for p in merged
        ]
        return AgentAnswerResponse(answer=text, passages=passages_out)

    except GenerationCancelled:
        logger.info("agent/answer/multi canceled request_id=%s", request_id)
        raise HTTPException(status_code=499, detail="request canceled")
    except Exception as e:
        logger.error("agent/answer/multi failed: %s", e)
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        _unregister_request(request_id)


@app.post("/agent/answer", response_model=AgentAnswerResponse)
def agent_answer_endpoint(req: AgentAnswerRequest):
    request_id = (req.request_id or str(uuid4())).strip()
    cancel_flag = _register_request(request_id)

    try:
        result = agent_answer(
            query=req.query,
            workspace_id=req.workspace_id,
            user_role=req.user_role,
            k=req.k,
            src_lang=req.lang,
            history=req.history,
            history_text=req.history_text,
            where=req.where,
            should_cancel=cancel_flag.is_set,
            stream=False,
            user_id=req.user_id,
        )
        if cancel_flag.is_set():
            raise GenerationCancelled("Request canceled")

        passages = [
            PassageResult(
                id=p["id"],
                content=p["content"],
                metadata=p.get("metadata", {}),
                rrf_score=p.get("rrf_score", 0.0),
                final_score=p.get("_final_score", 0.0),
            )
            for p in result.get("passages", [])
        ]
        return AgentAnswerResponse(answer=result.get("text", ""), passages=passages)
    except GenerationCancelled:
        logger.info("agent/answer canceled request_id=%s", request_id)
        raise HTTPException(status_code=499, detail="request canceled")
    except Exception as e:
        logger.error("agent/answer failed: %s", e)
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        _unregister_request(request_id)


@app.post("/agent/answer/stream")
def agent_answer_stream(req: AgentAnswerRequest):
    request_id = (req.request_id or str(uuid4())).strip()
    cancel_flag = _register_request(request_id)

    def event_stream():
        try:
            result = agent_answer(
                query=req.query,
                workspace_id=req.workspace_id,
                user_role=req.user_role,
                k=req.k,
                src_lang=req.lang,
                history=req.history,
                history_text=req.history_text,
                where=req.where,
                should_cancel=cancel_flag.is_set,
                stream=True,
                user_id=req.user_id,
            )
            gen = result.get("stream")
            if gen is None:
                return
            yield from gen
        except GenerationCancelled:
            logger.info("agent/answer/stream canceled request_id=%s", request_id)
        except Exception as e:
            logger.error("agent/answer/stream error request_id=%s: %s", request_id, e)
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


@app.post("/agent/ingest-batch", response_model=BatchIngestResult)
def agent_ingest_batch(req: BatchIngestRequest):
    try:
        result = index_documents_batch(items=req.items, workspace_id=req.workspace_id, user_id=req.user_id)
        reload_chroma_clients()
        return BatchIngestResult(**result)
    except Exception as e:
        logger.error("agent/ingest-batch failed: %s", e)
        raise HTTPException(status_code=500, detail=str(e))


@app.delete("/agent/document")
def agent_remove_document(storage_file: str, workspace_id: str, user_id: str | None = None):
    try:
        count = remove_document(storage_file=storage_file, workspace_id=workspace_id, user_id=user_id)
        reload_chroma_clients()
        return {"ok": True, "deleted": count}
    except Exception as e:
        logger.error("agent/document DELETE failed: %s", e)
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/summary/text", response_model=SummaryResponse)
def summary_text(req: SummaryTextRequest):
    request_id = (req.request_id or str(uuid4())).strip()
    cancel_flag = _register_request(request_id)
    try:
        result = summarize_text(
            text=req.text,
            lang=req.lang,
            style=req.style,
            n_clusters=req.n_clusters,
            should_cancel=cancel_flag.is_set,
        )
        return SummaryResponse(**result)
    except Exception as e:
        logger.error("summary/text failed: %s", e)
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        _unregister_request(request_id)


@app.post("/summary/document", response_model=SummaryResponse)
def summary_document(req: SummaryDocumentRequest):
    request_id = (req.request_id or str(uuid4())).strip()
    cancel_flag = _register_request(request_id)
    try:
        result = summarize_s3_document(
            s3_key=req.s3_key,
            workspace_id=req.workspace_id,
            lang=req.lang,
            style=req.style,
            n_clusters=req.n_clusters,
            should_cancel=cancel_flag.is_set,
        )
        return SummaryResponse(**result)
    except Exception as e:
        logger.error("summary/document failed: %s", e)
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        _unregister_request(request_id)


@app.post("/summary/workspace", response_model=SummaryResponse)
def summary_workspace(req: SummaryWorkspaceRequest):
    request_id = (req.request_id or str(uuid4())).strip()
    cancel_flag = _register_request(request_id)
    try:
        result = summarize_workspace(
            workspace_id=req.workspace_id,
            user_id=req.user_id,
            lang=req.lang,
            style=req.style,
            n_clusters=req.n_clusters,
            should_cancel=cancel_flag.is_set,
        )
        return SummaryResponse(**result)
    except Exception as e:
        import traceback
        logger.error("summary/workspace failed: %s\n%s", e, traceback.format_exc())
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        _unregister_request(request_id)


@app.post("/summary/workspace/stream")
def summary_workspace_stream(req: SummaryWorkspaceRequest):
    request_id  = (req.request_id or str(uuid4())).strip()
    cancel_flag = _register_request(request_id)

    def event_stream():
        try:
            for chunk in summarize_workspace_stream(
                workspace_id=req.workspace_id,
                user_id=req.user_id,
                lang=req.lang,
                style=req.style,
                n_clusters=req.n_clusters,
                should_cancel=cancel_flag.is_set,
            ):
                yield chunk
        except GenerationCancelled:
            import json
            yield json.dumps({"type": "error", "message": "Cancelled."}) + "\n"
        except Exception as e:
            import json, traceback
            logger.error("summary/workspace/stream failed: %s\n%s", e, traceback.format_exc())
            yield json.dumps({"type": "error", "message": str(e)}) + "\n"
        finally:
            _unregister_request(request_id)

    return StreamingResponse(
        event_stream(),
        media_type="text/plain; charset=utf-8",
        headers={
            "Cache-Control": "no-cache",
            "X-Accel-Buffering": "no"
        },
    )


@app.post("/summary/messages", response_model=SummaryResponse)
def summary_messages(req: SummaryMessagesRequest):
    request_id = (req.request_id or str(uuid4())).strip()
    cancel_flag = _register_request(request_id)
    try:
        result = summarize_messages(
            messages=req.messages,
            lang=req.lang,
            style=req.style,
            n_clusters=req.n_clusters,
            should_cancel=cancel_flag.is_set,
        )
        return SummaryResponse(**result)
    except Exception as e:
        logger.error("summary/messages failed: %s", e)
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        _unregister_request(request_id)


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
            "X-Accel-Buffering": "no"
        },
    )
