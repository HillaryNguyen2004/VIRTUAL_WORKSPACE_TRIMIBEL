"""
Flask API — LSTM Productivity Predictor (NEXT-DAY FORECAST)
============================================================

Predicts TOMORROW's productivity class for an employee, using the
last LOOKBACK days of behavior (window ends with TODAY).

Must match train_lstm_nextday.py + evaluate_classifier_nextday.py:
  • Same FEATURES list, same order
  • Same feature engineering (rolling rates, lag scores, etc.)
  • Same class thresholds: Low <50, Medium 50-79, High >=80
  • Same scaler.pkl + baseline.pkl
"""

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

model    = load_model("models/lstm_productivity_nextday.keras")
scaler   = joblib.load("models/scaler_nextday.pkl")
baseline = joblib.load("models/baseline_nextday.pkl")
model_lock = threading.Lock()

# ════════════════════════════════════════════════════════════
# FEATURES — must match train_lstm_nextday.py EXACTLY (27 total)
# ════════════════════════════════════════════════════════════
FEATURES = [
    # Personal context (2)
    'user_id_norm', 'score_vs_baseline',
    # Today's raw behavioural inputs (7)
    'hours_worked', 'is_late', 'checked_in', 'had_day_off',
    'tasks_completed', 'avg_task_score', 'avg_task_percentage',
    # Behavioural rates over past windows (6)
    'is_late_rate_7d', 'is_late_rate_14d',
    'checked_in_rate_7d', 'checked_in_rate_14d',
    'had_day_off_rate_7d', 'had_day_off_rate_14d',
    # Task / workload (3)
    'has_task_signal', 'task_workload', 'checkin_streak',
    # Lagged score signals (5)
    'score_yesterday', 'score_3d_ago', 'score_7d_ago',
    'score_delta_1d', 'score_delta_7d',
    # Past score window stats (3)
    'score_avg_7d', 'score_avg_14d', 'score_std_7d',
    # Calendar (1)
    'day_of_week',
]
TARGET   = 'productivity_score'
LOOKBACK = 14

# Class thresholds — must match training
CLASS_NAMES     = ['Low', 'Medium', 'High']
CLASS_THRESHOLDS = (50, 80)   # Low <50, Medium 50-79, High >=80
CLASS_MIDPOINTS = {
    'Low':    25.0,   # midpoint of 0-49
    'Medium': 65.0,   # midpoint of 50-79
    'High':   90.0,   # midpoint of 80-100
}

# How much history we pull from PG. We need:
#   LOOKBACK days of features (the window itself)
# + 14 days BEFORE that for shift(1).rolling(14) to be valid
# + a small safety margin
HISTORY_DAYS = LOOKBACK + 14 + 7   # = 35


# ════════════════════════════════════════════════════════════
# Helpers
# ════════════════════════════════════════════════════════════
def score_to_class_idx(score: float) -> int:
    if score >= CLASS_THRESHOLDS[1]: return 2
    if score >= CLASS_THRESHOLDS[0]: return 1
    return 0


def class_idx_to_score(class_idx: int) -> float:
    return CLASS_MIDPOINTS[CLASS_NAMES[class_idx]]


def predict_classification(X: np.ndarray):
    """Returns (predicted_class_idx, probabilities_list)."""
    with model_lock:
        pred_probs = model(X, training=False).numpy()[0]
    return int(np.argmax(pred_probs)), pred_probs.tolist()


def get_employee_history(user_id: int, n_days: int = HISTORY_DAYS) -> pd.DataFrame:
    """Pull the most recent n_days of records for an employee, oldest first."""
    query = text("""
        SELECT
            e.user_id,
            e.name          AS employee_name,
            d.full_date,
            f.hours_worked, f.is_late, f.checked_in, f.had_day_off,
            f.tasks_completed, f.avg_task_score, f.avg_task_percentage,
            f.productivity_score
        FROM fact_employee_productivity f
        JOIN dim_employee e ON f.employee_sk = e.employee_sk
        JOIN dim_date     d ON f.date_sk     = d.date_sk
        WHERE e.user_id = :user_id
        ORDER BY d.full_date DESC
        LIMIT :limit_n
    """)
    with pg_engine.connect() as conn:
        df = pd.read_sql(query, conn, params={"user_id": user_id, "limit_n": int(n_days)})
    df['full_date'] = pd.to_datetime(df['full_date'])
    df = df.sort_values('full_date').reset_index(drop=True)
    return df


