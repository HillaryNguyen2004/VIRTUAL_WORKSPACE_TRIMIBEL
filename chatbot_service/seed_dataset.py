import os, httpx
from langfuse import Langfuse
from dotenv import load_dotenv

load_dotenv()

langfuse = Langfuse(
    public_key=os.environ["LANGFUSE_PUBLIC_KEY"],
    secret_key=os.environ["LANGFUSE_SECRET_KEY"],
    base_url=os.environ.get("LANGFUSE_BASE_URL", "https://cloud.langfuse.com"),
    httpx_client=httpx.Client(timeout=60),
)
langfuse.auth_check()

DATASET_NAME = "eval/general_knowledge_structured"
langfuse.create_dataset(name=DATASET_NAME)

# Output rule for all prompts:
# Model must return ONLY JSON: {"final_answer":"...", "confidence":0.0}
# This makes scoring and parsing consistent.
test_cases = [
    # ---------- Deterministic math / conversions (exact scoring) ----------
    {
        "input": 'Return ONLY JSON: {"final_answer":"string","confidence":0.0}. Compute 17 * 23.',
        "expected_output": {"final_answer": "391"},
    },
    {
        "input": 'Return ONLY JSON: {"final_answer":"string","confidence":0.0}. Compute (125 - 37) + 19.',
        "expected_output": {"final_answer": "107"},
    },
    {
        "input": 'Return ONLY JSON: {"final_answer":"string","confidence":0.0}. What is 15% of 240?',
        "expected_output": {"final_answer": "36"},
    },
    {
        "input": 'Return ONLY JSON: {"final_answer":"string","confidence":0.0}. Convert 2.5 hours to minutes.',
        "expected_output": {"final_answer": "150"},
    },
    {
        "input": 'Return ONLY JSON: {"final_answer":"string","confidence":0.0}. Convert 7.2 km to meters.',
        "expected_output": {"final_answer": "7200"},
    },
    {
        "input": 'Return ONLY JSON: {"final_answer":"string","confidence":0.0}. If a price is 120 and discounted by 15%, what is the new price?',
        "expected_output": {"final_answer": "102"},
    },

    # ---------- Sorting / simple logic (exact scoring) ----------
    {
        "input": 'Return ONLY JSON: {"final_answer":"string","confidence":0.0}. Sort these numbers ascending: 9, 1, 12, 3. Output as comma-separated numbers.',
        "expected_output": {"final_answer": "1,3,9,12"},
    },
    {
        "input": 'Return ONLY JSON: {"final_answer":"string","confidence":0.0}. True/False: If all A are B and all B are C, then all A are C.',
        "expected_output": {"final_answer": "True"},
    },

    # ---------- Information extraction (exact scoring if you standardize format) ----------
    {
        "input": (
            'Return ONLY JSON: {"final_answer":"string","confidence":0.0}. '
            "Extract invoice_id, amount, due_date from this text and return them as a compact JSON string inside final_answer: "
            'Text: "Invoice 1042, total $318.50 due 2026-03-10." '
            'Required final_answer format: {"invoice_id":"...","amount":"...","due_date":"..."}'
        ),
        "expected_output": {"final_answer": '{"invoice_id":"1042","amount":"318.50","due_date":"2026-03-10"}'},
    },
    {
        "input": (
            'Return ONLY JSON: {"final_answer":"string","confidence":0.0}. '
            "Extract name and meeting_time. "
            'Text: "Meeting with Linh at 14:30 tomorrow." '
            'Assume today is 2026-03-02. Output final_answer as {"name":"...","meeting_time":"YYYY-MM-DD HH:MM"}'
        ),
        "expected_output": {"final_answer": '{"name":"Linh","meeting_time":"2026-03-03 14:30"}'},
    },

    # ---------- General knowledge (harder to exact-score; use must_include) ----------
    # These are still useful, but compare using keyword-based scoring.
    {
        "input": 'Return ONLY JSON: {"final_answer":"string","confidence":0.0}. What is the capital of France?',
        "expected_output": {"must_include": ["Paris"]},
    },
    {
        "input": 'Return ONLY JSON: {"final_answer":"string","confidence":0.0}. Who wrote "1984"?',
        "expected_output": {"must_include": ["George Orwell", "Orwell"]},
    },
    {
        "input": 'Return ONLY JSON: {"final_answer":"string","confidence":0.0}. What is H2O commonly called?',
        "expected_output": {"must_include": ["water"]},
    },

    # ---------- Vietnamese (good to test multilingual behavior) ----------
    {
        "input": 'Chỉ trả về JSON: {"final_answer":"string","confidence":0.0}. 3 * 19 = ?',
        "expected_output": {"final_answer": "57"},
    },
    {
        "input": 'Chỉ trả về JSON: {"final_answer":"string","confidence":0.0}. Thủ đô của Nhật Bản là gì?',
        "expected_output": {"must_include": ["Tokyo", "Tōkyō"]},
    },
]

for i, case in enumerate(test_cases, start=1):
    langfuse.create_dataset_item(
        dataset_name=DATASET_NAME,
        input=case["input"],
        expected_output=case["expected_output"],
        metadata={"case_id": f"general_{i:03d}", "type": "general_structured"},
    )

langfuse.flush()
print("Seeded dataset:", DATASET_NAME)