import numpy as np
import pandas as pd
import joblib
import sys
from datetime import date
from sqlalchemy import create_engine
sys.path.append('../etl')
from config import PG_CONFIG
from tensorflow.keras.models import load_model

# ─────────────────────────────────────────────────────────
# 1. Load model and scaler
# ─────────────────────────────────────────────────────────
model  = load_model("models/lstm_productivity.keras")
scaler = joblib.load("models/scaler.pkl")

FEATURES = [
    # Must match train_lstm.py EXACTLY
    'hours_worked',
    'is_late',
    'checked_in',
    'had_day_off',
    'tasks_completed',
    'avg_task_score',
    'avg_task_percentage',
    'has_task_signal',
    'task_workload',
    'score_yesterday',
    'score_3d_ago',
    'score_7d_ago',
    'score_delta_1d',
    'score_delta_7d',
    'checkin_streak',
]
TARGET   = 'productivity_score'
LOOKBACK = 14  # must match train_lstm.py

# ─────────────────────────────────────────────────────────
# 2. Pull data from PostgreSQL
# ─────────────────────────────────────────────────────────
TRAINING_CUTOFF = date.today()

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

df['is_late']     = df['is_late'].astype(int)
df['checked_in']  = df['checked_in'].astype(int)
df['had_day_off'] = df['had_day_off'].astype(int)

# ── Must match train_lstm.py exactly ──────────────────────
df['has_task_signal'] = (
    (df['avg_task_score'] > 0) |
    (df['avg_task_percentage'] > 0) |
    (df['tasks_completed'] > 0)
).astype(int)

df['score_yesterday'] = df.groupby('user_id')['productivity_score'].shift(1)
df['score_3d_ago']    = df.groupby('user_id')['productivity_score'].shift(3)
df['score_7d_ago']    = df.groupby('user_id')['productivity_score'].shift(7)
df['score_delta_1d']  = df['score_yesterday'] - df['score_3d_ago']
df['score_delta_7d']  = df['score_3d_ago']    - df['score_7d_ago']

df['checkin_streak'] = df.groupby('user_id')['checked_in'].transform(
    lambda x: x.groupby((x != x.shift()).cumsum()).cumcount() + 1
) * df['checked_in']

df['task_workload'] = (
    df['tasks_completed'] + df['avg_task_percentage'] / 100.0
)

df.fillna(0, inplace=True)
# ──────────────────────────────────────────────────────────

# ─────────────────────────────────────────────────────────
# 3. Scale and build sequences
# ─────────────────────────────────────────────────────────
all_cols = FEATURES + [TARGET]
df[all_cols] = scaler.transform(df[all_cols])

# ── Build sequences WITH date tracking ────────────────────
X_list, y_list, date_list = [], [], []

for uid, grp in df.groupby('user_id'):
    grp   = grp.sort_values('full_date').reset_index(drop=True)
    vals  = grp[all_cols].values
    dates = grp['full_date'].values
    if len(vals) < LOOKBACK + 1:
        continue
    for i in range(LOOKBACK, len(vals)):
        X_list.append(vals[i - LOOKBACK:i, :-1])
        y_list.append(vals[i, -1])
        date_list.append(dates[i])

X = np.array(X_list)
y = np.array(y_list)
date_idx = pd.Series([pd.Timestamp(d) for d in date_list])

# ── Use ONLY the test set — same split as train_lstm.py ───
val_end   = pd.Timestamp('2026-01-31')
test_mask = date_idx > val_end

X = X[test_mask]
y = y[test_mask]

print(f"Evaluating on {len(X)} test sequences (after {val_end.date()})")

# ─────────────────────────────────────────────────────────
# 4. Predict and inverse-scale back to 0-100
# ─────────────────────────────────────────────────────────
y_pred_scaled = model.predict(X, verbose=0).flatten()

# Inverse scale — reconstruct dummy array for scaler
dummy_actual = np.zeros((len(y), len(all_cols)))
dummy_pred   = np.zeros((len(y_pred_scaled), len(all_cols)))
dummy_actual[:, -1] = y
dummy_pred[:, -1]   = y_pred_scaled

actual_scores    = scaler.inverse_transform(dummy_actual)[:, -1]
predicted_scores = scaler.inverse_transform(dummy_pred)[:, -1]

# ─────────────────────────────────────────────────────────
# 5. Convert scores → class labels
# ─────────────────────────────────────────────────────────
def to_class(score):
    if score >= 80: return 'High'
    if score >= 60: return 'Medium'
    return 'Low'

