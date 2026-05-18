from __future__ import annotations
import os
import shutil
import json
from typing import List, Dict, Any
from functools import lru_cache
from pathlib import Path

from chromadb import PersistentClient
from chromadb.config import Settings as ChromaSettings

from ..config import settings

def _resolve_base_chroma_dir() -> Path:
    raw = os.getenv("CHROMA_DIR", settings.chroma_dir)
    base = Path(raw)
    if base.is_absolute():
        return base
    return (Path.cwd() / base).resolve()

def normalize_workspace_id(workspace_id: str | None) -> str:
    raw = str(workspace_id or "global").strip()
    clean = "".join(ch if (ch.isalnum() or ch in "-_") else "_" for ch in raw)
    return clean or "global"

def normalize_user_id(user_id: str | int | None) -> str | None:
    """Return a safe directory-name segment for the user, or None if absent."""
    if user_id is None:
        return None
    raw = str(user_id).strip()
    clean = "".join(ch if (ch.isalnum() or ch in "-_") else "_" for ch in raw)
    return clean or None

def resolve_chroma_path(workspace_id: str | None, user_id: str | int | None = None) -> str:
    """
    Disk layout
    -----------
    With user_id  : <base>/documents/<user_id>/<workspace_id>/
    Without       : <base>/workspaces/<workspace_id>/
    """
    workspace_scope = normalize_workspace_id(workspace_id)
    safe_user = normalize_user_id(user_id)
    base = _resolve_base_chroma_dir()
    if safe_user:
        return str(base / "documents" / safe_user / workspace_scope)
    return str(base / "workspaces" / workspace_scope)

@lru_cache(maxsize=256)
def _get_client(chroma_path: str) -> PersistentClient:
    Path(chroma_path).mkdir(parents=True, exist_ok=True)
    return PersistentClient(
        path=chroma_path,
        settings=ChromaSettings(anonymized_telemetry=False),
    )

def get_collection(workspace_id: str | None = None, user_id: str | int | None = None):
    chroma_path = resolve_chroma_path(workspace_id, user_id=user_id)
    client = _get_client(chroma_path)
    return client.get_or_create_collection(settings.collection)

def _sanitize_metadata_value(value: Any) -> Any:
    if value is None:
        return None
    if isinstance(value, (str, int, float, bool)):
        return value
    if hasattr(value, "item"):
        try:
            native = value.item()
            if isinstance(native, (str, int, float, bool)):
                return native
        except Exception:
            pass
    if isinstance(value, (list, tuple, set, dict)):
        return json.dumps(value, ensure_ascii=False, default=str)
    return str(value)

def _sanitize_metadata(metadata: Dict[str, Any]) -> Dict[str, Any]:
    cleaned: Dict[str, Any] = {}
    for key, value in metadata.items():
        sanitized = _sanitize_metadata_value(value)
        if sanitized is not None:
            cleaned[key] = sanitized
    return cleaned

def add_chunks(
    ids: List[str],
    docs: List[str],
    metas: List[Dict[str, Any]],
    embeddings: List[List[float]],
    workspace_id: str | None = None,
    user_id: str | int | None = None,
) -> None:
    coll = get_collection(workspace_id=workspace_id, user_id=user_id)
    safe_metas = [_sanitize_metadata(meta) for meta in metas]
    coll.add(ids=ids, documents=docs, metadatas=safe_metas, embeddings=embeddings)
    # Invalidate BM25 index so next search rebuilds from the updated collection
    try:
        from ..bm25_store import invalidate_bm25_index
        invalidate_bm25_index(workspace_id=workspace_id, user_id=user_id)
    except Exception:
        pass

def delete_by_storage_file(
    storage_file: str,
    workspace_id: str | None = None,
    user_id: str | int | None = None,
) -> int:
    """Delete all chunks for a specific storage file. Returns number of deleted chunks."""
    coll = get_collection(workspace_id=workspace_id, user_id=user_id)
    if coll.count() == 0:
        return 0
    where = {"storage_file": {"$eq": storage_file}}
    # Count first so we can report how many were deleted
    results = coll.get(where=where, include=["metadatas"])
    count = len(results.get("ids", []))
    if count > 0:
        coll.delete(where=where)
        # Invalidate BM25 index so next search rebuilds without deleted docs
        try:
            from ..bm25_store import invalidate_bm25_index
            invalidate_bm25_index(workspace_id=workspace_id, user_id=user_id)
        except Exception:
            pass
    return count