def engineer_features(df: pd.DataFrame, user_id: int) -> pd.DataFrame:
    """Mirror train_lstm_nextday.py feature engineering exactly.

    df must contain a single user's records, sorted by full_date ascending.
    """
    df = df.copy()

    # Binary features
    df['is_late']     = df['is_late'].astype(int)
    df['checked_in']  = df['checked_in'].astype(int)
    df['had_day_off'] = df['had_day_off'].astype(int)

    # Personal baseline
    if user_id in baseline['user_id'].values:
        ub = baseline[baseline['user_id'] == user_id].iloc[0]
        personal_mean = float(ub['personal_mean'])
        personal_std  = max(float(ub['personal_std']), 1.0)
    else:
        # Unknown user — fall back to global mean of training data
        personal_mean = float(baseline['personal_mean'].mean())
        personal_std  = max(float(baseline['personal_std'].mean()), 1.0)

    df['score_vs_baseline'] = (df['productivity_score'] - personal_mean) / personal_std

    # user_id_norm — same map as training
    all_user_ids = sorted(baseline['user_id'].unique())
    uid_map = {uid: i / len(all_user_ids) for i, uid in enumerate(all_user_ids)}
    df['user_id_norm'] = float(uid_map.get(user_id, 0.5))

    # Rolling rates (single-user — no groupby needed)
    for col in ['is_late', 'checked_in', 'had_day_off']:
        df[f'{col}_rate_7d']  = df[col].shift(1).rolling(7,  min_periods=1).mean()
        df[f'{col}_rate_14d'] = df[col].shift(1).rolling(14, min_periods=1).mean()

    # Task signals
    df['has_task_signal'] = (
        (df['avg_task_score']      > 0) |
        (df['avg_task_percentage'] > 0) |
        (df['tasks_completed']     > 0)
    ).astype(int)
    df['task_workload'] = df['tasks_completed'] + df['avg_task_percentage'] / 100.0

    # Lag score features
    df['score_yesterday'] = df['productivity_score'].shift(1)
    df['score_3d_ago']    = df['productivity_score'].shift(3)
    df['score_7d_ago']    = df['productivity_score'].shift(7)
    df['score_delta_1d']  = df['score_yesterday'] - df['score_3d_ago']
    df['score_delta_7d']  = df['score_3d_ago']    - df['score_7d_ago']

    # Past score window stats
    df['score_avg_7d']  = df['productivity_score'].shift(1).rolling(7,  min_periods=1).mean()
    df['score_avg_14d'] = df['productivity_score'].shift(1).rolling(14, min_periods=1).mean()
    df['score_std_7d']  = df['productivity_score'].shift(1).rolling(7,  min_periods=1).std()

    # Check-in streak
    df['checkin_streak'] = (
        df['checked_in']
        .groupby((df['checked_in'] != df['checked_in'].shift()).cumsum())
        .cumcount() + 1
    ) * df['checked_in']

    # Day of week
    df['day_of_week'] = df['full_date'].dt.dayofweek

    df.fillna(0, inplace=True)
    return df


def get_trend(current_score: float, predicted_class_idx: int, recent_scores: list) -> str:
    """Compare predicted class against today's class; fall back to slope."""
    current_class_idx = score_to_class_idx(current_score)

    if predicted_class_idx > current_class_idx:
        return "improving"
    if predicted_class_idx < current_class_idx:
        return "declining"

    if len(recent_scores) >= 7:
        x = np.arange(len(recent_scores))
        slope = float(np.polyfit(x, recent_scores, 1)[0])
        if slope < -1.0: return "declining"
        if slope >  1.0: return "improving"

    return "stable"


