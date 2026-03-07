import os, time, json, httpx
from langfuse import Langfuse, observe
from langfuse.openai import OpenAI
from api.schemas import ChatResponse
from src.rag.config import settings

# Adjust these as needed for dataset and model
DATASET_NAME = settings.dataset_name
MODEL_NAME = settings.ollama_model
OPENAI_BASE_URL = settings.openai_base_url
OPENAI_API_KEY = settings.openai_api_key

# Initialize OpenAI client and Langfuse
client = OpenAI(base_url=OPENAI_BASE_URL, api_key=OPENAI_API_KEY)

langfuse = Langfuse(
    public_key=settings.langfuse_public_key,
    secret_key=settings.langfuse_secret_key,
    base_url=settings.langfuse_base_url,
    httpx_client=httpx.Client(timeout=httpx.Timeout(connect=60, read=300, write=60, pool=300)),
)
langfuse.auth_check()
dataset = langfuse.get_dataset(DATASET_NAME)

# System prompt for RAG evaluation - instruct model to use only provided context and return structured JSON
def extract_json(text: str):
    if not text:
        return None
    t = text.strip()

    try:
        obj = json.loads(t)
        return obj if isinstance(obj, dict) else None
    except Exception:
        pass

    # brace scan: take first JSON object
    start = None
    depth = 0
    for i, ch in enumerate(t):
        if ch == "{":
            if depth == 0:
                start = i
            depth += 1
        elif ch == "}":
            if depth > 0:
                depth -= 1
                if depth == 0 and start is not None:
                    cand = t[start:i+1]
                    try:
                        obj = json.loads(cand)
                        return obj if isinstance(obj, dict) else None
                    except Exception:
                        return None
    return None

# Simple heuristic scoring function to check if expected facts are present in the answer (for partial credit)
def score_response_facts(facts, answer: str):
    if not facts:
        return None
    ans = (answer or "").lower()
    hits = sum(1 for f in facts if str(f).lower() in ans)
    return hits / len(facts)

# System prompt for RAG evaluation
@observe
def run_one(message: str, context: list):
    allowed = [{"id": c["id"], "source": c.get("source", "")} for c in (context or [])]
    ctx_text = "\n".join([f'- id={c["id"]} source={c.get("source","")} text="{c["text"]}"' for c in context])

    system = f"""You are a RAG assistant. Use ONLY the provided CONTEXT.
        Return ONLY valid JSON matching exactly:
        {{"answer":"string","citations":[{{"rank":1,"id":"string","source":"string"}}]}}
        Rules:
        - If answer not found in CONTEXT: answer="Not found in provided context.", citations=[]
        - citations[*].id MUST match one of the allowed ids exactly
        - citations[*].source MUST match the corresponding allowed source exactly
        - rank must start at 1 and increase by 1
        Allowed citations: {json.dumps(allowed)}
        No markdown. No code blocks. No extra keys.
    """

    messages = [
        {"role": "system", "content": system},
        {"role": "user", "content": f"QUESTION: {message}\n\nCONTEXT:\n{ctx_text}"},
    ]

    t0 = time.time()
    resp = client.chat.completions.create(model=MODEL_NAME, messages=messages, temperature=0)
    out = resp.choices[0].message.content or ""
    latency_ms = (time.time() - t0) * 1000
    langfuse.update_current_trace(input={"message": message}, output=out)
    return out, latency_ms

# main evaluation loop
def main():
    run_name = f"{MODEL_NAME}-{time.strftime('%Y-%m-%dT%H:%M:%S')}"
    items = list(dataset.items)
    print(f"Loaded {len(items)} items from {DATASET_NAME}")

    for item in items:
        inp = item.input or {}
        message = inp.get("message") or inp.get("question")
        context = inp.get("context", [])

        with item.run(
            run_name=run_name,
            run_description="RAG benchmark",
            run_metadata={"model": MODEL_NAME, "base_url": OPENAI_BASE_URL},
        ) as root_span:
            raw_out, latency_ms = run_one(message, context)

            root_span.score_trace(name="latency_ms", value=float(latency_ms))

            parsed = extract_json(raw_out)
            json_ok = 1.0 if isinstance(parsed, dict) else 0.0
            root_span.score_trace(name="json_ok", value=float(json_ok))
            if not json_ok:
                continue

            # schema validate
            try:
                cr = ChatResponse.model_validate(parsed)
                schema_ok = 1.0
            except Exception:
                schema_ok = 0.0
                root_span.score_trace(name="schema_ok", value=float(schema_ok))
                continue

            root_span.score_trace(name="schema_ok", value=float(schema_ok))

            # citation checks
            allowed_map = {c["id"]: c.get("source", "") for c in context}
            cites = cr.citations or []

            if not cites:
                citation_id_in_context = 0.0
                citation_source_match = 0.0
            else:
                id_ok = 0
                src_ok = 0
                for c in cites:
                    if c.id in allowed_map:
                        id_ok += 1
                        if c.source == allowed_map[c.id]:
                            src_ok += 1
                citation_id_in_context = id_ok / len(cites)
                citation_source_match = src_ok / len(cites)

            root_span.score_trace(name="citation_id_in_context", value=float(citation_id_in_context))
            root_span.score_trace(name="citation_source_match", value=float(citation_source_match))

            # correctness proxy
            facts = (item.expected_output or {}).get("response_facts", [])
            hit_rate = score_response_facts(facts, cr.answer)
            if hit_rate is not None:
                root_span.score_trace(name="response_facts_hit_rate", value=float(hit_rate))

    langfuse.flush()
    print("Done. Run name:", run_name)

if __name__ == "__main__":
    main()