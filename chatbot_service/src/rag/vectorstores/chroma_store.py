from __future__ import annotations
import os
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

def resolve_chroma_path(workspace_id: str | None) -> str:
    workspace_scope = normalize_workspace_id(workspace_id)
    return str(_resolve_base_chroma_dir() / "workspaces" / workspace_scope)

@lru_cache(maxsize=128)
def _get_client(chroma_path: str) -> PersistentClient:
    Path(chroma_path).mkdir(parents=True, exist_ok=True)
    return PersistentClient(
        path=chroma_path,
        settings=ChromaSettings(anonymized_telemetry=False),
    )

def get_collection(workspace_id: str | None = None):
    chroma_path = resolve_chroma_path(workspace_id)
    client = _get_client(chroma_path)
    return client.get_or_create_collection(settings.collection)

def add_chunks(
    ids: List[str],
    docs: List[str],
    metas: List[Dict[str, Any]],
    embeddings: List[List[float]],
    workspace_id: str | None = None,
) -> None:
    coll = get_collection(workspace_id=workspace_id)
    coll.add(ids=ids, documents=docs, metadatas=metas, embeddings=embeddings)

def delete_by_storage_file(
    storage_file: str,
    workspace_id: str | None = None,
) -> int:
    """Delete all chunks for a specific storage file. Returns number of deleted chunks."""
    coll = get_collection(workspace_id=workspace_id)
    if coll.count() == 0:
        return 0
    where = {"storage_file": {"$eq": storage_file}}
    # Count first so we can report how many were deleted
    results = coll.get(where=where, include=["metadatas"])
    count = len(results.get("ids", []))
    if count > 0:
        # Use where-based delete to ensure all matching chunks are removed
        coll.delete(where=where)
    return count

def delete_collection(workspace_id: str | None = None) -> None:
    chroma_path = resolve_chroma_path(workspace_id)
    client = _get_client(chroma_path)
    try:
        client.delete_collection(settings.collection)
    except Exception:
        # Collection may not exist yet; ignore and recreate on next write.
        pass
    
def query_by_vector(
    vec: List[float],
    k: int,
    workspace_id: str | None = None,
    where: dict | None = None,
) -> List[Dict[str, Any]]:
    coll = get_collection(workspace_id=workspace_id)
    
    count = coll.count()
    if count == 0:
        return []
    safe_k = min(k, count)
    
    query_kwargs = {
        "query_embeddings": [vec],
        "n_results": safe_k,
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
    
    # ChromaDB trả về [None] thay vì [] khi không có kết quả
    return [
        {
            "id":       ids[i],
            "content":  docs[i],
            "metadata": metas[i] if i < len(metas) else {},
        }
        for i in range(len(docs))
        if docs[i] is not None  # lọc kết quả None
    ]