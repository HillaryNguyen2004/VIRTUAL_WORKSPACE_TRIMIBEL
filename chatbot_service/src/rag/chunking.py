from __future__ import annotations

import csv
import re
from pathlib import Path
from typing import Iterable, Tuple, List, Dict
from xml.etree import ElementTree
from zipfile import ZipFile
from pypdf import PdfReader

from langchain_text_splitters import (
    MarkdownHeaderTextSplitter,
    RecursiveCharacterTextSplitter
)
from langchain_experimental.text_splitter import SemanticChunker

# =========================
# CONFIG
# =========================
OFFICE_XML_NAMESPACES = {
    "main": "http://schemas.openxmlformats.org/spreadsheetml/2006/main",
    "w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main",
}

SUPPORTED_EXTENSIONS = {
    ".pdf", ".txt", ".md", ".docx",
    ".xlsx", ".csv"
}

# =========================
# CHUNK HEADER
# =========================
def build_chunk_header(meta: dict) -> str:
    """
    Build a short context header that gets prepended to every chunk before
    embedding and storage.

    Why: embedding models and LLMs work better when each chunk carries
    its own provenance — which file, which section, which page it came from.
    Without this, a chunk like "Attendance is tracked daily" gives no clue
    about whether it's a policy doc, a report, or a tutorial.

    The header format is intentionally plain text (not JSON/XML) so the LLM
    reads it naturally as part of the chunk.

    Output example:
        [File: HR Policy.pdf | Section: 2. Leave Policy | Page: 4 | Type: pdf]
    """
    parts = []

    source = meta.get("source") or meta.get("file_name") or ""
    if source:
        parts.append(f"File: {source}")

    section = meta.get("section") or ""
    if section:
        # Truncate very long section titles
        parts.append(f"Section: {section[:80]}")

    page = meta.get("page")
    if page is not None:
        parts.append(f"Page: {page}")

    row = meta.get("row_index")
    if row is not None:
        parts.append(f"Row: {row}")

    sheet = meta.get("sheet") or ""
    if sheet:
        parts.append(f"Sheet: {sheet}")

    headers = meta.get("headers") or {}
    if headers:
        h_str = " > ".join(
            f"{v}" for v in headers.values() if v
        )
        if h_str:
            parts.append(f"Heading: {h_str}")

    doc_type = meta.get("type") or ""
    if doc_type:
        parts.append(f"Type: {doc_type}")

    if not parts:
        return ""

    return "[" + " | ".join(parts) + "]"


def prepend_header(chunk: str, meta: dict) -> str:
    """Return chunk text with its context header prepended."""
    header = build_chunk_header(meta)
    if not header:
        return chunk
    return f"{header}\n{chunk}"


# =========================
# EMBEDDING WRAPPER
# =========================
class OllamaEmbeddingWrapper:
    def embed_documents(self, texts: List[str]) -> List[List[float]]:
        from src.rag.embeddings.ollama import embed_texts
        return embed_texts(texts)

    def embed_query(self, text: str) -> List[float]:
        from src.rag.embeddings.ollama import embed_query
        return embed_query(text)

# =========================
# SPLITTERS
# =========================
def make_semantic_splitter():
    """
    Uses a semantic chunker with a custom embedding model to split text into semantically meaningful chunks.
    """
    
    return SemanticChunker(
        OllamaEmbeddingWrapper(),
        breakpoint_threshold_type="percentile",
        breakpoint_threshold_amount=85,
    )

def make_recursive_splitter():
    """
    Fallback splitter that uses a recursive character-based approach to split text into chunks of a specified size with some overlap.
    """
    
    return RecursiveCharacterTextSplitter(
        chunk_size=800,
        chunk_overlap=100
    )

# =========================
# SECTION SPLIT
# =========================
def split_by_sections(text: str) -> List[Dict]:
    """
    Detect multi-format sections:
    - 1. / 1.1
    - # markdown
    - bullet
    - ALL CAPS titles
    """

    pattern = r"""
    (?=\n\s*(?:                 # lookahead
        \d+\.\d+ |              # 1.1
        \d+\.\s |               # 1.
        [A-Z][A-Z\s]{5,} |      # ALL CAPS TITLE
        \#\s | \#\#\s |         # markdown
        [-•]\s                 # bullet
    ))
    """
    parts = re.split(pattern, text)

    results = []
    for part in parts:
        part = part.strip()
        if not part:
            continue

        lines = part.split("\n")
        title = lines[0][:100]

        results.append({
            "title": title,
            "content": part
        })

    return results