actual_classes    = np.array([to_class(s) for s in actual_scores])
predicted_classes = np.array([to_class(s) for s in predicted_scores])

# ─────────────────────────────────────────────────────────
# 6. Confusion matrix
# ─────────────────────────────────────────────────────────
classes = ['Low', 'Medium', 'High']
n       = len(classes)
cm      = np.zeros((n, n), dtype=int)
idx     = {c: i for i, c in enumerate(classes)}

for a, p in zip(actual_classes, predicted_classes):
    cm[idx[a]][idx[p]] += 1

print("\n" + "="*50)
print("CONFUSION MATRIX")
print("="*50)
header = f"{'':>10}" + "".join(f"{'Pred '+c:>12}" for c in classes)
print(header)
for i, c in enumerate(classes):
    row = f"{'Act '+c:>10}" + "".join(f"{cm[i][j]:>12}" for j in range(n))
    print(row)

# ─────────────────────────────────────────────────────────
# 7. Precision, Recall, F1 per class  (from slide formulas)
# ─────────────────────────────────────────────────────────
print("\n" + "="*50)
print("PER-CLASS METRICS  (β = 1, balanced F1)")
print("="*50)
print(f"{'Class':<10} {'Precision':>10} {'Recall':>10} {'F1':>10} {'Support':>10}")
print("-"*50)

f1_scores = []
for i, c in enumerate(classes):
    tp = cm[i][i]
    fp = cm[:, i].sum() - tp          # other rows predicted as this class
    fn = cm[i, :].sum() - tp          # this class predicted as something else

    precision = tp / (tp + fp) if (tp + fp) > 0 else 0.0
    recall    = tp / (tp + fn) if (tp + fn) > 0 else 0.0
    f1        = 2 * precision * recall / (precision + recall) \
                if (precision + recall) > 0 else 0.0
    support   = cm[i, :].sum()

    f1_scores.append(f1)
    print(f"{c:<10} {precision:>10.3f} {recall:>10.3f} {f1:>10.3f} {support:>10}")

# ─────────────────────────────────────────────────────────
# 8. Summary metrics
# ─────────────────────────────────────────────────────────
accuracy   = np.diag(cm).sum() / cm.sum()
macro_f1   = np.mean(f1_scores)

# Regression metrics as bonus
mae  = np.mean(np.abs(actual_scores - predicted_scores))
rmse = np.sqrt(np.mean((actual_scores - predicted_scores) ** 2))
ss_res = np.sum((actual_scores - predicted_scores) ** 2)
ss_tot = np.sum((actual_scores - actual_scores.mean()) ** 2)
r2 = 1 - ss_res / ss_tot if ss_tot > 0 else 0

print("\n" + "="*50)
print("SUMMARY")
print("="*50)
print(f"  Accuracy       : {accuracy:.3f}  ({accuracy*100:.1f}%)")
print(f"  Macro F1       : {macro_f1:.3f}")
print(f"  MAE            : {mae:.2f} pts")
print(f"  RMSE           : {rmse:.2f} pts")
print(f"  R²             : {r2:.4f}")

print("\n" + "="*50)
print("TRUSTWORTHINESS VERDICT")
print("="*50)
if macro_f1 >= 0.90:
    verdict = "EXCELLENT — highly trustworthy for thesis"
elif macro_f1 >= 0.80:
    verdict = "GOOD — trustworthy, suitable for thesis"
elif macro_f1 >= 0.70:
    verdict = "ACCEPTABLE — usable with caveats in thesis"
else:
    verdict = "POOR — retrain or review data before using"

print(f"  Macro F1 = {macro_f1:.3f}  →  {verdict}")

if r2 > 0.60:
    print(f"  R² = {r2:.4f}  →  Model explains {r2*100:.1f}% of variance ✓")
else:
    print(f"  R² = {r2:.4f}  →  Low variance explained — check data quality")

if accuracy >= 0.80:
    verdict_acc = "EXCELLENT — highly trustworthy for thesis"
elif accuracy >= 0.70:
    verdict_acc = "GOOD — trustworthy, suitable for thesis"
elif accuracy >= 0.60:
    verdict_acc = "ACCEPTABLE — usable with caveats in thesis"
else:
    verdict_acc = "POOR — retrain or review data before using"

print(f"  Accuracy = {accuracy*100:.1f}%  →  {verdict_acc}")

# Note: MAE context for regression-to-classification
print(f"\n  Note: MAE of {mae:.1f} pts on a 0-100 scale.")
print(f"  Classification accuracy limited by {mae:.1f}pt error near class boundaries.")
print(f"  Regression performance (MAE/R²) is the primary metric for this model.")