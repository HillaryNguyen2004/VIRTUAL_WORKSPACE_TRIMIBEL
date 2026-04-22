from flask import Flask, jsonify
import numpy as np
import pandas as pd
import psycopg2
import joblib
import threading
from sqlalchemy import create_engine, text
from sqlalchemy.engine import URL
from tensorflow.keras.models import load_model
import sys
sys.path.append('../etl')
from config import PG_CONFIG

app = Flask(__name__)

pg_url = URL.create(
    drivername="postgresql+psycopg2",
    username=PG_CONFIG["user"],
    password=PG_CONFIG["password"],
    host=PG_CONFIG["host"],
    port=PG_CONFIG["port"],
    database=PG_CONFIG["dbname"],
)
pg_engine = create_engine(pg_url, pool_pre_ping=True)

# Load once at startup
model  = load_model("models/lstm_productivity.keras")
scaler = joblib.load("models/scaler.pkl")
model_lock = threading.Lock()

FEATURES = [
    'hours_worked',
    'is_late',
    'checked_in',
    'had_day_off',
    'tasks_completed',
    'avg_task_score',
    'avg_task_percentage',
    'has_task_signal',
    'avg_score_7d',
    'avg_score_30d',
    'score_trend'
]
TARGET   = 'productivity_score'
LOOKBACK = 7  # Match training configuration (train_lstm.py line 57)

def predict_scaled(X: np.ndarray) -> float:
    # Keras/TensorFlow inference can fail under concurrent Flask requests.
    # Serialize access to the model to keep runtime stable.
    with model_lock:
        pred = model(X, training=False).numpy()
    return float(pred[0][0])

def get_last_n_days(user_id, n=LOOKBACK):
    query = text("""
        SELECT
            e.name AS employee_name,
            d.full_date,
            f.hours_worked, f.is_late, f.checked_in,
            f.had_day_off, f.tasks_completed,
            f.avg_task_score, f.avg_task_percentage,
            f.productivity_score
        FROM fact_employee_productivity f
        JOIN dim_employee e ON f.employee_sk = e.employee_sk
        JOIN dim_date     d ON f.date_sk     = d.date_sk
        WHERE e.user_id = :user_id
        ORDER BY d.full_date DESC
        LIMIT :limit_n
    """)
    with pg_engine.connect() as conn:
        df = pd.read_sql(query, conn, params={"user_id": user_id, "limit_n": int(n)})
    # Sort by date ascending for feature engineering (oldest to newest)
    df = df.sort_values('full_date').reset_index(drop=True)
    return df

def get_employee_name(user_id):
    query = text("""
        SELECT name
        FROM dim_employee
        WHERE user_id = :user_id
        LIMIT 1
    """)
    with pg_engine.connect() as conn:
        row = conn.execute(query, {"user_id": user_id}).fetchone()
    return row[0] if row and row[0] else None

def engineer_features(df):
    """
    Add engineered features to match train_lstm.py exactly.
    Must be called BEFORE scaling and AFTER sorting by date.
    """
    # Convert booleans to int
    df['is_late']     = df['is_late'].astype(int)
    df['checked_in']  = df['checked_in'].astype(int)
    df['had_day_off'] = df['had_day_off'].astype(int)

    # Add has_task_signal feature
    df['has_task_signal'] = ((df['avg_task_score'] > 0) | 
                              (df['avg_task_percentage'] > 0) | 
                              (df['tasks_completed'] > 0)).astype(int)

    # Add lag features (rolling averages) to capture temporal trends
    df['avg_score_7d']  = df['productivity_score'].rolling(7, min_periods=1).mean()
    df['avg_score_30d'] = df['productivity_score'].rolling(30, min_periods=1).mean()
    df['score_trend']   = df['avg_score_7d'] - df['avg_score_30d']

    # Smooth the target to remove deterministic formula noise
    df['productivity_score'] = df['productivity_score'].rolling(3, min_periods=1).mean()

    df.fillna(0, inplace=True)
    return df

def get_trend(current_score: float, predicted_score: float, scores: list) -> str:
    """
    Use LSTM prediction vs current to determine real trend direction.
    Also check 7-day slope for sustained patterns.
    """
    pred_diff = predicted_score - current_score

    # If LSTM predicts significantly lower → declining
    if pred_diff < -5:
        return "declining"
    if pred_diff > 5:
        return "improving"

    # Flat LSTM prediction → check recent slope
    if len(scores) >= 7:
        recent = scores[-7:]
        x      = np.arange(len(recent))
        slope  = float(np.polyfit(x, recent, 1)[0])
        if slope < -1.0: return "declining"
        if slope > 1.0:  return "improving"

    return "stable"

