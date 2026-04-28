from flask import Flask, jsonify
import numpy as np
import pandas as pd
import psycopg2
import joblib
import threading
import logging
from sqlalchemy import create_engine, text
from sqlalchemy.engine import URL
from tensorflow.keras.models import load_model
import sys
sys.path.append('../etl')
from config import PG_CONFIG

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

model    = load_model("models/lstm_productivity.keras")
scaler   = joblib.load("models/scaler.pkl")
baseline = joblib.load("models/baseline.pkl")  # Load personal baseline
model_lock = threading.Lock()

# ── Must match train_lstm.py EXACTLY ────────────────── (20 total)
FEATURES = [
    # Personal context (2) — individual prediction
    'user_id_norm',
    'score_vs_baseline',
    # Core attendance (4)
    'hours_worked',
    'is_late',
    'checked_in',
    'had_day_off',
    # Task signals (5)
    'tasks_completed',
    'avg_task_score',
    'avg_task_percentage',
    'has_task_signal',
    'task_workload',
    # Temporal lag features (5)
    'score_yesterday',
    'score_3d_ago',
    'score_7d_ago',
    'score_delta_1d',
    'score_delta_7d',
    # Behavioral patterns (2)
    'checkin_streak',
    'day_of_week',
]
TARGET   = 'productivity_score'
LOOKBACK = 14  # must match train_lstm.py

# Class definitions — must match train_lstm.py thresholds (75→80, 55→50)
CLASS_NAMES   = ['Low', 'Medium', 'High']
CLASS_MIDPOINTS = {
    'Low':    25.0,   # midpoint of 0–50
    'Medium': 65.0,   # midpoint of 50–79
    'High':   90.0,   # midpoint of 80–100
}


def predict_classification(X: np.ndarray) -> tuple:
    """
    Returns (predicted_class_idx, probabilities_list).
    Uses model_lock so concurrent Flask requests don't race on TF graph.
    """
    with model_lock:
        pred_probs = model(X, training=False).numpy()[0]
    predicted_class_idx = int(np.argmax(pred_probs))
    return predicted_class_idx, pred_probs.tolist()


def class_idx_to_score(class_idx: int) -> float:
    """
    Convert a predicted class index to a representative score (0-100).
    Used so that the Laravel dashboard can keep showing numeric bars.
    """
    return CLASS_MIDPOINTS[CLASS_NAMES[class_idx]]


