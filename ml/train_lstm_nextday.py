"""
LSTM Productivity Predictor — NEXT-DAY FORECAST
================================================

  • Window: features from days [t-13 .. t]   (14 days, ending today)
  • Target: TOMORROW's class   (Low / Medium / High)
  • Use:    "Given the past two weeks of behavior including today,
             forecast tomorrow's productivity level."

Thesis story:
  This is a true forecasting model — the productivity score for the
  prediction date is unknown to the model at training time.
  Random Forest ceiling on this dataset: ~69% accuracy.
  Naive baseline ("tomorrow's class = today's class"): ~66%.
  LSTM target: 67-72%. The small uplift over naive is the genuine
  learnable signal in the time series.

Stability fixes vs prior version:
  • Flat learning rate + ReduceLROnPlateau (was cyclical LR)
  • No artificial Low-class weight boost
  • Larger batch (128) for smoother gradients
  • ARIMA features replaced with rolling-mean rates
"""

import numpy as np
import pandas as pd
import psycopg2
import joblib
import os
import sys
from datetime import date

from sklearn.preprocessing import MinMaxScaler
from sklearn.utils.class_weight import compute_class_weight
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import LSTM, Dense, Dropout, Input
from tensorflow.keras.callbacks import EarlyStopping, ReduceLROnPlateau
from tensorflow.keras.optimizers import Adam

sys.path.append('../etl')
from config import PG_CONFIG

os.makedirs("models", exist_ok=True)

# ════════════════════════════════════════════════════════════
# SEED — uncomment for deterministic results
# ════════════════════════════════════════════════════════════
# import random, tensorflow as tf
# SEED = 42
# random.seed(SEED); np.random.seed(SEED); tf.random.set_seed(SEED)

# ════════════════════════════════════════════════════════════
# 1. PULL DATA
# ════════════════════════════════════════════════════════════
print("Fetching data from DW...")
TRAINING_CUTOFF = date.today()
print(f"Training cutoff: {TRAINING_CUTOFF}")

