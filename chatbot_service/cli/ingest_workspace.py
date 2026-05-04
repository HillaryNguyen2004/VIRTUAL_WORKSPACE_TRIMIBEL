"""
Ingest script for workspace-specific data.

This script:
1. Reads files from a specific workspace directory
2. Chunks them using RecursiveCharacterTextSplitter
3. Embeds chunks using Ollama
4. Stores in workspace-specific Chroma collection
5. Outputs chunk counts and status
"""

from __future__ import annotations
import argparse
import json
import os
import sys
from datetime import datetime
from pathlib import Path
from typing import Optional
from urllib import request as urlrequest
from urllib import error as urlerror

# Ensure project root is importable when running this script by file path.
PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from src.rag.chunking import SUPPORTED_EXTENSIONS, iter_data_files, chunk_file, prepend_header
from src.rag.embeddings.ollama import embed_texts
from src.rag.vectorstores.chroma_store import (
    add_chunks,
    count_legacy_chunks,
    delete_collection,
    normalize_workspace_id,
    reload_chroma_clients,
)

os.environ.setdefault("ANONYMIZED_TELEMETRY", "True")

CHATBOT_API_BASE = os.getenv("CHATBOT_API_URL", "http://127.0.0.1:8002")

def _notify_chroma_reload() -> None:
    """Tell the running chatbot API to clear its stale ChromaDB client cache."""
    try:
        url = CHATBOT_API_BASE.rstrip("/") + "/reload-chroma"
        req = urlrequest.Request(url=url, method="POST", data=b"")
        with urlrequest.urlopen(req, timeout=10):
            pass
        print("ChromaDB cache cleared on API server.", flush=True)
    except Exception as exc:
        print(f"Warning: could not notify API to reload ChromaDB: {exc}", flush=True)

def infer_locale_from_path(path: Path, default_locale: str = "en-US") -> str:
    """Infer locale from filename or path."""
    name = path.name.lower()
    if ".vi." in name or "/vi/" in str(path.as_posix()).lower():
        return "vi-VN"
    if ".en." in name or "/en/" in str(path.as_posix()).lower():
        return "en-US"
    return default_locale

def ingest_workspace_directory(
    workspace_dir: str,
    target_file: Optional[str] = None,
    workspace_id: Optional[str] = None,
    original_name: Optional[str] = None,
    storage_file_name: Optional[str] = None,
) -> dict:
    """
    Ingest all files from a specific workspace directory.
    
    Args:
        workspace_dir: Path to the workspace data directory
        target_file: Optional specific file to ingest within the workspace (defaults to all files)
        workspace_id: Optional workspace ID for scoping in vector store (defaults to sanitized directory name)
        
    Returns:
        Dictionary with ingest results
    """
    data_dir = Path(workspace_dir)
    
    if not data_dir.exists():
        return {
            'success': False,
            'error': f'Workspace directory not found: {workspace_dir}',
            'files': [],
        }

    workspace_scope = normalize_workspace_id(workspace_id or data_dir.name)
    print(f"Ingest scope -> workspace: {workspace_scope}", flush=True)
    
    results = []
    total_chunks = 0
    failed_files = 0
    completed_files = 0
    first_error: Optional[str] = None
    
    if target_file:
        target_path = Path(target_file)
        if not target_path.exists():
            return {
                'success': False,
                'error': f'Target file not found: {target_file}',
                'files': [],
            }
        if target_path.suffix.lower() not in SUPPORTED_EXTENSIONS:
            return {
                'success': False,
                'error': f'Unsupported target file type: {target_path.suffix}',
                'files': [],
            }
        files_to_ingest = [target_path]
    else:
        files_to_ingest = list(iter_data_files(data_dir))

    # Ingest each file
    for path in files_to_ingest:
        print(f"Ingesting {path.name}...", flush=True)
        
        try:
            chunks, metas = chunk_file(path)

            display_name = (original_name.strip() if original_name and target_file else path.name)
            display_name = display_name or path.name

            # storage_file_name is the canonical DB filename (S3 UUID). Fall back to the
            # temp path basename only when not provided (e.g. CLI / batch ingest).
            storage_key = (storage_file_name.strip() if storage_file_name and target_file else path.name) or path.name

            if not chunks:
                print(f"  Skipped empty: {path.name}", flush=True)
                results.append({
                    'file': path.name,
                    'status': 'skipped',
                    'reason': 'empty',
                    'chunks': 0,
                })
                continue

            # Infer locale
            locale = infer_locale_from_path(path)
            for m in metas:
                m["source"] = display_name
                m["file_name"] = display_name
                m["storage_file"] = storage_key
                m["locale"] = locale
                m["workspace_id"] = workspace_scope

            # Rebuild chunk headers now that metadata has the final display_name.
            # chunk_file() already injected headers using path.name; we replace
            # them here so the header says "HR Policy.pdf" not "tmp_abc123.pdf".
            chunks = [prepend_header(chunk, meta) for chunk, meta in zip(chunks, metas)]

            # Embed texts
            vectors = embed_texts(chunks)
            ids = [f"{storage_key}-{i}" for i in range(len(chunks))]
            
            # Add to vector store
            add_chunks(
                ids=ids,
                docs=chunks,
                metas=metas,
                embeddings=vectors,
                workspace_id=workspace_scope,
            )
            
            total_chunks += len(chunks)
            print(f"  Added {len(chunks)} chunks", flush=True)
            completed_files += 1
            
            results.append({
                'file': path.name,
                'status': 'completed',
                'chunks': len(chunks),
            })
            
        except Exception as e:
            print(f"  Error ingesting {path.name}: {str(e)}", flush=True)
            failed_files += 1
            if first_error is None:
                first_error = str(e)
            results.append({
                'file': path.name,
                'status': 'failed',
                'error': str(e),
                'chunks': 0,
            })
    
    print(f"Done. Total chunks: {total_chunks}", flush=True)

    # Warn if the collection still contains legacy chunks (ingested before the
    # chunk-header upgrade). Those chunks lack provenance headers and will give
    # weaker retrieval results. Re-ingest the affected files to fix this.
    try:
        legacy = count_legacy_chunks(workspace_id=workspace_scope)
        if legacy > 0:
            print(
                f"\n[WARNING] {legacy} sampled chunk(s) in workspace '{workspace_scope}' "
                "do not have contextual headers (legacy format). "
                "Re-ingest those files to improve retrieval accuracy.",
                flush=True,
            )
    except Exception:
        pass  # never let the notice crash the ingest result

    if failed_files > 0:
        return {
            'success': False,
            'error': first_error or f'{failed_files} file(s) failed during ingest',
            'total_chunks': total_chunks,
            'files': results,
        }

    if target_file and completed_files == 0:
        return {
            'success': False,
            'error': 'No chunks generated for target file',
            'total_chunks': total_chunks,
            'files': results,
        }

    _notify_chroma_reload()
    return {
        'success': True,
        'total_chunks': total_chunks,
        'files': results,
    }

