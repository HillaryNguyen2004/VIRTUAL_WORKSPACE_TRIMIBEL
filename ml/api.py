"""
Flask API — LSTM Productivity Predictor (NEXT-DAY FORECAST)
============================================================

Endpoints:
  /predict/<id>     — single-employee prediction (lean)
  /predict/all      — bulk predictions WITH chatbot context (RAG-ready)
  /health           — service liveness

The bulk endpoint enriches each employee with numeric behavioural metrics
and pre-narrated explanation text. The pre-narrated fields are designed
to embed cleanly into a vector database so a chatbot can answer questions
like "why is this employee predicted Low?" without needing access to the
raw model.
"""

from flask import Flask, jsonify
import numpy as np
import pandas as pd
import joblib
import threading
import logging
from sqlalchemy import create_engine, text
from tensorflow.keras.models import load_model
import sys
sys.path.append('../etl')
from config import PG_URL

# ════════════════════════════════════════════════════════════
# Logging setup
# ════════════════════════════════════════════════════════════
# Force-configure logging — basicConfig may be a no-op if another import
# (sqlalchemy, tensorflow) has already registered a root handler.
log_handler = logging.StreamHandler()
log_handler.setFormatter(logging.Formatter(
    '%(asctime)s - %(name)s - %(levelname)s - %(message)s'
))
root_logger = logging.getLogger()
root_logger.handlers.clear()
root_logger.addHandler(log_handler)
root_logger.setLevel(logging.DEBUG)

logger = logging.getLogger(__name__)
logger.setLevel(logging.DEBUG)

# Quiet down noisy third-party loggers
logging.getLogger('werkzeug').setLevel(logging.INFO)
logging.getLogger('sqlalchemy.engine').setLevel(logging.WARNING)

app = Flask(__name__)

pg_engine = create_engine(PG_URL, pool_pre_ping=True)

model    = load_model("models/lstm_productivity_nextday.keras")
scaler   = joblib.load("models/scaler_nextday.pkl")
baseline = joblib.load("models/baseline_nextday.pkl")
model_lock = threading.Lock()

# ════════════════════════════════════════════════════════════
# FEATURES — must match train_lstm_nextday.py EXACTLY (27 total)
# ════════════════════════════════════════════════════════════
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
TARGET   = 'productivity_score'
LOOKBACK = 14

CLASS_NAMES      = ['Low', 'Medium', 'High']
CLASS_THRESHOLDS = (50, 80)
CLASS_MIDPOINTS  = {'Low': 25.0, 'Medium': 65.0, 'High': 90.0}

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
    with model_lock:
        pred_probs = model(X, training=False).numpy()[0]
    return int(np.argmax(pred_probs)), pred_probs.tolist()


