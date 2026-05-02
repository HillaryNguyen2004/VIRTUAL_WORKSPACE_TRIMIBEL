from __future__ import annotations

from pathlib import Path
from typing import Iterable, Tuple, List
from xml.etree import ElementTree
from zipfile import ZipFile

from pdfminer.high_level import extract_text as pdf_to_text
from langchain_text_splitters import RecursiveCharacterTextSplitter

OFFICE_XML_NAMESPACES = {
    "a": "http://schemas.openxmlformats.org/drawingml/2006/main",
    "main": "http://schemas.openxmlformats.org/spreadsheetml/2006/main",
    "w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main",
}

SUPPORTED_EXTENSIONS = {".pdf", ".txt", ".md", ".docx", ".xlsx"}


def _normalize_text(parts: List[str]) -> str:
    cleaned = [part.strip() for part in parts if part and part.strip()]
    return "\n".join(cleaned)


def _read_docx(path: Path) -> str:
    paragraphs: List[str] = []

    with ZipFile(path) as archive:
        with archive.open("word/document.xml") as document_xml:
            root = ElementTree.parse(document_xml).getroot()

    for paragraph in root.findall(".//w:p", OFFICE_XML_NAMESPACES):
        runs = [node.text or "" for node in paragraph.findall(".//w:t", OFFICE_XML_NAMESPACES)]
        text = "".join(runs).strip()
        if text:
            paragraphs.append(text)

    return _normalize_text(paragraphs)


def _read_xlsx(path: Path) -> str:
    rows: List[str] = []

    def shared_strings(archive: ZipFile) -> List[str]:
        if "xl/sharedStrings.xml" not in archive.namelist():
            return []

        with archive.open("xl/sharedStrings.xml") as strings_xml:
            root = ElementTree.parse(strings_xml).getroot()

        values: List[str] = []
        for item in root.findall("main:si", OFFICE_XML_NAMESPACES):
            text_nodes = [node.text or "" for node in item.findall(".//main:t", OFFICE_XML_NAMESPACES)]
            values.append("".join(text_nodes))
        return values

    with ZipFile(path) as archive:
        string_table = shared_strings(archive)

        worksheet_names = sorted(
            name
            for name in archive.namelist()
            if name.startswith("xl/worksheets/sheet") and name.endswith(".xml")
        )

        for sheet_idx, worksheet_name in enumerate(worksheet_names):
            with archive.open(worksheet_name) as sheet_xml:
                root = ElementTree.parse(sheet_xml).getroot()

            headers: List[str] = []
            sheet_rows: List[List[str]] = []

            for row in root.findall(".//main:row", OFFICE_XML_NAMESPACES):
                values: List[str] = []

                for cell in row.findall("main:c", OFFICE_XML_NAMESPACES):
                    cell_type = cell.attrib.get("t")
                    value_node = cell.find("main:v", OFFICE_XML_NAMESPACES)

                    if value_node is None or value_node.text is None:
                        inline_text_nodes = cell.findall(".//main:t", OFFICE_XML_NAMESPACES)
                        inline_text = "".join(node.text or "" for node in inline_text_nodes).strip()
                        values.append(inline_text)
                        continue

                    cell_value = value_node.text

                    if cell_type == "s":
                        try:
                            shared_index = int(cell_value)
                            values.append(string_table[shared_index])
                        except (IndexError, ValueError):
                            values.append(cell_value)
                    else:
                        values.append(cell_value)

                if values:
                    sheet_rows.append(values)

            if not sheet_rows:
                continue

            # first row = headers
            headers = sheet_rows[0]

            for row_values in sheet_rows[1:]:
                pairs = []

                for i, value in enumerate(row_values):
                    if i < len(headers):
                        header = headers[i].strip()
                    else:
                        header = f"column_{i}"

                    if value and value.strip():
                        pairs.append(f"{header}: {value.strip()}")

                if pairs:
                    rows.append(f"Sheet {sheet_idx+1} | " + ", ".join(pairs))

    return _normalize_text(rows)

def _read_csv(path: Path) -> str:
    rows = []

    with open(path, newline="", encoding="utf-8") as f:
        reader = csv.reader(f)
        data = list(reader)

    if not data:
        return ""

    headers = data[0]

    for row in data[1:]:
        pairs = []
        for i, value in enumerate(row):
            if i < len(headers):
                header = headers[i].strip()
            else:
                header = f"column_{i}"

            if value.strip():
                pairs.append(f"{header}: {value.strip()}")

        if pairs:
            rows.append("Row:\n- " + "\n- ".join(pairs))

    return "\n\n".join(rows)

def read_file(path: Path) -> str:
    ext = path.suffix.lower()
    if ext == ".pdf":
        return pdf_to_text(str(path))
    if ext == ".docx":
        return _read_docx(path)
    if ext == ".xlsx":
        return _read_xlsx(path)
    if ext == ".csv":
        return _read_csv(path)
    return path.read_text(encoding="utf-8", errors="ignore")

# returns a RecursiveCharacterTextSplitter
def make_splitter(chunk: int, overlap: int) -> RecursiveCharacterTextSplitter:
    return RecursiveCharacterTextSplitter(chunk_size=chunk, chunk_overlap=overlap)

def split_text(text: str, splitter: RecursiveCharacterTextSplitter) -> List[str]:
    return splitter.split_text(text)

def iter_data_files(root: Path) -> Iterable[Path]:
    for p in root.iterdir():
        if p.is_file() and p.suffix.lower() in SUPPORTED_EXTENSIONS:
            yield p

# returns chunks, metadatas
def chunk_file(path: Path, splitter: RecursiveCharacterTextSplitter) -> Tuple[List[str], List[dict]]:
    text = read_file(path)
    chunks = split_text(text, splitter)
    metas = []
    char_offset = 0
    lines_so_far = 1
    lines_per_page = 45

    for i, chunk in enumerate(chunks):
        # Count lines up to this chunk's position in the original text
        start = text.find(chunk, char_offset)
        if start >= 0:
            lines_so_far = text[:start].count('\n') + 1
            char_offset = start + len(chunk)

        page = max(1, (lines_so_far - 1) // lines_per_page + 1)
        metas.append({
            "source": path.name,
            "chunk_index": i,
            "page": page,
            "line": lines_so_far,
        })

    return chunks, metas
