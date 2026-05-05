"""
LSTM Productivity Predictor — ROLLING-WINDOW RETRAINING
========================================================

Purpose
-------
This script is designed to be invoked by the application scheduler on
the 1st of each month (see app/Console/Kernel.php). Unlike the canonical
training script (train_lstm_nextday.py) which uses fixed split dates for
thesis reproducibility, this script computes splits *dynamically* from
the current date so that the test window always lies in the future
relative to training.

Why rolling-window?
-------------------
Naive monthly retraining + fixed evaluation = leakage:
  After several months, the original test window (e.g. ">2026-01-31") lies
  *inside* the new training window. Reported accuracy will inflate
  meaninglessly. See thesis Section 3.X.6 for the discussion.

This script avoids that by sliding the entire (train, val, test) tuple
forward each month. Each retrain produces metrics on a fresh, unseen
test window.

Outputs
-------
For a retrain executed on 2026-05-01, this script writes to:
    models/runs/2026-05-01/
        ├── lstm_productivity.keras
        ├── scaler.pkl
        ├── baseline.pkl
        ├── history.csv
        ├── config.json
        └── metrics.json

The dashboard's `models/lstm_productivity.keras` symlink should be
updated to point at the latest run only after a sanity check (e.g.
test accuracy did not regress more than 5pp from the previous month).
This script does NOT update the symlink automatically; that's a separate
operations task.

Usage
-----
    python3 train_lstm_rolling.py [--seed 42] [--test-days 30] [--val-days 90]

The thesis canonical model and reported numbers are NOT changed by this
script. They remain anchored to train_lstm_nextday.py with the fixed
2025-10-31 / 2026-01-31 split.
"""

import argparse
import json
import os
import platform
import random
import sys
import time
from datetime import date, datetime, timedelta

import joblib
import numpy as np
import pandas as pd
import psycopg2
import tensorflow as tf

from sklearn.preprocessing import MinMaxScaler
from sklearn.utils.class_weight import compute_class_weight
from tensorflow.keras.callbacks import (CSVLogger, EarlyStopping,
                                        ReduceLROnPlateau)
from tensorflow.keras.layers import LSTM, Dense, Dropout, Input
from tensorflow.keras.models import Sequential
from tensorflow.keras.optimizers import Adam

sys.path.append("../etl")
from config import PG_CONFIG  # noqa: E402

# ════════════════════════════════════════════════════════════
# CLI
# ════════════════════════════════════════════════════════════
parser = argparse.ArgumentParser(description="Rolling-window LSTM retraining")
parser.add_argument("--seed",       type=int, default=42)
parser.add_argument("--test-days",  type=int, default=30,  help="Length of test window in days")
parser.add_argument("--val-days",   type=int, default=90,  help="Length of validation window in days")
parser.add_argument("--buffer-days",type=int, default=1,   help="Gap between splits in days")
args = parser.parse_args()

SEED         = args.seed
TODAY        = date.today()
TEST_DAYS    = args.test_days
VAL_DAYS     = args.val_days
BUFFER_DAYS  = args.buffer_days

# Compute split dates
test_end    = TODAY
test_start  = test_end   - timedelta(days=TEST_DAYS)
val_end     = test_start - timedelta(days=BUFFER_DAYS)
val_start   = val_end    - timedelta(days=VAL_DAYS)
train_end   = val_start  - timedelta(days=BUFFER_DAYS)

# Output directory keyed by today's date
RUN_DIR = f"models/runs/{TODAY.isoformat()}"
os.makedirs(RUN_DIR, exist_ok=True)

# ════════════════════════════════════════════════════════════
# Determinism
# ════════════════════════════════════════════════════════════
random.seed(SEED)
np.random.seed(SEED)
tf.random.set_seed(SEED)
os.environ["PYTHONHASHSEED"] = str(SEED)

