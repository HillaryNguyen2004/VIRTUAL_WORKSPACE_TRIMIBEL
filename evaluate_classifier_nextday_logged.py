"""
Evaluation — NEXT-DAY FORECAST (Thesis logging version)
========================================================

Adds to the standard evaluator:
  • Reads model artefacts from runs/seed_{SEED}/
  • Computes MAE & RMSE on E[score] = Σ P(class) × midpoint
  • Naive baseline comparison
  • Writes complete metrics.json

Usage:
    python3 evaluate_classifier_nextday_logged.py --seed 42
"""

import argparse
import json
import os
import sys
from datetime import date, datetime

import numpy as np
import pandas as pd
import joblib
from sqlalchemy import create_engine
from tensorflow.keras.models import load_model

sys.path.append('../etl')
from config import PG_CONFIG

# ════════════════════════════════════════════════════════════
# CLI
# ════════════════════════════════════════════════════════════
parser = argparse.ArgumentParser()
parser.add_argument('--seed', type=int, default=42)
parser.add_argument('--cutoff', type=str, default='2026-04-29')
args = parser.parse_args()

SEED            = args.seed
TRAINING_CUTOFF = date.fromisoformat(args.cutoff)
RUN_DIR         = f"runs/seed_{SEED}"

print(f"=" * 60)
print(f"  EVAL — seed={SEED}")
print(f"=" * 60)

# ════════════════════════════════════════════════════════════
# 1. Load model + scaler + baseline
# ════════════════════════════════════════════════════════════
model    = load_model(f"{RUN_DIR}/lstm_productivity.keras")
scaler   = joblib.load(f"{RUN_DIR}/scaler.pkl")
baseline = joblib.load(f"{RUN_DIR}/baseline.pkl")

FEATURES = [
    'user_id_norm', 'score_vs_baseline',
    'hours_worked', 'is_late', 'checked_in', 'had_day_off',
    'tasks_completed', 'avg_task_score', 'avg_task_percentage',
    'is_late_rate_7d', 'is_late_rate_14d',
    'checked_in_rate_7d', 'checked_in_rate_14d',
    'had_day_off_rate_7d', 'had_day_off_rate_14d',
    'has_task_signal', 'task_workload', 'checkin_streak',
    'score_yesterday', 'score_3d_ago', 'score_7d_ago',
    'score_delta_1d', 'score_delta_7d',
    'score_avg_7d', 'score_avg_14d', 'score_std_7d',
    'day_of_week',
]
TARGET   = 'productivity_score'
LOOKBACK = 14

# Class midpoints — used for E[score] and MAE/RMSE
CLASS_MIDPOINTS = np.array([25.0, 65.0, 90.0])

# ════════════════════════════════════════════════════════════
# 2. Load + prepare data (mirrors training exactly)
# ════════════════════════════════════════════════════════════
engine = create_engine(
    f"postgresql://{PG_CONFIG['user']}:{PG_CONFIG['password']}"
    f"@{PG_CONFIG['host']}:{PG_CONFIG['port']}/{PG_CONFIG['dbname']}"
)
df = pd.read_sql("""
    SELECT e.user_id, d.full_date,
           f.hours_worked, f.is_late, f.checked_in, f.had_day_off,
           f.tasks_completed, f.avg_task_score, f.avg_task_percentage,
           f.productivity_score
    FROM fact_employee_productivity f
    JOIN dim_employee e ON f.employee_sk = e.employee_sk
    JOIN dim_date     d ON f.date_sk     = d.date_sk
    ORDER BY e.user_id, d.full_date
""", engine)
df['full_date'] = pd.to_datetime(df['full_date'])
df = df[df['full_date'] <= pd.Timestamp(TRAINING_CUTOFF)]

# Personal baseline
df = df.merge(baseline, on='user_id', how='left')
df['score_vs_baseline'] = (
    (df['productivity_score'] - df['personal_mean'])
    / df['personal_std'].fillna(1).clip(lower=1)
)
user_ids = sorted(df['user_id'].unique())
uid_map  = {uid: i / len(user_ids) for i, uid in enumerate(user_ids)}
df['user_id_norm'] = df['user_id'].map(uid_map)

# Feature engineering — must mirror training exactly
df['is_late']     = df['is_late'].astype(int)
df['checked_in']  = df['checked_in'].astype(int)
df['had_day_off'] = df['had_day_off'].astype(int)

g = df.groupby('user_id', group_keys=False)
for col in ['is_late', 'checked_in', 'had_day_off']:
    df[f'{col}_rate_7d']  = g[col].transform(lambda x: x.shift(1).rolling(7,  min_periods=1).mean())
    df[f'{col}_rate_14d'] = g[col].transform(lambda x: x.shift(1).rolling(14, min_periods=1).mean())

df['has_task_signal'] = (
    (df['avg_task_score']      > 0) |
    (df['avg_task_percentage'] > 0) |
    (df['tasks_completed']     > 0)
).astype(int)
df['task_workload'] = df['tasks_completed'] + df['avg_task_percentage'] / 100.0

