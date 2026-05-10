"""
search_agent.py
===============
Standalone search & indexing agent.

Public API
----------
index_document(path, workspace_id, original_name, storage_file_name)
    Chunk, embed and store a local file into ChromaDB.

index_s3_document(s3_key, workspace_id, original_name, storage_file_name)
    Download from S3, then call index_document().

index_documents_batch(items, workspace_id)
    Index multiple S3 keys in one call.

remove_document(storage_file, workspace_id)
    Delete all ChromaDB chunks for a given storage file key.

search(query, workspace_id, k, src_lang, history, where, should_cancel)
    Full multi-query RAG retrieval: expansion + cross-lingual + RRF rerank.

answer(query, workspace_id, user_role, k, src_lang, history, should_cancel)
    End-to-end: retrieve → build prompt → generate → return (text, passages).
"""
from __future__ import annotations

import logging
from pathlib import Path
from typing import Any, Callable, Dict, List, Optional

from .chunking import chunk_file, prepend_header, SUPPORTED_EXTENSIONS
from .embeddings.ollama import embed_texts
from .vectorstores.chroma_store import add_chunks, normalize_workspace_id, delete_by_storage_file
from .s3_storage import download_s3_file
from .retrieval import retrieve
from .prompting import build_rag_prompt, build_general_prompt
from .ollama_generate import generate_answer, stream_answer as ollama_stream, GenerationCancelled
from .lang import detect_lang

log = logging.getLogger(__name__)


# =============================================================================
# INDEXING
# =============================================================================

def index_document(
    path: Path | str,
    workspace_id: str,
    original_name: Optional[str] = None,
    storage_file_name: Optional[str] = None,
    user_id: Optional[str] = None,
) -> int:
    """
    Chunk, embed and store a single local file into ChromaDB.

    Parameters
    ----------
    path             : local filesystem path to the file
    workspace_id     : ChromaDB workspace scope (e.g. "online_doc_42")
    original_name    : human-readable filename shown in chunk headers
    storage_file_name: canonical key used in metadata (e.g. S3 UUID filename).
                       Defaults to the file's basename.

    Returns
    -------
    Number of chunks stored.
    """

    file_path = Path(path)
    if not file_path.exists():
        raise FileNotFoundError(f"File not found: {file_path}")

    if file_path.suffix.lower() not in SUPPORTED_EXTENSIONS:
        raise ValueError(f"Unsupported file type: {file_path.suffix}")

    workspace_scope = normalize_workspace_id(workspace_id)
    display_name = (original_name.strip() if original_name else None) or file_path.name
    storage_key = (storage_file_name.strip() if storage_file_name else None) or file_path.name

    log.info("search_agent.index_document: %s → workspace=%s", display_name, workspace_scope)

    chunks, metas = chunk_file(file_path)
    if not chunks:
        log.warning("search_agent.index_document: no chunks produced for %s", display_name)
        return 0

    for m in metas:
        m["source"] = display_name
        m["file_name"] = display_name
        m["storage_file"] = storage_key
        m["workspace_id"] = workspace_scope

    chunks = [prepend_header(chunk, meta) for chunk, meta in zip(chunks, metas)]

    vectors = embed_texts(chunks)
    ids = [f"{storage_key}-{i}" for i in range(len(chunks))]

    add_chunks(
        ids=ids,
        docs=chunks,
        metas=metas,
        embeddings=vectors,
        workspace_id=workspace_scope,
        user_id=user_id,
    )

    log.info("search_agent.index_document: stored %d chunks for %s", len(chunks), display_name)
    return len(chunks)


def index_s3_document(
    s3_key: str,
    workspace_id: str,
    original_name: Optional[str] = None,
    storage_file_name: Optional[str] = None,
    user_id: Optional[str] = None,
) -> int:
    """
    Download a file from S3 and index it into ChromaDB.

    Parameters
    ----------
    s3_key           : S3 object key (e.g. "documents/42/document.docx")
    workspace_id     : ChromaDB workspace scope
    original_name    : human-readable display name for chunk headers
    storage_file_name: canonical storage key stored in chunk metadata

    Returns
    -------
    Number of chunks stored.
    """

    log.info("search_agent.index_s3_document: downloading s3_key=%s", s3_key)

    with download_s3_file(s3_key) as local_path:
        return index_document(
            path=local_path,
            workspace_id=workspace_id,
            original_name=original_name,
            storage_file_name=storage_file_name or Path(s3_key).name,
            user_id=user_id,
        )


def index_documents_batch(
    items: List[Dict[str, str]],
    workspace_id: str,
    user_id: Optional[str] = None,
) -> Dict[str, Any]:
    """
    Index multiple S3 documents in one call.

    Each item in `items` is a dict with keys:
        s3_key           (required)
        original_name    (optional)
        storage_file_name(optional)

    Returns a summary dict:
        {
            "total_chunks": int,
            "success": [s3_key, ...],
            "failed":  [{"s3_key": ..., "error": ...}, ...]
        }
    """
    total_chunks = 0
    success: List[str] = []
    failed: List[Dict[str, str]] = []

    for item in items:
        s3_key = item.get("s3_key", "")
        if not s3_key:
            continue
        try:
            n = index_s3_document(
                s3_key=s3_key,
                workspace_id=workspace_id,
                original_name=item.get("original_name"),
                storage_file_name=item.get("storage_file_name"),
                user_id=user_id,
            )
            total_chunks += n
            success.append(s3_key)
        except Exception as e:
            log.warning("search_agent.index_documents_batch: failed %s — %s", s3_key, e)
            failed.append({"s3_key": s3_key, "error": str(e)})

    return {
        "total_chunks": total_chunks,
        "success": success,
        "failed": failed,
    }


