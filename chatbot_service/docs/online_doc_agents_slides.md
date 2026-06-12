# Online Document, Summary & Search Agents

---

## Title

**Online Document, Summary & Search Agents**

- Presenter: Your Name
- Date: May 11, 2026

Notes: Quick one-line intro; say what audience will learn.

---

## Agenda

- Problem: documents too many, hard to find
- Online Document Agent: ingest & manage
- Summary Agent: extractive/abstractive summaries
- Search Agent: semantic retrieval & RAG
- Architecture, tools, demos, next steps

Notes: Walk through agenda in 20s.

---

## Problem: Why agents are needed

- Large volumes of docs across formats (PDF, DOCX, HTML)
- Manual reading is slow and inconsistent
- Information is fragmented and hard to update
- Users need concise answers and source links

Notes: Give a short real-world example (support, legal, product docs).

---

## Online Document Agent — Purpose

- Ingest online documents (URLs, cloud drives, uploads)
- Normalize and extract text + metadata
- Index for retrieval and downstream tasks
- Keep documents synced and versioned

Notes: Emphasize automation and continuous sync.

---

## Online Document Agent — Components

- Connectors: web scrapers, APIs, cloud storage loaders
- Parsers: HTML, PDF, DOCX, OCR pipeline
- Preprocessor: cleaning, chunking, metadata tagging
- Storage: vector DB + metadata DB
- Orchestration: scheduling, change detection

Notes: Mention common libraries (BeautifulSoup, Apache Tika).

---

## Summary Agent — Purpose

- Produce concise, context-aware summaries
- Support extractive & abstractive modes
- Generate TL;DR, bullet points, and Q&A-ready summaries
- Attach provenance (source links + spans)

Notes: Stress provenance for trust and audits.

---

## Summary Agent — Approach

- Input: chunks + metadata + context window
- Option A (Extractive): rank and return key passages
- Option B (Abstractive): LLM condense with citations
- Hybrid: extract then rewrite for fluency
- Output: summary, highlights, citations, confidence score

Notes: Short pros/cons: extractive = precise, abstractive = fluent.

---

## Search Agent — Purpose

- Answer user queries over the document corpus
- Combine semantic search (embeddings) + lexical filters
- Support RAG: fetch context → generate answer → cite sources

Notes: Explain why semantic search improves recall.

---

## Search Agent — Components

- Embeddings: encode documents & queries
- Vector store: fast nearest-neighbor (FAISS/PGVector/Weaviate)
- Retriever: top-k + filtering (date, author, doc type)
- Reranker: BM25/ML ranker or cross-encoder
- Response builder: RAG assembly + citation formatting

Notes: Mention latency vs accuracy tradeoffs.

---

## Combined Architecture (flow)

1. Ingest (Online Document Agent) → parse & chunk
2. Store embeddings + metadata in vector/relational DB
3. Summary Agent creates summaries & updates metadata
4. Search Agent retrieves chunks → RAG LLM generates answers
5. UI returns answer + highlighted sources + links

Notes: Suggest a small diagram on slide if possible.

---

## Tools & Tech Stack (examples)

- Document loaders: Apache Tika, pdfplumber, mammoth
- Embeddings: OpenAI, Mistral, Cohere, Hugging Face models
- Vector DBs: FAISS, Milvus, Weaviate, Pinecone, PGVector
- Orchestration: Airflow, Prefect, cron, serverless functions
- Frameworks: LangChain, LlamaIndex, Haystack

Notes: Offer tradeoffs: managed vs self-hosted.

---

## Metrics & Evaluation

- Retrieval: recall@k, MRR
- Summaries: ROUGE, human eval, factuality checks
- Latency & cost per query
- User satisfaction / task completion

Notes: Recommend small user study after prototype.

---

## Example Use Cases

- Customer support: answer from KB with citations
- Legal: find precedents, summarize contracts
- Research: ingest papers, surface key findings
- Product: changelogs, migration guides, quick onboarding

Notes: Pick one use case and walk through live demo.

---

## Demo / Next Steps

- Prototype: ingest small dataset → searchable demo
- Deliverables: dataset, vector DB, simple UI, slides
- Want export to PPTX, speaker notes, or a runnable demo?

Notes: Offer to export slides to PPTX or create a demo repo.

---

## Thank you

- Questions?
- Contact: your.email@example.com

Notes: Close and invite feedback.
