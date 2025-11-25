from __future__ import annotations
import os
from dataclasses import dataclass
from dotenv import load_dotenv

load_dotenv()

# read .env
@dataclass(frozen=True)
class Settings:
    google_api_key: str = os.getenv("GOOGLE_API_KEY", "")
    embed_model: str = os.getenv("EMBED_MODEL", "text-embedding-004")
    gen_model: str = os.getenv("GEN_MODEL", "gemini-2.5-flash")
    chroma_dir: str = os.getenv("CHROMA_DIR", "./var/chroma_db")
    collection: str = os.getenv("COLLECTION", "kb_collection")
    top_k: int = int(os.getenv("TOP_K", "5"))
    chunk_size: int = int(os.getenv("CHUNK_SIZE", "800"))
    chunk_overlap: int = int(os.getenv("CHUNK_OVERLAP", "200"))

settings = Settings()