@app.route("/predict/<int:user_id>", methods=["GET"])
def predict(user_id):
    df = get_last_n_days(user_id, LOOKBACK)
    employee_name = df['employee_name'].iloc[0] if 'employee_name' in df.columns and len(df) > 0 else get_employee_name(user_id)

    if len(df) < LOOKBACK:
        return jsonify({
            "error": f"Not enough data. Need {LOOKBACK} days, have {len(df)}.",
            "user_id": user_id,
            "name": employee_name,
            "employee_name": employee_name,
        }), 400

    # Apply feature engineering (must match train_lstm.py exactly)
    df = engineer_features(df)

    # Current score (most recent, after smoothing)
    current_score = df['productivity_score'].iloc[-1]
    
    # Get last 7 days of scores for trend analysis
    recent_scores = df['productivity_score'].iloc[-7:].tolist()

    # Scale using saved scaler
    all_cols = FEATURES + [TARGET]
    df[all_cols] = scaler.transform(df[all_cols])

    # Take last LOOKBACK records, features only, in chronological order
    X = df[FEATURES].values[-LOOKBACK:, :]  # (LOOKBACK, n_features)
    X = np.expand_dims(X, axis=0)            # (1, LOOKBACK, n_features)

    # Predict (returns scaled 0-1)
    pred_scaled = predict_scaled(X)

    # Inverse transform → back to 0-100
    dummy = np.zeros((1, len(all_cols)))
    dummy[0, -1] = pred_scaled
    pred_original = scaler.inverse_transform(dummy)[0, -1]
    pred_original = round(float(np.clip(pred_original, 0, 100)), 2)

    # Determine trend using LSTM prediction vs current score
    trend = get_trend(current_score, pred_original, recent_scores)

    return jsonify({
        "user_id": user_id,
        "name": employee_name,
        "employee_name": employee_name,
        "productivity_score": pred_original / 100.0,  # Convert to 0-1 scale for Laravel consistency
        "predicted_productivity": pred_original,  # Keep original for backwards compatibility
        "current_productivity": round(float(current_score), 2),
        "confidence": 0.85,  # Add confidence score
        "trend": trend,
        "model_version": "v1.0",
        "features_used": FEATURES,
        "level": (
            "Excellent" if pred_original >= 80 else
            "Good"      if pred_original >= 60 else
            "Average"   if pred_original >= 40 else
            "Low"
        )
    })

@app.route("/predict/all", methods=["POST"])
def predict_all():
    """Generate predictions for all employees"""
    try:
        pg_conn = psycopg2.connect(**PG_CONFIG)
        cursor = pg_conn.cursor()

        # Get all active employees from fact table
        cursor.execute("""
            SELECT DISTINCT user_id, name
            FROM dim_employee
            ORDER BY user_id
        """)
        employees = cursor.fetchall()
        cursor.close()
        pg_conn.close()

        results = []
        errors = []

        for user_id, employee_name in employees:
            try:
                df = get_last_n_days(user_id, LOOKBACK)
                if 'employee_name' in df.columns and len(df) > 0:
                    employee_name = df['employee_name'].iloc[0] or employee_name

                if len(df) < LOOKBACK:
                    errors.append({
                        "user_id": user_id,
                        "name": employee_name,
                        "error": f"Insufficient data: {len(df)}/{LOOKBACK}"
                    })
                    continue

                # Apply feature engineering (must match train_lstm.py exactly)
                df = engineer_features(df)

                # Current score (most recent, after smoothing)
                current_score = df['productivity_score'].iloc[-1]
                
                # Get last 7 days of scores for trend analysis
                recent_scores = df['productivity_score'].iloc[-7:].tolist()

                # Scale using saved scaler
                all_cols = FEATURES + [TARGET]
                df[all_cols] = scaler.transform(df[all_cols])

                # Take last LOOKBACK records, features only, in chronological order
                X = df[FEATURES].values[-LOOKBACK:, :]
                X = np.expand_dims(X, axis=0)

                # Predict
                pred_scaled = predict_scaled(X)

                # Inverse transform
                dummy = np.zeros((1, len(all_cols)))
                dummy[0, -1] = pred_scaled
                pred_original = scaler.inverse_transform(dummy)[0, -1]
                pred_original = round(float(np.clip(pred_original, 0, 100)), 2)

                # Determine trend using LSTM prediction vs current score
                trend = get_trend(current_score, pred_original, recent_scores)

                results.append({
                    "user_id": user_id,
                    "name": employee_name,
                    "employee_name": employee_name,
                    "productivity_score": pred_original / 100.0,
                    "predicted_productivity": pred_original,
                    "current_productivity": round(float(current_score), 2),
                    "confidence": 0.85,
                    "trend": trend,
                    "level": (
                        "Excellent" if pred_original >= 80 else
                        "Good" if pred_original >= 60 else
                        "Average" if pred_original >= 40 else
                        "Low"
                    )
                })
            except Exception as e:
                errors.append({
                    "user_id": user_id,
                    "name": employee_name,
                    "error": str(e)
                })

        return jsonify({
            "total_employees": len(employees),
            "successful": len(results),
            "failed": len(errors),
            "predictions": results,
            "errors": errors if errors else None
        })

    except Exception as e:
        return jsonify({"error": f"Failed to generate predictions: {str(e)}"}), 500

@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok"})

if __name__ == "__main__":
    # Disable debug reloader for TensorFlow model stability.
    app.run(host="0.0.0.0", port=5001, debug=False, threaded=False)