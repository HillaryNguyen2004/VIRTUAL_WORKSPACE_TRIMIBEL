from __future__ import annotations
from pathlib import Path
from typing import Iterable, Tuple, List
from pdfminer.high_level import extract_text as pdf_to_text
from langchain_text_splitters import RecursiveCharacterTextSplitter

# handles PDF/TXT/MD
def read_file(path: Path) -> str:
    ext = path.suffix.lower()
    if ext == ".pdf":
        return pdf_to_text(str(path))
    return path.read_text(encoding="utf-8", errors="ignore")

# returns a RecursiveCharacterTextSplitter
def make_splitter(chunk: int, overlap: int) -> RecursiveCharacterTextSplitter:
    return RecursiveCharacterTextSplitter(chunk_size=chunk, chunk_overlap=overlap)

def split_text(text: str, splitter: RecursiveCharacterTextSplitter) -> List[str]:
    return splitter.split_text(text)

# yields eligible files under data/raw/
def iter_data_files(root: Path) -> Iterable[Path]:
    for p in root.iterdir():
        if p.is_file() and p.suffix.lower() in {".pdf", ".txt", ".md"}:
            yield p

# returns chunks, metadatas
def chunk_file(path: Path, splitter: RecursiveCharacterTextSplitter) -> Tuple[List[str], List[dict]]:
    text = read_file(path)
    chunks = split_text(text, splitter)
    metas = [{"source": path.name, "chunk_index": i} for i in range(len(chunks))]
    return chunks, metas
