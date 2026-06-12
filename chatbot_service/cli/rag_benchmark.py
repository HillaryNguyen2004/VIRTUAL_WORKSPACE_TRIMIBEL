import os
import time
import json
import argparse
import sys
import re
from pathlib import Path
from typing import List, Dict

# =========================
# PATH SETUP
# =========================
PROJECT_ROOT = Path(__file__).resolve().parents[1]
if str(PROJECT_ROOT) not in sys.path:
    sys.path.insert(0, str(PROJECT_ROOT))

from src.rag.config import settings
from src.rag.pipeline import answer
from src.rag.memory import clear_memory

os.environ["ANONYMIZED_TELEMETRY"] = "False"

# =========================
# TEST CASES (FIXED)
# =========================
TEST_CASES = [

    # ── Trend queries ─────────────────────────────────────────
    {
        "question": "Which employees are declining?",
        "expected_ids": ["6","7","8","13","17","18","19","20","25","30"],
        "match_type": "f1",
    },
    {
        "question": "Which employees are improving?",
        "expected_ids": ["2","4","5","10","11","12","14","16","26","27","28"],
        "match_type": "f1",
    },
    {
        "question": "Which employees are stable?",
        "expected_ids": ["1","3","9","15","21","22","23","24","29"],
        "match_type": "f1",
    },

    # ── Predicted-level queries ───────────────────────────────
    {
        "question": "Which employees are predicted High performers?",
        "expected_ids": [
            "1","2","3","4","5","6","8","10","11","12",
            "15","16","17","21","25","26","27","29"
        ],
        "match_type": "f1",
    },
    {
        "question": "Who are predicted Medium performers?",
        "expected_ids": ["9","13","14","18","19","20","22","23","24","28"],
        "match_type": "f1",
    },
    {
        "question": "Who are predicted Low / needs urgent attention?",
        "expected_ids": ["7","30"],
        "match_type": "exact",
    },

    # ── At-risk: declining trend + active alerts ──────────────
    {
        "question": "Which employees are at risk?",
        "expected_ids": ["6","7","8","13","17","20","25","30"],
        "match_type": "f1",
    },

    # ── High performers with a declining trend ────────────────
    {
        "question": "Which high performers are declining?",
        "expected_ids": ["6","8","17","25"],
        "match_type": "f1",
    },

    # ── Overwork signal ───────────────────────────────────────
    {
        "question": "Who is working more than 9 hours a day?",
        "expected_ids": ["3","9","20"],
        "match_type": "f1",
    },

    # ── Team overview (keyword check) ─────────────────────────
    {
        "question": "Give team overview",
        "expected_keywords": ["declining", "improving", "stable", "30"],
        "match_type": "any",
    },

    # ── Vietnamese equivalents ────────────────────────────────
    {
        "question": "Những nhân viên nào đang giảm năng suất?",
        "expected_ids": ["6","7","8","13","17","18","19","20","25","30"],
        "match_type": "f1",
    },
    {
        "question": "Ai đang tăng năng suất?",
        "expected_ids": ["2","4","5","10","11","12","14","16","26","27","28"],
        "match_type": "f1",
    },
    {
        "question": "Nhân viên nào cần can thiệp khẩn cấp?",
        "expected_ids": ["7","30"],
        "match_type": "exact",
    },

    # ── Specific employee lookup ──────────────────────────────
    {
        "question": "Tell me about Tran Hanh",
        "expected_ids": ["30"],
        "match_type": "f1",
    },
    {
        "question": "How is Le Giang performing?",
        "expected_ids": ["21"],
        "match_type": "f1",
    },

    # ── Out-of-scope (hallucination guard) ────────────────────
    {
        "question": "Which employees got promoted?",
        "expected_keywords": [
            "no information", "not covered", "not mentioned",
            "don't know", "no data", "not available",
            "does not", "not in", "cannot find",
        ],
        "match_type": "any",
    },
]

# =========================
# EXTRACTION
# =========================
def extract_ids_from_answer(answer: str) -> set:
    """
    Extract IDs from text:
    - (ID 4)
    - ID 4
    """
    matches = re.findall(r'\bID\s*(\d+)\b', answer, re.IGNORECASE)
    return set(matches)


