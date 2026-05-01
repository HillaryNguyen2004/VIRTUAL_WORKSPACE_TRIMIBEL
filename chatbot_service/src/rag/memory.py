from __future__ import annotations
from collections import deque
from typing import List, Dict


class SlidingWindowMemory:
    """
    Lưu lịch sử hội thoại theo sliding window.
    Khi vượt max_turns, tự động drop tin nhắn cũ nhất.
    """

    def __init__(self, max_turns: int = 10):
        # max_turns = số cặp (user + assistant)
        self._history: deque[Dict[str, str]] = deque(maxlen=max_turns * 2)

    def add(self, role: str, content: str) -> None:
        """role: 'user' hoặc 'assistant'"""
        self._history.append({"role": role, "content": content})

    def get_history(self) -> List[Dict[str, str]]:
        return list(self._history)

    def get_context_text(self) -> str:
        """Format lịch sử thành text để inject vào prompt."""
        lines = []
        for msg in self._history:
            prefix = "User" if msg["role"] == "user" else "Assistant"
            lines.append(f"{prefix}: {msg['content']}")
        return "\n".join(lines)

    def clear(self) -> None:
        self._history.clear()

    def __len__(self) -> int:
        return len(self._history)


# Global store: session_id → SlidingWindowMemory
_sessions: Dict[str, SlidingWindowMemory] = {}


def get_memory(session_id: str, max_turns: int = 10) -> SlidingWindowMemory:
    if session_id not in _sessions:
        _sessions[session_id] = SlidingWindowMemory(max_turns=max_turns)
    return _sessions[session_id]


def clear_memory(session_id: str) -> None:
    if session_id in _sessions:
        _sessions[session_id].clear()