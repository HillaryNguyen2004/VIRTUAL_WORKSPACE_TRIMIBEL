import numpy as np
import pandas as pd
import psycopg2
import joblib
import os
from datetime import date
from sklearn.preprocessing import MinMaxScaler
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import LSTM, Dense, Dropout, Input
from tensorflow.keras.callbacks import EarlyStopping, ReduceLROnPlateau
import sys
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

# CUT OFF future seeded data — only include real data up to today
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

# ════════════════════════════════════════════════════════════
# 2. FEATURE ENGINEERING
#
# FIX 1: Removed rolling averages of the target as features
#         (avg_score_7d, avg_score_30d, score_trend) — these
#         directly leaked the target into features, causing
#         the model to just copy today's score as prediction.
#
# FIX 2: Removed target smoothing — smoothing hid real
#         variation and made the model predict flat lines.
#
# KEPT:   All original behavioral features preserved.
# ADDED:  Lag features (yesterday, 3d ago, delta) that give
#         temporal context WITHOUT leaking the target directly.
# ════════════════════════════════════════════════════════════
LOOKBACK = 14   # FIX 3: increased from 7 → 14 for more context
TARGET   = 'productivity_score'

df['is_late']     = df['is_late'].astype(int)
df['checked_in']  = df['checked_in'].astype(int)
df['had_day_off'] = df['had_day_off'].astype(int)

# ── Original behavioral features (kept) ───────────────────
df['has_task_signal'] = (
    (df['avg_task_score'] > 0) |
    (df['avg_task_percentage'] > 0) |
    (df['tasks_completed'] > 0)
).astype(int)

# ── Lag features (new — safe, no target leakage) ──────────
# These give the model temporal context using PAST scores,
# not rolling averages that include the current target value.
df['score_yesterday'] = df.groupby('user_id')['productivity_score'].shift(1)
df['score_3d_ago']    = df.groupby('user_id')['productivity_score'].shift(3)
df['score_7d_ago']    = df.groupby('user_id')['productivity_score'].shift(7)
df['score_delta_1d']  = df['score_yesterday'] - df['score_3d_ago']   # short trend
df['score_delta_7d']  = df['score_3d_ago']    - df['score_7d_ago']   # medium trend

# ── Attendance streak (how many days in a row checked in) ─
df['checkin_streak'] = df.groupby('user_id')['checked_in'].transform(
    lambda x: x.groupby((x != x.shift()).cumsum()).cumcount() + 1
) * df['checked_in']  # reset to 0 when not checked in

# ── Task workload signal ───────────────────────────────────
df['task_workload'] = (
    df['tasks_completed'] + df['avg_task_percentage'] / 100.0
)

df.fillna(0, inplace=True)

# ════════════════════════════════════════════════════════════
# 3. FEATURES LIST
# ════════════════════════════════════════════════════════════
FEATURES = [
    # Core attendance
    'hours_worked',
    'is_late',
    'checked_in',
    'had_day_off',
    # Task signals
    'tasks_completed',
    'avg_task_score',
    'avg_task_percentage',
    'has_task_signal',
    'task_workload',
    # Temporal lag features (safe — no target leakage)
    'score_yesterday',
    'score_3d_ago',
    'score_7d_ago',
    'score_delta_1d',
    'score_delta_7d',
    # Behavioral patterns
    'checkin_streak',
]

print(f"Features: {len(FEATURES)} → {FEATURES}")

# ════════════════════════════════════════════════════════════
# 4. SCALE THE DATA
# ════════════════════════════════════════════════════════════
scaler = MinMaxScaler()
all_cols = FEATURES + [TARGET]
df[all_cols] = scaler.fit_transform(df[all_cols])

joblib.dump(scaler, "models/scaler.pkl")
print("Scaler saved → models/scaler.pkl")

# ════════════════════════════════════════════════════════════
# 5. BUILD SEQUENCES (per employee) WITH DATE TRACKING
#    Track dates to enable time-based splitting
#    X shape: (samples, LOOKBACK, num_features)
#    y shape: (samples,)
# ════════════════════════════════════════════════════════════
X_list, y_list, date_list = [], [], []

for user_id, group in df.groupby('user_id'):
    group = group.sort_values('full_date').reset_index(drop=True)
    values = group[all_cols].values
    dates  = group['full_date'].values

    if len(values) < LOOKBACK + 1:
        print(f"  Skipping user {user_id} — only {len(values)} days (need {LOOKBACK + 1})")
        continue

    for i in range(LOOKBACK, len(values)):
        X_list.append(values[i - LOOKBACK:i, :-1])  # features only
        y_list.append(values[i, -1])                  # target only
        date_list.append(dates[i])                    # date of prediction

if len(X_list) == 0:
    print("⚠️  Not enough data to train.")
    print(f"    Each employee needs at least {LOOKBACK + 1} records.")
    print(f"    Try lowering LOOKBACK to 7 if data is sparse.")
    exit()