# =========================
# SCORING
# =========================
def score_answer(answer: str, case: Dict):
    answer_lower = answer.lower()

    # =========================
    # ID-BASED SCORING
    # =========================
    if "expected_ids" in case:
        expected = set(case["expected_ids"])
        predicted = extract_ids_from_answer(answer)

        hits = len(expected & predicted)

        # Debug (keep this!)
        print("Expected:", expected)
        print("Predicted:", predicted)
        print("Hits:", expected & predicted)

        match_type = case.get("match_type", "f1")

        # ---- EXACT ----
        if match_type == "exact":
            is_match = predicted == expected
            return {
                "precision": 1.0 if is_match else 0.0,
                "recall": 1.0 if is_match else 0.0,
                "f1": 1.0 if is_match else 0.0,
                "predicted_ids": list(predicted)
            }

        # ---- SUBSET ----
        if match_type == "subset":
            is_subset = predicted.issubset(expected)
            return {
                "precision": 1.0 if is_subset else 0.0,
                "recall": 1.0 if predicted else 0.0,
                "f1": 1.0 if is_subset else 0.0,
                "predicted_ids": list(predicted)
            }

        # ---- F1 (DEFAULT) ----
        precision = hits / max(len(predicted), 1)
        recall = hits / max(len(expected), 1)
        f1 = (2 * precision * recall) / max((precision + recall), 1e-6)

        return {
            "precision": precision,
            "recall": recall,
            "f1": f1,
            "predicted_ids": list(predicted)
        }

    # =========================
    # KEYWORD SCORING
    # =========================
    if "expected_keywords" in case:
        expected = case["expected_keywords"]
        hits = sum(1 for kw in expected if kw in answer_lower)
        score = hits / len(expected)

        return {
            "precision": score,
            "recall": score,
            "f1": score
        }

    return {"precision": 0, "recall": 0, "f1": 0}


# =========================
# GROUNDEDNESS
# =========================
def groundedness(answer: str, citations: List[Dict]) -> float:
    if not citations:
        return 0.0
    return min(len(citations) / 5, 1.0)

def warmup(workspace_id: str, model_name: str):
    print(f"  Warming up {model_name}...")
    try:
        answer(
            user_q="hello",
            k=1,
            workspace_id=workspace_id,
            user_role="admin",
            user_id="warmup",
        )
        clear_memory("warmup")
        print("  Warm up done.")
    except Exception as e:
        print(f"  Warm up failed: {e}")

# =========================
# BENCHMARK
# =========================
def run_benchmark(k: int, workspace_id: str):
    # Switch model
    model_name = settings.ollama_model

    print(f"\n{'='*60}")
    print(f"Model    : {model_name}")
    print(f"k        : {k}")
    print(f"Workspace: {workspace_id}")
    print(f"{'='*60}")

    warmup(workspace_id, model_name)
    
    results = []

    total_f1 = 0
    total_latency = 0
    total_grounded = 0

    for case in TEST_CASES:
        clear_memory("benchmark")
        print(f"\n=== {case['question']} ===")

        t0 = time.time()

        answer_text, citations, usage = answer(
            user_q=case["question"],
            k=k,
            workspace_id=workspace_id,
            user_role="admin",
            user_id="benchmark",
        )

        latency = time.time() - t0

        s = score_answer(answer_text, case)
        g = groundedness(answer_text, citations)

        total_f1 += s["f1"]
        total_latency += latency
        total_grounded += g

        print("Answer:", answer_text)
        print("Score:", s)
        print("Grounded:", g)
        print("Latency:", latency)

        results.append({
            "question": case["question"],
            "answer": answer_text,
            "score": s,
            "groundedness": g,
            "latency": latency,
            "num_citations": len(citations),
        })

    summary = {
        "k": k,
        "workspace": workspace_id,
        "avg_f1": total_f1 / len(TEST_CASES),
        "avg_groundedness": total_grounded / len(TEST_CASES),
        "avg_latency": total_latency / len(TEST_CASES),
        "details": results
    }

    return summary


# =========================
# CLI
# =========================
def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--k", type=int, default=5)
    parser.add_argument("--workspace", default="productivity")
    parser.add_argument("--output", default="rag_result.json")

    args = parser.parse_args()

    result = run_benchmark(args.k, args.workspace)

    with open(args.output, "w") as f:
        json.dump(result, f, indent=2)

    print("\nSaved:", args.output)


if __name__ == "__main__":
    main()