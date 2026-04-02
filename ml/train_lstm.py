import numpy as np
import pandas as pd
import psycopg2
import joblib
import os
from sklearn.preprocessing import MinMaxScaler
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import LSTM, Dense, Dropout
from tensorflow.keras.callbacks import EarlyStopping
import sys
sys.path.append('../etl')
from config import PG_CONFIG

os.makedirs("models", exist_ok=True)

# ════════════════════════════════════════════════════════════
# 1. PULL DATA FROM POSTGRESQL
# ════════════════════════════════════════════════════════════
print("Fetching data from DW...")
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
    ORDER BY e.user_id, d.full_date ASC
""", pg_conn)

pg_conn.close()
print(f"Loaded {len(df)} rows for {df['user_id'].nunique()} employees.")

# ════════════════════════════════════════════════════════════
# 2. FEATURES AND TARGET
# ════════════════════════════════════════════════════════════
FEATURES = [
    'hours_worked',
    'is_late',
    'checked_in',
    'had_day_off',
    'tasks_completed',
    'avg_task_score',
    'avg_task_percentage'
]
TARGET   = 'productivity_score'
LOOKBACK = 30    # 30-day window — you have the data for it

df['is_late']     = df['is_late'].astype(int)
df['checked_in']  = df['checked_in'].astype(int)
df['had_day_off'] = df['had_day_off'].astype(int)
df.fillna(0, inplace=True)

# ════════════════════════════════════════════════════════════
# 3. SCALE THE DATA
# ════════════════════════════════════════════════════════════
scaler = MinMaxScaler()
all_cols = FEATURES + [TARGET]
df[all_cols] = scaler.fit_transform(df[all_cols])

# Save scaler — needed later for prediction
joblib.dump(scaler, "models/scaler.pkl")
print("Scaler saved → models/scaler.pkl")

# ════════════════════════════════════════════════════════════
# 4. BUILD SEQUENCES  (per employee)
#    X shape: (samples, LOOKBACK, num_features)
#    y shape: (samples,)
# ════════════════════════════════════════════════════════════
X_list, y_list = [], []

for user_id, group in df.groupby('user_id'):
    group = group.sort_values('full_date').reset_index(drop=True)
    values = group[all_cols].values   # (days, features+target)

    if len(values) < LOOKBACK + 1:
        print(f"  Skipping user {user_id} — only {len(values)} days (need {LOOKBACK+1})")
        continue

    for i in range(LOOKBACK, len(values)):
        X_list.append(values[i - LOOKBACK:i, :-1])  # features only
        y_list.append(values[i, -1])                  # target only

if len(X_list) == 0:
    print("⚠️  Not enough data to train (need more days per employee).")
    print("    Run your app longer to collect more check_in/task data,")
    print("    or lower LOOKBACK to 3 for testing.")
    exit()

X = np.array(X_list)   # (samples, LOOKBACK, n_features)
y = np.array(y_list)   # (samples,)
print(f"Training shape → X: {X.shape}, y: {y.shape}")

# ════════════════════════════════════════════════════════════
# 5. TRAIN / VALIDATION SPLIT
# ════════════════════════════════════════════════════════════
split = int(len(X) * 0.8)
X_train, X_val = X[:split], X[split:]
y_train, y_val = y[:split], y[split:]

# ════════════════════════════════════════════════════════════
# 6. BUILD LSTM MODEL
# ════════════════════════════════════════════════════════════
from tensorflow.keras.layers import Input

model = Sequential([
    Input(shape=(LOOKBACK, len(FEATURES))),
    LSTM(64, return_sequences=True),
    Dropout(0.2),
    LSTM(32, return_sequences=False),
    Dropout(0.2),
    Dense(16, activation='relu'),
    Dense(1,  activation='linear')    # ← KEY FIX: linear not sigmoid
])

model.compile(
    optimizer='adam',
    loss='mean_squared_error',
    metrics=['mean_absolute_error']
)
model.summary()

early_stop = EarlyStopping(
    monitor='val_loss',
    patience=10,
    restore_best_weights=True,
    verbose=1
)

# ════════════════════════════════════════════════════════════
# 7. TRAIN
# ════════════════════════════════════════════════════════════
print("\nTraining LSTM...")
history = model.fit(
    X_train, y_train,
    validation_data=(X_val, y_val),
    epochs=100,
    batch_size=64,     # ← bigger batch for 40k rows
    callbacks=[early_stop],
    verbose=1
)

# ════════════════════════════════════════════════════════════
# 8. SAVE + REPORT
# ════════════════════════════════════════════════════════════
model.save("models/lstm_productivity.keras")

best_loss = min(history.history['val_loss'])
best_mae  = min(history.history['mean_absolute_error'])
epochs_ran = len(history.history['loss'])

print(f"\n✅ Model saved → models/lstm_productivity.keras")
print(f"   Epochs run    : {epochs_ran}")
print(f"   Best val_loss : {best_loss:.4f}")
print(f"   Best val_mae  : {best_mae:.4f}")

# ── Quality check ─────────────────────────────────────────
if best_mae < 0.10:
    print("   Quality       : GOOD — model learned meaningful patterns")
elif best_mae < 0.20:
    print("   Quality       : OK — acceptable for a thesis demo")
else:
    print("   Quality       : POOR — consider checking data or retraining")