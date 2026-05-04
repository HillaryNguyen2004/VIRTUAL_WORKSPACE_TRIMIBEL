from __future__ import annotations

from datetime import datetime
from typing import Optional

from .vectorstores.chroma_store import get_collection


def get_latest_snapshot_date(workspace_id: str | None = None) -> Optional[str]:
    """Return the latest ISO snapshot_date stored in the workspace collection.

    The productivity ingest stores snapshot dates in ISO format (YYYY-MM-DD),
    so a lexicographic max is sufficient once we filter out malformed values.
    """
    try:
        collection = get_collection(workspace_id=workspace_id)
        total = collection.count()
        if total == 0:
            return None

        # Fetch metadata only; we do not need the document bodies here.
        results = collection.get(limit=total, include=["metadatas"])
        metadatas = results.get("metadatas") or []

        dates: list[str] = []
        for metadata in metadatas:
            if not metadata:
                continue
            snapshot_date = metadata.get("snapshot_date")
            if not snapshot_date or not isinstance(snapshot_date, str):
                continue
            try:
                datetime.strptime(snapshot_date, "%Y-%m-%d")
            except ValueError:
                continue
            dates.append(snapshot_date)

        return max(dates) if dates else None
    except Exception:
        return None