pg_conn = psycopg2.connect(**PG_CONFIG)
df = pd.read_sql("""
    SELECT e.user_id, d.full_date,
           f.hours_worked, f.is_late, f.checked_in, f.had_day_off,
           f.tasks_completed, f.avg_task_score, f.avg_task_percentage,
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
# 1.5  PERSONAL BASELINE
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
    (df['productivity_score'] - df['personal_mean']) / df['personal_std']
)
user_ids = sorted(df['user_id'].unique())
uid_map  = {uid: i / len(user_ids) for i, uid in enumerate(user_ids)}
df['user_id_norm'] = df['user_id'].map(uid_map)

joblib.dump(baseline, "models/baseline_nextday.pkl")
print("Baseline saved → models/baseline_nextday.pkl")

# ════════════════════════════════════════════════════════════
# 2.  FEATURE ENGINEERING
# ════════════════════════════════════════════════════════════
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

df['day_of_week'] = df['full_date'].dt.dayofweek
df.fillna(0, inplace=True)

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
print(f"Features: {len(FEATURES)}")

# ════════════════════════════════════════════════════════════
# 3.  SCALE
# ════════════════════════════════════════════════════════════
scaler = MinMaxScaler()
all_cols = FEATURES + [TARGET]
df[all_cols] = scaler.fit_transform(df[all_cols])
joblib.dump(scaler, "models/scaler_nextday.pkl")
print("Scaler saved → models/scaler_nextday.pkl")

# ════════════════════════════════════════════════════════════
# 4.  BUILD SEQUENCES — NEXT-DAY TARGET
#       Window: days [i-LOOKBACK+1 .. i]   (last day = today)
#       Target: productivity_score on day i+1 (tomorrow)
# ════════════════════════════════════════════════════════════
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
        date_list.append(dates[i + 1])  # date of the target

X = np.array(X_list)
y_scaled = np.array(y_list)
date_idx = pd.Series([pd.Timestamp(d) for d in date_list])
print(f"Sequences → X: {X.shape}, y: {y_scaled.shape}")

# ════════════════════════════════════════════════════════════
# 5.  CONVERT y → CLASS INDICES
# ════════════════════════════════════════════════════════════
def to_class_idx(score):
    if score >= 80: return 2
    if score >= 50: return 1
    return 0

target_idx = all_cols.index(TARGET)
score_min  = scaler.data_min_[target_idx]
score_max  = scaler.data_max_[target_idx]
y_raw = y_scaled * (score_max - score_min) + score_min
y     = np.array([to_class_idx(s) for s in y_raw])
print(f"Class dist: Low={int((y==0).sum())} | Med={int((y==1).sum())} | High={int((y==2).sum())}")

# ════════════════════════════════════════════════════════════
# 6.  TIME-BASED SPLIT
# ════════════════════════════════════════════════════════════
train_end = pd.Timestamp('2025-10-31')
val_end   = pd.Timestamp('2026-01-31')
train_mask = date_idx <= train_end
val_mask   = (date_idx > train_end) & (date_idx <= val_end)
test_mask  = date_idx > val_end

X_train, y_train = X[train_mask], y[train_mask]
X_val,   y_val   = X[val_mask],   y[val_mask]
X_test,  y_test  = X[test_mask],  y[test_mask]
print(f"Train: {len(X_train)} | Val: {len(X_val)} | Test: {len(X_test)}")

# ════════════════════════════════════════════════════════════
# 7.  CLASS WEIGHTS
# ════════════════════════════════════════════════════════════
weights = compute_class_weight('balanced', classes=np.array([0,1,2]), y=y_train)
class_weight_dict = {0: weights[0], 1: weights[1], 2: weights[2]}
print(f"Class weights: Low={weights[0]:.2f} | Med={weights[1]:.2f} | High={weights[2]:.2f}")

# ════════════════════════════════════════════════════════════
# 8.  MODEL
# ════════════════════════════════════════════════════════════
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
model.summary()

early_stop = EarlyStopping(
    monitor='val_loss',
    patience=15,
    restore_best_weights=True,
    verbose=1,
)
reduce_lr = ReduceLROnPlateau(
    monitor='val_loss',
    factor=0.5,
    patience=5,
    min_lr=1e-6,
    verbose=1,
)

# ════════════════════════════════════════════════════════════
# 9.  TRAIN
# ════════════════════════════════════════════════════════════
print("\nTraining LSTM (next-day forecast)...")
history = model.fit(
    X_train, y_train,
    validation_data=(X_val, y_val),
    epochs=120,
    batch_size=128,
    callbacks=[early_stop, reduce_lr],
    class_weight=class_weight_dict,
    verbose=1,
)

if len(X_test) > 0:
    test_loss, test_acc = model.evaluate(X_test, y_test, verbose=0)
    print(f"\n📊 Test set performance:")
    print(f"   Test loss:     {test_loss:.4f}")
    print(f"   Test accuracy: {test_acc*100:.2f}%")

# ════════════════════════════════════════════════════════════
# 10. SAVE + REPORT
# ════════════════════════════════════════════════════════════
model.save("models/lstm_productivity_nextday.keras")

best_val_loss = min(history.history['val_loss'])
best_val_acc  = max(history.history['val_accuracy'])
train_acc     = max(history.history['accuracy'])
epochs_ran    = len(history.history['loss'])

print(f"\n✅ Model saved → models/lstm_productivity_nextday.keras")
print(f"   Epochs run     : {epochs_ran}")
print(f"   Best val_loss  : {best_val_loss:.4f}")
print(f"   Best train_acc : {train_acc*100:.2f}%")
print(f"   Best val_acc   : {best_val_acc*100:.2f}%")

overfit_gap = train_acc - best_val_acc
print(f"   Overfit gap    : {overfit_gap*100:.2f}%")
if overfit_gap > 0.10:
    print("   ⚠️  Overfit detected.")
else:
    print("   ✅ No significant overfit.")

print("\nNote: The next-day forecast ceiling on this dataset is ~70% (RF baseline).")
print("Naive 'tomorrow = today' baseline is ~66%. Anything in 67-72% is meaningful.")