print("=" * 60)
print("  ROLLING-WINDOW RETRAIN")
print("=" * 60)
print(f"  Today          : {TODAY}")
print(f"  Train         <= {train_end}")
print(f"  Validation    : {val_start} .. {val_end}")
print(f"  Test          : {test_start} .. {test_end}")
print(f"  Seed          : {SEED}")
print(f"  Output        : {RUN_DIR}")
print("=" * 60)

start_time = time.time()

# ════════════════════════════════════════════════════════════
# 1. PULL DATA
# ════════════════════════════════════════════════════════════
print("\n[1/9] Fetching data from DW...")
pg_conn = psycopg2.connect(**PG_CONFIG)

df = pd.read_sql(
    """
    SELECT
        e.user_id,
        e.name AS employee_name,
        d.full_date,
        f.hours_worked, f.is_late, f.checked_in, f.had_day_off,
        f.tasks_completed, f.avg_task_score, f.avg_task_percentage,
        f.productivity_score
    FROM fact_employee_productivity f
    JOIN dim_employee e ON f.employee_sk = e.employee_sk
    JOIN dim_date     d ON f.date_sk     = d.date_sk
    WHERE d.full_date <= %(today)s
    ORDER BY e.user_id, d.full_date ASC
    """,
    pg_conn,
    params={"today": TODAY},
)
pg_conn.close()
df["full_date"] = pd.to_datetime(df["full_date"])
print(f"   Loaded {len(df)} rows for {df['user_id'].nunique()} employees.")

# ════════════════════════════════════════════════════════════
# 2. PERSONAL BASELINE
#    Compute from training window only (no leakage from val/test)
# ════════════════════════════════════════════════════════════
print("\n[2/9] Computing personal baselines...")
baseline_cutoff = pd.Timestamp(train_end)
baseline = (
    df[df["full_date"] <= baseline_cutoff]
    .groupby("user_id")["productivity_score"]
    .agg(["mean", "std"])
    .rename(columns={"mean": "personal_mean", "std": "personal_std"})
    .reset_index()
)
baseline["personal_std"] = baseline["personal_std"].fillna(1).clip(lower=1)
df = df.merge(baseline, on="user_id", how="left")
df["score_vs_baseline"] = (df["productivity_score"] - df["personal_mean"]) / df["personal_std"]

user_ids = sorted(df["user_id"].unique())
uid_map = {uid: i / len(user_ids) for i, uid in enumerate(user_ids)}
df["user_id_norm"] = df["user_id"].map(uid_map)
joblib.dump(baseline, f"{RUN_DIR}/baseline.pkl")

# ════════════════════════════════════════════════════════════
# 3. FEATURE ENGINEERING (identical to train_lstm_nextday.py)
# ════════════════════════════════════════════════════════════
print("\n[3/9] Engineering features...")
LOOKBACK = 14
TARGET = "productivity_score"

df["is_late"]     = df["is_late"].astype(int)
df["checked_in"]  = df["checked_in"].astype(int)
df["had_day_off"] = df["had_day_off"].astype(int)

g = df.groupby("user_id", group_keys=False)

for col in ["is_late", "checked_in", "had_day_off"]:
    df[f"{col}_rate_7d"] = g[col].transform(
        lambda x: x.shift(1).rolling(7, min_periods=1).mean()
    )
    df[f"{col}_rate_14d"] = g[col].transform(
        lambda x: x.shift(1).rolling(14, min_periods=1).mean()
    )

df["has_task_signal"] = (
    (df["avg_task_score"] > 0)
    | (df["avg_task_percentage"] > 0)
    | (df["tasks_completed"] > 0)
).astype(int)
df["task_workload"] = df["tasks_completed"] + df["avg_task_percentage"] / 100.0

df["score_yesterday"] = g["productivity_score"].shift(1)
df["score_3d_ago"]    = g["productivity_score"].shift(3)
df["score_7d_ago"]    = g["productivity_score"].shift(7)
df["score_delta_1d"]  = df["score_yesterday"] - df["score_3d_ago"]
df["score_delta_7d"]  = df["score_3d_ago"]    - df["score_7d_ago"]

