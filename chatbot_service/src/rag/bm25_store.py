"""
bm25_store.py
=============
In-memory BM25 index per workspace, built lazily from ChromaDB stored documents.

The index is keyed by chroma_path (resolved from workspace_id + user_id) so each
isolated workspace has its own independent index.  Indexes are invalidated after
writes (add_chunks / delete) via explicit cache-clear calls.
"""
from __future__ import annotations

import logging
import re
from typing import Any, Dict, List, Optional

log = logging.getLogger(__name__)

# Lazy import so startup stays fast even if rank_bm25 is not installed
_BM25Okapi = None
_bm25_import_tried = False


def _get_bm25_class():
    global _BM25Okapi, _bm25_import_tried
    if _bm25_import_tried:
        return _BM25Okapi
    _bm25_import_tried = True
    try:
        from rank_bm25 import BM25Okapi
        _BM25Okapi = BM25Okapi
        log.info("bm25_store: rank_bm25 loaded successfully")
    except ImportError:
        _BM25Okapi = None
        log.warning("bm25_store: rank_bm25 not installed — BM25 disabled (pip install rank-bm25)")
    return _BM25Okapi


# Cache: chroma_path → {"index": BM25Okapi, "docs": [...], "ids": [...], "metas": [...]}
_bm25_cache: Dict[str, Dict[str, Any]] = {}


def _tokenize(text: str) -> List[str]:
    """Simple whitespace+punctuation tokenizer that preserves Vietnamese diacritics."""
    text = text.lower()
    # Split on non-alphanumeric (keep accented chars via \w which is unicode-aware)
    tokens = re.findall(r"\w+", text)
    return [t for t in tokens if len(t) >= 2]


def _chroma_path_key(workspace_id: str | None, user_id: str | int | None) -> str:
    from .vectorstores.chroma_store import resolve_chroma_path
    return resolve_chroma_path(workspace_id, user_id=user_id)


def build_bm25_index(
    workspace_id: str | None = None,
    user_id: str | int | None = None,
) -> bool:
    """
    Fetch all documents from ChromaDB and build a BM25Okapi index in memory.

    Returns True on success, False if rank_bm25 is unavailable or collection empty.
    """
    BM25Okapi = _get_bm25_class()
    if BM25Okapi is None:
        return False

    from .vectorstores.chroma_store import get_chunks, normalize_workspace_id
    workspace_scope = normalize_workspace_id(workspace_id)

    # Fetch all docs — use large limit; collections are per-workspace so manageable
    chunks = get_chunks(k=50_000, workspace_id=workspace_scope, user_id=user_id)
    if not chunks:
        log.debug("bm25_store: no documents in workspace=%s uid=%s", workspace_scope, user_id)
        return False

    docs = [c["content"] for c in chunks]
    ids = [c["id"] for c in chunks]
    metas = [c.get("metadata", {}) for c in chunks]

    tokenized = [_tokenize(d) for d in docs]
    index = BM25Okapi(tokenized)

    key = _chroma_path_key(workspace_scope, user_id)
    _bm25_cache[key] = {
        "index": index,
        "docs": docs,
        "ids": ids,
        "metas": metas,
    }
    log.info(
        "bm25_store: built index for workspace=%s uid=%s — %d docs",
        workspace_scope, user_id, len(docs),
    )
    return True


def get_bm25_index(
    workspace_id: str | None = None,
    user_id: str | int | None = None,
) -> Dict[str, Any] | None:
    """Return cached index entry, building it on first access."""
    from .vectorstores.chroma_store import normalize_workspace_id
    workspace_scope = normalize_workspace_id(workspace_id)
    key = _chroma_path_key(workspace_scope, user_id)
    if key not in _bm25_cache:
        build_bm25_index(workspace_id=workspace_scope, user_id=user_id)
    return _bm25_cache.get(key)


def invalidate_bm25_index(
    workspace_id: str | None = None,
    user_id: str | int | None = None,
) -> None:
    """Remove the cached index so it is rebuilt on next access."""
    from .vectorstores.chroma_store import normalize_workspace_id
    workspace_scope = normalize_workspace_id(workspace_id)
    key = _chroma_path_key(workspace_scope, user_id)
    _bm25_cache.pop(key, None)
    log.debug("bm25_store: invalidated index for workspace=%s uid=%s", workspace_scope, user_id)


def bm25_search(
    query: str,
    workspace_id: str | None = None,
    user_id: str | int | None = None,
    k: int = 20,
) -> List[Dict[str, Any]]:
    """
    BM25 keyword search over a workspace.

    Returns up to k results, each dict with keys:
        id, content, metadata, bm25_score
    Sorted descending by bm25_score.
    """
    entry = get_bm25_index(workspace_id=workspace_id, user_id=user_id)
    if entry is None:
        return []

    index = entry["index"]
    docs = entry["docs"]
    ids = entry["ids"]
    metas = entry["metas"]

    tokens = _tokenize(query)
    if not tokens:
        return []

    try:
        scores = index.get_scores(tokens)
    except Exception as e:
        log.warning("bm25_store: BM25 scoring failed: %s", e)
        return []

    # Pair (score, idx) and take top k
    ranked = sorted(enumerate(scores), key=lambda x: x[1], reverse=True)
    top = [(idx, float(score)) for idx, score in ranked[:k] if score > 0]

    return [
        {
            "id": ids[idx],
            "content": docs[idx],
            "metadata": metas[idx],
            "bm25_score": score,
        }
        for idx, score in top
    ]
