"""
Evaluation — NEXT-DAY FORECAST
Mirrors train_lstm_nextday.py exactly. Also reports the naive
"tomorrow's class = today's class" baseline so you can show the
LSTM's actual contribution.
"""

import numpy as np
import pandas as pd
import joblib
import sys
from datetime import date
import json
from datetime import datetime

from sqlalchemy import create_engine
from tensorflow.keras.models import load_model

sys.path.append('../etl')
from config import PG_URL

# ─────────────────────────────────────────────────────────
# 1.  Load model + scaler + baseline
# ─────────────────────────────────────────────────────────
model    = load_model("models/lstm_productivity_nextday.keras")
scaler   = joblib.load("models/scaler_nextday.pkl")
baseline = joblib.load("models/baseline_nextday.pkl")

FEATURES = [
    # User context (2)
    'user_id_norm', 'score_vs_baseline',
    # Attendance basics (6)
    'hours_worked', 'is_late', 'checked_in', 'had_day_off',
    'time_at_office_h', 'minutes_late',
    # Task basics (5)
    'tasks_completed', 'avg_task_score', 'avg_task_percentage',
    'active_task_count', 'overdue_task_count',
    # Task pressure (3)
    'high_priority_task_count', 'days_to_nearest_deadline', 'total_estimated_hours',
    # Attendance rates (6)
    'is_late_rate_7d', 'is_late_rate_14d',
    'checked_in_rate_7d', 'checked_in_rate_14d',
    'had_day_off_rate_7d', 'had_day_off_rate_14d',
    # Task signals (2)
    'has_task_signal', 'task_workload',
    # Streaks & context (2)
    'checkin_streak', 'day_of_week',
    # Historical scores (8)
    'score_yesterday', 'score_3d_ago', 'score_7d_ago',
    'score_delta_1d', 'score_delta_7d',
    'score_avg_7d', 'score_avg_14d', 'score_std_7d',
    # Calendar context (4) — NEW ETL v2
    'is_half_day_off', 'is_holiday', 'is_day_before_holiday', 'is_day_after_holiday',
    # Timing signal (1) — NEW ETL v2
    'checkin_hour',
]
TARGET   = 'productivity_score'
LOOKBACK = 14

