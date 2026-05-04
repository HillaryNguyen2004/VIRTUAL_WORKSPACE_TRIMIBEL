"""
Evaluation script — mirrors train_lstm.py exactly.

  • Same FEATURES list, in the same order.
  • Same rolling-rate feature engineering (no ARIMA).
  • Same windowing: target = TOMORROW's class.
  • Same class thresholds: Low <50, Medium 50–79, High ≥80.
  • Uses scaler.transform() — never fit_transform on test data.
"""

import numpy as np
import pandas as pd
import joblib
import sys
from datetime import date

from sqlalchemy import create_engine
from tensorflow.keras.models import load_model

sys.path.append('../etl')
from config import PG_URL

# ─────────────────────────────────────────────────────────
# 1.  Load model + scaler + baseline
# ─────────────────────────────────────────────────────────
model    = load_model("models/lstm_productivity.keras")
scaler   = joblib.load("models/scaler.pkl")
baseline = joblib.load("models/baseline.pkl")

FEATURES = [
    # Personal context (2)
    'user_id_norm',
    'score_vs_baseline',
    # Today's raw behavioural inputs (7)
    'hours_worked',
    'is_late',
    'checked_in',
    'had_day_off',
    'tasks_completed',
    'avg_task_score',
    'avg_task_percentage',
    # Behavioural rates over past windows (6)
    'is_late_rate_7d', 'is_late_rate_14d',
    'checked_in_rate_7d', 'checked_in_rate_14d',
    'had_day_off_rate_7d', 'had_day_off_rate_14d',
    # Task / workload (3)
    'has_task_signal',
    'task_workload',
    'checkin_streak',
    # Lagged score signals (5)
    'score_yesterday',
    'score_3d_ago',
    'score_7d_ago',
    'score_delta_1d',
    'score_delta_7d',
    # Past score window stats (3)
    'score_avg_7d',
    'score_avg_14d',
    'score_std_7d',
    # Calendar (1)
    'day_of_week',
]
TARGET   = 'productivity_score'
LOOKBACK = 14

# ─────────────────────────────────────────────────────────
# 2.  Pull data from PostgreSQL
# ─────────────────────────────────────────────────────────
TRAINING_CUTOFF = date.today()

engine = create_engine(PG_URL)

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

# ─────────────────────────────────────────────────────────
# 3.  Personal baseline + user_id_norm
# ─────────────────────────────────────────────────────────
df = df.merge(baseline, on='user_id', how='left')
df['score_vs_baseline'] = (
    (df['productivity_score'] - df['personal_mean'])
    / df['personal_std'].fillna(1).clip(lower=1)
)

user_ids = sorted(df['user_id'].unique())
uid_map  = {uid: i / len(user_ids) for i, uid in enumerate(user_ids)}
df['user_id_norm'] = df['user_id'].map(uid_map)

# ─────────────────────────────────────────────────────────
# 4.  Feature engineering — MUST mirror train_lstm.py
# ─────────────────────────────────────────────────────────
df['is_late']     = df['is_late'].astype(int)
df['checked_in']  = df['checked_in'].astype(int)
df['had_day_off'] = df['had_day_off'].astype(int)

g = df.groupby('user_id', group_keys=False)

# Rolling rates (replaces ARIMA prob features)
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

# ─────────────────────────────────────────────────────────
# 5.  Scale (transform — NEVER fit_transform)
# ─────────────────────────────────────────────────────────
all_cols = FEATURES + [TARGET]
df[all_cols] = scaler.transform(df[all_cols])

# ─────────────────────────────────────────────────────────
# 6.  Build sequences — NEXT-DAY target
#       Window: features at days [i-LOOKBACK+1 .. i]
#       Target: productivity_score at day i+1
# ─────────────────────────────────────────────────────────
X_list, y_list, date_list = [], [], []

for uid, grp in df.groupby('user_id'):
    grp = grp.sort_values('full_date').reset_index(drop=True)
    feat_vals = grp[FEATURES].values
    targ_vals = grp[TARGET].values
    dates     = grp['full_date'].values

    if len(grp) < LOOKBACK + 1:
        continue

    for i in range(LOOKBACK - 1, len(grp) - 1):
        X_list.append(feat_vals[i - LOOKBACK + 1 : i + 1, :])
        y_list.append(targ_vals[i + 1])
        date_list.append(dates[i + 1])

