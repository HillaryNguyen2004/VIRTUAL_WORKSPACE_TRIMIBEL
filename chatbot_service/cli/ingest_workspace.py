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

from src.rag.chunking import SUPPORTED_EXTENSIONS, iter_data_files, chunk_file
from src.rag.embeddings.ollama import embed_texts
from src.rag.vectorstores.chroma_store import add_chunks, normalize_workspace_id

os.environ.setdefault("ANONYMIZED_TELEMETRY", "True")

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
    user_role: Optional[str] = None,
    original_name: Optional[str] = None,
    storage_file_name: Optional[str] = None,
) -> dict:
    """
    Ingest all files from a specific workspace directory.
    
    Args:
        workspace_dir: Path to the workspace data directory
        target_file: Optional specific file to ingest within the workspace (defaults to all files)
        workspace_id: Optional workspace ID for scoping in vector store (defaults to sanitized directory name)
        user_role: Optional user role for metadata (defaults to env RAG_USER_ROLE or '
        
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
    role_scope = (user_role or os.getenv('RAG_USER_ROLE', 'user')).strip() or 'user'
    print(f"Ingest scope -> workspace: {workspace_scope}, role: {role_scope}", flush=True)
    
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

            # Include user-facing file name in chunk text to improve file-specific retrieval.
            chunks = [f"File: {display_name}\n{chunk}" for chunk in chunks]

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
                m["role_scope"] = role_scope

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
                user_role=role_scope,
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
    user_role: Optional[str] = None,
) -> dict:
    """
    Refresh the productivity vector database by fetching predictions from the Flask API and re-indexing.
    
    Args:
        api_base_url: Optional base URL for the Flask API (defaults to env PRODUCTIVITY_API_BASE_URL)
        user_role: Optional user role for metadata (defaults to env RAG_USER_ROLE or 'user')
    
    Returns:
        dict: Result of the refresh operation, including success status and any error messages.
    """
    base_url = (api_base_url or os.getenv("PRODUCTIVITY_API_BASE_URL", "http://127.0.0.1:5001")).strip()

    payload = fetch_productivity_predictions(base_url)
    predictions = payload.get("predictions") or []

    if not predictions:
        return {"success": False, "error": "No predictions"}

    docs, metas, ids = [], [], []

    now = datetime.utcnow()
    date_str = now.strftime("%Y-%m-%d")
    month_str = now.strftime("%Y-%m")
    year = now.year

    declining = 0
    improving = 0
    high = 0

    for item in predictions:
        user_id = str(item.get("user_id"))
        name = item.get("employee_name") or f"user_{user_id}"

        current = item.get("current_productivity")
        predicted = item.get("predicted_productivity")
        trend = item.get("trend")
        level = item.get("level")
        confidence = item.get("confidence")

        if trend == "declining" and level not in ("Excellent", "Good"):
            performance_note = "This employee is at risk and may need intervention."
        elif trend == "declining" and level in ("Excellent", "Good"):
            performance_note = "Despite strong performance, productivity is trending downward. Monitor for early burnout signs."
        elif trend == "improving":
            performance_note = "This employee is on an improving trajectory."
        else:
            performance_note = ""

        level_note = "This employee is a high performer." if level == "Excellent" else ""

        text = (
            f"On {date_str}, employee {name} (ID {user_id}) "
            f"has current productivity of {current}%.\n"
            f"The model predicts productivity will be {predicted}%, "
            f"indicating a {trend} trend.\n"
            f"Performance level is {level} with confidence {confidence}.\n"
        )
        if performance_note:
            text += f"{performance_note}\n"
        if level_note:
            text += f"{level_note}\n"

        docs.append(text.strip())

        metas.append({
            "workspace_id": "productivity",
            "user_id": user_id,
            "employee_name": name,
            "trend": trend,
            "level": level,
            "record_type": "employee_snapshot",
            "role_scope": (user_role or "admin"),
            "snapshot_date": date_str,
            "month": month_str,
            "year": year,
        })

        ids.append(f"productivity-{user_id}-{date_str}")

        if trend == "declining":
            declining += 1
        if trend == "improving":
            improving += 1
        if level == "Excellent":
            high += 1

    # ===== Team summary =====
    total = len(predictions)

    pct_declining = round(declining / total * 100) if total else 0
    pct_improving = round(improving / total * 100) if total else 0

    summary_text = (
        f"Team productivity overview report. Date: {date_str}.\n"
        f"Summary type: productivity overview, team performance, overall productivity.\n"
        f"Total employees: {total}.\n"
        f"Declining employees: {declining} ({pct_declining}% of team).\n"
        f"Improving employees: {improving} ({pct_improving}% of team).\n"
        f"High performers (Excellent level): {high}.\n"
        f"Overall team health: "
        + (
            "critical — majority of employees are declining."
            if pct_declining >= 60
            else "moderate — some employees need attention."
            if pct_declining >= 30
            else "good — most employees are stable or improving."
        )
        + "\nThis report summarizes overall team productivity status.\n"
    )

    docs.append(summary_text.strip())

    metas.append({
        "workspace_id": "productivity",
        "record_type": "team_summary",
        "role_scope": (user_role or "admin"),
        "snapshot_date": date_str,
        "month": month_str,
        "year": year,
    })

    ids.append(f"productivity-team-summary-{month_str}")

    # ===== Embed & store =====
    vectors = embed_texts(docs)

    add_chunks(
        ids=ids,
        docs=docs,
        metas=metas,
        embeddings=vectors,
        workspace_id="productivity",
        user_role=user_role,
    )

    return {
        "success": True,
        "total_indexed": len(docs),
        "snapshot_date": date_str,
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
    parser.add_argument("user_role", nargs="?", help="User role (legacy mode).")
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
        result = refresh_productivity_vectordb_from_predict_all(args.api_base_url, args.user_role)
    else:
        if not args.workspace_dir:
            parser.print_help()
            sys.exit(1)
        result = ingest_workspace_directory(
            args.workspace_dir,
            args.target_file,
            args.workspace_id,
            args.user_role,
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