def generate_prediction_for_user(user_id: int, employee_name: str = None) -> dict:
    logger.debug(f"[PREDICT] User {user_id}: starting next-day prediction")

    df = get_employee_history(user_id, HISTORY_DAYS)

    if 'employee_name' in df.columns and len(df):
        retrieved = df['employee_name'].iloc[0]
        if retrieved:
            employee_name = retrieved

    if len(df) < LOOKBACK + 14:
        # Not enough history for rolling-rate features to be meaningful
        raise ValueError(
            f"Insufficient history: {len(df)} days available, "
            f"need at least {LOOKBACK + 14} for stable rolling features"
        )

    df = engineer_features(df, user_id)

    # Track unscaled values BEFORE scaling
    current_score_raw = float(df['productivity_score'].iloc[-1])
    recent_scores_raw = df['productivity_score'].iloc[-7:].tolist()
    last_date         = df['full_date'].iloc[-1]

    # Scale (transform — never fit_transform!)
    all_cols = FEATURES + [TARGET]
    df[all_cols] = scaler.transform(df[all_cols])

    # Build the input window: last LOOKBACK rows, FEATURES only
    X = df[FEATURES].values[-LOOKBACK:, :]
    if X.shape != (LOOKBACK, len(FEATURES)):
        raise ValueError(f"Bad input shape: {X.shape}, expected ({LOOKBACK}, {len(FEATURES)})")
    X = np.expand_dims(X, axis=0)

    # Predict tomorrow's class
    predicted_class_idx, probabilities = predict_classification(X)
    predicted_level = CLASS_NAMES[predicted_class_idx]
    confidence      = float(probabilities[predicted_class_idx])
    predicted_score = class_idx_to_score(predicted_class_idx)

    trend = get_trend(current_score_raw, predicted_class_idx, recent_scores_raw)

    logger.debug(
        f"[PREDICT] User {user_id}: predicting for {(last_date + pd.Timedelta(days=1)).date()} "
        f"→ {predicted_level} (confidence={confidence:.3f})"
    )

    return {
        "user_id":            user_id,
        "name":               employee_name,
        "employee_name":      employee_name,
        # Classification outputs
        "predicted_level":    predicted_level,
        "predicted_class":    predicted_class_idx,
        "class_probabilities": {
            "Low":    round(float(probabilities[0]), 4),
            "Medium": round(float(probabilities[1]), 4),
            "High":   round(float(probabilities[2]), 4),
        },
        # Numeric score for dashboard bars (midpoint of predicted class)
        "predicted_productivity": round(predicted_score, 1),
        "productivity_score":     round(predicted_score / 100.0, 4),  # legacy
        "confidence":             round(confidence, 4),
        "confidence_score":       round(confidence, 4),
        # Today's actual score from warehouse
        "current_productivity":   round(current_score_raw, 2),
        "trend":                  trend,
        # Metadata
        "prediction_target_date": (last_date + pd.Timedelta(days=1)).strftime('%Y-%m-%d'),
        "based_on_data_through":  last_date.strftime('%Y-%m-%d'),
        "model_version":          "v3.0_nextday",
        "lookback":               LOOKBACK,
        "level":                  predicted_level,  # legacy alias
    }


# ════════════════════════════════════════════════════════════
# Routes
# ════════════════════════════════════════════════════════════
@app.route("/predict/<int:user_id>", methods=["GET"])
def predict(user_id):
    try:
        return jsonify(generate_prediction_for_user(user_id))
    except ValueError as e:
        logger.warning(f"[PREDICT] User {user_id}: {e}")
        return jsonify({"error": str(e), "user_id": user_id}), 400
    except Exception as e:
        logger.error(f"[PREDICT] User {user_id}: {e}", exc_info=True)
        return jsonify({"error": f"Prediction failed: {e}", "user_id": user_id}), 500


@app.route("/predict/all", methods=["POST"])
def predict_all():
    try:
        with pg_engine.connect() as conn:
            employees_df = pd.read_sql(
                text("SELECT DISTINCT user_id, name FROM dim_employee ORDER BY user_id"),
                conn,
            )

        results, errors = [], []
        for row in employees_df.itertuples(index=False):
            uid, name = int(row.user_id), row.name
            try:
                results.append(generate_prediction_for_user(uid, name))
            except ValueError as e:
                errors.append({"user_id": uid, "name": name, "error": str(e)})
            except Exception as e:
                logger.error(f"[PREDICT_ALL] User {uid}: {e}", exc_info=True)
                errors.append({"user_id": uid, "name": name, "error": f"{e}"})

        return jsonify({
            "total_employees": len(employees_df),
            "successful":      len(results),
            "failed":          len(errors),
            "predictions":     results,
            "errors":          errors or None,
        })

    except Exception as e:
        logger.error(f"[PREDICT_ALL] Fatal: {e}", exc_info=True)
        return jsonify({"error": f"Failed: {e}"}), 500


@app.route("/health", methods=["GET"])
def health():
    return jsonify({
        "status":           "ok",
        "model_version":    "v3.0_nextday",
        "lookback":         LOOKBACK,
        "n_features":       len(FEATURES),
        "class_thresholds": {"low_max": CLASS_THRESHOLDS[0], "high_min": CLASS_THRESHOLDS[1]},
    })


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5001, debug=False, threaded=False)