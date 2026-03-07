from __future__ import annotations
import os
from pathlib import Path
from typing import List
from src.rag.config import settings
from src.rag.chunking import make_splitter, iter_data_files, chunk_file
# from src.rag.embeddings.gemini import embed_texts
from src.rag.embeddings.ollama import embed_texts
from src.rag.vectorstores.chroma_store import add_chunks

"""
scans data/raw/

loads + splits docs

embeds chunks with Ollama

upserts to Chroma

Run before serving queries, or whenever you add/replace docs.
"""
os.environ["ANONYMIZED_TELEMETRY"] = "False"

# infers locale from filename or path
def infer_locale_from_path(path: Path, default_locale: str = "en-US") -> str:
    name = path.name.lower()
    if ".vi." in name or "/vi/" in str(path.as_posix()).lower():
        return "vi-VN"
    if ".en." in name or "/en/" in str(path.as_posix()).lower():
        return "en-US"
    # add more as needed
    return default_locale

def main():
    data_dir = Path("data/raw")
    data_dir.mkdir(parents=True, exist_ok=True)

    splitter = make_splitter(settings.chunk_size, settings.chunk_overlap)

    for path in iter_data_files(data_dir):
        print(f"Ingesting {path.name}...")
        chunks, metas = chunk_file(path, splitter)
        if not chunks:
            print(f"  Skipped empty: {path.name}")
            continue
        
        locale = infer_locale_from_path(path)
        for m in metas:
            m["locale"] = locale
        
        vectors = embed_texts(chunks)
        ids = [f"{path.name}-{i}" for i in range(len(chunks))]
        add_chunks(ids=ids, docs=chunks, metas=metas, embeddings=vectors)
        print(f"  Added {len(chunks)} chunks")
    print("Done.")

if __name__ == "__main__":
    main()
