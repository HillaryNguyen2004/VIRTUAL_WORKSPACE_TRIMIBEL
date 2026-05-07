"""
LSTM Productivity Predictor — NEXT-DAY FORECAST (Thesis logging version)
========================================================================

Adds to the standard training script:
  • Command-line --seed argument (deterministic runs)
  • Per-epoch history saved as CSV (for learning-curve figure)
  • Configuration snapshot saved as JSON
  • Wall-clock time tracked

Usage:
    python3 train_lstm_nextday_logged.py --seed 42

All artefacts for one run are written to:
    runs/seed_{SEED}/
        ├── lstm_productivity.keras
        ├── scaler.pkl
        ├── baseline.pkl
        ├── history.csv          # per-epoch loss, accuracy, lr
        └── config.json          # all hyperparameters + environment info
"""

import argparse
import json
import os
import platform
import random
import sys
import time
from datetime import date, datetime

import numpy as np
import pandas as pd
import psycopg2
import joblib
import tensorflow as tf
from sklearn.preprocessing import MinMaxScaler
from sklearn.utils.class_weight import compute_class_weight
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import LSTM, Dense, Dropout, Input
from tensorflow.keras.callbacks import EarlyStopping, ReduceLROnPlateau, CSVLogger
from tensorflow.keras.optimizers import Adam

sys.path.append('../etl')
from config import PG_CONFIG

# ════════════════════════════════════════════════════════════
# CLI
# ════════════════════════════════════════════════════════════
parser = argparse.ArgumentParser()
parser.add_argument('--seed', type=int, default=42, help='Random seed')
parser.add_argument('--cutoff', type=str, default='2026-04-29',
                    help='Training data cutoff date (YYYY-MM-DD). Locked for reproducibility.')
args = parser.parse_args()

SEED            = args.seed
TRAINING_CUTOFF = date.fromisoformat(args.cutoff)

# Output directory for this run
RUN_DIR = f"runs/seed_{SEED}"
os.makedirs(RUN_DIR, exist_ok=True)

# ════════════════════════════════════════════════════════════
# Determinism
# ════════════════════════════════════════════════════════════
random.seed(SEED)
np.random.seed(SEED)
tf.random.set_seed(SEED)
os.environ['PYTHONHASHSEED'] = str(SEED)

print(f"=" * 60)
print(f"  RUN — seed={SEED} | cutoff={TRAINING_CUTOFF}")
print(f"=" * 60)

start_time = time.time()

# ════════════════════════════════════════════════════════════
# 1. PULL DATA FROM POSTGRESQL
# ════════════════════════════════════════════════════════════
print("\n[1/9] Fetching data from DW...")
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
        f.tasks_in_progress,
        f.avg_task_score,
        f.avg_task_percentage,
        f.productivity_score,
        -- NEW ETL v2 features (13 new columns)
        f.checkin_hour, f.checkout_hour, f.minutes_late, f.time_at_office_h,
        f.active_task_count, f.high_priority_task_count,
        f.days_to_nearest_deadline, f.overdue_task_count,
        f.total_estimated_hours,
        f.is_half_day_off, f.is_holiday, f.is_day_before_holiday, f.is_day_after_holiday,
        f.active_phase_title
    FROM fact_employee_productivity f
    JOIN dim_employee e ON f.employee_sk = e.employee_sk
    JOIN dim_date     d ON f.date_sk     = d.date_sk
    WHERE d.full_date <= %(cutoff)s
    ORDER BY e.user_id, d.full_date ASC
