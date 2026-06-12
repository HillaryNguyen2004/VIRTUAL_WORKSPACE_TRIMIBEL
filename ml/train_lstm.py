"""
LSTM Productivity Predictor — Next-Day Forecast Edition
========================================================

Key change vs previous version:
  • Target is shifted to TOMORROW's class (not today's).
  • Sequence window includes today's features (last timestep = day t).
  • The model now solves a real forecasting problem instead of trying
    to recover a deterministic same-day formula from past data only.

ARIMA on binary features has been replaced with simple rolling-mean
"rate" features (rate_7d, rate_14d). Same idea, no statsmodels.
"""

import numpy as np
import pandas as pd
import psycopg2
import joblib
import os
import math
import sys
from datetime import date

from sklearn.preprocessing import MinMaxScaler
from sklearn.utils.class_weight import compute_class_weight
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import LSTM, Dense, Dropout, Input
from tensorflow.keras.callbacks import EarlyStopping, LambdaCallback
from tensorflow.keras.optimizers import Adam

sys.path.append('../etl')
from config import PG_CONFIG

os.makedirs("models", exist_ok=True)

# ════════════════════════════════════════════════════════════
# SEED — uncomment for deterministic results (thesis demo)
# ════════════════════════════════════════════════════════════
# import random, tensorflow as tf
# SEED = 42
# random.seed(SEED)
# np.random.seed(SEED)
# tf.random.set_seed(SEED)

# ════════════════════════════════════════════════════════════
# 1. PULL DATA FROM POSTGRESQL (with cutoff)
# ════════════════════════════════════════════════════════════
print("Fetching data from DW...")

TRAINING_CUTOFF = date.today()
print(f"Training cutoff: {TRAINING_CUTOFF} (excluding seeded future data)")

pg_conn = psycopg2.connect(**PG_CONFIG)

df = pd.read_sql("""
    SELECT
        e.user_id,
        e.name          AS employee_name,
        d.full_date,
        f.hours_worked,
        f.is_late,
        f.checked_in,
        f.had_day_off,
        f.tasks_completed,
        f.avg_task_score,
        f.avg_task_percentage,
        f.productivity_score
    FROM fact_employee_productivity f
    JOIN dim_employee e ON f.employee_sk = e.employee_sk
    JOIN dim_date     d ON f.date_sk     = d.date_sk
    WHERE d.full_date <= %(cutoff)s
    ORDER BY e.user_id, d.full_date ASC
""", pg_conn, params={"cutoff": TRAINING_CUTOFF})

pg_conn.close()
print(f"Loaded {len(df)} rows for {df['user_id'].nunique()} employees.")

df['full_date'] = pd.to_datetime(df['full_date'])

# ════════════════════════════════════════════════════════════
# 1.5  PERSONAL BASELINE — context for individual performance
#       Computed from data on/before train_baseline_cutoff so that
#       baseline mean/std do NOT leak future information.
# ════════════════════════════════════════════════════════════
train_baseline_cutoff = pd.Timestamp('2025-10-31')

baseline = (
    df[df['full_date'] <= train_baseline_cutoff]
    .groupby('user_id')['productivity_score']
    .agg(['mean', 'std'])
    .rename(columns={'mean': 'personal_mean', 'std': 'personal_std'})
    .reset_index()
)
baseline['personal_std'] = baseline['personal_std'].fillna(1).clip(lower=1)
df = df.merge(baseline, on='user_id', how='left')

df['score_vs_baseline'] = (
    (df['productivity_score'] - df['personal_mean'])
    / df['personal_std']
)

user_ids = sorted(df['user_id'].unique())
uid_map  = {uid: i / len(user_ids) for i, uid in enumerate(user_ids)}
df['user_id_norm'] = df['user_id'].map(uid_map)

joblib.dump(baseline, "models/baseline.pkl")
print(f"Personal baseline computed for {len(baseline)} employees → models/baseline.pkl")

# ════════════════════════════════════════════════════════════
# 2.  FEATURE ENGINEERING
#
# Approach:
#   • Today's raw features (the inputs the formula uses) are KEPT.
#     Each timestep in the LSTM window will hold one day's full
#     feature vector, and the LAST timestep is day t (today).
#   • Target is tomorrow's class — model has to use the trajectory
#     across the window to forecast where the score will land next.
#   • Lag features still help (recency of past scores) and are
#     leakage-safe via .shift().
#   • ARIMA-on-binary replaced with rolling-mean "rate" features.
# ════════════════════════════════════════════════════════════
LOOKBACK = 14

# Binary attendance features (0/1)
df['is_late']     = df['is_late'].astype(int)
df['checked_in']  = df['checked_in'].astype(int)
df['had_day_off'] = df['had_day_off'].astype(int)

# ── Rolling rates (replaces ARIMA prob features) ──────────
# Past-window rates only (.shift(1) so today is excluded).
g = df.groupby('user_id', group_keys=False)
for col in ['is_late', 'checked_in', 'had_day_off']:
    df[f'{col}_rate_7d']  = g[col].transform(lambda x: x.shift(1).rolling(7,  min_periods=1).mean())
    df[f'{col}_rate_14d'] = g[col].transform(lambda x: x.shift(1).rolling(14, min_periods=1).mean())

