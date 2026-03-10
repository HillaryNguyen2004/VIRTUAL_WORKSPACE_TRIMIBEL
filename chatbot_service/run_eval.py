import os, time, json, re, httpx
from dotenv import load_dotenv
from langfuse import Langfuse, observe
from langfuse.openai import OpenAI

load_dotenv()

DATASET_NAME = os.getenv("DATASET_NAME", "eval/general_knowledge_structured")
MODEL_NAME = os.environ["MODEL_NAME"]
OPENAI_BASE_URL = os.environ["OPENAI_BASE_URL"]
OPENAI_API_KEY = os.getenv("OPENAI_API_KEY", "local")

client = OpenAI(base_url=OPENAI_BASE_URL, api_key=OPENAI_API_KEY)

# Increase connect timeout to avoid TLS handshake timeout
langfuse = Langfuse(
    public_key=os.environ["LANGFUSE_PUBLIC_KEY"],
    secret_key=os.environ["LANGFUSE_SECRET_KEY"],
    base_url=os.environ.get("LANGFUSE_BASE_URL", "https://cloud.langfuse.com"),
    httpx_client=httpx.Client(
        timeout=httpx.Timeout(connect=30, read=120, write=30, pool=120)
    ),
)

langfuse.auth_check()
dataset = langfuse.get_dataset(DATASET_NAME)

SYSTEM_JSON_ONLY = """You must return ONLY a single valid JSON object.
No markdown. No code blocks. No explanations.
Schema: {"final_answer":"string","confidence":0.0}
"""

def _iter_json_objects(text: str):
    """Extract candidate JSON objects using brace counting (more reliable than regex)."""
    if not text:
        return
    start = None
    depth = 0
    for i, ch in enumerate(text):
        if ch == "{":
            if depth == 0:
                start = i
            depth += 1
        elif ch == "}":
            if depth > 0:
                depth -= 1
                if depth == 0 and start is not None:
                    yield text[start:i+1]
                    start = None

def extract_json(text: str):
    """Prefer JSON object containing final_answer/confidence if present."""
    if text is None:
        return None
    t = text.strip()

    # strict parse
    try:
        obj = json.loads(t)
        if isinstance(obj, dict):
            return obj
    except Exception:
        pass

    best = None
    for cand in _iter_json_objects(t):
        try:
            obj = json.loads(cand)
            if not isinstance(obj, dict):
                continue
            # Prefer the wrapper schema
            if "final_answer" in obj and "confidence" in obj:
                return obj
            best = best or obj
        except Exception:
            continue
    return best

def normalize_answer(s: str) -> str:
    return re.sub(r"\s+", " ", (s or "").strip())

def score_must_include(must_include, final_answer: str) -> float:
    if not must_include:
        return 1.0
    hay = (final_answer or "").lower()
    hits = sum(1 for kw in must_include if str(kw).lower() in hay)
    return hits / len(must_include)

@observe
def run_one(prompt: str):
    # Add system message to reduce “Python code” style answers
    messages = [
        {"role": "system", "content": SYSTEM_JSON_ONLY},
        {"role": "user", "content": prompt},
    ]
    t0 = time.time()
    resp = client.chat.completions.create(
        model=MODEL_NAME,
        messages=messages,
        temperature=0,
        # Optional: helps some models stop early (not guaranteed)
        stop=["```", "\n\n```"],
    )
    out = resp.choices[0].message.content or ""
    latency_ms = (time.time() - t0) * 1000
    langfuse.update_current_trace(input=prompt, output=out)
    return out, latency_ms

def main():
    run_name = f"{MODEL_NAME}-{time.strftime('%Y-%m-%dT%H:%M:%S')}"
    items = list(dataset.items)
    print(f"Loaded {len(items)} items from {DATASET_NAME}")

    for item in items:
        if not isinstance(item.input, str):
            continue

        # Retry around Langfuse API calls (TLS handshake/connect timeouts)
        for attempt in range(3):
            try:
                with item.run(
                    run_name=run_name,
                    run_description="general knowledge structured benchmark",
                    run_metadata={"model": MODEL_NAME, "base_url": OPENAI_BASE_URL, "dataset": DATASET_NAME},
                ) as root_span:
                    raw_out, latency_ms = run_one(item.input)

                    root_span.score_trace(name="latency_ms", value=float(latency_ms))

                    parsed = extract_json(raw_out)
                    json_ok = 1.0 if isinstance(parsed, dict) else 0.0
                    root_span.score_trace(name="json_ok", value=float(json_ok))

                    if not isinstance(parsed, dict):
                        break

                    final_answer = parsed.get("final_answer", None)
                    confidence = parsed.get("confidence", None)

                    schema_ok = 1.0 if isinstance(final_answer, str) and isinstance(confidence, (int, float)) else 0.0
                    root_span.score_trace(name="schema_ok", value=float(schema_ok))

                    exp = item.expected_output or {}
                    if isinstance(exp, str):
                        exp = {"final_answer": exp}

                    if isinstance(exp, dict) and isinstance(exp.get("final_answer"), str) and isinstance(final_answer, str):
                        expected = normalize_answer(exp["final_answer"])
                        got = normalize_answer(final_answer)
                        root_span.score_trace(name="final_answer_exact", value=1.0 if got == expected else 0.0)

                    if isinstance(exp, dict) and isinstance(exp.get("must_include"), list) and isinstance(final_answer, str):
                        hit_rate = score_must_include(exp["must_include"], final_answer)
                        root_span.score_trace(name="must_include_hit_rate", value=float(hit_rate))

                break  # success, exit retry loop

            except (httpx.ConnectTimeout, httpx.ReadTimeout, httpx.TimeoutException) as e:
                if attempt == 2:
                    raise
                backoff = 2 ** attempt
                print(f"[warn] Langfuse timeout, retrying in {backoff}s... ({e})")
                time.sleep(backoff)

    langfuse.flush()
    print("Done. Run name:", run_name)

if __name__ == "__main__":
    main()