def fetch_productivity_predictions(api_base_url: str) -> dict:
    """
    Fetch productivity predictions from Flask API /predict/all.
    
    Args:
        api_base_url: Base URL of the Flask API (e.g., http://localhost:5001)
        
    Returns:
        dict: The JSON response from the API or an error message.
    """
    
    url = api_base_url.rstrip("/") + "/predict/all"
    req = urlrequest.Request(url=url, method="POST")
    with urlrequest.urlopen(req, timeout=600) as resp:
        return json.loads(resp.read().decode("utf-8"))


def refresh_productivity_vectordb_from_predict_all(
    api_base_url: Optional[str] = None,
) -> dict:
    """
    Refresh the productivity vector database by fetching predictions from the Flask API and re-indexing.
    
    Args:
        api_base_url: Optional base URL for the Flask API (defaults to env PRODUCTIVITY_API_BASE_URL)
    
    Returns:
        dict: Result of the refresh operation, including success status and any error messages.
    """
    base_url = (api_base_url or os.getenv("PRODUCTIVITY_API_BASE_URL", "http://127.0.0.1:5001")).strip()

    payload = fetch_productivity_predictions(base_url)
    predictions = payload.get("predictions") or []

    if not predictions:
        return {"success": False, "error": "No predictions"}

    # Rebuild the productivity workspace from scratch so stale vectors do not
    # remain after a refresh. Keep deletion idempotent; the caller (Laravel)
    # may already remove on-disk files before invoking this CLI.
    delete_collection(workspace_id="productivity")
    reload_chroma_clients()

    docs, metas, ids = [], [], []

    # Determine a canonical snapshot/prediction target date. Prefer the
    # prediction_target_date field from the payload when available so IDs and
    # snapshot dates align with the model's target day.
    target_dates = set()
    for it in predictions:
        dt = it.get("prediction_target_date")
        if dt:
            target_dates.add(dt)

    if len(target_dates) == 1:
        snapshot_date = list(target_dates)[0]
    else:
        snapshot_date = datetime.utcnow().strftime("%Y-%m-%d")

    month_str = snapshot_date[:7]
    try:
        year = int(snapshot_date.split("-")[0])
    except Exception:
        year = datetime.utcnow().year

    # Counters for team summary
    declining = 0
    improving = 0
    stable = 0
    high = 0

    for item in predictions:
        user_id = str(item.get("user_id") or "")
        name = item.get("employee_name") or item.get("name") or f"user_{user_id}"

        current = item.get("current_productivity")
        predicted = item.get("predicted_productivity")
        trend = item.get("trend")
        level = item.get("level") or item.get("predicted_level")
        confidence = item.get("confidence") or item.get("confidence_score")
        model_version = item.get("model_version")
        predicted_level = item.get("predicted_level")
        predicted_class = item.get("predicted_class")
        productivity_score = item.get("productivity_score")
        class_probs = item.get("class_probabilities")
        based_on = item.get("based_on_data_through")
        lookback = item.get("lookback")

        # Human-readable doc: concise, consistent structure for retrieval previews
        doc_text = (
            f"{name} (User ID: {user_id}) has a current productivity of {current} "
            f"and is predicted to reach {predicted} (level: {predicted_level}). "
            f"Trend: {trend}. Snapshot date: {snapshot_date}."
        )
        docs.append(doc_text)

        meta = {
            "workspace_id": "productivity",
            "record_type": "employee_snapshot",
            "user_id": user_id,
            "employee_name": name,
            "level": level,
            "current_productivity": current,
            "predicted_productivity": predicted,
            "predicted_level": predicted_level,
            "predicted_class": predicted_class,
            "productivity_score": productivity_score,
            "confidence": confidence,
            # Chroma metadata values must be primitive types. Serialize
            # complex structures like dicts/lists to JSON strings.
            "class_probabilities": json.dumps(class_probs) if class_probs is not None else None,
            "trend": trend,
            "model_version": model_version,
            "based_on_data_through": based_on,
            "lookback": lookback,
            "snapshot_date": snapshot_date,
            "month": month_str,
            "year": year,
        }

        metas.append(meta)

        # Use the prediction target date in the ID so each snapshot is uniquely
        # addressable by user + target date.
        ids.append(f"productivity-{user_id}-{snapshot_date}")

        if trend == "declining":
            declining += 1
        if trend == "improving":
            improving += 1
        if trend == "stable":
            stable += 1
        if (level or "") == "High":
            high += 1

    # ===== Team summary =====
    total = len(predictions)
    pct_declining = round(declining / total * 100) if total else 0
    pct_improving = round(improving / total * 100) if total else 0
    pct_stable = round(stable / total * 100) if total else 0

    summary_text = (
        f"Team productivity overview report. Date: {snapshot_date}.\n"
        f"Total employees: {total}. Declining: {declining} ({pct_declining}%). Improving: {improving} ({pct_improving}%). Stable: {stable} ({pct_stable}%). High performers (High): {high}."
    )

    docs.append(summary_text)

    metas.append({
        "workspace_id": "productivity",
        "record_type": "team_summary",
        "snapshot_date": snapshot_date,
        "month": month_str,
        "year": year,
        "total_employees": total,
        "declining": declining,
        "improving": improving,
        "stable": stable,
        "high_performers": high,
    })

    ids.append(f"productivity-team-summary-{snapshot_date}")

    # ===== Embed & store =====
    vectors = embed_texts(docs)

    add_chunks(
        ids=ids,
        docs=docs,
        metas=metas,
        embeddings=vectors,
        workspace_id="productivity",
    )

    _notify_chroma_reload()
    return {
        "success": True,
        "total_indexed": len(docs),
        "snapshot_date": snapshot_date,
        "month": month_str,
        "year": year,
    }

