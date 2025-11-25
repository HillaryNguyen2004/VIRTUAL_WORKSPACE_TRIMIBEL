# Chatbot Service · Gemini RAG API

A **Retrieval-Augmented Generation** chatbot powered by **Google Gemini, FastAPI, and ChromaDB**.
Drop in docs, ingest, and ask grounded questions through a clean HTTP API.

## ✨ Features

- 📚 RAG pipeline: chunk → embed → store → retrieve → generate

- 🤖 Generation with Gemini 2.5 Flash (fallbacks supported)

- 🔍 Vector search with ChromaDB (persistent)

- 🧠 Embeddings with text-embedding-004 (fixed dimensionality)

- ⚡ FastAPI HTTP endpoint: /chat

- 🧩 Modular “Standard” layout for easy scaling and testing

## 🧱 Architecture
```
flowchart TD
  A[User / Client] -->|POST /chat| B[FastAPI]
  B --> C[embed_query()]
  C --> D[ChromaDB: query_by_vector]
  D --> E[Top-K Context]
  E --> F[Prompt Builder]
  F --> G[Gemini (gen model)]
  G --> H[Answer + Citations]
  H --> A
```

## 📁 Project Structure
```
chatbot-service/
├─ api/
│  ├─ __init__.py
│  └─ app.py                 # FastAPI server (health + /chat)
├─ cli/
│  ├─ ingest.py              # Ingest docs in data/raw → Chroma
│  └─ eval.py                # Optional local Q&A CLI
├─ src/
│  └─ rag/
│     ├─ __init__.py
│     ├─ config.py           # .env settings
│     ├─ chunking.py         # loaders + splitters
│     ├─ embeddings/
│     │  └─ gemini.py        # text-embedding-004 (+ EMBED_DIM)
│     ├─ vectorstores/
│     │  └─ chroma_store.py  # add + query_by_vector
│     ├─ retrieval.py        # embed_query + top-k
│     ├─ prompting.py        # prompt template
│     ├─ generator.py        # gemini generateContent
│     └─ pipeline.py         # end-to-end answer()
├─ data/
│  └─ raw/                   # Put PDFs/TXT here (gitignored)
├─ var/
│  └─ chroma_db/             # Chroma persistent store (gitignored)
├─ requirements.txt
└─ README.md
```

## 🚀 Quickstart
### 1) Environment
```
python -m venv .venv
source .venv/bin/activate         # Windows: .\.venv\Scripts\Activate.ps1
pip install -r requirements.txt
```

**Create `.env`**
```
GOOGLE_API_KEY=your_key_here
GEN_MODEL=gemini-2.5-flash
EMBED_MODEL=models/text-embedding-004
EMBED_DIM=768
CHROMA_DIR=var/chroma_db
COLLECTION=docs
TOP_K=5
```

> Keep EMBED_DIM consistent across ingest and query. Changing it requires rebuilding the DB.

### 2) Ingest documents

Put PDFs/TXT into `data/raw/`, then:
```
python -m cli.ingest
```

### 3) Run the API
```
uvicorn api.app:app --host localhost --port 8080 --reload
```

Health check:
```
curl http://localhost:8080/healthz
```

## 🧭 API
`POST /chat`

Request
Ask multiple language (vi-VN for Viet Nam, en-US for English)
```
{
  "message": "Explain the architecture.",
  "k": 4,
  "lang": "en-US"
}
```

```
Response

{
  "answer": "A concise, grounded explanation...",
  "citations": [
    { "rank": 1, "id": "Topic.pdf-0", "source": "Topic.pdf" }
  ]
}
```

`GET /healthz`

Returns `{"status":"ok"}`

## ⚙️ Configuration
| Variable | Example | Notes |
| --- | --- | --- |
| `GOOGLE_API_KEY` | `sk-...` | Required |
| `GEN_MODEL` | `gemini-2.5-flash` | Fallbacks supported |
| `EMBED_MODEL` | `models/text-embedding-004` | Keep models/ prefix |
| `EMBED_DIM` | `768` | Must match collection dimensionality |
| `CHROMA_DIR` | `var/chroma_db` | Persistent store path |
| `COLLECTION` | `docs` | Chroma collection name |
| `TOP_K` | `5` | Retrieval depth |

## 🔁 Model fallback (optional)

If a quota error occurs:

1. Try `gemini-2.5-flash-lite`

2. Then `gemini-2.5-pro` (if your key has access)

This can be automated inside `generator.py` with a try -> fallback flow.


