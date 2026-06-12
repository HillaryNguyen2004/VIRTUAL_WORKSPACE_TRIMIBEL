"""
Multi-seed experiment runner — for thesis variance reporting.

Runs train + evaluate across 5 seeds, then aggregates:
  • Mean ± std for every metric
  • A LaTeX-ready summary table
  • Comparison table for the thesis

Usage:
    python3 run_5_seeds.py
"""

import json
import os
import statistics
import subprocess
import sys
from datetime import datetime

SEEDS  = [42, 43, 44, 45, 46]
CUTOFF = "2026-04-29"   # lock this — change only if your data window changes


def run(cmd):
    print(f"\n{'='*60}\n  $ {' '.join(cmd)}\n{'='*60}")
    r = subprocess.run(cmd, check=False)
    if r.returncode != 0:
        print(f"⚠️  Command failed (returncode={r.returncode})")
        sys.exit(r.returncode)


def aggregate():
    """Read every runs/seed_*/metrics.json and produce a summary."""
    all_metrics = []
    for s in SEEDS:
        p = f"runs/seed_{s}/metrics.json"
        if not os.path.exists(p):
            print(f"⚠️  {p} missing — skipping")
            continue
        with open(p) as f:
            all_metrics.append(json.load(f))

    if not all_metrics:
        print("No metrics found. Did training fail?")
        return

    # Pull each metric across seeds
    keys_to_aggregate = [
        ("accuracy",        "Test accuracy (%)"),
        ("macroF1",         "Macro F1"),
        ("f1Low",           "F1 — Low"),
        ("f1Med",           "F1 — Medium"),
        ("f1High",          "F1 — High"),
        ("mae",             "MAE (points)"),
        ("rmse",            "RMSE (points)"),
        ("naiveAccuracy",   "Naive accuracy (%)"),
        ("uplift_accuracy", "Uplift over naive (pp)"),
    ]

    summary = {}
    for k, label in keys_to_aggregate:
        vals = [m[k] for m in all_metrics if k in m]
        if not vals: continue
        summary[k] = {
            "label":  label,
            "values": vals,
            "mean":   round(statistics.mean(vals), 3),
            "std":    round(statistics.stdev(vals), 3) if len(vals) > 1 else 0.0,
            "min":    round(min(vals), 3),
            "max":    round(max(vals), 3),
        }

    # Aggregated summary file
    out = {
        "seeds":     SEEDS,
        "n_runs":    len(all_metrics),
        "cutoff":    CUTOFF,
        "summary":   summary,
        "perRun":    [{"seed": m["seed"],
                       "accuracy": m["accuracy"],
                       "macroF1":  m["macroF1"],
                       "mae":      m["mae"],
                       "rmse":     m["rmse"]} for m in all_metrics],
        "generated": datetime.utcnow().isoformat() + "Z",
    }
    os.makedirs("runs", exist_ok=True)
    with open("runs/summary.json", "w") as f:
        json.dump(out, f, indent=2)

    # Pretty-print
    print(f"\n{'=' * 60}")
    print(f"  AGGREGATED RESULTS — {len(all_metrics)} runs")
    print(f"{'=' * 60}\n")

    print(f"  {'Metric':<28} {'Mean':>10} {'Std':>10} {'Min':>10} {'Max':>10}")
    print(f"  {'-' * 70}")
    for k, info in summary.items():
        print(f"  {info['label']:<28} {info['mean']:>10} {info['std']:>10} {info['min']:>10} {info['max']:>10}")

    # Per-seed table
    print(f"\n  Per-seed results:")
    print(f"  {'Seed':<8} {'Accuracy %':>12} {'Macro F1':>10} {'MAE':>8} {'RMSE':>8}")
    print(f"  {'-' * 50}")
    for r in out["perRun"]:
        print(f"  {r['seed']:<8} {r['accuracy']:>12.2f} {r['macroF1']:>10.3f} {r['mae']:>8.2f} {r['rmse']:>8.2f}")

    # LaTeX-ready paragraph
    acc      = summary['accuracy']
    f1       = summary['macroF1']
    mae      = summary['mae']
    rmse     = summary['rmse']
    uplift   = summary['uplift_accuracy']

    print(f"\n  Thesis-ready sentence (drop into Section 4.3):")
    print(f"  " + "─" * 60)
    print(f"  Across {len(all_metrics)} independent training runs (seeds "
          f"{', '.join(map(str, SEEDS[:len(all_metrics)]))}), the model achieved "
          f"{acc['mean']:.2f}% ± {acc['std']:.2f}% test accuracy, "
          f"macro F1 of {f1['mean']:.3f} ± {f1['std']:.3f}, "
          f"MAE of {mae['mean']:.2f} ± {mae['std']:.2f} points and RMSE "
          f"of {rmse['mean']:.2f} ± {rmse['std']:.2f} points on the predicted "
          f"score (E[score] = Σ P(class) × midpoint). The uplift over the "
          f"naive 'tomorrow = today' baseline averaged "
          f"{uplift['mean']:+.2f} ± {uplift['std']:.2f} percentage points.")
    print(f"  " + "─" * 60)

    print(f"\n✅ Aggregated summary → runs/summary.json")


# ════════════════════════════════════════════════════════════
# Main
# ════════════════════════════════════════════════════════════
if __name__ == "__main__":
    print(f"\n{'#' * 60}")
    print(f"#  5-SEED EXPERIMENT — seeds={SEEDS}")
    print(f"#  Cutoff: {CUTOFF}")
    print(f"{'#' * 60}\n")

    for s in SEEDS:
        run(["python3", "train_lstm_nextday_logged.py", "--seed", str(s), "--cutoff", CUTOFF])
        run(["python3", "evaluate_classifier_nextday_logged.py", "--seed", str(s), "--cutoff", CUTOFF])

    aggregate()