from flask import Flask, jsonify
import numpy as np
import pandas as pd
import psycopg2
import joblib
from tensorflow.keras.models import load_model
import sys
sys.path.append('../etl')
from config import PG_CONFIG

app = Flask(__name__)

# Load once at startup
model  = load_model("models/lstm_productivity.keras")
scaler = joblib.load("models/scaler.pkl")

FEATURES = [
    'hours_worked', 'is_late', 'checked_in',
    'had_day_off', 'tasks_completed',
    'avg_task_score', 'avg_task_percentage'
]
LOOKBACK = 7

def get_last_n_days(user_id, n=LOOKBACK):
    pg_conn = psycopg2.connect(**PG_CONFIG)
    df = pd.read_sql(f"""
        SELECT
            f.hours_worked, f.is_late, f.checked_in,
            f.had_day_off, f.tasks_completed,
            f.avg_task_score, f.avg_task_percentage,
            f.productivity_score
        FROM fact_employee_productivity f
        JOIN dim_employee e ON f.employee_sk = e.employee_sk
        JOIN dim_date     d ON f.date_sk     = d.date_sk
        WHERE e.user_id = {user_id}
        ORDER BY d.full_date DESC
        LIMIT {n}
    """, pg_conn)
    pg_conn.close()
    return df

@app.route("/predict/<int:user_id>", methods=["GET"])
def predict(user_id):
    df = get_last_n_days(user_id, LOOKBACK)

    if len(df) < LOOKBACK:
        return jsonify({
            "error": f"Not enough data. Need {LOOKBACK} days, have {len(df)}."
        }), 400

    # Convert booleans
    df['is_late']     = df['is_late'].astype(int)
    df['checked_in']  = df['checked_in'].astype(int)
    df['had_day_off'] = df['had_day_off'].astype(int)
    df.fillna(0, inplace=True)

    # Scale using saved scaler
    all_cols = FEATURES + ['productivity_score']
    df[all_cols] = scaler.transform(df[all_cols])

    # Reverse to chronological order, take features only
    X = df[FEATURES].values[::-1]            # (LOOKBACK, n_features)
    X = np.expand_dims(X, axis=0)            # (1, LOOKBACK, n_features)

    # Predict (returns scaled 0-1)
    pred_scaled = model.predict(X)[0][0]

    # Inverse transform → back to 0-100
    dummy = np.zeros((1, len(all_cols)))
    dummy[0, -1] = pred_scaled
    pred_original = scaler.inverse_transform(dummy)[0, -1]
    pred_original = round(float(np.clip(pred_original, 0, 100)), 2)

    return jsonify({
        "user_id": user_id,
        "productivity_score": pred_original / 100.0,  # Convert to 0-1 scale for Laravel consistency
        "predicted_productivity": pred_original,  # Keep original for backwards compatibility
        "confidence": 0.85,  # Add confidence score
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
            SELECT DISTINCT user_id
            FROM dim_employee
            ORDER BY user_id
        """)
        employees = cursor.fetchall()
        cursor.close()
        pg_conn.close()

        results = []
        errors = []

        for (user_id,) in employees:
            try:
                df = get_last_n_days(user_id, LOOKBACK)

                if len(df) < LOOKBACK:
                    errors.append({
                        "user_id": user_id,
                        "error": f"Insufficient data: {len(df)}/{LOOKBACK}"
                    })
                    continue

                # Convert booleans
                df['is_late'] = df['is_late'].astype(int)
                df['checked_in'] = df['checked_in'].astype(int)
                df['had_day_off'] = df['had_day_off'].astype(int)
                df.fillna(0, inplace=True)

                # Scale using saved scaler
                all_cols = FEATURES + ['productivity_score']
                df[all_cols] = scaler.transform(df[all_cols])

                # Reverse to chronological order, take features only
                X = df[FEATURES].values[::-1]
                X = np.expand_dims(X, axis=0)

                # Predict
                pred_scaled = model.predict(X, verbose=0)[0][0]

                # Inverse transform
                dummy = np.zeros((1, len(all_cols)))
                dummy[0, -1] = pred_scaled
                pred_original = scaler.inverse_transform(dummy)[0, -1]
                pred_original = round(float(np.clip(pred_original, 0, 100)), 2)

                results.append({
                    "user_id": user_id,
                    "productivity_score": pred_original / 100.0,
                    "predicted_productivity": pred_original,
                    "confidence": 0.85,
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
    app.run(host="0.0.0.0", port=5001, debug=True)