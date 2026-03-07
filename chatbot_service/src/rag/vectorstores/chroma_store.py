from __future__ import annotations
from typing import List, Dict, Any

from chromadb import PersistentClient
from chromadb.config import Settings as ChromaSettings

from ..config import settings

_client = PersistentClient(
    path=settings.chroma_dir,
    settings=ChromaSettings(anonymized_telemetry=False),
)

def get_collection():
    return _client.get_or_create_collection(settings.collection)

def add_chunks(ids: List[str], docs: List[str], metas: List[Dict[str, Any]], embeddings: List[List[float]]) -> None:
    coll = get_collection()
    coll.add(ids=ids, documents=docs, metadatas=metas, embeddings=embeddings)

def query_by_vector(vec: List[float], k: int) -> List[Dict[str, Any]]:
    coll = get_collection()
    res = coll.query(query_embeddings=[vec], n_results=k)
    docs = res.get("documents", [[]])[0]
    ids = res.get("ids", [[]])[0]
    metas = res.get("metadatas", [[]])[0]
    return [{"id": ids[i], "content": docs[i], "metadata": metas[i] if i < len(metas) else {}} for i in range(len(docs))]