df["score_avg_7d"]  = g["productivity_score"].transform(
    lambda x: x.shift(1).rolling(7, min_periods=1).mean()
)
df["score_avg_14d"] = g["productivity_score"].transform(
    lambda x: x.shift(1).rolling(14, min_periods=1).mean()
)
df["score_std_7d"]  = g["productivity_score"].transform(
    lambda x: x.shift(1).rolling(7, min_periods=1).std()
)

df["checkin_streak"] = df.groupby("user_id")["checked_in"].transform(
    lambda x: x.groupby((x != x.shift()).cumsum()).cumcount() + 1
) * df["checked_in"]

df["day_of_week"] = df["full_date"].dt.dayofweek
df.fillna(0, inplace=True)

FEATURES = [
    "user_id_norm", "score_vs_baseline",
    "hours_worked", "is_late", "checked_in", "had_day_off",
    "tasks_completed", "avg_task_score", "avg_task_percentage",
    "is_late_rate_7d", "is_late_rate_14d",
    "checked_in_rate_7d", "checked_in_rate_14d",
    "had_day_off_rate_7d", "had_day_off_rate_14d",
    "has_task_signal", "task_workload", "checkin_streak",
    "score_yesterday", "score_3d_ago", "score_7d_ago",
    "score_delta_1d", "score_delta_7d",
    "score_avg_7d", "score_avg_14d", "score_std_7d",
    "day_of_week",
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
print("\n[5/9] Building sequences...")
X_list, y_list, date_list = [], [], []
for _user_id, group in df.groupby("user_id"):
    group = group.sort_values("full_date").reset_index(drop=True)
    feat_vals = group[FEATURES].values
    targ_vals = group[TARGET].values
    dates     = group["full_date"].values
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
# 6. SPLIT — DYNAMIC, ROLLING-WINDOW
# ════════════════════════════════════════════════════════════
print("\n[6/9] Splitting (rolling-window)...")
train_end_ts = pd.Timestamp(train_end)
val_end_ts   = pd.Timestamp(val_end)
test_end_ts  = pd.Timestamp(test_end)

train_mask = date_idx <= train_end_ts
val_mask   = (date_idx > train_end_ts) & (date_idx <= val_end_ts)
test_mask  = (date_idx > val_end_ts)   & (date_idx <= test_end_ts)

X_train, y_train = X[train_mask], y[train_mask]
X_val,   y_val   = X[val_mask],   y[val_mask]
X_test,  y_test  = X[test_mask],  y[test_mask]
print(f"   Train: {len(X_train)} | Val: {len(X_val)} | Test: {len(X_test)}")

# Sanity check — abort if any split is empty
if len(X_train) == 0 or len(X_val) == 0:
    print("\n⚠️  ABORT: insufficient data in train or validation window.")
    print("    The warehouse may not have enough historical data yet.")
    sys.exit(1)

if len(X_test) == 0:
    print("\n⚠️  WARNING: test window is empty. Training will proceed but")
    print("    no test metrics will be computed for this run.")

# Class weights (computed but not applied — see thesis 4.X.X.5)
weights = compute_class_weight("balanced", classes=np.array([0, 1, 2]), y=y_train)
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
    Dense(16, activation="relu"),
    Dense(3,  activation="softmax"),
])
model.compile(
    optimizer=Adam(learning_rate=5e-4, clipnorm=1.0),
    loss="sparse_categorical_crossentropy",
    metrics=["accuracy"],
)
n_params = model.count_params()
print(f"   Total parameters: {n_params:,}")

# ════════════════════════════════════════════════════════════
# 8. TRAIN
# ════════════════════════════════════════════════════════════
print("\n[8/9] Training...")
history = model.fit(
    X_train, y_train,
    validation_data=(X_val, y_val),
    epochs=120,
    batch_size=128,
    callbacks=[
        EarlyStopping(monitor="val_loss", patience=15,
                      restore_best_weights=True, verbose=1),
        ReduceLROnPlateau(monitor="val_loss", factor=0.5, patience=5,
                          min_lr=1e-6, verbose=1),
        CSVLogger(f"{RUN_DIR}/history.csv", append=False),
    ],
    # class_weight=class_weight_dict,   # disabled — see thesis 4.X.X.5
    verbose=2,
)