""", pg_conn, params={"cutoff": TRAINING_CUTOFF})
pg_conn.close()
print(f"   Loaded {len(df)} rows for {df['user_id'].nunique()} employees.")

df['full_date'] = pd.to_datetime(df['full_date'])

# ════════════════════════════════════════════════════════════
# 2. PERSONAL BASELINE
# ════════════════════════════════════════════════════════════
print("\n[2/9] Computing personal baselines...")
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
    (df['productivity_score'] - df['personal_mean']) / df['personal_std']
)
user_ids = sorted(df['user_id'].unique())
uid_map  = {uid: i / len(user_ids) for i, uid in enumerate(user_ids)}
df['user_id_norm'] = df['user_id'].map(uid_map)
joblib.dump(baseline, f"{RUN_DIR}/baseline.pkl")

# ════════════════════════════════════════════════════════════
# 3. FEATURE ENGINEERING
# ════════════════════════════════════════════════════════════
print("\n[3/9] Engineering features...")
LOOKBACK = 14
TARGET   = 'productivity_score'

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

# -- Cyclical encoding for day-of-week (captures weekly periodicity) ----
df['dow_sin'] = np.sin(2 * np.pi * df['full_date'].dt.dayofweek / 7)
df['dow_cos'] = np.cos(2 * np.pi * df['full_date'].dt.dayofweek / 7)

# -- Semantic NULL handling for ETL v2 columns --
# checkout_hour: -1 means no checkout recorded + flag for model
df['checkout_hour'] = df['checkout_hour'].fillna(-1)
df['has_checkout']  = (df['checkout_hour'] >= 0).astype(int)

# days_to_nearest_deadline: 999 = no deadline, clip to 90 (max planning horizon)
df['days_to_nearest_deadline'] = df['days_to_nearest_deadline'].fillna(999).clip(upper=90)

# Timing signals: 0 = didn't record
df['checkin_hour']     = df['checkin_hour'].fillna(0)
df['minutes_late']     = df['minutes_late'].fillna(0)
df['time_at_office_h'] = df['time_at_office_h'].fillna(0)
df['score_std_7d']     = df['score_std_7d'].fillna(0)

# Historical scores: use employee's personal mean when missing (individual baseline)
employee_means = df.groupby('user_id')['productivity_score'].transform('mean')
for col in ['score_yesterday', 'score_3d_ago', 'score_7d_ago',
            'score_avg_7d', 'score_avg_14d']:
    df[col] = df[col].fillna(employee_means)

# Catch remaining NaNs with fallback
df.fillna(0, inplace=True)

# -- Log-normalize task counts (raw counts dominate scaler) --
for col in ['tasks_completed', 'tasks_in_progress',
            'active_task_count', 'overdue_task_count',
            'high_priority_task_count', 'total_estimated_hours']:
    if col in df.columns:
        df[col] = np.log1p(df[col])

# -- Encode phase type from active_phase_title --
if 'active_phase_title' not in df.columns:
    df['active_phase_title'] = ''
df['active_phase_title'] = df['active_phase_title'].fillna('')
df['is_deployment_phase'] = df['active_phase_title'].str.contains('Deployment', case=False, na=False).astype(int)
df['is_research_phase']   = df['active_phase_title'].str.contains('Research',   case=False, na=False).astype(int)
df['is_planning_phase']   = df['active_phase_title'].str.contains('Planning',   case=False, na=False).astype(int)

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
    # Streaks & context + cyclical day-of-week (2 + 2)
    'checkin_streak', 'dow_sin', 'dow_cos',
    # Historical scores (8)
    'score_yesterday', 'score_3d_ago', 'score_7d_ago',
    'score_delta_1d', 'score_delta_7d',
    'score_avg_7d', 'score_avg_14d', 'score_std_7d',
    # Calendar context (4) — NEW ETL v2
    'is_half_day_off', 'is_holiday', 'is_day_before_holiday', 'is_day_after_holiday',
    # Timing signals (3) — NEW ETL v2 + has_checkout flag
    'checkin_hour', 'checkout_hour', 'has_checkout',
    # Phase type encoding (3) — NEW: structured phase signal
    'is_deployment_phase', 'is_research_phase', 'is_planning_phase',
]
print(f"   {len(FEATURES)} features prepared.")

# ════════════════════════════════════════════════════════════
# 4. SCALE
# ════════════════════════════════════════════════════════════
print("\n[4/9] Scaling features...")
scaler = MinMaxScaler()
all_cols = FEATURES + [TARGET]
df[all_cols] = scaler.fit_transform(df[all_cols])
joblib.dump(scaler, f"{RUN_DIR}/scaler.pkl")

# ════════════════════════════════════════════════════════════
# 5. BUILD SEQUENCES — NEXT-DAY TARGET
# ════════════════════════════════════════════════════════════
print("\n[5/9] Building sequences (next-day target)...")
X_list, y_list, date_list = [], [], []
for user_id, group in df.groupby('user_id'):
    group = group.sort_values('full_date').reset_index(drop=True)
    feat_vals = group[FEATURES].values
    targ_vals = group[TARGET].values
    dates     = group['full_date'].values
    if len(group) < LOOKBACK + 1:
        continue
    for i in range(LOOKBACK - 1, len(group) - 1):
        X_list.append(feat_vals[i - LOOKBACK + 1 : i + 1, :])
        y_list.append(targ_vals[i + 1])
        date_list.append(dates[i + 1])

X = np.array(X_list)
y_scaled = np.array(y_list)
date_idx = pd.Series([pd.Timestamp(d) for d in date_list])

# Convert y → class indices
def to_class_idx(score):
    if score >= 80: return 2
    if score >= 50: return 1
    return 0

target_idx = all_cols.index(TARGET)
score_min  = scaler.data_min_[target_idx]
score_max  = scaler.data_max_[target_idx]
y_raw = y_scaled * (score_max - score_min) + score_min
y     = np.array([to_class_idx(s) for s in y_raw])

print(f"   X: {X.shape} | y: {y.shape}")
print(f"   Class dist — Low:{int((y==0).sum())} Med:{int((y==1).sum())} High:{int((y==2).sum())}")

# ════════════════════════════════════════════════════════════
# 6. SPLIT
# ════════════════════════════════════════════════════════════
print("\n[6/9] Splitting (time-based)...")
train_end = pd.Timestamp('2025-10-31')
val_end   = pd.Timestamp('2026-01-31')
train_mask = date_idx <= train_end
val_mask   = (date_idx > train_end) & (date_idx <= val_end)
test_mask  = date_idx > val_end

X_train, y_train = X[train_mask], y[train_mask]
X_val,   y_val   = X[val_mask],   y[val_mask]
X_test,  y_test  = X[test_mask],  y[test_mask]
print(f"   Train: {len(X_train)} | Val: {len(X_val)} | Test: {len(X_test)}")

# Class weights (balanced)
weights = compute_class_weight('balanced', classes=np.array([0,1,2]), y=y_train)
class_weight_dict = {0: weights[0], 1: weights[1], 2: weights[2]}

# ════════════════════════════════════════════════════════════
# 7. MODEL
# ════════════════════════════════════════════════════════════
print("\n[7/9] Building model...")
model = Sequential([
    Input(shape=(LOOKBACK, len(FEATURES))),
    LSTM(64, return_sequences=True),
    Dropout(0.3),
    LSTM(32, return_sequences=False),
    Dropout(0.3),
    Dense(16, activation='relu'),
    Dense(3,  activation='softmax'),
])
model.compile(
    optimizer=Adam(learning_rate=5e-4, clipnorm=1.0),
    loss='sparse_categorical_crossentropy',
    metrics=['accuracy'],
)
n_params = model.count_params()
print(f"   Total parameters: {n_params:,}")

# ════════════════════════════════════════════════════════════
# 8. TRAIN
# ════════════════════════════════════════════════════════════
print("\n[8/9] Training...")
early_stop = EarlyStopping(monitor='val_loss', patience=15, restore_best_weights=True, verbose=1)
reduce_lr  = ReduceLROnPlateau(monitor='val_loss', factor=0.5, patience=5, min_lr=1e-6, verbose=1)
csv_log    = CSVLogger(f"{RUN_DIR}/history.csv", append=False)

history = model.fit(
    X_train, y_train,
    validation_data=(X_val, y_val),
    epochs=120,
    batch_size=128,
    callbacks=[early_stop, reduce_lr, csv_log],
    # class_weight=class_weight_dict,
    verbose=2,
)

# ════════════════════════════════════════════════════════════
# 9. SAVE + REPORT
# ════════════════════════════════════════════════════════════
print("\n[9/9] Saving artefacts...")
model.save(f"{RUN_DIR}/lstm_productivity.keras")

test_loss, test_acc = model.evaluate(X_test, y_test, verbose=0)
elapsed = time.time() - start_time

config_dump = {
    "seed": SEED,
    "training_cutoff": str(TRAINING_CUTOFF),
    "split_dates": {
        "train_end": "2025-10-31",
        "val_end":   "2026-01-31",
    },
    "set_sizes": {
        "train": int(len(X_train)),
        "val":   int(len(X_val)),
        "test":  int(len(X_test)),
    },
    "lookback": LOOKBACK,
    "n_features": len(FEATURES),
    "features": FEATURES,
    "class_thresholds": {"low_max": 50, "high_min": 80},
    "model": {
        "layers": ["LSTM(64)", "Dropout(0.3)", "LSTM(32)", "Dropout(0.3)",
                   "Dense(16, relu)", "Dense(3, softmax)"],
        "n_params": int(n_params),
    },
    "optimizer": {"name": "Adam", "lr": 5e-4, "clipnorm": 1.0},
    "loss": "sparse_categorical_crossentropy",
    "batch_size": 128,
    "max_epochs": 120,
    "callbacks": ["EarlyStopping(patience=15)", "ReduceLROnPlateau(factor=0.5, patience=5)"],
    "class_weights": {str(k): float(v) for k, v in class_weight_dict.items()},
    "training_results": {
        "epochs_ran":  len(history.history['loss']),
        "best_val_loss": float(min(history.history['val_loss'])),
        "best_val_acc":  float(max(history.history['val_accuracy'])),
        "best_train_acc": float(max(history.history['accuracy'])),
        "test_accuracy": float(test_acc),
        "test_loss": float(test_loss),
        "elapsed_seconds": round(elapsed, 1),
    },
    "environment": {
        "python":     platform.python_version(),
        "tensorflow": tf.__version__,
        "numpy":      np.__version__,
        "pandas":     pd.__version__,
        "platform":   platform.platform(),
    },
    "timestamp": datetime.utcnow().isoformat() + "Z",
}

with open(f"{RUN_DIR}/config.json", "w") as f:
    json.dump(config_dump, f, indent=2)

print(f"\n✅ DONE — seed={SEED} | test_acc={test_acc*100:.2f}% | epochs={len(history.history['loss'])} | {elapsed:.1f}s")
print(f"   Artefacts → {RUN_DIR}/")
