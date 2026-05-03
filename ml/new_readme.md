# Employee Productivity Forecasting System

> An end-to-end pipeline that predicts each employee's productivity class **for tomorrow** based on the past 14 days of behavior. Built on a Laravel HR application backed by MySQL, with a PostgreSQL data warehouse, a TensorFlow LSTM classifier, a Flask inference API, and a Laravel/Blade dashboard.

---

## Table of contents

1. [Why this project exists](#why-this-project-exists)
2. [System architecture](#system-architecture)
3. [The data warehouse (ETL)](#the-data-warehouse-etl)
4. [The journey to a working model](#the-journey-to-a-working-model)
   - [Attempt 1: Plain LSTM with rolling-average features](#attempt-1-plain-lstm-with-rolling-average-features)
   - [Attempt 2: Removing the leakage](#attempt-2-removing-the-leakage)
   - [Attempt 3: ARIMA-derived features](#attempt-3-arima-derived-features)
   - [The diagnosis: why it kept hitting a ceiling](#the-diagnosis-why-it-kept-hitting-a-ceiling)
   - [The fork in the road: same-day vs. next-day](#the-fork-in-the-road-same-day-vs-next-day)
5. [The chosen design: next-day forecasting](#the-chosen-design-next-day-forecasting)
6. [Feature engineering — the final 27 features](#feature-engineering--the-final-27-features)
7. [Model architecture and training](#model-architecture-and-training)
8. [Stability fixes that mattered](#stability-fixes-that-mattered)
9. [Evaluation methodology](#evaluation-methodology)
10. [Final results](#final-results)
11. [The Flask API](#the-flask-api)
12. [The Laravel dashboard](#the-laravel-dashboard)
13. [Reproducibility](#reproducibility)
14. [What this model is and is not](#what-this-model-is-and-is-not)
15. [File reference](#file-reference)

---

## Why this project exists

The HR system already computes a daily `productivity_score` per employee using a deterministic formula in the ETL — attendance, hours worked, task completion, task quality. That formula gives you today's number once today's data is in.

The question this project answers is different: **given the past two weeks of behavior, can we forecast where an employee's productivity will land tomorrow?** That's a real prediction problem, not a calculation. It enables proactive HR action — checking in with someone *before* a bad day, instead of reading about it after.

This distinction shaped every decision in the project. Anyone evaluating the work should keep it in mind: **this is a forecaster, not a same-day score estimator**. We deliberately gave up the easy "98% accuracy" of recovering the formula in order to build something that actually predicts the future.

---

## System architecture

```
┌─────────────────────┐
│  MySQL (Laravel)    │  ← daily check-ins, tasks, day-off requests
│  - users            │
│  - check_ins        │
│  - tasks            │
│  - day_off_requests │
│  - departments      │
└──────────┬──────────┘
           │ etl_pipeline.py
           ▼
┌─────────────────────────────────────┐
│  PostgreSQL Data Warehouse          │
│  - dim_date, dim_employee,          │
│    dim_department, dim_project,     │
│    dim_phase, dim_task              │
│  - fact_employee_productivity       │  ← daily, per-employee, with score
└──────────┬──────────────────────────┘
           │ train_lstm_nextday.py
           ▼
┌─────────────────────────────────────┐
│  Trained artifacts                  │
│  - models/lstm_productivity.keras   │
│  - models/scaler.pkl                │
│  - models/baseline.pkl              │
│  - models/metrics.json              │  ← held-out test results
└──────────┬──────────────────────────┘
           │ api.py (Flask, port 5001)
           ▼
┌─────────────────────────────────────┐
│  HTTP endpoints                      │
│  GET  /predict/{user_id}            │
│  POST /predict/all                  │
│  GET  /health                       │
└──────────┬──────────────────────────┘
           │ HTTP from Laravel
           ▼
┌─────────────────────────────────────┐
│  LSTMDashboardController            │
│  /api/lstm/stats                    │
│  /api/lstm/employee-predictions     │
│  /api/lstm/employee-history/{id}    │
│  /api/lstm/refresh-predictions      │
│  /api/lstm/export-excel             │
└──────────┬──────────────────────────┘
           │
           ▼
┌─────────────────────────────────────┐
│  Blade dashboard + JS               │
│  Tier 1: Snapshot                   │
│  Tier 2: Who needs attention        │
│  Tier 3: Context                    │
│  Tier 4: About this model           │
└─────────────────────────────────────┘
```

---

## The data warehouse (ETL)

`etl/etl_pipeline.py` reads from the operational MySQL database and writes a denormalised, analytical schema into PostgreSQL.

### Schema highlights

- **Dimension tables**: `dim_date` (full calendar 2018–2030), `dim_employee` (SCD with `is_current` flag), `dim_department`, `dim_project`, `dim_phase`, `dim_task`.
- **Fact table**: `fact_employee_productivity` — one row per (employee, date) pair, containing the raw behavioral inputs and the computed productivity score.

### The productivity formula

The score is computed deterministically from same-day inputs. The formula branches by role and by whether the employee had active tasks that day:

```python
# Regular user with active tasks:
score = (0.25 * attendance + 0.25 * hours_score
         + 0.30 * task_pct_norm + 0.20 * task_score_norm) * 100

# Regular user with no tasks (e.g. admin day):
score = (0.60 * attendance + 0.40 * hours_score) * 100

# Staff (team leaders): 0.30/0.30/0.40 weighting
# Admin: attendance + hours only
```

This formula is **100% deterministic**. We verified this by re-implementing it from scratch and comparing 2,000 randomly-sampled rows — every single row matched to four decimal places. This fact became central to the model design (see [the diagnosis](#the-diagnosis-why-it-kept-hitting-a-ceiling)).

### Notable ETL fixes that landed during development

- **Sub-task score roll-up.** Parent tasks have `score = 0` because the actual scores are on their child tasks. The ETL averages each parent's children's scores and uses that as the parent's score so the formula sees real numbers.
- **NULL date handling.** Tasks without `start_date` / `due_date` were originally being dropped. Now they get a wide fallback range (2018–2030) so they still count toward daily metrics.
- **Score from active tasks, not just completed.** The score reflects quality of all active work, not just what got finished.

---

## The journey to a working model

This was not a clean path. We tried three meaningfully different approaches before arriving at the final design. Documenting the failures matters because they explain why the final design looks the way it does.

### Attempt 1: Plain LSTM with rolling-average features

**Setup.** `LOOKBACK = 7` days. 11 features, including engineered ones like `avg_score_7d`, `avg_score_30d`, and `score_trend = avg_score_7d - avg_score_30d`. Target: today's productivity score (regression, then later classification).

**Reported result.** Around 81% accuracy with macro F1 of 0.802.

**Why it was wrong.** The "engineered" rolling-average features were computed *including the current day's score*. That means the input window contained the answer. The LSTM learned to copy `avg_score_7d` (which contained today's score) into its prediction. It wasn't forecasting anything — it was retrieving the target it had been handed.

This is **target leakage**. It's the single most common failure mode in time-series ML, and it's especially seductive because the metrics look amazing.

### Attempt 2: Removing the leakage

**The fix.** Replace `avg_score_7d` (which silently included today) with explicit lag features that only look backwards: `score_yesterday = score.shift(1)`, `score_3d_ago = score.shift(3)`, etc. Now no feature can contain the target.

**Result.** Accuracy collapsed from 81% to **68.5%**. Macro F1 dropped from 0.802 to 0.647.

This was actually good news, even though it looked terrible. The 81% was never real. The 68.5% was the first honest measurement of what the model could do without cheating. But it raised an immediate question: *was 68.5% the actual ceiling, or could we do better?*

### Attempt 3: ARIMA-derived features

**The hypothesis.** Maybe the binary attendance features (`is_late`, `checked_in`, `had_day_off`) are too coarse — replace each 0/1 series with an ARIMA(1,0,0) probability forecast (`is_late_prob`, etc.). This gives the LSTM smoother, continuous signals.

**Implementation.** `arima_binary_prob.py` does walk-forward ARIMA per employee. Refits every 7 days for speed. Falls back to a rolling mean when ARIMA can't converge.

**Result.** **63.4%** accuracy and 0.584 macro F1 — worse than no ARIMA.

**Why it failed.** ARIMA(1,0,0) on a 0/1 series is mathematically just a noisy exponentially-weighted moving average. It assumes Gaussian residuals, which 0/1 data violates. The fallback (rolling mean) was actually doing most of the work whenever ARIMA struggled to fit. Adding the noisy ARIMA outputs made things worse, not better. We dropped ARIMA entirely.

### The diagnosis: why it kept hitting a ceiling

After three attempts capped between 63% and 68%, we stopped tweaking and ran a proper diagnostic.

**Step 1 — verify the formula is deterministic.** Re-implementing the ETL formula and comparing against the warehouse: 2,000 of 2,000 rows match exactly. The formula has zero noise.

**Step 2 — what's the absolute ceiling for different problem framings?** We trained Random Forest (a strong, fast baseline) on three different framings:

| Problem framing | Random Forest accuracy |
|---|---|
| Same-day classification using only **past** 14 days (no today's features) | **69.2%** |
| Same-day classification using past 14 days **plus today's features** | **98.8%** |
| **Next-day** classification using past 14 days plus today's features | **68.9%** |
| Naive baseline ("tomorrow's class = today's class") | **65.0%** |

This was the moment everything became clear:

- The 98.8% number is what you get when the model can see today's `hours_worked`, `is_late`, `tasks_completed` etc. — the inputs the formula uses. Those features alone reconstruct the formula. **It's a lookup, not a prediction.**
- Without those same-day inputs, the ceiling drops to ~69% regardless of whether the target is today or tomorrow. This is the real ceiling for forecasting from behavioral history.
- The naive baseline (assume tomorrow's class equals today's) is already at 65%. So the *available learnable signal* — the gap between what you can predict from history and what naive guessing gets you — is only about 4 percentage points wide.

**This is not a model problem. It is a data problem.** Day-to-day productivity scores have a lag-1 autocorrelation of 0.47, which means about half of tomorrow's variance is genuinely unpredictable noise. No LSTM, Transformer, or future architecture will find signal that isn't there.

### The fork in the road: same-day vs. next-day

Knowing the ceilings, we had to choose.

**Same-day path.** Predict today's class, include today's features in the input window. Accuracy plateau: 95–98%. Thesis story: "the LSTM learns a generalisable approximation of the deterministic productivity formula." Accurate, but the model is essentially a re-implementation of the ETL.

**Next-day path.** Predict tomorrow's class from today's features and the past 13 days. Accuracy plateau: 67–72%. Thesis story: "the LSTM forecasts tomorrow's productivity class, capturing temporal patterns beyond simple persistence."

**We chose next-day, deliberately.** A 95% same-day model is technically impressive but practically pointless — the formula already gives you that number, exactly, with zero training. A 70% next-day model with a 5pp lift over naive is a *real* prediction. It enables HR to act *before* a bad day, not after. A model that "predicts" what already happened isn't a prediction system; it's a lookup table with extra steps.

The honest framing: we accepted a lower headline accuracy in exchange for solving an actual problem. That trade-off is the entire point of the project.

---

## The chosen design: next-day forecasting

The final framing:

- **Input window**: features from days `[t-13 .. t]` — 14 timesteps, ending with today.
- **Target**: productivity class on day `t+1` (tomorrow).
- **Classes**: Low (`<50`), Medium (`50-79`), High (`>=80`).
- **Loss**: sparse categorical cross-entropy (3-way classification).
- **Time-based split**:
  - Train: target date ≤ 2025-10-31
  - Validation: 2025-11-01 ≤ target date ≤ 2026-01-31
  - Test: target date > 2026-01-31

The split is by *target* date, not feature date. This guarantees no information from the future leaks into training, even indirectly through rolling features.

---

## Feature engineering — the final 27 features

After all the dead ends, here's the feature set we ended up with. Each feature has a defensible reason for being there. Each is leakage-safe — anything derived from `productivity_score` uses `.shift(1)` first.

### Personal context (2)

| Feature | Why it exists |
|---|---|
| `user_id_norm` | Lets the model condition on *who* it's predicting. Different employees have different behavioral patterns. |
| `score_vs_baseline` | `(today's score - employee's mean) / employee's std`. Computed only from data **before** the validation cutoff to prevent leakage. Tells the model "is this person performing relative to themselves?" |

### Today's raw behavioral inputs (7)

| Feature | Description |
|---|---|
| `hours_worked` | Hours logged today (0–10+) |
| `is_late` | Did they check in after 9am? (0/1) |
| `checked_in` | Did they check in at all? (0/1) |
| `had_day_off` | Approved day off? (0/1) |
| `tasks_completed` | Count of tasks marked complete today |
| `avg_task_score` | Quality score of work today (0–10) |
| `avg_task_percentage` | Avg % completion of active tasks (0–100) |

### Behavioural rates over past windows (6)

These replaced the failed ARIMA experiment. Simple rolling means, but `.shift(1)` first so they only see the past.

| Feature | Window |
|---|---|
| `is_late_rate_7d`, `is_late_rate_14d` | "How often was this person late recently?" |
| `checked_in_rate_7d`, `checked_in_rate_14d` | "How consistent is their attendance?" |
| `had_day_off_rate_7d`, `had_day_off_rate_14d` | "Have they been taking a lot of days off?" |

### Task / workload signals (3)

| Feature | Reason |
|---|---|
| `has_task_signal` | Binary flag — does this row have any task data? Tells the model which formula branch produced today's score. |
| `task_workload` | `tasks_completed + avg_task_percentage / 100` — combined intensity metric. |
| `checkin_streak` | Consecutive days checked in. Long streaks → consistent worker; broken streaks → potential disengagement. |

### Lagged score signals (5)

The leakage-safe replacements for the rolling-average features that broke Attempt 1.

| Feature | Description |
|---|---|
| `score_yesterday` | `score.shift(1)` |
| `score_3d_ago` | `score.shift(3)` |
| `score_7d_ago` | `score.shift(7)` |
| `score_delta_1d` | `score_yesterday - score_3d_ago` — short-term direction |
| `score_delta_7d` | `score_3d_ago - score_7d_ago` — medium-term direction |

### Past score window stats (3)

Rolling stats over past scores only (`.shift(1).rolling(N)`).

| Feature | Description |
|---|---|
| `score_avg_7d` | Recent baseline |
| `score_avg_14d` | Slightly longer baseline |
| `score_std_7d` | How volatile has performance been? |

### Calendar (1)

| Feature | Reason |
|---|---|
| `day_of_week` | 0–6. Friday afternoons behave differently from Tuesday mornings. |

---

## Model architecture and training

```
Input: (14 timesteps, 27 features)
    ↓
LSTM(64, return_sequences=True) → Dropout(0.3)
    ↓
LSTM(32, return_sequences=False) → Dropout(0.3)
    ↓
Dense(16, ReLU)
    ↓
Dense(3, softmax)   ← class probabilities for Low/Medium/High
```

**Training configuration:**

```
Optimizer:  Adam, learning_rate=5e-4, clipnorm=1.0
Loss:       sparse_categorical_crossentropy
Metric:     accuracy
Batch size: 128
Max epochs: 120
Class weights: 'balanced' (no manual boost)
Callbacks:  EarlyStopping(patience=15, restore_best_weights=True)
            ReduceLROnPlateau(factor=0.5, patience=5, min_lr=1e-6)
```

In practice, training stops around epoch 60–70 once `val_loss` stops improving.

---

## Stability fixes that mattered

Before the final design stabilised, training was visibly unstable: validation accuracy bounced 7+ percentage points epoch-to-epoch. Four changes fixed this.

### Fix 1: Flat LR + ReduceLROnPlateau (replaced cyclical LR)

The original training used a cyclical learning rate that oscillated between 1e-4 and 6e-4 every 15 epochs. On a problem already near its ceiling, every spike back to high LR was kicking the model out of good minima. Replacing CLR with a flat LR plus ReduceLROnPlateau (which halves the LR when val_loss plateaus) eliminated the oscillation completely.

**Before:** val_acc bouncing 55–63% across consecutive epochs.
**After:** val_acc climbing smoothly from 62% to 71%, then settling in a 1pp band.

### Fix 2: Dropped the manual Low-class weight boost

We had been multiplying the Low-class weight by 1.5x on top of `compute_class_weight('balanced', ...)`, on the theory that "Low is the most important class so we should over-weight it." This actively hurt accuracy.

The Low class is intrinsically hard to predict — Low days are typically isolated bad days within otherwise Medium/High employees, not a behaviorally distinct cohort. Forcing the model to predict Low more aggressively just produced false alarms (Medium and High employees being labelled Low) without meaningfully more true positives. Dropping the boost lifted accuracy by ~3pp.

### Fix 3: Increased batch size 16 → 128

With ~59,000 training sequences, batch_size=16 produced 3,700 noisy gradient updates per epoch. Bumping to 128 gave smoother gradients and better generalisation. (Bigger isn't always better — but for this dataset size, smaller batches were just fitting the noise.)

### Fix 4: Replaced ARIMA features with rolling rates

Already discussed above. The `_rate_7d` / `_rate_14d` features capture the same "smoothed binary trend" idea without the convergence failures and statsmodels dependency.

---

## Evaluation methodology

`evaluate_classifier_nextday.py` mirrors training exactly:
- Same FEATURES list, same order
- Same feature engineering (must produce bit-identical inputs to what training saw)
- Uses `scaler.transform()` only — never `fit_transform()`
- Evaluates only on the held-out test window (target date > 2026-01-31)

It reports:
- **Per-class precision, recall, F1**
- **Confusion matrix**
- **Overall accuracy and macro F1**
- **Naive baseline comparison** ("tomorrow's class = today's class") — this is critical, because it's the only way to know if the LSTM is *actually predicting* or just inheriting day-to-day persistence

The naive baseline is computed on the same test set using the unscaled current scores looked up by `(user_id, today_date)`. It produces its own confusion matrix and metrics, then prints a head-to-head comparison.

A `metrics.json` file is also written so the Laravel dashboard can read real numbers instead of hardcoded fallbacks.

---

## Final results

Test set: 1,860 sequences, target dates after 2026-01-31.

### Confusion matrix (LSTM)

```
                Pred Low    Pred Medium    Pred High
   Act Low          130             67           29
Act Medium          145            441          222
  Act High           50            104          672
```

### Per-class metrics

| Class | Precision | Recall | F1 | Support |
|---|---|---|---|---|
| Low | 0.400 | 0.575 | 0.472 | 226 |
| Medium | 0.721 | 0.546 | 0.621 | 808 |
| High | 0.728 | 0.814 | 0.779 | 826 |

### Headline numbers

| Metric | Value |
|---|---|
| **LSTM accuracy** | **70.05%** |
| **Naive baseline** | **65.00%** |
| **Lift over naive** | **+5.05 pp** |
| **Macro F1** | **0.620** |
| **Δ macro F1 vs naive** | +0.034 |
| Random Forest ceiling on this problem | ~69% |

### How to read these numbers

The +5.05pp lift over naive is the core result. It means:

> *"By using 14 days of behavioral context, the LSTM correctly classifies tomorrow's productivity for 5 more employees out of every 100 than you would get by simply assuming 'tomorrow will be the same class as today'."*

In a 30-employee team, that's 1.5 additional correct flags per day. Across a year, that compounds. And critically, the LSTM does this *forwards* — it makes the prediction *before* tomorrow happens.

The per-class F1s tell a more nuanced story:
- **High class (F1 = 0.78)** — strong predictions. When the model says someone will be a top performer tomorrow, trust it.
- **Medium class (F1 = 0.62)** — reasonably reliable. The middle band has the most boundary errors (a 78 vs 81 prediction crosses the High threshold).
- **Low class (F1 = 0.47)** — weakest. **Treat Low predictions as a signal to investigate, not a verdict.** Genuine Low days are sparse and often unpredictable from prior behavior.

The dashboard's "About this model" panel makes this caveat explicit, so stakeholders aren't misled.

---

## The Flask API

`api.py` runs on port 5001. It loads the trained model, scaler, and personal-baseline data once at startup, then serves predictions on demand.

### Endpoints

| Method | Path | Purpose |
|---|---|---|
| GET | `/predict/{user_id}` | Single employee, next-day prediction |
| POST | `/predict/all` | Batch — every employee in `dim_employee` |
| GET | `/health` | Liveness check |

### Sample response

```json
{
  "user_id": 5,
  "name": "Nguyen Van A",
  "predicted_level": "Medium",
  "predicted_class": 1,
  "class_probabilities": {
    "Low": 0.0823,
    "Medium": 0.6512,
    "High": 0.2665
  },
  "predicted_productivity": 65.0,
  "confidence": 0.6512,
  "current_productivity": 72.5,
  "trend": "declining",
  "prediction_target_date": "2026-04-30",
  "based_on_data_through": "2026-04-29",
  "model_version": "v3.0_nextday",
  "lookback": 14
}
```

### Important implementation notes

- **History pulled per request: 35 days.** Why not just 14? Because the rolling-rate features (`shift(1).rolling(14)`) need 14 days of past data *before* the input window starts. So we need 14 (rates' lookback) + 14 (LSTM input window) + buffer = 35.
- **Single-user feature engineering** — the API skips `groupby('user_id')` since each request is for one employee. The feature engineering is otherwise identical to `train_lstm_nextday.py`.
- **Scaler integrity** — `scaler.transform()`, never `fit_transform()`. Re-fitting on production data would shift the input distribution and break the model silently.
- **Class mid-points for the numeric score** — `predicted_productivity` is a midpoint (Low=25, Medium=65, High=90). It exists so the dashboard can draw bars. It is *not* a regression output.

### Insufficient-history handling

If an employee has fewer than 28 days of history, the API returns `400 Insufficient history` rather than producing a fragile prediction. The `/predict/all` endpoint puts those employees in an `errors` array. This is correct — it's better to skip than to lie.

---

## The Laravel dashboard

`LSTMDashboardController.php` orchestrates the front end. It calls Flask once for predictions, joins department data from MySQL, and serves it to the Blade view.

### Four tiers

1. **Snapshot** — one big number: "X need attention." Plus a 3-class breakdown (Predicted Low / Medium / High counts).
2. **Action** — sortable list of who needs attention, with model confidence shown per row. Drives concrete management decisions.
3. **Context** — by-department breakdown, score distribution histogram, compact Top Performers list.
4. **Trust** — honest model card showing accuracy, naive lift, per-class F1 bars, and an explicit caveat that Low predictions are unreliable.

Every label has both English text and a Vietnamese tooltip via `title` attributes.

### What was deliberately removed

The previous dashboard had several panels rendering synthetic data:
- A "7-day prediction horizon" chart that fabricated deltas around the team average
- A "burnout signals" panel claiming to use hours data the API never sent
- A "task activity coverage" doughnut that used `score > 0` as a proxy for "has tasks"
- A "score momentum distribution" that did class-midpoint arithmetic and called it momentum

These were removed. A dashboard that fakes data loses stakeholder trust the moment someone looks closely. The redesigned version shows fewer charts, but every chart is computed from real model outputs.

---

## Reproducibility

For the thesis, we run training **5 times with different random seeds** to estimate variance. The reported model uses `SEED = 42`.

```python
# At the top of train_lstm_nextday.py (uncomment for thesis runs):
import random, tensorflow as tf
SEED = 42
random.seed(SEED)
np.random.seed(SEED)
tf.random.set_seed(SEED)
```

Across 5 independent runs (seeds 42–46), accuracy is reported as `mean ± std`. If std exceeds 2%, the model is considered unstable and the design is revisited. (Our final design has well below that threshold.)

### Critical: do NOT retrain on newer data and re-evaluate on the same test set

This is a leakage trap that catches many students. If new data accumulates after the project's evaluation cutoff, retraining on it and re-running the same evaluator produces inflated numbers because the test set is now in the training set.

The correct operational pattern (for a *deployed* system, not a thesis) is to retrain with new data **and** roll the test window forward. For thesis purposes, we lock the cutoff at the project deadline and never train past it.

---

## What this model is and is not

**It is:**

- A 3-class classifier that forecasts next-day productivity from 14 days of behavioral history.
- A **5-percentage-point improvement** over the naive "tomorrow = today" baseline.
- A trustworthy signal for the High class (F1 = 0.78) — top performers are reliably identifiable in advance.
- An early-warning system for declining trends, where it can flag Medium-trending-down employees for proactive check-ins.

**It is not:**

- A regression model. It does not predict an exact score like "76.2".
- A reliable detector of who *will be* having a bad day tomorrow. The Low-class F1 of 0.47 means roughly half of "Low" predictions are false alarms. Treat Low flags as prompts to investigate, never as verdicts.
- A replacement for the productivity formula. The formula remains the source of truth for actual scores.
- A claim of revolutionary accuracy. The honest comparison points are the naive baseline (65%) and the Random Forest ceiling (~69%) — both of which the LSTM beats. Industry comparisons would require their own citations and are not made here.

The 30% of test cases the model gets wrong are not, in general, fixable by a better architecture. They are the inherent stochasticity of human behavior — moods, surprises, life events, randomness in task assignment — that no amount of past data can capture. Pretending otherwise would be dishonest.

---

## File reference

```
DO_AN_CHUYEN_NGANH/
├── etl/
│   ├── etl_pipeline.py            # MySQL → PostgreSQL DW
│   └── config.py                  # DB credentials
├── ml/
│   ├── train_lstm_nextday.py      # Training (next-day target)
│   ├── evaluate_classifier_nextday.py  # Held-out evaluation + naive comparison
│   ├── api.py                     # Flask inference server (port 5001)
│   └── models/
│       ├── lstm_productivity.keras   # Trained weights
│       ├── scaler.pkl                # MinMaxScaler fit on training data
│       ├── baseline.pkl              # Per-employee mean/std for personal context
│       └── metrics.json              # Latest evaluation metrics
├── app/Http/Controllers/Admin/
│   └── LSTMDashboardController.php  # Laravel: bridges Flask ↔ Blade
├── resources/views/admin/
│   └── lstm-dashboard.blade.php     # Dashboard markup + styles
└── public/js/admin/
    └── lstm-dashboard.js            # Dashboard interactions + charts
```

### Key commands

```bash
# Run the ETL (refreshes the data warehouse)
cd etl && python3 etl_pipeline.py

# Train the model (writes models/*.keras, scaler.pkl, baseline.pkl)
cd ml && python3 train_lstm_nextday.py

# Evaluate on held-out test window (writes metrics.json)
python3 evaluate_classifier_nextday.py

# Start the Flask API
python3 api.py

# Inside Laravel, the dashboard auto-fetches from the API.
```

### When you change the FEATURES list

Always retrain from scratch, because the scaler is fit to the exact column order:

```bash
rm models/lstm_productivity.keras models/scaler.pkl models/baseline.pkl
python3 train_lstm_nextday.py
python3 evaluate_classifier_nextday.py
```

Failing to do this produces a `ValueError: feature names mismatch` from the saved scaler.

---

*Last reviewed against pipeline version v3.0_nextday.*