# =========================
# SEMANTIC CORE
# =========================
def is_bullet_block(text: str):
    lines = text.split("\n")
    bullet_lines = [l for l in lines if l.strip().startswith(("-", "*"))]
    return len(bullet_lines) >= 2

def semantic_chunk_text(text: str) -> List[str]:
    semantic = make_semantic_splitter()

    try:
        docs = semantic.create_documents([text])
        chunks = [d.page_content for d in docs]

        # fallback nếu semantic không chia được
        if len(chunks) < 2:
            raise ValueError("Too few semantic chunks")

        return chunks

    except Exception:
        splitter = make_recursive_splitter()
        return splitter.split_text(text)

# =========================
# PDF
# =========================
def chunk_pdf(path: Path):
    reader = PdfReader(str(path))

    chunks, metas = [], []

    for i, page in enumerate(reader.pages):
        text = page.extract_text() or ""
        if not text.strip():
            continue

        sections = split_by_sections(text)

        for sec in sections:
            sub_chunks = semantic_chunk_text(sec["content"])

            for idx, chunk in enumerate(sub_chunks):
                chunks.append(chunk)
                metas.append({
                    "source": path.name,
                    "page": i + 1,
                    "section": sec["title"],
                    "chunk_index": idx,
                    "type": "semantic",
                })

    return chunks, metas

# =========================
# DOCX
# =========================
def read_docx(path: Path) -> str:
    paragraphs = []

    with ZipFile(path) as archive:
        root = ElementTree.parse(archive.open("word/document.xml")).getroot()

    for p in root.findall(".//w:p", OFFICE_XML_NAMESPACES):
        texts = [t.text or "" for t in p.findall(".//w:t", OFFICE_XML_NAMESPACES)]
        line = "".join(texts).strip()
        if line:
            paragraphs.append(line)

    return "\n".join(paragraphs)

# =========================
# TABLE
# =========================
def extract_xlsx_as_dicts(path: Path) -> List[Dict[str, str]]:
    def load_shared_strings(archive: ZipFile) -> List[str]:
        try:
            if "xl/sharedStrings.xml" not in archive.namelist():
                return []

            root = ElementTree.parse(archive.open("xl/sharedStrings.xml")).getroot()

            return [
                "".join(t.text or "" for t in si.findall(".//main:t", OFFICE_XML_NAMESPACES))
                for si in root.findall("main:si", OFFICE_XML_NAMESPACES)
            ]
        except:
            return []

    def get_cell_value(cell, shared_strings):
        try:
            cell_type = cell.attrib.get("t")
            value_node = cell.find("main:v", OFFICE_XML_NAMESPACES)

            if value_node is None:
                return "".join(
                    n.text or "" for n in cell.findall(".//main:t", OFFICE_XML_NAMESPACES)
                ).strip()

            value = value_node.text

            if cell_type == "s" and value.isdigit():
                return shared_strings[int(value)]

            return value or ""
        except:
            return ""

    rows_out = []

    with ZipFile(path) as archive:
        shared_strings = load_shared_strings(archive)

        sheets = sorted(f for f in archive.namelist() if f.startswith("xl/worksheets/sheet"))

        for sheet_idx, sheet in enumerate(sheets):
            root = ElementTree.parse(archive.open(sheet)).getroot()
            rows = root.findall(".//main:row", OFFICE_XML_NAMESPACES)

            parsed = []
            for r in rows:
                vals = [
                    get_cell_value(c, shared_strings).strip()
                    for c in r.findall("main:c", OFFICE_XML_NAMESPACES)
                ]
                if any(vals):
                    parsed.append(vals)

            if not parsed:
                continue

            headers = parsed[0]

            for i, row in enumerate(parsed[1:]):
                obj = {
                    headers[j]: val
                    for j, val in enumerate(row)
                    if val
                }
                if obj:
                    obj["_sheet"] = f"sheet_{sheet_idx+1}"
                    obj["_row"] = str(i+1)
                    rows_out.append(obj)

    return rows_out