# ── Task / behavioural features ───────────────────────────
df['has_task_signal'] = (
    (df['avg_task_score']      > 0) |
    (df['avg_task_percentage'] > 0) |
    (df['tasks_completed']     > 0)
).astype(int)

df['task_workload'] = df['tasks_completed'] + df['avg_task_percentage'] / 100.0

# ── Lag features on the score (safe — past only) ──────────
df['score_yesterday'] = g['productivity_score'].shift(1)
df['score_3d_ago']    = g['productivity_score'].shift(3)
df['score_7d_ago']    = g['productivity_score'].shift(7)
df['score_delta_1d']  = df['score_yesterday'] - df['score_3d_ago']
df['score_delta_7d']  = df['score_3d_ago']    - df['score_7d_ago']

# ── Past-window score stats (safe) ────────────────────────
df['score_avg_7d']  = g['productivity_score'].transform(lambda x: x.shift(1).rolling(7,  min_periods=1).mean())
df['score_avg_14d'] = g['productivity_score'].transform(lambda x: x.shift(1).rolling(14, min_periods=1).mean())
df['score_std_7d']  = g['productivity_score'].transform(lambda x: x.shift(1).rolling(7,  min_periods=1).std())

# ── Check-in streak ───────────────────────────────────────
df['checkin_streak'] = df.groupby('user_id')['checked_in'].transform(
    lambda x: x.groupby((x != x.shift()).cumsum()).cumcount() + 1
) * df['checked_in']

# ── Day of week ───────────────────────────────────────────
df['day_of_week'] = df['full_date'].dt.dayofweek  # 0=Mon, 6=Sun

df.fillna(0, inplace=True)