def get_last_n_days(user_id, n=LOOKBACK):
    query = text("""
        SELECT
            e.name          AS employee_name,
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
    # Sort ascending — oldest first — required for lag features
    df = df.sort_values('full_date').reset_index(drop=True)
    return df


def engineer_features(df: pd.DataFrame, user_id: int) -> pd.DataFrame:
    """
    Reproduces the exact feature engineering from train_lstm.py.
    Must be called AFTER sorting by full_date ascending.
    Note: lag shifts are done within a single user's sorted window,
    so groupby is not needed here (we already filtered to one user).
    """
    df = df.copy()

    df['is_late']     = df['is_late'].astype(int)
    df['checked_in']  = df['checked_in'].astype(int)
    df['had_day_off'] = df['had_day_off'].astype(int)

    # Task signals
    df['has_task_signal'] = (
        (df['avg_task_score'] > 0) |
        (df['avg_task_percentage'] > 0) |
        (df['tasks_completed'] > 0)
    ).astype(int)

    df['task_workload'] = df['tasks_completed'] + df['avg_task_percentage'] / 100.0

    # Lag features — shift within this single-employee window
    df['score_yesterday'] = df['productivity_score'].shift(1)
    df['score_3d_ago']    = df['productivity_score'].shift(3)
    df['score_7d_ago']    = df['productivity_score'].shift(7)
    df['score_delta_1d']  = df['score_yesterday'] - df['score_3d_ago']
    df['score_delta_7d']  = df['score_3d_ago']    - df['score_7d_ago']

    # Attendance streak
    df['checkin_streak'] = (
        df['checked_in']
        .groupby((df['checked_in'] != df['checked_in'].shift()).cumsum())
        .cumcount() + 1
    ) * df['checked_in']

    # Day of week (0=Mon … 6=Sun)
    df['day_of_week'] = pd.to_datetime(df['full_date']).dt.dayofweek

    df.fillna(0, inplace=True)

    # Personal baseline features (FIX 2)
    if user_id in baseline['user_id'].values:
        user_baseline = baseline[baseline['user_id'] == user_id].iloc[0]
        personal_mean = user_baseline['personal_mean']
        personal_std  = max(user_baseline['personal_std'], 1)  # avoid div by 0
        df['score_vs_baseline'] = (df['productivity_score'] - personal_mean) / personal_std
    else:
        df['score_vs_baseline'] = 0.0  # unknown user, default to 0

    # Normalised user ID (all users in the baseline set)
    all_user_ids = sorted(baseline['user_id'].unique())
    uid_map = {uid: i / len(all_user_ids) for i, uid in enumerate(all_user_ids)}
    df['user_id_norm'] = float(uid_map.get(user_id, 0.5))  # default to midpoint if not in baseline

    return df


def get_trend(current_score: float, predicted_class_idx: int, scores: list) -> str:
    """
    Compare predicted class against current score's class.
    Falls back to 7-day slope when the class is unchanged.
    """
    # Classify current score using train_lstm.py thresholds (updated)
    if current_score >= 80:
        current_class_idx = 2
    elif current_score >= 50:
        current_class_idx = 1
    else:
        current_class_idx = 0

    if predicted_class_idx > current_class_idx:
        return "improving"
    if predicted_class_idx < current_class_idx:
        return "declining"

    # Same class — check recent slope for fine-grained signal
    if len(scores) >= 7:
        recent = scores[-7:]
        x      = np.arange(len(recent))
        slope  = float(np.polyfit(x, recent, 1)[0])
        if slope < -1.0:
            return "declining"
        if slope > 1.0:
            return "improving"

    return "stable"


def generate_prediction_for_user(user_id: int, employee_name: str = None) -> dict:
    logger.debug(f"[PREDICT] User {user_id}: Starting prediction")

    df = get_last_n_days(user_id, LOOKBACK)

    # Extract employee name from query results
    if 'employee_name' in df.columns and len(df) > 0:
        retrieved = df['employee_name'].iloc[0]
        if retrieved:
            employee_name = retrieved

    if len(df) < LOOKBACK:
        raise ValueError(f"Insufficient data: {len(df)}/{LOOKBACK} days")

    df = engineer_features(df, user_id)

    # Raw (unscaled) current score — used for trend logic
    current_score_raw = float(df['productivity_score'].iloc[-1])
    recent_scores_raw = df['productivity_score'].iloc[-7:].tolist()

    # Scale using the saved scaler — same columns, same order as training
    all_cols = FEATURES + [TARGET]
    df[all_cols] = scaler.transform(df[all_cols])

    # Build input tensor: (1, LOOKBACK, num_features)
    X = df[FEATURES].values[-LOOKBACK:, :]
    X = np.expand_dims(X, axis=0)

    # Classify
    predicted_class_idx, probabilities = predict_classification(X)
    predicted_level = CLASS_NAMES[predicted_class_idx]
    confidence      = float(probabilities[predicted_class_idx])

    # Representative numeric score for the dashboard (midpoint of predicted class)
    predicted_score = class_idx_to_score(predicted_class_idx)

    logger.debug(
        f"[PREDICT] User {user_id}: class={predicted_level} "
        f"probs={[round(p, 3) for p in probabilities]}"
    )

    trend = get_trend(current_score_raw, predicted_class_idx, recent_scores_raw)

    return {
        "user_id":            user_id,
        "name":               employee_name,
        "employee_name":      employee_name,
        # Classification outputs
        "predicted_level":    predicted_level,       # "Low" | "Medium" | "High"
        "predicted_class":    predicted_class_idx,   # 0 | 1 | 2
        "class_probabilities": {
            "Low":    round(float(probabilities[0]), 4),
            "Medium": round(float(probabilities[1]), 4),
            "High":   round(float(probabilities[2]), 4),
        },
        # Numeric score for dashboard bars — midpoint of predicted class
        "predicted_productivity": round(predicted_score, 1),
        # Kept for Laravel backwards compatibility
        "productivity_score":     round(predicted_score / 100.0, 4),
        "confidence":             round(confidence, 4),
        "confidence_score":       round(confidence, 4),
        # Current (real) score from warehouse
        "current_productivity":   round(current_score_raw, 2),
        "trend":                  trend,
        "model_version":          "v2.0_classifier",
        "features_used":          FEATURES,
        "lookback":               LOOKBACK,
        "level": predicted_level,  # alias kept for old consumers
    }


@app.route("/predict/<int:user_id>", methods=["GET"])
def predict(user_id):
    try:
        result = generate_prediction_for_user(user_id)
        return jsonify(result)
    except ValueError as e:
        logger.warning(f"[PREDICT] User {user_id}: {e}")
        return jsonify({"error": str(e), "user_id": user_id}), 400
    except Exception as e:
        logger.error(f"[PREDICT] User {user_id}: {e}")
        return jsonify({"error": f"Prediction failed: {str(e)}", "user_id": user_id}), 500


@app.route("/predict/all", methods=["POST"])
def predict_all():
    try:
        with pg_engine.connect() as conn:
            employees_df = pd.read_sql(
                text("SELECT DISTINCT user_id, name FROM dim_employee ORDER BY user_id"),
                conn
            )

        employees = list(employees_df.itertuples(index=False, name=None))
        results, errors = [], []

        for (user_id, employee_name) in employees:
            try:
                result = generate_prediction_for_user(int(user_id), employee_name)
                results.append(result)
            except ValueError as e:
                errors.append({"user_id": user_id, "name": employee_name, "error": str(e)})
            except Exception as e:
                errors.append({"user_id": user_id, "name": employee_name,
                               "error": f"Prediction error: {str(e)}"})

        return jsonify({
            "total_employees": len(employees),
            "successful":      len(results),
            "failed":          len(errors),
            "predictions":     results,
            "errors":          errors if errors else None,
        })

    except Exception as e:
        logger.error(f"[PREDICT_ALL] Fatal: {e}")
        return jsonify({"error": f"Failed: {str(e)}"}), 500


@app.route("/health", methods=["GET"])
def health():
    return jsonify({"status": "ok", "model_version": "v2.0_classifier", "lookback": LOOKBACK})


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5001, debug=False, threaded=False)