# ════════════════════════════════════════════════════════════
# 9. EVALUATE + SAVE
# ════════════════════════════════════════════════════════════
print("\n[9/9] Evaluating...")
model.save(f"{RUN_DIR}/lstm_productivity.keras")

# Test metrics if we have a test window
test_acc = None
test_loss = None
if len(X_test) > 0:
    test_loss, test_acc = model.evaluate(X_test, y_test, verbose=0)
    print(f"   Test accuracy: {test_acc * 100:.2f}%")
    print(f"   Test loss    : {test_loss:.4f}")

# Naive baseline on the same test set
naive_acc = None
if len(X_test) > 0:
    # For each test sequence, the naive prediction is "class on day t" (last day of window)
    # Decode the y_yesterday from the LAST timestep of each X sequence.
    last_step_score_scaled = X_test[:, -1, FEATURES.index("score_yesterday")]
    last_step_score = (
        last_step_score_scaled
        * (scaler.data_max_[FEATURES.index("score_yesterday")] - scaler.data_min_[FEATURES.index("score_yesterday")])
        + scaler.data_min_[FEATURES.index("score_yesterday")]
    )
    naive_pred = np.array([to_class_idx(s) for s in last_step_score])
    naive_acc = float((naive_pred == y_test).mean())
    print(f"   Naive baseline: {naive_acc * 100:.2f}%")
    if test_acc is not None:
        print(f"   Uplift        : {(test_acc - naive_acc) * 100:+.2f} pp")

# Save metrics + config
elapsed = time.time() - start_time
metrics = {
    "run_date":       str(TODAY),
    "seed":           SEED,
    "split_dates": {
        "train_end":  str(train_end),
        "val_start":  str(val_start),
        "val_end":    str(val_end),
        "test_start": str(test_start),
        "test_end":   str(test_end),
    },
    "set_sizes": {
        "train": int(len(X_train)),
        "val":   int(len(X_val)),
        "test":  int(len(X_test)),
    },
    "test_accuracy":  None if test_acc is None else round(float(test_acc) * 100, 2),
    "test_loss":      None if test_loss is None else round(float(test_loss), 4),
    "naive_accuracy": None if naive_acc is None else round(float(naive_acc) * 100, 2),
    "uplift":         (None if test_acc is None or naive_acc is None
                       else round(float(test_acc - naive_acc) * 100, 2)),
    "epochs_ran":     len(history.history["loss"]),
    "best_val_loss":  float(min(history.history["val_loss"])),
    "best_val_acc":   float(max(history.history["val_accuracy"])),
    "elapsed_s":      round(elapsed, 1),
    "n_params":       int(n_params),
    "lookback":       LOOKBACK,
    "n_features":     len(FEATURES),
}
with open(f"{RUN_DIR}/metrics.json", "w") as f:
    json.dump(metrics, f, indent=2)

config = {
    **metrics,
    "features": FEATURES,
    "environment": {
        "python":     platform.python_version(),
        "tensorflow": tf.__version__,
        "numpy":      np.__version__,
        "pandas":     pd.__version__,
        "platform":   platform.platform(),
    },
    "class_weights_computed": {str(k): float(v) for k, v in class_weight_dict.items()},
    "class_weights_applied":  False,
    "timestamp": datetime.utcnow().isoformat() + "Z",
}
with open(f"{RUN_DIR}/config.json", "w") as f:
    json.dump(config, f, indent=2)

print(f"\n✅ DONE — {elapsed:.1f}s")
print(f"   Artefacts → {RUN_DIR}/")
if test_acc is not None:
    print(f"   Test acc  : {test_acc*100:.2f}% (naive: {naive_acc*100:.2f}%)")
print()
print("NOTE: this script does NOT update models/lstm_productivity.keras")
print("      symlink. Operator should review metrics.json and update")
print("      manually after sanity checking against previous month.")
