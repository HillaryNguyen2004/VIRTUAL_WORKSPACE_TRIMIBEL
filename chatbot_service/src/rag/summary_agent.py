"""
summary_agent.py
================
Map-reduce summarisation agent with per-file grouping and rich citations.

Pipeline for summarize_workspace / summarize_s3_document
---------------------------------------------------------
1. Load all chunks + stored embeddings from ChromaDB.
2. Group chunks by source file (metadata["storage_file"]).
3. For each file:
   a. Run K-Means → pick one representative chunk per cluster (MAP phase).
   b. Summarise those representatives with the LLM → one file-level summary.
4. Combine all file-level summaries into a final REDUCE call to the LLM.
5. Return the final summary plus rich per-file citations.

Benefits over the old single-pass approach
------------------------------------------
- Larger documents get proper coverage: each file's content is summarised
  independently before being merged, so no file drowns out the others.
- Citations now include filename, page, and section pulled from chunk metadata.
- Auto k: k is derived from each file's chunk count so the caller rarely
  needs to tune n_clusters.

For raw text / chat messages (not yet in ChromaDB) the pipeline falls back
to the original embed-on-the-fly → K-Means → single LLM call.

Public API
----------
summarize_workspace(workspace_id, user_id, lang, style, n_clusters, should_cancel)
summarize_text(text, lang, style, n_clusters, should_cancel)
summarize_s3_document(s3_key, workspace_id, lang, style, n_clusters, should_cancel)
summarize_messages(messages, lang, style, n_clusters, should_cancel)
"""
from __future__ import annotations

import logging
from collections import defaultdict
from typing import Callable, Dict, List, Optional, Tuple

import numpy as np

from .embeddings.ollama import embed_texts
from .ollama_generate import generate_answer, GenerationCancelled
from .vectorstores.chroma_store import get_collection, normalize_workspace_id

log = logging.getLogger(__name__)

# ---------------------------------------------------------------------------
# Constants
# ---------------------------------------------------------------------------
_CHUNK_SIZE         = 800
_CHUNK_OVERLAP      = 100
_MAX_CLUSTERS       = 20
_MIN_CLUSTERS       = 3
_MAX_CHUNK_CHARS    = 600   # chars per excerpt sent to LLM (raised from 400)
_MAX_FILE_SUMMARY_CHARS = 1200  # cap each file-level summary fed into reduce

_SYSTEM_PROMPT = (
    "You are an expert summarisation assistant. "
    "Given a set of representative excerpts from a document or conversation, "
    "produce a clear, concise, well-structured summary. "
    "Preserve key facts, figures, names, and dates. "
    "Never hallucinate information that is not in the excerpts. "
    "If information is missing or conflicting, say so explicitly."
)

_REDUCE_SYSTEM_PROMPT = (
    "You are an expert summarisation assistant. "
    "You are given individual summaries of multiple documents or sections. "
    "Synthesise them into one coherent, well-structured final summary. "
    "Identify common themes, highlight the most important points, and note "
    "any contradictions or gaps. Do not hallucinate."
)


# ---------------------------------------------------------------------------
# K-Means (pure numpy)
# ---------------------------------------------------------------------------

def _kmeans(vectors: np.ndarray, k: int, max_iter: int = 100, seed: int = 42) -> np.ndarray:
    rng = np.random.default_rng(seed)
    n   = len(vectors)
    k   = min(k, n)

    # K-Means++ init
    centroids = [vectors[rng.integers(n)]]
    for _ in range(k - 1):
        dists = np.array([min(float(np.dot(v - c, v - c)) for c in centroids) for v in vectors])
        total = dists.sum()
        probs = dists / total if total > 0 else np.ones(n) / n
        centroids.append(vectors[rng.choice(n, p=probs)])
    centroids = np.array(centroids, dtype=np.float32)

    labels = np.zeros(n, dtype=np.int32)
    for _ in range(max_iter):
        diffs      = vectors[:, None, :] - centroids[None, :, :]
        dists2     = (diffs ** 2).sum(axis=2)
        new_labels = dists2.argmin(axis=1)
        if np.array_equal(new_labels, labels):
            break
        labels = new_labels
        for j in range(k):
            members = vectors[labels == j]
            if len(members):
                centroids[j] = members.mean(axis=0)

    return labels