def get_employee_history(user_id: int, n_days: int = HISTORY_DAYS) -> pd.DataFrame:
    query = text("""
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
    df = df.copy()

    df['is_late']     = df['is_late'].astype(int)
    df['checked_in']  = df['checked_in'].astype(int)
    df['had_day_off'] = df['had_day_off'].astype(int)

    if user_id in baseline['user_id'].values:
        ub = baseline[baseline['user_id'] == user_id].iloc[0]
        personal_mean = float(ub['personal_mean'])
        personal_std  = max(float(ub['personal_std']), 1.0)
    else:
        personal_mean = float(baseline['personal_mean'].mean())
        personal_std  = max(float(baseline['personal_std'].mean()), 1.0)

    df['score_vs_baseline'] = (df['productivity_score'] - personal_mean) / personal_std

    all_user_ids = sorted(baseline['user_id'].unique())
    uid_map = {uid: i / len(all_user_ids) for i, uid in enumerate(all_user_ids)}
    df['user_id_norm'] = float(uid_map.get(user_id, 0.5))

    for col in ['is_late', 'checked_in', 'had_day_off']:
        df[f'{col}_rate_7d']  = df[col].shift(1).rolling(7,  min_periods=1).mean()
        df[f'{col}_rate_14d'] = df[col].shift(1).rolling(14, min_periods=1).mean()

    df['has_task_signal'] = (
        (df['avg_task_score']      > 0) |
        (df['avg_task_percentage'] > 0) |
        (df['tasks_completed']     > 0)
    ).astype(int)
    df['task_workload'] = df['tasks_completed'] + df['avg_task_percentage'] / 100.0

    df['score_yesterday'] = df['productivity_score'].shift(1)
    df['score_3d_ago']    = df['productivity_score'].shift(3)
    df['score_7d_ago']    = df['productivity_score'].shift(7)
    df['score_delta_1d']  = df['score_yesterday'] - df['score_3d_ago']
    df['score_delta_7d']  = df['score_3d_ago']    - df['score_7d_ago']

    df['score_avg_7d']  = df['productivity_score'].shift(1).rolling(7,  min_periods=1).mean()
    df['score_avg_14d'] = df['productivity_score'].shift(1).rolling(14, min_periods=1).mean()
    df['score_std_7d']  = df['productivity_score'].shift(1).rolling(7,  min_periods=1).std()

    df['checkin_streak'] = (
        df['checked_in']
        .groupby((df['checked_in'] != df['checked_in'].shift()).cumsum())
        .cumcount() + 1
    ) * df['checked_in']

    df['day_of_week'] = df['full_date'].dt.dayofweek

    df.fillna(0, inplace=True)
    return df


def get_trend(current_score: float, predicted_class_idx: int, recent_scores: list) -> str:
    current_class_idx = score_to_class_idx(current_score)
    if predicted_class_idx > current_class_idx: return "improving"
    if predicted_class_idx < current_class_idx: return "declining"
    if len(recent_scores) >= 7:
        x = np.arange(len(recent_scores))
        slope = float(np.polyfit(x, recent_scores, 1)[0])
        if slope < -1.0: return "declining"
        if slope >  1.0: return "improving"
    return "stable"


def _personal_baseline_for(user_id: int):
    if user_id in baseline['user_id'].values:
        ub = baseline[baseline['user_id'] == user_id].iloc[0]
        return float(ub['personal_mean']), max(float(ub['personal_std']), 1.0)
    return (
        float(baseline['personal_mean'].mean()),
        max(float(baseline['personal_std'].mean()), 1.0),
    )


# ════════════════════════════════════════════════════════════
# Behavioral metrics + narration (used for chatbot context)
# ════════════════════════════════════════════════════════════
def _compute_behavioural_metrics(df_eng: pd.DataFrame, user_id: int) -> dict:
    """
    Build the four metric groups the chatbot will use to answer 'why' questions.
    All values are rounded for clean JSON / vector DB metadata.
    """
    last_row       = df_eng.iloc[-1]
    last_14        = df_eng.iloc[-14:]
    last_7         = df_eng.iloc[-7:]
    last_14_scores = last_14['productivity_score'].tolist()
    last_7_scores  = last_7['productivity_score'].tolist()

    personal_mean, personal_std = _personal_baseline_for(user_id)

    # Score trend slope (points per day, fitted over 14 days)
    if len(last_14_scores) >= 7:
        x = np.arange(len(last_14_scores))
        slope = float(np.polyfit(x, last_14_scores, 1)[0])
    else:
        slope = 0.0

    # Z-score of today vs personal baseline
    score_vs_baseline = (
        float(last_row['productivity_score']) - personal_mean
    ) / personal_std

    # Attendance group (relative to days the employee was supposed to work)
    attendance_rate_7d  = float(last_7['checked_in'].mean())
    attendance_rate_14d = float(last_14['checked_in'].mean())
    late_rate_7d        = float(last_row['is_late_rate_7d'])
    late_rate_14d       = float(last_row['is_late_rate_14d'])
    day_off_rate_14d    = float(last_row['had_day_off_rate_14d'])
    checkin_streak      = int(last_row['checkin_streak'])

    # Task group — averaged only over days that had task signal
    days_with_tasks_7d = last_7[last_7['has_task_signal'] == 1]
    if len(days_with_tasks_7d) > 0:
        avg_task_score_7d      = float(days_with_tasks_7d['avg_task_score'].mean())
        avg_task_completion_7d = float(days_with_tasks_7d['avg_task_percentage'].mean())
    else:
        avg_task_score_7d      = 0.0
        avg_task_completion_7d = 0.0
    tasks_completed_7d  = int(last_7['tasks_completed'].sum())
    task_signal_rate_7d = float(last_7['has_task_signal'].mean())

    # Hours group
    avg_hours_7d   = float(last_7['hours_worked'].mean())
    total_hours_7d = float(last_7['hours_worked'].sum())

    return {
        "attendance": {
            "attendance_rate_7d":  round(attendance_rate_7d,  3),
            "attendance_rate_14d": round(attendance_rate_14d, 3),
            "late_rate_7d":        round(late_rate_7d,        3),
            "late_rate_14d":       round(late_rate_14d,       3),
            "day_off_rate_14d":    round(day_off_rate_14d,    3),
            "checkin_streak":      checkin_streak,
        },
        "tasks": {
            "avg_task_score_7d":      round(avg_task_score_7d,      2),
            "avg_task_completion_7d": round(avg_task_completion_7d, 2),
            "tasks_completed_7d":     tasks_completed_7d,
            "task_signal_rate_7d":    round(task_signal_rate_7d,    3),
        },
        "scores": {
            "score_mean_7d":              round(float(np.mean(last_7_scores)),  2),
            "score_mean_14d":             round(float(np.mean(last_14_scores)), 2),
            "score_std_14d":              round(float(np.std(last_14_scores)),  2),
            "score_trend_slope":          round(slope, 2),
            "score_vs_personal_baseline": round(score_vs_baseline, 2),
            "personal_baseline_mean":     round(personal_mean, 2),
            "personal_baseline_std":      round(personal_std,  2),
        },
        "hours": {
            "avg_hours_7d":   round(avg_hours_7d,   2),
            "total_hours_7d": round(total_hours_7d, 2),
        },
    }


def _build_narrative(employee_name: str, metrics: dict,
                     predicted_level: str, current_score: float,
                     class_probs: dict) -> dict:
    """
    Build pre-narrated text fields for embedding in the vector DB.
    Returns:
        {
          "behavior_summary":     <one paragraph>,
          "notable_signals":      [<list of strings>],
          "prediction_rationale": <one sentence>,
        }
    """
    a   = metrics["attendance"]
    t   = metrics["tasks"]
    s   = metrics["scores"]
    h   = metrics["hours"]
    base_mean = s["personal_baseline_mean"]

    # ── notable_signals: each is a discrete observable fact ──────────
    signals = []

    # Late-rate observations
    if a["late_rate_7d"] > 0.30:
        signals.append(
            f"Late on {a['late_rate_7d']*100:.0f}% of the past 7 days "
            f"(14-day baseline: {a['late_rate_14d']*100:.0f}%)."
        )
    elif a["late_rate_7d"] > a["late_rate_14d"] + 0.15:
        signals.append(
            f"Late rate has increased recently: {a['late_rate_14d']*100:.0f}% "
            f"over 14 days, {a['late_rate_7d']*100:.0f}% in the last 7."
        )

    # Attendance observations
    if a["attendance_rate_7d"] < 0.85:
        signals.append(
            f"Attendance dropped to {a['attendance_rate_7d']*100:.0f}% "
            f"over the past 7 days "
            f"(14-day baseline: {a['attendance_rate_14d']*100:.0f}%)."
        )

    # Task quality observations
    if t["task_signal_rate_7d"] >= 0.5 and t["avg_task_score_7d"] > 0:
        if t["avg_task_score_7d"] < base_mean - 15:
            signals.append(
                f"Average task quality this week is {t['avg_task_score_7d']:.1f}, "
                f"well below the personal baseline of {base_mean:.1f}."
            )
        elif t["avg_task_score_7d"] > base_mean + 10:
            signals.append(
                f"Average task quality this week is {t['avg_task_score_7d']:.1f}, "
                f"above the personal baseline of {base_mean:.1f}."
            )

    # Score trend
    if s["score_trend_slope"] < -1.5:
        signals.append(
            f"Productivity scores are trending down "
            f"(~{s['score_trend_slope']:.1f} points per day)."
        )
    elif s["score_trend_slope"] > 1.5:
        signals.append(
            f"Productivity scores are trending up "
            f"(~{s['score_trend_slope']:.1f} points per day)."
        )

    # Vs personal baseline
    if s["score_vs_personal_baseline"] < -1.5:
        signals.append(
            f"Today's score is {abs(s['score_vs_personal_baseline']):.1f} "
            f"standard deviations below this employee's typical performance."
        )
    elif s["score_vs_personal_baseline"] > 1.5:
        signals.append(
            f"Today's score is {s['score_vs_personal_baseline']:.1f} "
            f"standard deviations above this employee's typical performance."
        )

    # Hours observations
    if h["avg_hours_7d"] > 9.0:
        signals.append(
            f"Average hours worked this week is {h['avg_hours_7d']:.1f} per day "
            f"(over standard 8-hour day)."
        )
    elif h["avg_hours_7d"] < 6.0 and a["attendance_rate_7d"] > 0.5:
        signals.append(
            f"Average hours worked this week is only {h['avg_hours_7d']:.1f} "
            f"per day despite {a['attendance_rate_7d']*100:.0f}% attendance."
        )

    # Volatility
    if s["score_std_14d"] > 20:
        signals.append(
            f"Performance has been highly variable "
            f"(σ = {s['score_std_14d']:.1f} over 14 days)."
        )

    # ── behavior_summary: one paragraph for embedding ────────────────
    summary_parts = [
        f"{employee_name} had a productivity score of {current_score:.1f} on the "
        f"most recent day (personal baseline: {base_mean:.1f} ± "
        f"{s['personal_baseline_std']:.1f})."
    ]
    summary_parts.append(
        f"Over the past 14 days, scores averaged {s['score_mean_14d']:.1f}; "
        f"the past 7 days averaged {s['score_mean_7d']:.1f}."
    )
    summary_parts.append(
        f"Attendance was {a['attendance_rate_7d']*100:.0f}% over the past 7 days "
        f"with a {a['late_rate_7d']*100:.0f}% late rate."
    )
    if t["task_signal_rate_7d"] > 0:
        summary_parts.append(
            f"Average task quality was {t['avg_task_score_7d']:.1f} and average "
            f"task completion was {t['avg_task_completion_7d']:.1f}% on the "
            f"{int(t['task_signal_rate_7d']*7)} days with task activity."
        )
    summary_parts.append(
        f"Average hours worked: {h['avg_hours_7d']:.1f} per day."
    )
    behavior_summary = " ".join(summary_parts)

    # ── prediction_rationale: one sentence linking signals to prediction ─
    direction = (
        "deteriorating recent indicators"
        if predicted_level == "Low"
        else "strong recent indicators"
        if predicted_level == "High"
        else "mixed recent indicators"
    )

    top_class = max(class_probs.items(), key=lambda kv: kv[1])
    confidence_qualifier = (
        "with high confidence"
        if top_class[1] > 0.75
        else "with moderate confidence"
        if top_class[1] > 0.55
        else "but with low confidence — the model also assigns substantial probability to the adjacent class"
    )

    rationale = (
        f"The model predicted {predicted_level} ({top_class[1]*100:.0f}%) "
        f"based on {direction} over the past two weeks, {confidence_qualifier}."
    )

    return {
        "behavior_summary":     behavior_summary,
        "notable_signals":      signals,
        "prediction_rationale": rationale,
    }


# ════════════════════════════════════════════════════════════
# Core prediction
# ════════════════════════════════════════════════════════════
def _build_prediction_core(user_id: int, employee_name: str = None,
                           include_chatbot_context: bool = False):
    """
    Run the full pipeline. If include_chatbot_context is True, also computes
    behavioural metrics and pre-narrated text for chatbot use.
    """
    logger.debug(
        f"[PREDICT] User {user_id}: starting next-day prediction "
        f"(chatbot_context={include_chatbot_context})"
    )

    raw_df = get_employee_history(user_id, HISTORY_DAYS)

    if 'employee_name' in raw_df.columns and len(raw_df):
        retrieved = raw_df['employee_name'].iloc[0]
        if retrieved:
            employee_name = retrieved

    if len(raw_df) < LOOKBACK + 14:
        raise ValueError(
            f"Insufficient history: {len(raw_df)} days available, "
            f"need at least {LOOKBACK + 14} for stable rolling features"
        )

    df_eng = engineer_features(raw_df, user_id)

    current_score_raw = float(df_eng['productivity_score'].iloc[-1])
    recent_scores_raw = df_eng['productivity_score'].iloc[-7:].tolist()
    last_date         = df_eng['full_date'].iloc[-1]

    all_cols = FEATURES + [TARGET]
    df_scaled = df_eng.copy()
    df_scaled[all_cols] = scaler.transform(df_scaled[all_cols])

    X = df_scaled[FEATURES].values[-LOOKBACK:, :]
    if X.shape != (LOOKBACK, len(FEATURES)):
        raise ValueError(f"Bad input shape: {X.shape}")
    X = np.expand_dims(X, axis=0)

    predicted_class_idx, probabilities = predict_classification(X)
    predicted_level = CLASS_NAMES[predicted_class_idx]
    confidence      = float(probabilities[predicted_class_idx])
    predicted_score = class_idx_to_score(predicted_class_idx)
    trend           = get_trend(current_score_raw, predicted_class_idx, recent_scores_raw)

    logger.info(
        f"[PREDICT] User {user_id} ({employee_name}): "
        f"target={(last_date + pd.Timedelta(days=1)).date()} "
        f"→ {predicted_level} (conf={confidence:.3f}, trend={trend})"
    )

    class_probs = {
        "Low":    round(float(probabilities[0]), 4),
        "Medium": round(float(probabilities[1]), 4),
        "High":   round(float(probabilities[2]), 4),
    }

    result = {
        "user_id":            user_id,
        "name":               employee_name,
        "employee_name":      employee_name,
        "predicted_level":    predicted_level,
        "predicted_class":    predicted_class_idx,
        "class_probabilities": class_probs,
        "predicted_productivity": round(predicted_score, 1),
        "productivity_score":     round(predicted_score / 100.0, 4),
        "confidence":             round(confidence, 4),
        "confidence_score":       round(confidence, 4),
        "current_productivity":   round(current_score_raw, 2),
        "trend":                  trend,
        "prediction_target_date": (last_date + pd.Timedelta(days=1)).strftime('%Y-%m-%d'),
        "based_on_data_through":  last_date.strftime('%Y-%m-%d'),
        "model_version":          "v3.0_nextday",
        "lookback":               LOOKBACK,
        "level":                  predicted_level,
    }

    if include_chatbot_context:
        metrics = _compute_behavioural_metrics(df_eng, user_id)
        narrative = _build_narrative(
            employee_name=employee_name or f"User {user_id}",
            metrics=metrics,
            predicted_level=predicted_level,
            current_score=current_score_raw,
            class_probs=class_probs,
        )
        result["metrics"]              = metrics
        result["behavior_summary"]     = narrative["behavior_summary"]
        result["notable_signals"]      = narrative["notable_signals"]
        result["prediction_rationale"] = narrative["prediction_rationale"]

    return result


# ════════════════════════════════════════════════════════════
# Routes
# ════════════════════════════════════════════════════════════
@app.route("/predict/<int:user_id>", methods=["GET"])
def predict(user_id):
    """Lean prediction (no chatbot context)."""
    try:
        return jsonify(_build_prediction_core(user_id, include_chatbot_context=False))
    except ValueError as e:
        logger.warning(f"[PREDICT] User {user_id}: {e}")
        return jsonify({"error": str(e), "user_id": user_id}), 400
    except Exception as e:
        logger.error(f"[PREDICT] User {user_id}: {e}", exc_info=True)
        return jsonify({"error": f"Prediction failed: {e}", "user_id": user_id}), 500


@app.route("/predict/all", methods=["POST"])
def predict_all():
    """
    Bulk prediction WITH chatbot context.

    Each employee record includes:
      • All standard prediction fields (your dashboard already uses these)
      • metrics: numeric behavioral aggregates (attendance, tasks, scores, hours)
      • behavior_summary: one-paragraph natural-language summary for embedding
      • notable_signals: array of specific observable facts
      • prediction_rationale: one-sentence explanation of the prediction

    The chatbot context fields (last 4) are designed to embed cleanly in a
    vector database, allowing a chatbot to answer 'why' questions by retrieving
    the relevant employee record.
    """
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
                results.append(
                    _build_prediction_core(uid, name, include_chatbot_context=True)
                )
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
        "class_thresholds": {"low_max": CLASS_THRESHOLDS[0],
                              "high_min": CLASS_THRESHOLDS[1]},
        "endpoints": {
            "predict":     "/predict/<id>  (lean payload)",
            "predict_all": "/predict/all   (bulk + chatbot context, POST)",
        },
    })


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5001, debug=False, threaded=False)