X = np.array(X_list)
y = np.array(y_list)
date_idx = pd.Series([pd.Timestamp(d) for d in date_list])
print(f"Training shape → X: {X.shape}, y: {y.shape}")

# ════════════════════════════════════════════════════════════
# 6. TIME-BASED TRAIN / VALIDATION / TEST SPLIT
#    80% train (up to 2025-10-31)
#    10% validation (2025-11-01 to 2026-01-31)
#    10% test (2026-02-01 onwards)
#    Prevents data leakage — model never sees future during training
# ════════════════════════════════════════════════════════════
train_end = pd.Timestamp('2025-10-31')
val_end   = pd.Timestamp('2026-01-31')

train_mask = date_idx <= train_end
val_mask   = (date_idx > train_end) & (date_idx <= val_end)
test_mask  = date_idx > val_end

X_train, y_train = X[train_mask], y[train_mask]
X_val, y_val     = X[val_mask], y[val_mask]
X_test, y_test   = X[test_mask], y[test_mask]

print(f"Train: {len(X_train)} sequences (up to {train_end.date()})")
print(f"Val:   {len(X_val)} sequences ({train_end.date()} to {val_end.date()})")
print(f"Test:  {len(X_test)} sequences (after {val_end.date()})")

# ════════════════════════════════════════════════════════════
# 7. BUILD LSTM MODEL
#
# FIX 4: Smaller model (32→16 units) — large models overfit
#         on small datasets. Less capacity forces the model
#         to learn generalizable patterns.
# FIX 5: Higher dropout (0.3) — more regularization.
# FIX 6: Smaller batch size (16) — more gradient updates
#         per epoch helps on small datasets.
# ════════════════════════════════════════════════════════════
model = Sequential([
    Input(shape=(LOOKBACK, len(FEATURES))),
    LSTM(32, return_sequences=True),
    Dropout(0.3),                        # FIX 5: was 0.2
    LSTM(16, return_sequences=False),
    Dropout(0.3),                        # FIX 5: was 0.2
    Dense(8, activation='relu'),
    Dense(1, activation='linear')
])

model.compile(
    optimizer='adam',
    loss='mean_squared_error',
    metrics=['mean_absolute_error']
)
model.summary()

# ── Callbacks ─────────────────────────────────────────────
early_stop = EarlyStopping(
    monitor='val_loss',
    patience=15,                         # slightly more patience
    restore_best_weights=True,
    verbose=1
)

# Reduce learning rate when stuck — helps escape local minima
reduce_lr = ReduceLROnPlateau(
    monitor='val_loss',
    factor=0.5,
    patience=7,
    min_lr=1e-6,
    verbose=1
)

# ════════════════════════════════════════════════════════════
# 8. TRAIN
# ════════════════════════════════════════════════════════════
print("\nTraining LSTM...")
history = model.fit(
    X_train, y_train,
    validation_data=(X_val, y_val),
    epochs=200,
    batch_size=16,                       # FIX 6: was 64
    callbacks=[early_stop, reduce_lr],
    verbose=1
)

# Evaluate on held-out test set
if len(X_test) > 0:
    test_loss, test_mae = model.evaluate(X_test, y_test, verbose=0)
    print(f"\n📊 Test set performance (Feb 2026 onwards):")
    print(f"   Test loss: {test_loss:.4f}")
    print(f"   Test MAE:  {test_mae:.4f}")

# ════════════════════════════════════════════════════════════
# 9. SAVE + REPORT
# ════════════════════════════════════════════════════════════
model.save("models/lstm_productivity.keras")

best_val_loss = min(history.history['val_loss'])
best_val_mae  = min(history.history['val_mean_absolute_error'])
train_mae     = min(history.history['mean_absolute_error'])
epochs_ran    = len(history.history['loss'])

print(f"\n✅ Model saved → models/lstm_productivity.keras")
print(f"   Epochs run     : {epochs_ran}")
print(f"   Best val_loss  : {best_val_loss:.4f}")
print(f"   Best train_mae : {train_mae:.4f}")
print(f"   Best val_mae   : {best_val_mae:.4f}")

# ── Overfit check ──────────────────────────────────────────
overfit_gap = best_val_mae - train_mae
print(f"   Overfit gap    : {overfit_gap:.4f} (train_mae vs val_mae)")
if overfit_gap > 0.05:
    print("   ⚠️  Overfit detected — gap too large. Consider more dropout.")
else:
    print("   ✅ No significant overfit detected.")

# ── Quality check ──────────────────────────────────────────
if best_val_mae < 0.05:
    print("   Quality        : EXCELLENT — strong generalization")
elif best_val_mae < 0.10:
    print("   Quality        : GOOD — model learned meaningful patterns")
elif best_val_mae < 0.20:
    print("   Quality        : OK — acceptable for a thesis demo")
else:
    print("   Quality        : POOR — consider checking data or retraining")