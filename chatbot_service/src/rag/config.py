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
    openai_api_key: str = os.getenv("OPENAI_API_KEY", "")
    openai_model: str = os.getenv("OPENAI_MODEL", "gpt-3.5-turbo")
    openrouter_api_key: str = os.getenv("OPENROUTER_API_KEY", "")
    openrouter_model: str = os.getenv("OPENROUTER_MODEL", "")
    chroma_dir: str = os.getenv("CHROMA_DIR", "./var/chroma_db")
    collection: str = os.getenv("COLLECTION", "kb_collection")
    top_k: int = int(os.getenv("TOP_K", "5"))
    chunk_size: int = int(os.getenv("CHUNK_SIZE", "800"))
    chunk_overlap: int = int(os.getenv("CHUNK_OVERLAP", "200"))
    db_connection: str = os.getenv("DB_CONNECTION", "mysql")
    db_host: str = os.getenv("DB_HOST", "127.0.0.1")
    db_port: str = os.getenv("DB_PORT", "3306")
    db_database: str = os.getenv("DB_DATABASE", "")
    db_username: str = os.getenv("DB_USERNAME", "")
    db_password: str = os.getenv("DB_PASSWORD", "")

settings = Settings()
