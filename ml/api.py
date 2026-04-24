from flask import Flask, jsonify
import numpy as np
import pandas as pd
import joblib
import threading
import logging
from sqlalchemy import create_engine, text
from sqlalchemy.engine import URL
from tensorflow.keras.models import load_model
import sys
sys.path.append('../etl')
from config import PG_CONFIG

# Configure logging
logging.basicConfig(
    level=logging.DEBUG,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

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
    # Temporal lag features
    'score_yesterday',
    'score_3d_ago',
    'score_7d_ago',
    'score_delta_1d',
    'score_delta_7d',
    # Behavioral patterns
    'checkin_streak',
]
TARGET   = 'productivity_score'
LOOKBACK = 14  # Match training configuration (train_lstm.py)

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

    # Task signal
    df['has_task_signal'] = (
        (df['avg_task_score'] > 0) |
        (df['avg_task_percentage'] > 0) |
        (df['tasks_completed'] > 0)
    ).astype(int)

    # Task workload
    df['task_workload'] = (
        df['tasks_completed'] + df['avg_task_percentage'] / 100.0
    )

    # Lag features — must match train_lstm.py exactly
    df['score_yesterday'] = df['productivity_score'].shift(1)
    df['score_3d_ago']    = df['productivity_score'].shift(3)
    df['score_7d_ago']    = df['productivity_score'].shift(7)
    df['score_delta_1d']  = df['score_yesterday'] - df['score_3d_ago']
    df['score_delta_7d']  = df['score_3d_ago']    - df['score_7d_ago']

    # Checkin streak
    df['checkin_streak'] = (
        df['checked_in']
        .groupby((df['checked_in'] != df['checked_in'].shift()).cumsum())
        .cumcount() + 1
    ) * df['checked_in']

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

def generate_prediction_for_user(user_id, employee_name=None):
    """
    Shared prediction logic used by both single and batch endpoints.
    Ensures consistency across all prediction requests.
    Returns a dict with prediction results or raises an exception.
    """
    logger.debug(f"[PREDICT] User {user_id}: Starting prediction")
    
    df = get_last_n_days(user_id, LOOKBACK)
    logger.debug(f"[PREDICT] User {user_id}: Retrieved {len(df)} rows")
    
    if 'employee_name' in df.columns and len(df) > 0:
        retrieved_name = df['employee_name'].iloc[0]
        if retrieved_name:
            employee_name = retrieved_name
    
    if not employee_name:
        employee_name = get_employee_name(user_id)
    
    logger.debug(f"[PREDICT] User {user_id}: Employee name = {employee_name}")

    if len(df) < LOOKBACK:
        raise ValueError(f"Insufficient data: {len(df)}/{LOOKBACK} days")

    # Apply feature engineering
    df = engineer_features(df)
    logger.debug(f"[PREDICT] User {user_id}: Raw scores = {df['productivity_score'].tolist()}")

    # Current score (most recent, after smoothing)
    current_score = df['productivity_score'].iloc[-1]
    
    # Get last 7 days of scores for trend analysis
    recent_scores = df['productivity_score'].iloc[-7:].tolist()
    logger.debug(f"[PREDICT] User {user_id}: Current score = {current_score}")

    # Scale using saved scaler
    all_cols = FEATURES + [TARGET]
    df[all_cols] = scaler.transform(df[all_cols])

    # Take last LOOKBACK records, features only
    X = df[FEATURES].values[-LOOKBACK:, :]
    X = np.expand_dims(X, axis=0)

    # Predict (using thread-safe model)
    pred_scaled = predict_scaled(X)

    # Inverse transform
    dummy = np.zeros((1, len(all_cols)))
    dummy[0, -1] = pred_scaled
    pred_original = scaler.inverse_transform(dummy)[0, -1]
    pred_original = round(float(np.clip(pred_original, 0, 100)), 2)
    
    logger.debug(f"[PREDICT] User {user_id}: Final prediction = {pred_original}")

    # Determine trend
    trend = get_trend(current_score, pred_original, recent_scores)

    return {
        "user_id": user_id,
        "name": employee_name,
        "employee_name": employee_name,
        "productivity_score": pred_original / 100.0,
        "predicted_productivity": pred_original,
        "current_productivity": round(float(current_score), 2),
        "confidence": 0.85,
        "trend": trend,
        "model_version": "v1.0",
        "features_used": FEATURES,
        "level": (
            "Excellent" if pred_original >= 80 else
            "Good" if pred_original >= 60 else
            "Average" if pred_original >= 40 else
            "Low"
        )
    }

@app.route("/predict/<int:user_id>", methods=["GET"])
def predict(user_id):
    try:
        result = generate_prediction_for_user(user_id)
        return jsonify(result)
    except ValueError as e:
        logger.warning(f"[PREDICT] User {user_id}: Validation error - {str(e)}")
        return jsonify({
            "error": str(e),
            "user_id": user_id
        }), 400
    except Exception as e:
        logger.error(f"[PREDICT] User {user_id}: Prediction failed - {str(e)}")
        return jsonify({
            "error": f"Prediction failed: {str(e)}",
            "user_id": user_id
        }), 500

@app.route("/predict/all", methods=["POST"])
def predict_all():
    """Generate predictions for all employees using consistent SQLAlchemy connection"""
    try:
        # Use SQLAlchemy for consistency (same as /predict/<user_id>)
        query = text("SELECT DISTINCT user_id, name FROM dim_employee ORDER BY user_id")
        
        logger.info("[PREDICT_ALL] Fetching all employees...")
        with pg_engine.connect() as conn:
            employees_df = pd.read_sql(query, conn)
        
        employees = list(employees_df.itertuples(index=False, name=None))
        logger.info(f"[PREDICT_ALL] Found {len(employees)} employees")
        
        results = []
        errors = []

        for idx, (user_id, employee_name) in enumerate(employees):
            try:
                logger.debug(f"[PREDICT_ALL] Processing {idx+1}/{len(employees)}: user_id={user_id}")
                result = generate_prediction_for_user(user_id, employee_name)
                results.append(result)
            except ValueError as e:
                logger.warning(f"[PREDICT_ALL] User {user_id}: Validation error - {str(e)}")
                errors.append({
                    "user_id": user_id,
                    "name": employee_name,
                    "error": str(e)
                })
            except Exception as e:
                logger.error(f"[PREDICT_ALL] User {user_id}: Prediction error - {str(e)}")
                errors.append({
                    "user_id": user_id,
                    "name": employee_name,
                    "error": f"Prediction error: {str(e)}"
                })

        logger.info(f"[PREDICT_ALL] Completed: {len(results)} successful, {len(errors)} failed")
        
        return jsonify({
            "total_employees": len(employees),
            "successful": len(results),
            "failed": len(errors),
            "predictions": results,
            "errors": errors if errors else None
        })

    except Exception as e:
        logger.error(f"[PREDICT_ALL] Fatal error: {str(e)}")
        return jsonify({"error": f"Failed to generate predictions: {str(e)}"}), 500

@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok"})

if __name__ == "__main__":
    # Disable debug reloader for TensorFlow model stability.
    app.run(host="0.0.0.0", port=5001, debug=False, threaded=False)