X = np.array(X_list)
y_scaled = np.array(y_list)
date_idx = pd.Series([pd.Timestamp(d) for d in date_list])

# Test set only — same split as training
val_end   = pd.Timestamp('2026-01-31')
test_mask = date_idx > val_end

X = X[test_mask]
y_scaled = y_scaled[test_mask]

print(f"Evaluating on {len(X)} test sequences (target date > {val_end.date()})")

# ─────────────────────────────────────────────────────────
# 7.  Predict
# ─────────────────────────────────────────────────────────
y_pred_probs = model.predict(X, verbose=0)
y_pred_idx   = np.argmax(y_pred_probs, axis=1)

# Inverse-scale actual scores to apply class thresholds
target_idx = all_cols.index(TARGET)
score_min  = scaler.data_min_[target_idx]
score_max  = scaler.data_max_[target_idx]
actual_scores = y_scaled * (score_max - score_min) + score_min

# ─────────────────────────────────────────────────────────
# 8.  Class labels
# ─────────────────────────────────────────────────────────
def to_class_label(score):
    if score >= 80: return 'High'
    if score >= 50: return 'Medium'
    return 'Low'

actual_classes = np.array([to_class_label(s) for s in actual_scores])
idx_to_class   = {0: 'Low', 1: 'Medium', 2: 'High'}
predicted_classes = np.array([idx_to_class[i] for i in y_pred_idx])

# ─────────────────────────────────────────────────────────
# 9.  Confusion matrix
# ─────────────────────────────────────────────────────────
classes = ['Low', 'Medium', 'High']
n   = len(classes)
cm  = np.zeros((n, n), dtype=int)
idx = {c: i for i, c in enumerate(classes)}

for a, p in zip(actual_classes, predicted_classes):
    cm[idx[a]][idx[p]] += 1

print("\n" + "=" * 50)
print("CONFUSION MATRIX")
print("=" * 50)
header = f"{'':>10}" + "".join(f"{'Pred ' + c:>12}" for c in classes)
print(header)
for i, c in enumerate(classes):
    row = f"{'Act ' + c:>10}" + "".join(f"{cm[i][j]:>12}" for j in range(n))
    print(row)

# ─────────────────────────────────────────────────────────
# 10. Per-class metrics
# ─────────────────────────────────────────────────────────
print("\n" + "=" * 50)
print("PER-CLASS METRICS  (β = 1, balanced F1)")
print("=" * 50)
print(f"{'Class':<10} {'Precision':>10} {'Recall':>10} {'F1':>10} {'Support':>10}")
print("-" * 50)

f1_scores = []
for i, c in enumerate(classes):
    tp = cm[i][i]
    fp = cm[:, i].sum() - tp
    fn = cm[i, :].sum() - tp

    precision = tp / (tp + fp) if (tp + fp) > 0 else 0.0
    recall    = tp / (tp + fn) if (tp + fn) > 0 else 0.0
    f1        = 2 * precision * recall / (precision + recall) if (precision + recall) > 0 else 0.0
    support   = cm[i, :].sum()

    f1_scores.append(f1)
    print(f"{c:<10} {precision:>10.3f} {recall:>10.3f} {f1:>10.3f} {support:>10}")

# ─────────────────────────────────────────────────────────
# 11. Summary
# ─────────────────────────────────────────────────────────
accuracy = np.diag(cm).sum() / cm.sum() if cm.sum() > 0 else 0.0
macro_f1 = float(np.mean(f1_scores))

print("\n" + "=" * 50)
print("SUMMARY")
print("=" * 50)
print(f"  Accuracy : {accuracy:.3f}  ({accuracy*100:.1f}%)")
print(f"  Macro F1 : {macro_f1:.3f}")

print("\n" + "=" * 50)
print("TRUSTWORTHINESS VERDICT")
print("=" * 50)
if macro_f1 >= 0.90:
    verdict = "EXCELLENT — highly trustworthy for thesis"
elif macro_f1 >= 0.80:
    verdict = "GOOD — trustworthy, suitable for thesis"
elif macro_f1 >= 0.70:
    verdict = "ACCEPTABLE — usable with caveats in thesis"
else:
    verdict = "POOR — retrain or review data before using"

print(f"  Macro F1 = {macro_f1:.3f}  →  {verdict}")
print(f"  Accuracy = {accuracy*100:.1f}%")