df['score_yesterday'] = g['productivity_score'].shift(1)
df['score_3d_ago']    = g['productivity_score'].shift(3)
df['score_7d_ago']    = g['productivity_score'].shift(7)
df['score_delta_1d']  = df['score_yesterday'] - df['score_3d_ago']
df['score_delta_7d']  = df['score_3d_ago']    - df['score_7d_ago']

df['score_avg_7d']  = g['productivity_score'].transform(lambda x: x.shift(1).rolling(7,  min_periods=1).mean())
df['score_avg_14d'] = g['productivity_score'].transform(lambda x: x.shift(1).rolling(14, min_periods=1).mean())
df['score_std_7d']  = g['productivity_score'].transform(lambda x: x.shift(1).rolling(7,  min_periods=1).std())

df['checkin_streak'] = df.groupby('user_id')['checked_in'].transform(
    lambda x: x.groupby((x != x.shift()).cumsum()).cumcount() + 1
) * df['checked_in']
df['day_of_week'] = df['full_date'].dt.dayofweek
df.fillna(0, inplace=True)

# Keep unscaled today-score table for naive baseline lookup
unscaled_today_score = df[['user_id', 'full_date', 'productivity_score']].copy()

# Scale
all_cols = FEATURES + [TARGET]
df[all_cols] = scaler.transform(df[all_cols])

# ════════════════════════════════════════════════════════════
# 3. Build sequences — next-day target
# ════════════════════════════════════════════════════════════
X_list, y_list, target_dates_list, today_dates_list, today_uids_list = [], [], [], [], []
for uid, grp in df.groupby('user_id'):
    grp = grp.sort_values('full_date').reset_index(drop=True)
    fv = grp[FEATURES].values
    tv = grp[TARGET].values
    ds = grp['full_date'].values
    if len(grp) < LOOKBACK + 1:
        continue
    for i in range(LOOKBACK - 1, len(grp) - 1):
        X_list.append(fv[i - LOOKBACK + 1 : i + 1, :])
        y_list.append(tv[i + 1])
        target_dates_list.append(ds[i + 1])
        today_dates_list.append(ds[i])
        today_uids_list.append(uid)

X = np.array(X_list)
y_scaled = np.array(y_list)
target_dates = pd.Series([pd.Timestamp(d) for d in target_dates_list])
today_dates  = pd.Series([pd.Timestamp(d) for d in today_dates_list])
today_uids   = pd.Series(today_uids_list)

val_end   = pd.Timestamp('2026-01-31')
test_mask = target_dates > val_end
X = X[test_mask]
y_scaled = y_scaled[test_mask]
today_dates_test = today_dates[test_mask].reset_index(drop=True)
today_uids_test  = today_uids[test_mask].reset_index(drop=True)

print(f"\nTest sequences: {len(X)}")

# ════════════════════════════════════════════════════════════
# 4. Predict
# ════════════════════════════════════════════════════════════
y_pred_probs = model.predict(X, verbose=0)
y_pred_idx   = np.argmax(y_pred_probs, axis=1)

# Real actual scores
target_idx = all_cols.index(TARGET)
score_min  = scaler.data_min_[target_idx]
score_max  = scaler.data_max_[target_idx]
actual_scores = y_scaled * (score_max - score_min) + score_min

# Class labels
def to_class_label(s):
    if s >= 80: return 'High'
    if s >= 50: return 'Medium'
    return 'Low'

actual_classes    = np.array([to_class_label(s) for s in actual_scores])
idx_to_class      = {0: 'Low', 1: 'Medium', 2: 'High'}
predicted_classes = np.array([idx_to_class[i] for i in y_pred_idx])

# ════════════════════════════════════════════════════════════
# 5. Confusion matrix + per-class metrics
# ════════════════════════════════════════════════════════════
classes = ['Low', 'Medium', 'High']
n   = len(classes)
cm  = np.zeros((n, n), dtype=int)
idx = {c: i for i, c in enumerate(classes)}
for a, p in zip(actual_classes, predicted_classes):
    cm[idx[a]][idx[p]] += 1

print("\n" + "=" * 50)
print("CONFUSION MATRIX (LSTM)")
print("=" * 50)
print(f"{'':>10}" + "".join(f"{'Pred ' + c:>12}" for c in classes))
for i, c in enumerate(classes):
    print(f"{'Act ' + c:>10}" + "".join(f"{cm[i][j]:>12}" for j in range(n)))

per_class = {}
print("\n" + "=" * 50)
print("PER-CLASS METRICS")
print("=" * 50)
print(f"{'Class':<10} {'Precision':>10} {'Recall':>10} {'F1':>10} {'Support':>10}")
print("-" * 50)
f1_scores = []
for i, c in enumerate(classes):
    tp = cm[i][i]; fp = cm[:, i].sum() - tp; fn = cm[i, :].sum() - tp
    precision = tp / (tp + fp) if (tp + fp) > 0 else 0.0
    recall    = tp / (tp + fn) if (tp + fn) > 0 else 0.0
    f1 = 2 * precision * recall / (precision + recall) if (precision + recall) > 0 else 0.0
    support = int(cm[i, :].sum())
    f1_scores.append(f1)
    per_class[c] = {
        'precision': round(precision, 4),
        'recall':    round(recall, 4),
        'f1':        round(f1, 4),
        'support':   support,
    }
    print(f"{c:<10} {precision:>10.3f} {recall:>10.3f} {f1:>10.3f} {support:>10}")