# ─────────────────────────────────────────────────────────
# 2.  Pull data
# ─────────────────────────────────────────────────────────
TRAINING_CUTOFF = date.today()
engine = create_engine(PG_URL)
df = pd.read_sql("""
    SELECT e.user_id, d.full_date,
           f.hours_worked, f.is_late, f.checked_in, f.had_day_off,
           f.tasks_completed, f.avg_task_score, f.avg_task_percentage,
           f.productivity_score,
           -- NEW ETL v2 features (12 new columns)
           f.checkin_hour, f.minutes_late, f.time_at_office_h,
           f.active_task_count, f.high_priority_task_count,
           f.days_to_nearest_deadline, f.overdue_task_count,
           f.total_estimated_hours,
           f.is_half_day_off, f.is_holiday, f.is_day_before_holiday, f.is_day_after_holiday
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
# 4.  Feature engineering — must mirror training
# ─────────────────────────────────────────────────────────
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

# Fill missing ETL v2 columns with 0 (in case they're NULL in DB)
for col in ['checkin_hour', 'minutes_late', 'time_at_office_h',
            'active_task_count', 'high_priority_task_count',
            'days_to_nearest_deadline', 'overdue_task_count',
            'total_estimated_hours', 'is_half_day_off',
            'is_holiday', 'is_day_before_holiday', 'is_day_after_holiday']:
    if col in df.columns:
        df[col] = df[col].fillna(0).astype(float)

df.fillna(0, inplace=True)

# Keep an unscaled copy of today's score for the naive baseline
df_unscaled_today_score = df[['user_id', 'full_date', 'productivity_score']].copy()

# ─────────────────────────────────────────────────────────
# 5.  Scale
# ─────────────────────────────────────────────────────────
all_cols = FEATURES + [TARGET]
df[all_cols] = scaler.transform(df[all_cols])

# ─────────────────────────────────────────────────────────
# 6.  Build sequences — NEXT-DAY target
#       Also collect the "today" date for naive baseline
# ─────────────────────────────────────────────────────────
X_list, y_list, target_date_list, today_date_list, today_uid_list = [], [], [], [], []
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
        target_date_list.append(ds[i + 1])
        today_date_list.append(ds[i])
        today_uid_list.append(uid)

X = np.array(X_list)
y_scaled = np.array(y_list)
target_dates = pd.Series([pd.Timestamp(d) for d in target_date_list])
today_dates  = pd.Series([pd.Timestamp(d) for d in today_date_list])
today_uids   = pd.Series(today_uid_list)

val_end   = pd.Timestamp('2026-01-31')
test_mask = target_dates > val_end
X = X[test_mask]
y_scaled = y_scaled[test_mask]
today_dates_test = today_dates[test_mask].reset_index(drop=True)
today_uids_test  = today_uids[test_mask].reset_index(drop=True)

print(f"Evaluating on {len(X)} test sequences (target date > {val_end.date()})")

# ─────────────────────────────────────────────────────────
# 7.  Predict
# ─────────────────────────────────────────────────────────
y_pred_probs = model.predict(X, verbose=0)
y_pred_idx   = np.argmax(y_pred_probs, axis=1)

target_idx = all_cols.index(TARGET)
score_min  = scaler.data_min_[target_idx]
score_max  = scaler.data_max_[target_idx]
actual_scores = y_scaled * (score_max - score_min) + score_min

def to_class_label(score):
    if score >= 80: return 'High'
    if score >= 50: return 'Medium'
    return 'Low'

actual_classes = np.array([to_class_label(s) for s in actual_scores])
idx_to_class   = {0: 'Low', 1: 'Medium', 2: 'High'}
predicted_classes = np.array([idx_to_class[i] for i in y_pred_idx])

# ─────────────────────────────────────────────────────────
# 8.  Naive baseline: tomorrow's class = today's class
# ─────────────────────────────────────────────────────────
naive_lookup = df_unscaled_today_score.set_index(['user_id', 'full_date'])['productivity_score'].to_dict()
naive_classes = []
for uid, td in zip(today_uids_test, today_dates_test):
    today_score = naive_lookup.get((uid, td), 0.0)
    naive_classes.append(to_class_label(today_score))
naive_classes = np.array(naive_classes)

# ─────────────────────────────────────────────────────────
# 9.  Confusion matrix (LSTM)
# ─────────────────────────────────────────────────────────
classes = ['Low', 'Medium', 'High']
n   = len(classes)
cm  = np.zeros((n, n), dtype=int)
idx = {c: i for i, c in enumerate(classes)}
for a, p in zip(actual_classes, predicted_classes):
    cm[idx[a]][idx[p]] += 1

print("\n" + "=" * 50)
print("CONFUSION MATRIX  (NEXT-DAY  —  LSTM)")
print("=" * 50)
print(f"{'':>10}" + "".join(f"{'Pred ' + c:>12}" for c in classes))
for i, c in enumerate(classes):
    print(f"{'Act ' + c:>10}" + "".join(f"{cm[i][j]:>12}" for j in range(n)))

# Per-class metrics
print("\n" + "=" * 50)
print("PER-CLASS METRICS  (LSTM)")
print("=" * 50)
print(f"{'Class':<10} {'Precision':>10} {'Recall':>10} {'F1':>10} {'Support':>10}")
print("-" * 50)
f1_scores = []
for i, c in enumerate(classes):
    tp = cm[i][i]; fp = cm[:, i].sum() - tp; fn = cm[i, :].sum() - tp
    precision = tp / (tp + fp) if (tp + fp) > 0 else 0.0
    recall    = tp / (tp + fn) if (tp + fn) > 0 else 0.0
    f1 = 2 * precision * recall / (precision + recall) if (precision + recall) > 0 else 0.0
    support = cm[i, :].sum()
    f1_scores.append(f1)
    print(f"{c:<10} {precision:>10.3f} {recall:>10.3f} {f1:>10.3f} {support:>10}")

accuracy_lstm  = np.diag(cm).sum() / cm.sum() if cm.sum() > 0 else 0.0
macro_f1_lstm  = float(np.mean(f1_scores))

# ─────────────────────────────────────────────────────────
# 10. Naive baseline metrics
# ─────────────────────────────────────────────────────────
cm_naive = np.zeros((n, n), dtype=int)
for a, p in zip(actual_classes, naive_classes):
    cm_naive[idx[a]][idx[p]] += 1

print("\n" + "=" * 50)
print("CONFUSION MATRIX  (NEXT-DAY  —  NAIVE 'tomorrow = today')")
print("=" * 50)
print(f"{'':>10}" + "".join(f"{'Pred ' + c:>12}" for c in classes))
for i, c in enumerate(classes):
    print(f"{'Act ' + c:>10}" + "".join(f"{cm_naive[i][j]:>12}" for j in range(n)))

f1_naive_scores = []
for i in range(n):
    tp = cm_naive[i][i]; fp = cm_naive[:, i].sum() - tp; fn = cm_naive[i, :].sum() - tp
    precision = tp / (tp + fp) if (tp + fp) > 0 else 0.0
    recall    = tp / (tp + fn) if (tp + fn) > 0 else 0.0
    f1 = 2 * precision * recall / (precision + recall) if (precision + recall) > 0 else 0.0
    f1_naive_scores.append(f1)
accuracy_naive = np.diag(cm_naive).sum() / cm_naive.sum() if cm_naive.sum() > 0 else 0.0
macro_f1_naive = float(np.mean(f1_naive_scores))

# ─────────────────────────────────────────────────────────
# 11. Comparison
# ─────────────────────────────────────────────────────────
print("\n" + "=" * 50)
print("LSTM  vs  NAIVE  COMPARISON")
print("=" * 50)
print(f"  LSTM   accuracy : {accuracy_lstm*100:5.2f}%   |  macro F1: {macro_f1_lstm:.3f}")
print(f"  Naive  accuracy : {accuracy_naive*100:5.2f}%   |  macro F1: {macro_f1_naive:.3f}")
print(f"  Δ accuracy      : {(accuracy_lstm-accuracy_naive)*100:+5.2f} pp")
print(f"  Δ macro F1      : {(macro_f1_lstm-macro_f1_naive):+.3f}")

print("\n" + "=" * 50)
print("VERDICT  (NEXT-DAY FORECAST)")
print("=" * 50)
gain = (accuracy_lstm - accuracy_naive) * 100
if gain >= 5:
    print(f"  ✅ LSTM beats naive by {gain:.1f} pp — meaningful learnable signal.")
elif gain >= 1:
    print(f"  ➖ LSTM beats naive by {gain:.1f} pp — modest signal, useful but limited.")
elif gain >= -1:
    print(f"  ⚠️  LSTM ≈ naive ({gain:+.1f} pp) — no meaningful gain over yesterday's class.")
else:
    print(f"  ❌ LSTM worse than naive by {abs(gain):.1f} pp — model is hurting, not helping.")

print(f"\n  Reference: RF ceiling on this problem is ~69% accuracy.")

metrics_out = {
    "accuracy":      round(accuracy_lstm * 100, 2),     # e.g. 70.05
    "naiveAccuracy": round(accuracy_naive * 100, 2),    # e.g. 65.00
    "macroF1":       round(macro_f1_lstm, 4),
    # f1_scores list is in classes order: ['Low', 'Medium', 'High']
    "f1Low":         round(f1_scores[0], 4),
    "f1Med":         round(f1_scores[1], 4),
    "f1High":        round(f1_scores[2], 4),
    "lookback":      LOOKBACK,
    "lastRun":       datetime.utcnow().isoformat() + "Z",
}
 
with open("models/metrics.json", "w") as f:
    json.dump(metrics_out, f, indent=2)
 
print(f"\n📝 metrics.json written → models/metrics.json")
print(json.dumps(metrics_out, indent=2))