def _auto_k(n_chunks: int, requested_k: int) -> int:
    """Compute a sensible k that scales with content size."""
    auto = max(_MIN_CLUSTERS, min(n_chunks // 5, _MAX_CLUSTERS))
    return min(requested_k, auto, n_chunks)


# ---------------------------------------------------------------------------
# Representative selection
# ---------------------------------------------------------------------------

def _pick_representatives(
    texts: List[str],
    vectors: np.ndarray,
    metas: List[dict],
    n_clusters: int,
) -> List[Tuple[int, str, dict]]:
    """
    Returns list of (cluster_idx, text, meta) sorted by cluster index.
    """
    k      = min(n_clusters, len(texts))
    labels = _kmeans(vectors, k)

    selected: List[Tuple[int, str, dict]] = []
    for ci in range(k):
        idx = np.where(labels == ci)[0]
        if len(idx) == 0:
            continue
        centroid = vectors[idx].mean(axis=0)
        dists    = ((vectors[idx] - centroid) ** 2).sum(axis=1)
        best     = idx[dists.argmin()]
        selected.append((ci, texts[best], metas[best] if metas else {}))

    selected.sort(key=lambda x: x[0])
    return selected


# ---------------------------------------------------------------------------
# Text splitting
# ---------------------------------------------------------------------------

def _split_text(text: str, chunk_size: int = _CHUNK_SIZE, overlap: int = _CHUNK_OVERLAP) -> List[str]:
    chunks, start = [], 0
    while start < len(text):
        end = min(start + chunk_size, len(text))
        chunks.append(text[start:end])
        start += chunk_size - overlap
    return [c for c in chunks if c.strip()]


# ---------------------------------------------------------------------------
# Prompt builders
# ---------------------------------------------------------------------------

def _style_and_lang(lang: str, style: str) -> str:
    lang_instr = (
        "Respond in Vietnamese." if lang == "vi"
        else "Respond in English." if lang == "en"
        else "Respond in the same language as the source material."
    )
    style_instr = {
        "bullet":    "Format the summary as bullet points.",
        "paragraph": "Format the summary as one or two flowing paragraphs.",
        "short":     "Give a single-sentence TL;DR (max 40 words).",
    }.get(style, "Format the summary as bullet points.")
    return f"{lang_instr} {style_instr}"


def _build_map_prompt(reps: List[Tuple[int, str, dict]], lang: str, style: str) -> str:
    """Prompt for the MAP phase: summarise one file's representative chunks."""
    header = _style_and_lang(lang, style)
    parts  = []
    for i, (_, text, meta) in enumerate(reps, 1):
        loc = _format_location(meta)
        label = f"[Excerpt {i}{(' — ' + loc) if loc else ''}]"
        parts.append(f"{label}\n{text[:_MAX_CHUNK_CHARS]}")
    excerpts = "\n\n---\n\n".join(parts)
    return (
        f"{header}\n"
        f"Write in clear, professional language. "
        f"Cite each point using the exact format [Excerpt N].\n\n"
        f"Representative excerpts:\n\n{excerpts}\n\n"
        f"Based on these excerpts, write a summary:"
    )


def _build_reduce_prompt(file_summaries: List[Tuple[str, str]], lang: str, style: str) -> str:
    """Prompt for the REDUCE phase: merge per-file summaries into one final summary."""
    header = _style_and_lang(lang, style)
    parts  = []
    for i, (fname, fsummary) in enumerate(file_summaries, 1):
        parts.append(f"[Document {i}: {fname}]\n{fsummary[:_MAX_FILE_SUMMARY_CHARS]}")
    combined = "\n\n---\n\n".join(parts)
    return (
        f"{header}\n"
        f"Write in clear, professional language. "
        f"Reference documents using the format [Document N].\n\n"
        f"Below are summaries of individual documents or sections:\n\n"
        f"{combined}\n\n"
        f"Synthesise these into one final summary:"
    )


def _build_single_prompt(reps: List[Tuple[int, str, dict]], lang: str, style: str) -> str:
    """Single-pass prompt (used for raw text / messages)."""
    return _build_map_prompt(reps, lang, style)


# ---------------------------------------------------------------------------
# Citation helpers
# ---------------------------------------------------------------------------

def _format_location(meta: dict) -> str:
    """Build a short human-readable location string from chunk metadata."""
    parts = []
    page    = meta.get("page")
    section = meta.get("section") or ""
    sheet   = meta.get("sheet") or ""
    if page is not None:
        parts.append(f"p.{page}")
    if section:
        parts.append(section[:50])
    if sheet and sheet not in ("summary",):
        parts.append(sheet)
    return ", ".join(parts)


def _build_rich_citations(reps: List[Tuple[int, str, dict]]) -> List[Dict]:
    citations = []
    for i, (_, _, meta) in enumerate(reps, 1):
        source   = meta.get("source") or meta.get("file_name") or "excerpt"
        location = _format_location(meta)
        citations.append({
            "rank":     i,
            "id":       f"Excerpt {i}",
            "source":   source,
            "location": location,
        })
    return citations


def _merge_citations(file_citations: List[List[Dict]], file_names: List[str]) -> List[Dict]:
    """Flatten per-file citation lists, adding file context."""
    merged, rank = [], 1
    for fname, cits in zip(file_names, file_citations):
        for c in cits:
            merged.append({
                "rank":     rank,
                "id":       f"Excerpt {rank}",
                "source":   fname,
                "location": c.get("location", ""),
            })
            rank += 1
    return merged


# ---------------------------------------------------------------------------
# Embedding validity
# ---------------------------------------------------------------------------

def _is_valid_embedding(embedding) -> bool:
    if embedding is None:
        return False
    try:
        arr = np.asarray(embedding, dtype=np.float32)
        return arr.ndim >= 1 and arr.shape[0] > 0
    except Exception:
        return False


def _normalise(vectors: np.ndarray) -> np.ndarray:
    norms = np.linalg.norm(vectors, axis=1, keepdims=True)
    norms = np.where(norms == 0, 1.0, norms)
    return vectors / norms


# ---------------------------------------------------------------------------
# MAP phase: summarise one group of chunks
# ---------------------------------------------------------------------------

def _map_summarise(
    texts: List[str],
    vectors: np.ndarray,
    metas: List[dict],
    lang: str,
    style: str,
    n_clusters: int,
    should_cancel: Optional[Callable[[], bool]],
) -> Tuple[str, List[Dict], int]:
    """
    Summarise one file's chunks. Returns (summary_text, citations, k_used).
    """
    k    = _auto_k(len(texts), n_clusters)
    reps = _pick_representatives(texts, vectors, metas, k)

    if should_cancel and should_cancel():
        raise GenerationCancelled("Cancelled in map phase")

    prompt          = _build_map_prompt(reps, lang, style)
    summary, _usage = generate_answer(prompt, system_prompt=_SYSTEM_PROMPT,
                                      should_cancel=should_cancel)
    citations       = _build_rich_citations(reps)
    return summary, citations, k


# ---------------------------------------------------------------------------
# Core pipeline: embed → cluster → map-reduce summarise
# ---------------------------------------------------------------------------

def _map_reduce_summarise(
    grouped: Dict[str, Tuple[List[str], np.ndarray, List[dict]]],
    lang: str,
    style: str,
    n_clusters: int,
    should_cancel: Optional[Callable[[], bool]],
) -> Dict:
    """
    grouped: {filename: (texts, normalised_vectors, metas)}
    Returns the full SummaryResponse-compatible dict.
    """
    file_summaries: List[Tuple[str, str]] = []
    all_citations:  List[List[Dict]]      = []
    all_file_names: List[str]             = []
    total_chunks = 0
    total_k      = 0

    for fname, (texts, vectors, metas) in grouped.items():
        if should_cancel and should_cancel():
            raise GenerationCancelled("Cancelled between map calls")

        log.info("summary_agent: MAP file=%s chunks=%d", fname, len(texts))
        fsummary, fcitations, fk = _map_summarise(
            texts, vectors, metas, lang, style, n_clusters, should_cancel
        )
        file_summaries.append((fname, fsummary))
        all_citations.append(fcitations)
        all_file_names.append(fname)
        total_chunks += len(texts)
        total_k      += fk

    if should_cancel and should_cancel():
        raise GenerationCancelled("Cancelled before reduce")

    # Single file → skip reduce, return map result directly
    if len(file_summaries) == 1:
        fname, fsummary = file_summaries[0]
        return {
            "summary":      fsummary,
            "truncated":    False,
            "n_clusters":   total_k,
            "total_chunks": total_chunks,
            "citations":    all_citations[0],
            "file_name":    fname,
            "source":       "map_reduce",
        }

    # Multiple files → REDUCE
    log.info("summary_agent: REDUCE %d file summaries", len(file_summaries))
    reduce_prompt   = _build_reduce_prompt(file_summaries, lang, style)
    final_summary, usage = generate_answer(
        reduce_prompt, system_prompt=_REDUCE_SYSTEM_PROMPT, should_cancel=should_cancel
    )

    return {
        "summary":      final_summary,
        "truncated":    False,
        "n_clusters":   total_k,
        "total_chunks": total_chunks,
        "citations":    _merge_citations(all_citations, all_file_names),
        "file_name":    ", ".join(all_file_names),
        "source":       "map_reduce",
        "usage":        usage,
    }


# ---------------------------------------------------------------------------
# Fallback: embed on the fly → single-pass (for raw text / messages)
# ---------------------------------------------------------------------------

def _embed_and_summarise(
    chunks: List[str],
    lang: str,
    style: str,
    n_clusters: int,
    should_cancel: Optional[Callable[[], bool]],
    source_label: str = "",
    metas: Optional[List[dict]] = None,
) -> Dict:
    if not chunks:
        return {"summary": "", "truncated": False, "n_clusters": 0,
                "total_chunks": 0, "citations": []}

    if should_cancel and should_cancel():
        raise GenerationCancelled("Cancelled before embedding")

    log.info("summary_agent: embedding %d chunks for %s", len(chunks), source_label or "text")
    raw_vectors = embed_texts(chunks)
    vectors     = _normalise(np.array(raw_vectors, dtype=np.float32))
    metas       = metas or [{} for _ in chunks]

    k    = _auto_k(len(chunks), n_clusters)
    reps = _pick_representatives(chunks, vectors, metas, k)

    if should_cancel and should_cancel():
        raise GenerationCancelled("Cancelled before generation")

    prompt          = _build_single_prompt(reps, lang, style)
    summary, usage  = generate_answer(prompt, system_prompt=_SYSTEM_PROMPT,
                                      should_cancel=should_cancel)
    return {
        "summary":      summary,
        "truncated":    False,
        "n_clusters":   k,
        "total_chunks": len(chunks),
        "citations":    _build_rich_citations(reps),
        "usage":        usage,
    }


# ---------------------------------------------------------------------------
# Public API
# ---------------------------------------------------------------------------

def summarize_workspace(
    workspace_id: str,
    user_id: Optional[str] = None,
    lang: str = "auto",
    style: str = "bullet",
    n_clusters: int = 10,
    should_cancel: Optional[Callable[[], bool]] = None,
) -> Dict:
    """
    Summarise all content ingested into a ChromaDB workspace using map-reduce.
    Groups chunks by source file, summarises each independently, then merges.
    """
    scope = normalize_workspace_id(workspace_id)
    log.info("summary_agent.summarize_workspace: workspace=%s user=%s", scope, user_id)

    coll  = get_collection(workspace_id=scope, user_id=user_id)
    total = coll.count()
    if total == 0:
        return {"summary": "", "truncated": False, "n_clusters": 0, "total_chunks": 0,
                "citations": [], "error": "No documents ingested into this workspace yet."}

    res        = coll.get(include=["documents", "embeddings", "metadatas"])
    docs       = res.get("documents") or []
    raw_embeds = res.get("embeddings")
    raw_metas  = res.get("metadatas") or [{} for _ in docs]

    if raw_embeds is None:
        embeds = []
    elif hasattr(raw_embeds, "tolist"):
        embeds = raw_embeds.tolist()
    else:
        embeds = list(raw_embeds)

    triples = [
        (d, e, m)
        for d, e, m in zip(docs, embeds, raw_metas)
        if bool(d) and _is_valid_embedding(e)
    ]
    if not triples:
        return {"summary": "", "truncated": False, "n_clusters": 0, "total_chunks": 0,
                "citations": [], "error": "Chunks found but embeddings missing — please re-ingest."}

    # Group by source file
    groups: Dict[str, Tuple[List[str], List, List[dict]]] = defaultdict(
        lambda: ([], [], [])
    )
    for text, emb, meta in triples:
        fname = (
            meta.get("storage_file")
            or meta.get("source")
            or meta.get("file_name")
            or "unknown"
        )
        groups[fname][0].append(text)
        groups[fname][1].append(emb)
        groups[fname][2].append(meta)

    # Convert embedding lists → normalised numpy arrays
    grouped: Dict[str, Tuple[List[str], np.ndarray, List[dict]]] = {}
    for fname, (texts, embs, metas) in groups.items():
        vectors = _normalise(np.array(embs, dtype=np.float32))
        grouped[fname] = (texts, vectors, metas)

    log.info("summary_agent.summarize_workspace: %d files, %d total chunks",
             len(grouped), len(triples))

    return _map_reduce_summarise(grouped, lang, style, n_clusters, should_cancel)


def summarize_text(
    text: str,
    lang: str = "auto",
    style: str = "bullet",
    n_clusters: int = 10,
    should_cancel: Optional[Callable[[], bool]] = None,
) -> Dict:
    """Summarise a raw text string (single-pass)."""
    if not text.strip():
        return {"summary": "", "truncated": False, "n_clusters": 0,
                "total_chunks": 0, "citations": []}
    chunks = _split_text(text)
    return _embed_and_summarise(chunks, lang, style, n_clusters, should_cancel,
                                source_label="raw_text")


def summarize_s3_document(
    s3_key: str,
    workspace_id: Optional[str] = None,
    lang: str = "auto",
    style: str = "bullet",
    n_clusters: int = 10,
    should_cancel: Optional[Callable[[], bool]] = None,
) -> Dict:
    """
    Summarise one S3 document using map-reduce if already ingested,
    otherwise falls back to download → extract → single-pass.
    """
    from pathlib import Path

    if workspace_id:
        scope        = normalize_workspace_id(workspace_id)
        storage_file = Path(s3_key).name
        try:
            coll  = get_collection(workspace_id=scope)
            if coll.count() > 0:
                res        = coll.get(
                    where={"storage_file": {"$eq": storage_file}},
                    include=["documents", "embeddings", "metadatas"],
                )
                docs       = res.get("documents") or []
                raw_embeds = res.get("embeddings")
                raw_metas  = res.get("metadatas") or [{} for _ in docs]

                if raw_embeds is None:
                    embeds = []
                elif hasattr(raw_embeds, "tolist"):
                    embeds = raw_embeds.tolist()
                else:
                    embeds = list(raw_embeds)

                triples = [
                    (d, e, m)
                    for d, e, m in zip(docs, embeds, raw_metas)
                    if bool(d) and _is_valid_embedding(e)
                ]
                if triples:
                    log.info("summary_agent.summarize_s3_document: %d stored chunks for %s",
                             len(triples), storage_file)
                    texts   = [t[0] for t in triples]
                    vectors = _normalise(np.array([t[1] for t in triples], dtype=np.float32))
                    metas   = [t[2] for t in triples]
                    k       = _auto_k(len(texts), n_clusters)
                    reps    = _pick_representatives(texts, vectors, metas, k)

                    if should_cancel and should_cancel():
                        raise GenerationCancelled("Cancelled before generation")

                    prompt          = _build_map_prompt(reps, lang, style)
                    summary, usage  = generate_answer(prompt, system_prompt=_SYSTEM_PROMPT,
                                                      should_cancel=should_cancel)
                    return {
                        "summary":      summary,
                        "truncated":    False,
                        "file_name":    storage_file,
                        "n_clusters":   k,
                        "total_chunks": len(texts),
                        "citations":    _build_rich_citations(reps),
                        "source":       "chromadb",
                        "usage":        usage,
                    }
        except GenerationCancelled:
            raise
        except Exception as e:
            log.warning("summary_agent: ChromaDB lookup failed (%s), falling back to S3", e)

    from .s3_storage import download_s3_file
    log.info("summary_agent.summarize_s3_document: downloading s3_key=%s", s3_key)
    with download_s3_file(s3_key) as local_path:
        text      = _extract_file_text(local_path)
        file_name = Path(s3_key).name

    if not text.strip():
        return {"summary": "", "truncated": False, "file_name": file_name,
                "n_clusters": 0, "total_chunks": 0, "citations": [],
                "error": "Could not extract text from file."}

    result             = summarize_text(text, lang=lang, style=style,
                                        n_clusters=n_clusters, should_cancel=should_cancel)
    result["file_name"] = file_name
    result["source"]    = "s3_extract"
    return result


def summarize_messages(
    messages: List[Dict[str, str]],
    lang: str = "auto",
    style: str = "bullet",
    n_clusters: int = 5,
    should_cancel: Optional[Callable[[], bool]] = None,
) -> Dict:
    """Summarise a chat history (single-pass, unchanged behaviour)."""
    if not messages:
        return {"summary": "", "truncated": False, "n_clusters": 0,
                "message_count": 0, "total_chunks": 0, "citations": []}

    lines = [
        (m.get("content") or "").strip()
        for m in messages
        if (m.get("content") or "").strip()
    ]
    if not lines:
        return {"summary": "", "truncated": False, "n_clusters": 0,
                "message_count": len(messages), "total_chunks": 0, "citations": []}

    if len(lines) <= _MIN_CLUSTERS:
        reps            = [(i, t, {}) for i, t in enumerate(lines)]
        prompt          = _build_single_prompt(reps, lang, style)
        summary, usage  = generate_answer(prompt, system_prompt=_SYSTEM_PROMPT,
                                          should_cancel=should_cancel)
        return {
            "summary":       summary,
            "truncated":     False,
            "n_clusters":    len(lines),
            "message_count": len(messages),
            "total_chunks":  len(lines),
            "citations":     _build_rich_citations(reps),
            "usage":         usage,
        }

    result                  = _embed_and_summarise(lines, lang, style, n_clusters,
                                                   should_cancel, source_label="messages")
    result["message_count"] = len(messages)
    return result


# ---------------------------------------------------------------------------
# File text extraction
# ---------------------------------------------------------------------------

def _extract_file_text(local_path) -> str:
    from pathlib import Path
    path = Path(local_path)
    ext  = path.suffix.lower()

    if ext == ".pdf":
        try:
            from pypdf import PdfReader
            reader = PdfReader(str(path))
            return "\n".join(page.extract_text() or "" for page in reader.pages)
        except Exception as e:
            log.warning("PDF extraction failed: %s", e)
            return ""

    if ext == ".docx":
        try:
            from zipfile import ZipFile
            from xml.etree import ElementTree
            NS = {"w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main"}
            with ZipFile(path) as z:
                root = ElementTree.parse(z.open("word/document.xml")).getroot()
            lines = []
            for p in root.findall(".//w:p", NS):
                line = "".join(t.text or "" for t in p.findall(".//w:t", NS)).strip()
                if line:
                    lines.append(line)
            return "\n".join(lines)
        except Exception as e:
            log.warning("DOCX extraction failed: %s", e)
            return ""

    if ext in (".txt", ".md", ".csv"):
        return path.read_text(encoding="utf-8", errors="replace")

    return ""
