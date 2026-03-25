"""
Ingest script for workspace-specific data.

This script:
1. Reads files from a specific workspace directory
2. Chunks them using RecursiveCharacterTextSplitter
3. Embeds chunks using Ollama/Gemini
4. Stores in workspace-specific Chroma collection
5. Outputs chunk counts and status
"""

from __future__ import annotations
import os
import sys
from pathlib import Path
from typing import Optional

# Ensure project root is importable when running this script by file path.
PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from src.rag.config import settings
from src.rag.chunking import SUPPORTED_EXTENSIONS, make_splitter, iter_data_files, chunk_file
from src.rag.embeddings.ollama import embed_texts
from src.rag.vectorstores.chroma_store import add_chunks

os.environ.setdefault("ANONYMIZED_TELEMETRY", "True")


def infer_locale_from_path(path: Path, default_locale: str = "en-US") -> str:
    """Infer locale from filename or path."""
    name = path.name.lower()
    if ".vi." in name or "/vi/" in str(path.as_posix()).lower():
        return "vi-VN"
    if ".en." in name or "/en/" in str(path.as_posix()).lower():
        return "en-US"
    return default_locale


def ingest_workspace_directory(workspace_dir: str, target_file: Optional[str] = None) -> dict:
    """
    Ingest all files from a specific workspace directory.
    
    Args:
        workspace_dir: Path to the workspace data directory
        
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
    
    # Create splitter
    splitter = make_splitter(settings.chunk_size, settings.chunk_overlap)
    
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
            chunks, metas = chunk_file(path, splitter)
            
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
                m["locale"] = locale
            
            # Embed texts
            vectors = embed_texts(chunks)
            ids = [f"{path.name}-{i}" for i in range(len(chunks))]
            
            # Add to vector store
            add_chunks(ids=ids, docs=chunks, metas=metas, embeddings=vectors)
            
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


def main():
    """Main entry point."""
    if len(sys.argv) < 2:
        print("Usage: python ingest_workspace.py <workspace_directory> [target_file]", flush=True)
        sys.exit(1)
    
    workspace_dir = sys.argv[1]
    target_file = sys.argv[2] if len(sys.argv) > 2 else None
    result = ingest_workspace_directory(workspace_dir, target_file)
    
    if not result['success']:
        print(f"Error: {result.get('error', 'Unknown error')}", flush=True)
        sys.exit(1)
    
    print(f"\nIngest completed successfully.", flush=True)
    sys.exit(0)


if __name__ == "__main__":
    main()
