from __future__ import annotations
import os
from dataclasses import dataclass
from dotenv import load_dotenv

load_dotenv()

# read .env
@dataclass(frozen=True)
class Settings:
    google_api_key: str = os.getenv("GOOGLE_API_KEY", "")
    embed_model: str = os.getenv("EMBED_MODEL", "qwen3-embedding:latest")
    embed_dim: int = int(os.getenv("EMBED_DIM", "768"))
    gen_model: str = os.getenv("GEN_MODEL", "llama3.2:latest")
    chroma_dir: str = os.getenv("CHROMA_DIR", "./var/chroma_db")
    collection: str = os.getenv("COLLECTION", "kb_collection")
    top_k: int = int(os.getenv("TOP_K", "4"))
    gen_max_tokens: int = int(os.getenv("GEN_MAX_TOKENS", "1024"))
    chunk_size: int = int(os.getenv("CHUNK_SIZE", "800"))
    chunk_overlap: int = int(os.getenv("CHUNK_OVERLAP", "200"))
    db_connection: str = os.getenv("DB_CONNECTION", "mysql")
    db_host: str = os.getenv("DB_HOST", "127.0.0.1")
    db_port: str = os.getenv("DB_PORT", "3306")
    db_database: str = os.getenv("DB_DATABASE", "")
    db_username: str = os.getenv("DB_USERNAME", "")
    db_password: str = os.getenv("DB_PASSWORD", "")
    ollama_model: str = os.getenv("MODEL_NAME", "")
    ollama_url: str = os.getenv("OLLAMA_BASE_URL", "http://localhost:11434")
    dataset_name: str = os.getenv("DATASET_NAME", "eval/rag_structured")
    openai_base_url: str = os.getenv("OPENAI_BASE_URL", "http://localhost:11434/v1")
    openai_api_key: str = os.getenv("OPENAI_API_KEY", "local")
    langfuse_public_key: str = os.getenv("LANGFUSE_PUBLIC_KEY", "")
    langfuse_secret_key: str = os.getenv("LANGFUSE_SECRET_KEY", "")
    langfuse_base_url: str = os.getenv("LANGFUSE_BASE_URL", "https://cloud.langfuse.com")
    anonymized_telemetry: bool = os.getenv("ANONYMIZED_TELEMETRY", "False").lower() in ("true", "1", "yes")
    aws_access_key_id: str = os.getenv("AWS_ACCESS_KEY_ID", "")
    aws_secret_access_key: str = os.getenv("AWS_SECRET_ACCESS_KEY", "")
    aws_region: str = os.getenv("AWS_DEFAULT_REGION", "ap-southeast-1")
    aws_bucket: str = os.getenv("AWS_BUCKET", "")

settings = Settings()