# ════════════════════════════════════════════════════════════
# 3.  FEATURES LIST
# ════════════════════════════════════════════════════════════
FEATURES = [
    # Personal context (2)
    'user_id_norm',
    'score_vs_baseline',
    # Today's raw behavioural inputs (7) — the formula's inputs
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
    # Task / workload signals (3)
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
TARGET = 'productivity_score'

print(f"Features: {len(FEATURES)}")

# ════════════════════════════════════════════════════════════
# 4.  SCALE THE DATA
# ════════════════════════════════════════════════════════════
scaler = MinMaxScaler()
all_cols = FEATURES + [TARGET]
df[all_cols] = scaler.fit_transform(df[all_cols])

joblib.dump(scaler, "models/scaler.pkl")
print("Scaler saved → models/scaler.pkl")

# ════════════════════════════════════════════════════════════
# 5.  BUILD SEQUENCES (per employee) — NEXT-DAY TARGET
#
#      Window:  features at days [i-LOOKBACK+1 .. i]  (incl. today)
#      Target:  productivity_score at day i+1        (tomorrow)
#
#      So the model sees today's actual behaviour as the most
#      recent timestep and forecasts where score lands next.
# ════════════════════════════════════════════════════════════
X_list, y_list, date_list = [], [], []

for user_id, group in df.groupby('user_id'):
    group = group.sort_values('full_date').reset_index(drop=True)
    feat_vals = group[FEATURES].values
    targ_vals = group[TARGET].values
    dates     = group['full_date'].values

    if len(group) < LOOKBACK + 1:
        print(f"  Skipping user {user_id} — only {len(group)} days (need {LOOKBACK + 1})")
        continue

    # i is the LAST day in the window (= "today")
    # We need a tomorrow (i+1), so stop at len-2
    for i in range(LOOKBACK - 1, len(group) - 1):
        window = feat_vals[i - LOOKBACK + 1 : i + 1, :]   # shape (LOOKBACK, n_features)
        target = targ_vals[i + 1]                         # tomorrow's score
        X_list.append(window)
        y_list.append(target)
        date_list.append(dates[i + 1])                    # date the target refers to

if len(X_list) == 0:
    print("⚠️  Not enough data to train.")
    sys.exit(1)

X = np.array(X_list)
y_scaled = np.array(y_list)
date_idx = pd.Series([pd.Timestamp(d) for d in date_list])
print(f"Training shape → X: {X.shape}, y: {y_scaled.shape}")

# ════════════════════════════════════════════════════════════
# 5.5  CONVERT y → CLASS INDICES (0=Low, 1=Medium, 2=High)
# ════════════════════════════════════════════════════════════
def to_class_idx(score):
    if score >= 80: return 2
    if score >= 50: return 1
    return 0

# Inverse-scale y back to 0–100 to apply thresholds
target_idx = all_cols.index(TARGET)
score_min  = scaler.data_min_[target_idx]
score_max  = scaler.data_max_[target_idx]
y_raw = y_scaled * (score_max - score_min) + score_min
y     = np.array([to_class_idx(s) for s in y_raw])
print(f"Class distribution: Low={int((y==0).sum())} | Med={int((y==1).sum())} | High={int((y==2).sum())}")

# ════════════════════════════════════════════════════════════
# 6.  TIME-BASED TRAIN / VAL / TEST SPLIT
#      Target date determines the split (since target is tomorrow,
#      this means: train on tomorrows up to 2025-10-31, etc.)
# ════════════════════════════════════════════════════════════
train_end = pd.Timestamp('2025-10-31')
val_end   = pd.Timestamp('2026-01-31')

train_mask = date_idx <= train_end
val_mask   = (date_idx > train_end) & (date_idx <= val_end)
test_mask  = date_idx > val_end

X_train, y_train = X[train_mask], y[train_mask]
X_val,   y_val   = X[val_mask],   y[val_mask]
X_test,  y_test  = X[test_mask],  y[test_mask]

print(f"Train: {len(X_train)} sequences (target date ≤ {train_end.date()})")
print(f"Val:   {len(X_val)} sequences   ({train_end.date()} < target ≤ {val_end.date()})")
print(f"Test:  {len(X_test)} sequences  (target > {val_end.date()})")

# ════════════════════════════════════════════════════════════
# 6.5  CLASS WEIGHTS — balance the rare Low class
# ════════════════════════════════════════════════════════════
weights = compute_class_weight(
    'balanced',
    classes=np.array([0, 1, 2]),
    y=y_train
)
weights[0] *= 1.5   # extra boost for Low — it's the most imbalanced
class_weight_dict = {0: weights[0], 1: weights[1], 2: weights[2]}
print(f"Class weights: Low={weights[0]:.2f} | Med={weights[1]:.2f} | High={weights[2]:.2f}")

# ════════════════════════════════════════════════════════════
# 7.  MODEL
# ════════════════════════════════════════════════════════════
model = Sequential([
    Input(shape=(LOOKBACK, len(FEATURES))),
    LSTM(48, return_sequences=True),
    Dropout(0.3),
    LSTM(24, return_sequences=False),
    Dropout(0.3),
    Dense(16, activation='relu'),
    Dense(3,  activation='softmax'),
])

model.compile(
    optimizer=Adam(learning_rate=1e-4, clipnorm=1.0),
    loss='sparse_categorical_crossentropy',
    metrics=['accuracy'],
)
model.summary()

early_stop = EarlyStopping(
    monitor='val_loss',
    patience=25,
    restore_best_weights=True,
    verbose=1,
)

# Cyclical LR — gentle bumps to escape local minima
def cyclical_lr(epoch, base_lr=1e-4, max_lr=6e-4, step_size=15):
    cycle = math.floor(1 + epoch / (2 * step_size))
    x = abs(epoch / step_size - 2 * cycle + 1)
    return base_lr + (max_lr - base_lr) * max(0, 1 - x)

clr_callback = LambdaCallback(
    on_epoch_begin=lambda epoch, logs: model.optimizer.learning_rate.assign(cyclical_lr(epoch))
)

# ════════════════════════════════════════════════════════════
# 8.  TRAIN
# ════════════════════════════════════════════════════════════
print("\nTraining LSTM...")
history = model.fit(
    X_train, y_train,
    validation_data=(X_val, y_val),
    epochs=200,
    batch_size=32,
    callbacks=[early_stop, clr_callback],
    class_weight=class_weight_dict,
    verbose=1,
)

if len(X_test) > 0:
    test_loss, test_acc = model.evaluate(X_test, y_test, verbose=0)
    print(f"\n📊 Test set performance (after {val_end.date()}):")
    print(f"   Test loss:     {test_loss:.4f}")
    print(f"   Test accuracy: {test_acc:.4f}  ({test_acc*100:.1f}%)")

# ════════════════════════════════════════════════════════════
# 9.  SAVE + REPORT
# ════════════════════════════════════════════════════════════
model.save("models/lstm_productivity.keras")

best_val_loss = min(history.history['val_loss'])
best_val_acc  = max(history.history['val_accuracy'])
train_acc     = max(history.history['accuracy'])
epochs_ran    = len(history.history['loss'])

print(f"\n✅ Model saved → models/lstm_productivity.keras")
print(f"   Epochs run     : {epochs_ran}")
print(f"   Best val_loss  : {best_val_loss:.4f}")
print(f"   Best train_acc : {train_acc:.4f}  ({train_acc*100:.1f}%)")
print(f"   Best val_acc   : {best_val_acc:.4f}  ({best_val_acc*100:.1f}%)")

overfit_gap = train_acc - best_val_acc
print(f"   Overfit gap    : {overfit_gap:.4f} (train − val accuracy)")
if overfit_gap > 0.10:
    print("   ⚠️  Overfit detected — consider more dropout or fewer epochs.")
else:
    print("   ✅ No significant overfit detected.")

if best_val_acc >= 0.85:
    print("   Quality        : EXCELLENT")
elif best_val_acc >= 0.75:
    print("   Quality        : GOOD — model learned meaningful patterns")
elif best_val_acc >= 0.65:
    print("   Quality        : OK — acceptable for a thesis demo")
else:
    print("   Quality        : POOR — review data or features")