def delete_collection(workspace_id: str | None = None, user_id: str | int | None = None) -> None:
    chroma_path = resolve_chroma_path(workspace_id, user_id=user_id)
    client = _get_client(chroma_path)
    try:
        client.delete_collection(settings.collection)
    except Exception:
        # Collection may not exist yet; ignore and recreate on next write.
        pass

def delete_workspace_storage(workspace_id: str | None = None, user_id: str | int | None = None) -> None:
    """Remove the entire persisted Chroma workspace directory."""
    chroma_path = Path(resolve_chroma_path(workspace_id, user_id=user_id))
    _get_client.cache_clear()
    if chroma_path.exists():
        shutil.rmtree(chroma_path)
    
def reload_chroma_clients() -> None:
    """Clear the LRU-cached PersistentClient instances so the next request
    creates fresh clients that reload the HNSW index from disk.

    Call this after an external process (e.g. ingest_workspace.py) has written
    new vectors to the ChromaDB files so the API server sees the updated index.
    """
    _get_client.cache_clear()

def count_legacy_chunks(workspace_id: str | None = None, user_id: str | int | None = None, sample: int = 50) -> int:
    """
    Sample up to `sample` documents from the collection and count how many
    do NOT start with a contextual header (i.e. ingested before the
    chunk-header upgrade). Returns the count of legacy chunks found.
    """
    coll = get_collection(workspace_id=workspace_id, user_id=user_id)
    total = coll.count()
    if total == 0:
        return 0

    safe_sample = min(sample, total)
    results = coll.get(limit=safe_sample, include=["documents"])
    docs = results.get("documents") or []
    legacy = sum(1 for d in docs if d and not d.lstrip().startswith("[File:"))
    return legacy

def get_chunks(
    k: int,
    workspace_id: str | None = None,
    user_id: str | int | None = None,
    where: dict | None = None,
) -> List[Dict[str, Any]]:
    """Fetch up to k documents without vector similarity (for aggregation/list queries)."""
    coll = get_collection(workspace_id=workspace_id, user_id=user_id)
    try:
        count = coll.count()
    except Exception as e:
        print(f"[ERROR] ChromaDB count failed: {e}")
        return []
    if count == 0:
        return []

    safe_k = min(k, count)
    get_kwargs: dict = {"limit": safe_k, "include": ["documents", "metadatas"]}
    if where:
        get_kwargs["where"] = where

    try:
        res = coll.get(**get_kwargs)
    except Exception as e:
        print(f"[ERROR] ChromaDB get failed: {e}")
        return []

    docs = res.get("documents") or []
    ids = res.get("ids") or []
    metas = res.get("metadatas") or []

    return [
        {
            "id": ids[i],
            "content": docs[i],
            "metadata": metas[i] if i < len(metas) else {},
        }
        for i in range(len(docs))
        if docs[i] is not None
    ]


def query_by_vector(
    vec: List[float],
    k: int,
    workspace_id: str | None = None,
    user_id: str | int | None = None,
    where: dict | None = None,
) -> List[Dict[str, Any]]:
    coll = get_collection(workspace_id=workspace_id, user_id=user_id)
    
    try:
        count = coll.count()
    except Exception as e:
        print(f"[ERROR] ChromaDB count failed: {e}")
        return []
    if count == 0:
        return []
    safe_k = min(k, count)
    
    query_kwargs = {
        "query_embeddings": [vec],
        "n_results": safe_k,
        "include": ["documents", "metadatas", "distances"],
    }
    if where:
        query_kwargs["where"] = where

    try:
        res = coll.query(**query_kwargs)
    except Exception as e:
        print(f"[ERROR] ChromaDB query failed: {e}")
        return []

    docs = res.get("documents", [[]])[0]
    ids = res.get("ids", [[]])[0]
    metas = res.get("metadatas", [[]])[0]
    dists = res.get("distances", [[]])[0]

    return [
        {
            "id":       ids[i],
            "content":  docs[i],
            "metadata": metas[i] if i < len(metas) else {},
            "distance": dists[i] if i < len(dists) else 1.0,
        }
        for i in range(len(docs))
        if docs[i] is not None
    ]