def structured_table_chunks(path: Path):
    ext = path.suffix.lower()

    if ext == ".csv":
        with open(path, encoding="utf-8-sig", errors="replace") as f:
            rows = list(csv.DictReader(f))

    elif ext == ".xlsx":
        rows = extract_xlsx_as_dicts(path)

    else:
        return [], []

    chunks, metas = [], []

    # Individual row chunks
    for i, row in enumerate(rows):
        parts = []
        for k, v in row.items():
            if not k.startswith("_") and v:
                parts.append(f"{k}: {v}")
        
        content = "\n".join(parts)
        chunks.append(f"Record {i+1}:\n{content}")

        metas.append({
            "source": path.name,
            "row_index": i,
            "sheet": row.get("_sheet") or "",
            "type": "table",
            "record_type": "row",
        })

    # Summary chunk — NEW
    if rows:
        summary_lines = [f"File: {path.name}", f"Total records: {len(rows)}"]

        # Lấy tên các cột từ row đầu tiên
        col_names = [k for k in rows[0].keys() if not k.startswith("_")]
        if col_names:
            summary_lines.append(f"Columns: {', '.join(col_names)}")

        # Lấy giá trị row đầu (thường là title/header của Excel báo cáo)
        first_row_vals = [
            v for k, v in rows[0].items()
            if not k.startswith("_") and v
        ]
        if first_row_vals:
            summary_lines.append(f"Report header: {' | '.join(first_row_vals[:5])}")

        # Tổng hợp tất cả nội dung thành 1 đoạn ngắn để LLM có context
        all_content_preview = []
        for row in rows[:5]:  # preview 5 rows đầu
            vals = [v for k, v in row.items() if not k.startswith("_") and v]
            if vals:
                all_content_preview.append(" | ".join(vals[:3]))

        if all_content_preview:
            summary_lines.append("Content preview:")
            summary_lines.extend(f"  - {line}" for line in all_content_preview)

        summary_text = "\n".join(summary_lines)
        chunks.append(summary_text)
        metas.append({
            "source": path.name,
            "row_index": -1,           # -1 = summary chunk
            "sheet": "summary",
            "type": "table_summary",   # type riêng để filter được
            "record_type": "file_summary",
        })

    return chunks, metas

# =========================
# MARKDOWN
# =========================
def split_markdown(text: str, source: str):
    splitter = MarkdownHeaderTextSplitter([
        ("#", "h1"), ("##", "h2"), ("###", "h3"),
    ])

    docs = splitter.split_text(text)

    return (
        [d.page_content for d in docs],
        [{
            "source": source,
            "headers": d.metadata,
            "type": "markdown",
            "chunk_index": i
        } for i, d in enumerate(docs)]
    )

# =========================
# MAIN ENTRY
# =========================
def chunk_file(path: Path) -> Tuple[List[str], List[dict]]:
    ext = path.suffix.lower()

    # PDF
    if ext == ".pdf":
        chunks, metas = chunk_pdf(path)

    # TABLE
    elif ext in [".csv", ".xlsx"]:
        chunks, metas = structured_table_chunks(path)

    else:
        # TEXT
        if ext == ".docx":
            text = read_docx(path)
        else:
            text = path.read_text(encoding="utf-8", errors="ignore")

        # MARKDOWN
        if ext == ".md":
            chunks, metas = split_markdown(text, path.name)

        else:
            # SECTION + SEMANTIC (txt, docx, and any other text format)
            sections = split_by_sections(text)
            chunks, metas = [], []

            for sec in sections:
                if is_bullet_block(sec["content"]):
                    sub_chunks = [sec["content"]]
                else:
                    sub_chunks = semantic_chunk_text(sec["content"])

                for i, chunk in enumerate(sub_chunks):
                    chunks.append(chunk)
                    metas.append({
                        "source": path.name,
                        "section": sec["title"],
                        "chunk_index": i,
                        "type": "semantic",
                    })

    return chunks, metas

# =========================
# ITERATOR
# =========================
def iter_data_files(root: Path) -> Iterable[Path]:
    for p in root.iterdir():
        if p.is_file() and p.suffix.lower() in SUPPORTED_EXTENSIONS:
            yield p