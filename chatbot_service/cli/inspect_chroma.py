from __future__ import annotations

import argparse
from contextlib import redirect_stderr
import io
import json
import os
from pathlib import Path
from pprint import pprint

os.environ.setdefault("ANONYMIZED_TELEMETRY", "False")

import chromadb
from chromadb.config import Settings as ChromaSettings
from dotenv import load_dotenv

PROJECT_ROOT = Path(__file__).resolve().parents[1]
load_dotenv(PROJECT_ROOT / ".env")

raw_db_path = Path(os.getenv("CHROMA_DIR", "var/chroma_db"))
DB_PATH = str((PROJECT_ROOT / raw_db_path).resolve()) if not raw_db_path.is_absolute() else str(raw_db_path)
DEFAULT_COLLECTION = os.getenv("COLLECTION", "kb_collection")

def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Inspect records stored in Chroma vector DB.")
    parser.add_argument("--db-path", help="Override the Chroma database path to inspect.")
    parser.add_argument("--collection", help="Collection name to inspect. Defaults to COLLECTION from .env.")
    parser.add_argument("--limit", type=int, default=5, help="Number of records to print. Ignored when --all is used.")
    parser.add_argument("--offset", type=int, default=0, help="Offset to start reading from.")
    parser.add_argument("--all", action="store_true", help="Print all records in the selected collection.")
    parser.add_argument("--doc-chars", type=int, default=400, help="Max number of document characters to print per record.")
    parser.add_argument("--include-embeddings", action="store_true", help="Also show embedding dimension and a short preview.")
    parser.add_argument("--json", action="store_true", help="Print records as JSON instead of human-readable text.")
    parser.add_argument("--list-collections", action="store_true", help="Only print available collections and exit.")
    return parser.parse_args()

def build_client(db_path: str) -> chromadb.PersistentClient:
    return chromadb.PersistentClient(
        path=db_path,
        settings=ChromaSettings(anonymized_telemetry=False),
    )

def pick_collection_name(client: chromadb.PersistentClient, requested: str | None, db_path: str) -> str | None:
    collection_names = [col.name for col in client.list_collections()]
    print("DB path:", db_path)
    print("Collections:", collection_names)

    if requested:
        return requested

    if DEFAULT_COLLECTION in collection_names:
        return DEFAULT_COLLECTION

    if collection_names:
        return collection_names[0]

    return None

def build_records(result: dict, include_embeddings: bool, doc_chars: int) -> list[dict]:
    ids = result.get("ids", [])
    docs = result.get("documents", [])
    metas = result.get("metadatas", [])
    embeddings = result.get("embeddings", []) if include_embeddings else []

    records = []
    for index, chunk_id in enumerate(ids):
        doc = docs[index] if index < len(docs) else None
        meta = metas[index] if index < len(metas) else {}
        record = {
            "id": chunk_id,
            "metadata": meta,
            "document": (doc or "")[:doc_chars],
        }
        if include_embeddings and index < len(embeddings):
            embedding = embeddings[index] or []
            record["embedding_dim"] = len(embedding)
            record["embedding_preview"] = embedding[:8]
        records.append(record)
    return records

def main() -> int:
    args = parse_args()
    db_path = args.db_path or DB_PATH
    with redirect_stderr(io.StringIO()):
        client = build_client(db_path)
    collection_name = pick_collection_name(client, args.collection, db_path)

    if args.list_collections:
        return 0

    if not collection_name:
        print("No collections found.")
        return 1

    with redirect_stderr(io.StringIO()):
        collection = client.get_collection(collection_name)
    total = collection.count()
    print("Collection:", collection_name)
    print("Chunk count:", total)

    if total == 0:
        return 0

    if args.offset >= total:
        print(f"Offset {args.offset} is beyond collection size {total}.")
        return 1

    limit = total - args.offset if args.all else min(args.limit, total - args.offset)
    include = ["documents", "metadatas"]
    if args.include_embeddings:
        include.append("embeddings")

    with redirect_stderr(io.StringIO()):
        result = collection.get(offset=args.offset, limit=limit, include=include)
    records = build_records(result, args.include_embeddings, args.doc_chars)

    if args.json:
        print(json.dumps(records, ensure_ascii=False, indent=2))
        return 0

    for record in records:
        print(f"\n--- {record['id']} ---")
        pprint(record["metadata"])
        print(record["document"])
        if args.include_embeddings:
            print(
                f"embedding_dim={record.get('embedding_dim', 0)} preview={record.get('embedding_preview', [])}"
            )

    return 0


if __name__ == "__main__":
    raise SystemExit(main())