def remove_document(storage_file: str, workspace_id: str, user_id: Optional[str] = None) -> int:
    """
    Delete all ChromaDB chunks for a given storage_file key.

    Returns the number of chunks deleted.
    """

    workspace_scope = normalize_workspace_id(workspace_id)
    count = delete_by_storage_file(storage_file, workspace_id=workspace_scope, user_id=user_id)
    log.info(
        "search_agent.remove_document: deleted %d chunks for storage_file=%s workspace=%s",
        count, storage_file, workspace_scope,
    )
    return count


# =============================================================================
# RETRIEVAL
# =============================================================================

def search(
    query: str,
    workspace_id: str,
    k: int = 5,
    src_lang: Optional[str] = None,
    history: Optional[List[Dict[str, str]]] = None,
    where: Optional[Dict[str, Any]] = None,
    should_cancel: Optional[Callable[[], bool]] = None,
    user_id: Optional[str] = None,
) -> List[Dict[str, Any]]:
    """
    Full multi-query RAG retrieval for the given workspace.

    Pipeline (delegated to retrieval.retrieve):
      1. Build context-enriched query from conversation history.
      2. Expand to paraphrase variants via LLM.
      3. Translate to the other language (VI↔EN).
      4. Parallel embed + fetch per variant.
      5. Merge with Reciprocal Rank Fusion.
      6. Rerank by RRF × (1 + keyword_overlap).

    Parameters
    ----------
    query        : user question
    workspace_id : ChromaDB workspace scope
    k            : number of top passages to return
    src_lang     : detected language ("vi" or "en") for cross-lingual translation
    history      : conversation history (list of {"role": ..., "content": ...})
    where        : optional ChromaDB metadata filter dict
    should_cancel: callable returning True if the request was cancelled

    Returns
    -------
    List of passage dicts: [{"id", "content", "metadata", "rrf_score", "_final_score"}, ...]
    """

    workspace_scope = normalize_workspace_id(workspace_id)

    log.info(
        "search_agent.search: query=%r workspace=%s k=%d lang=%s",
        query[:80], workspace_scope, k, src_lang,
    )

    return retrieve(
        query_text=query,
        k=k,
        workspace_id=workspace_scope,
        user_id=user_id,
        where=where or {},
        should_cancel=should_cancel,
        expand=True,
        src_lang=src_lang,
        history=history or [],
    )


# =============================================================================
# END-TO-END ANSWER
# =============================================================================

def answer(
    query: str,
    workspace_id: str,
    user_role: str = "user",
    k: int = 5,
    src_lang: Optional[str] = None,
    history: Optional[List[Dict[str, str]]] = None,
    history_text: str = "",
    where: Optional[Dict[str, Any]] = None,
    should_cancel: Optional[Callable[[], bool]] = None,
    stream: bool = False,
    user_id: Optional[str] = None,
) -> Dict[str, Any]:
    """
    End-to-end document-grounded answer for the given workspace.

    Pipeline:
      1. Retrieve top-k passages (multi-query + RRF + quality rerank).
      2. Select prompt:
         - passages found  → RAG prompt (confidence-aware, professional style)
         - no passages     → general fallback prompt
      3. Generate answer via Ollama (streaming or blocking).

    Parameters
    ----------
    query        : user question
    workspace_id : ChromaDB workspace scope
    user_role    : role string injected into the RAG prompt for access control
    k            : number of passages to retrieve
    src_lang     : source language code ("vi"/"en") for cross-lingual retrieval
    history      : structured conversation history for context-aware retrieval
    history_text : serialised history string for prompt injection
    where        : optional ChromaDB metadata filter
    should_cancel: callable returning True if request was cancelled
    stream       : if True, returns a generator under key "stream"; else full text

    Returns
    -------
    Dict with keys:
        text      : str  — full answer text (empty if stream=True)
        passages  : list — retrieved passage dicts
        stream    : generator (only present when stream=True)
    """

    target_lang = src_lang or detect_lang(query) or "en"

    # 1. Retrieve
    passages = search(
        query=query,
        workspace_id=workspace_id,
        k=k,
        src_lang=src_lang,
        history=history,
        where=where,
        should_cancel=should_cancel,
        user_id=user_id,
    )

    # 2. Build prompt
    if passages:
        prompt = build_rag_prompt(
            user_q=query,
            user_role=user_role,
            passages=passages,
            target_lang=target_lang,
            history=history_text,
            include_confidence=True,
        )
    else:
        prompt = build_general_prompt(
            user_q=query,
            target_lang=target_lang,
            history=history_text,
        )

    # 3. Generate
    if stream:
        def _gen():
            try:
                yield from ollama_stream(prompt, should_cancel=should_cancel)
            except GenerationCancelled:
                return

        return {"text": "", "passages": passages, "stream": _gen()}

    text, _ = generate_answer(prompt, should_cancel=should_cancel)
    return {"text": text, "passages": passages}
