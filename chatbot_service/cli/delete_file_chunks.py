from __future__ import annotations

import argparse
import sys
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from src.rag.vectorstores.chroma_store import delete_by_storage_file, normalize_workspace_id


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Delete ChromaDB chunks for a specific file."
    )
    parser.add_argument("storage_file", help="UUID-based filename stored in metadata (e.g. abc123.pdf)")
    parser.add_argument("workspace_id", help="Workspace ID or scope ('public' for public workspaces)")
    args = parser.parse_args()

    try:
        workspace_scope = normalize_workspace_id(args.workspace_id)
        deleted = delete_by_storage_file(
            storage_file=args.storage_file,
            workspace_id=workspace_scope,
        )
        print(f"Deleted {deleted} chunks for storage_file={args.storage_file} workspace={workspace_scope}", flush=True)
    except Exception as e:
        print(f"Error: {e}", flush=True)
        sys.exit(1)


if __name__ == "__main__":
    main()