accuracy = float(np.diag(cm).sum() / cm.sum())
macro_f1 = float(np.mean(f1_scores))

# ════════════════════════════════════════════════════════════
# 6. MAE / RMSE on E[score] = Σ P(class) × midpoint
# ════════════════════════════════════════════════════════════
expected_scores = y_pred_probs @ CLASS_MIDPOINTS  # vectorised dot product
errors = expected_scores - actual_scores
mae    = float(np.mean(np.abs(errors)))
rmse   = float(np.sqrt(np.mean(errors ** 2)))

# Also report MAE for naive midpoint approach (for comparison)
midpoint_only = CLASS_MIDPOINTS[y_pred_idx]
errors_midpoint = midpoint_only - actual_scores
mae_midpoint  = float(np.mean(np.abs(errors_midpoint)))
rmse_midpoint = float(np.sqrt(np.mean(errors_midpoint ** 2)))

print("\n" + "=" * 50)
print("SCORE-ERROR METRICS")
print("=" * 50)
print(f"  E[score] approach:")
print(f"    MAE  = {mae:.2f} points")
print(f"    RMSE = {rmse:.2f} points")
print(f"  Argmax-midpoint approach (for reference):")
print(f"    MAE  = {mae_midpoint:.2f} points")
print(f"    RMSE = {rmse_midpoint:.2f} points")

# ════════════════════════════════════════════════════════════
# 7. Naive baseline
# ════════════════════════════════════════════════════════════
naive_lookup = unscaled_today_score.set_index(['user_id', 'full_date'])['productivity_score'].to_dict()
naive_classes = np.array([
    to_class_label(naive_lookup.get((uid, td), 0.0))
    for uid, td in zip(today_uids_test, today_dates_test)
])

cm_naive = np.zeros((n, n), dtype=int)
for a, p in zip(actual_classes, naive_classes):
    cm_naive[idx[a]][idx[p]] += 1

f1_naive = []
for i in range(n):
    tp = cm_naive[i][i]; fp = cm_naive[:, i].sum() - tp; fn = cm_naive[i, :].sum() - tp
    precision = tp / (tp + fp) if (tp + fp) > 0 else 0.0
    recall    = tp / (tp + fn) if (tp + fn) > 0 else 0.0
    f1 = 2 * precision * recall / (precision + recall) if (precision + recall) > 0 else 0.0
    f1_naive.append(f1)

accuracy_naive = float(np.diag(cm_naive).sum() / cm_naive.sum())
macro_f1_naive = float(np.mean(f1_naive))

print("\n" + "=" * 50)
print("LSTM vs NAIVE")
print("=" * 50)
print(f"  LSTM   accuracy : {accuracy*100:5.2f}%   macro F1: {macro_f1:.3f}")
print(f"  Naive  accuracy : {accuracy_naive*100:5.2f}%   macro F1: {macro_f1_naive:.3f}")
print(f"  Δ accuracy      : {(accuracy-accuracy_naive)*100:+5.2f} pp")

# ════════════════════════════════════════════════════════════
# 8. Write metrics.json
# ════════════════════════════════════════════════════════════
metrics = {
    "seed": SEED,
    "test_set": {
        "size": int(len(X)),
        "target_date_min": str(target_dates[test_mask].min().date()),
        "target_date_max": str(target_dates[test_mask].max().date()),
    },
    # Classification
    "accuracy": round(accuracy * 100, 2),
    "macroF1":  round(macro_f1, 4),
    "f1Low":    round(f1_scores[0], 4),
    "f1Med":    round(f1_scores[1], 4),
    "f1High":   round(f1_scores[2], 4),
    "perClass": per_class,
    "confusionMatrix": cm.tolist(),
    # Score-error (E[score])
    "mae":  round(mae, 2),
    "rmse": round(rmse, 2),
    "mae_midpoint":  round(mae_midpoint, 2),
    "rmse_midpoint": round(rmse_midpoint, 2),
    # Naive baseline
    "naiveAccuracy":  round(accuracy_naive * 100, 2),
    "naiveMacroF1":   round(macro_f1_naive, 4),
    "uplift_accuracy": round((accuracy - accuracy_naive) * 100, 2),
    "uplift_macroF1":  round(macro_f1 - macro_f1_naive, 4),
    # Meta
    "lookback": LOOKBACK,
    "n_features": len(FEATURES),
    "evaluatedAt": datetime.utcnow().isoformat() + "Z",
}

with open(f"{RUN_DIR}/metrics.json", "w") as f:
    json.dump(metrics, f, indent=2)

print(f"\n📝 metrics.json → {RUN_DIR}/metrics.json")
print(f"✅ DONE — seed={SEED} | acc={accuracy*100:.2f}% | macro F1={macro_f1:.3f} | MAE={mae:.2f}")