def main():
    """Main entry point."""
    parser = argparse.ArgumentParser(
        description="Ingest workspace files or refresh productivity vectors from Flask predict/all.",
    )
    parser.add_argument("workspace_dir", nargs="?", help="Workspace directory path (legacy mode).")
    parser.add_argument("target_file", nargs="?", help="Target file path inside workspace (legacy mode).")
    parser.add_argument("workspace_id", nargs="?", help="Workspace id/scope (legacy mode).")
    parser.add_argument("original_name", nargs="?", help="Original display filename (legacy mode, optional).")
    parser.add_argument("storage_file_name", nargs="?", help="Canonical storage filename for vectordb metadata (e.g. S3 UUID filename).")
    parser.add_argument(
        "--refresh-productivity",
        action="store_true",
        help="Call Flask /predict/all and rebuild productivity vector DB.",
    )
    parser.add_argument(
        "--api-base-url",
        dest="api_base_url",
        help="Flask API base URL for /predict/all (default: PRODUCTIVITY_API_BASE_URL or http://127.0.0.1:5001).",
    )

    args = parser.parse_args()

    if args.refresh_productivity:
        result = refresh_productivity_vectordb_from_predict_all(args.api_base_url)
    else:
        if not args.workspace_dir:
            parser.print_help()
            sys.exit(1)
        result = ingest_workspace_directory(
            args.workspace_dir,
            args.target_file,
            args.workspace_id,
            args.original_name,
            args.storage_file_name,
        )
    
    if not result['success']:
        print(f"Error: {result.get('error', 'Unknown error')}", flush=True)
        sys.exit(1)
    
    print(f"\nIngest completed successfully.", flush=True)
    sys.exit(0)


if __name__ == "__main__":
    main()
