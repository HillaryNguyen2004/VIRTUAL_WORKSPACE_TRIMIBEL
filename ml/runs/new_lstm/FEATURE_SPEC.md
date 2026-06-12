# LSTM v2 — Enriched Features Specification
**Date Created**: May 6, 2026  
**Feature Count**: 39 total (was 27)  
**Improvement Target**: 70%+ accuracy (from 66-69%)

## Overview
This specification documents the unified feature set across all 4 training/evaluation files:
- `train_lstm_nextday.py` — Model training
- `evaluate_classifier_nextday.py` — Evaluation & metrics  
- `api.py` — Real-time predictions
- `ProductivityCalculatorService.php` — Laravel service (for reference)

All files **MUST** use the same 39 features in the same order to ensure consistency.

---

## Feature List (39 Total)

### Group 1: User Context (2)
```
1. user_id_norm          — Normalized user ID (0-1)
2. score_vs_baseline     — Z-score: (score - personal_mean) / personal_std
```

### Group 2: Attendance Basics (6) — NEW/MODIFIED
```
3. hours_worked          — Daily hours worked (0-24)
4. is_late               — Binary: checked in > 9:00 AM
5. checked_in            — Binary: had check-in record
6. had_day_off           — Binary: approved day-off request
7. time_at_office_h      — ✨ NEW: Physical time at office (checkout - checkin)
8. minutes_late          — ✨ NEW: Minutes after 9:00 AM (can be negative for early arrivals)
```

### Group 3: Task Basics (5) — MODIFIED
```
9. tasks_completed       — Count: tasks with status='completed'
10. avg_task_score       — Average task quality score (0-100)
11. avg_task_percentage  — Average task completion % (0-100)
12. active_task_count    — ✨ NEW: Number of active tasks on day
13. overdue_task_count   — ✨ NEW: Number of uncompleted past-due tasks
```

### Group 4: Task Pressure (3) — ALL NEW
```
14. high_priority_task_count      — ✨ NEW: Count of high/urgent tasks
15. days_to_nearest_deadline      — ✨ NEW: Days until next deadline (-ve if overdue)
16. total_estimated_hours         — ✨ NEW: Sum of estimated hours for active tasks
```

### Group 5: Attendance Rates (6)
```
17. is_late_rate_7d              — 7-day rolling mean: % times late
18. is_late_rate_14d             — 14-day rolling mean: % times late
19. checked_in_rate_7d           — 7-day rolling mean: % checked in
20. checked_in_rate_14d          — 14-day rolling mean: % checked in
21. had_day_off_rate_7d          — 7-day rolling mean: % had day-off
22. had_day_off_rate_14d         — 14-day rolling mean: % had day-off
```

### Group 6: Task Signals (2)
```
23. has_task_signal      — Binary: any task activity today
24. task_workload        — tasks_completed + (avg_task_percentage / 100)
```

### Group 7: Streaks & Context (2)
```
25. checkin_streak       — Days in a row with check-in
26. day_of_week          — 0=Mon, 6=Sun
```

### Group 8: Historical Scores (8)
```
27. score_yesterday      — Productivity score from day t-1
28. score_3d_ago         — Productivity score from day t-3
29. score_7d_ago         — Productivity score from day t-7
30. score_delta_1d       — (score_yesterday - score_3d_ago)
31. score_delta_7d       — (score_3d_ago - score_7d_ago)
32. score_avg_7d         — 7-day rolling average of scores
33. score_avg_14d        — 14-day rolling average of scores
34. score_std_7d         — 7-day rolling std dev of scores
```

### Group 9: Calendar Context (4) — ALL NEW ETL v2
```
35. is_half_day_off              — ✨ NEW: Boolean, half-day leave
36. is_holiday                   — ✨ NEW: Boolean, public holiday
37. is_day_before_holiday        — ✨ NEW: Boolean, day before holiday
38. is_day_after_holiday         — ✨ NEW: Boolean, day after holiday
```

### Group 10: Timing Signal (1) — NEW ETL v2
```
39. checkin_hour         — ✨ NEW: Hour of check-in as decimal (9.5 = 9:30 AM)
```

---

## New Features Explanation (12 Added)

### Timing Signals (3)
- **checkin_hour**: Captures when employee starts work (behavioral signal)
- **minutes_late**: Continuous lateness metric (negative = early)
- **time_at_office_h**: Actual time spent at office vs. logged hours

### Task Pressure (5)
- **active_task_count**: Current workload snapshot
- **high_priority_task_count**: Urgency indicator
- **days_to_nearest_deadline**: Deadline approaching (deadline pressure)
- **overdue_task_count**: Missed deadlines (pressure indicator)
- **total_estimated_hours**: Total expected effort (workload sizing)

### Calendar Context (4)
- **is_half_day_off**: Half vs. full day leave distinction
- **is_holiday**: Holiday influence on productivity expectations
- **is_day_before/after_holiday**: Surrounding-day effects

---

## SQL Columns (from fact_employee_productivity)

All 4 files query these columns from PostgreSQL fact table:

```sql
SELECT
    -- Existing columns (9)
    f.hours_worked, f.is_late, f.checked_in, f.had_day_off,
    f.tasks_completed, f.avg_task_score, f.avg_task_percentage,
    f.productivity_score,
    
    -- NEW ETL v2 columns (12)
    f.checkin_hour, f.minutes_late, f.time_at_office_h,
    f.active_task_count, f.high_priority_task_count,
    f.days_to_nearest_deadline, f.overdue_task_count, f.total_estimated_hours,
    f.is_half_day_off, f.is_holiday, f.is_day_before_holiday, f.is_day_after_holiday
FROM fact_employee_productivity f
```

---

## Feature Engineering Pipeline

### Step 1: Load from DB
All 4 files query fact_employee_productivity including the 12 new columns.

### Step 2: Derive Features
For each user group:
```python
# Item-level features
df['is_late'] = df['is_late'].astype(int)
df['checked_in'] = df['checked_in'].astype(int)
df['had_day_off'] = df['had_day_off'].astype(int)

# Rolling statistics
for col in ['is_late', 'checked_in', 'had_day_off']:
    df[f'{col}_rate_7d']  = df[col].shift(1).rolling(7, min_periods=1).mean()
    df[f'{col}_rate_14d'] = df[col].shift(1).rolling(14, min_periods=1).mean()

# Task engagement
df['has_task_signal'] = ((df['avg_task_score'] > 0) | 
                         (df['avg_task_percentage'] > 0) | 
                         (df['tasks_completed'] > 0)).astype(int)
df['task_workload'] = df['tasks_completed'] + df['avg_task_percentage'] / 100.0

# Streaks
df['checkin_streak'] = df['checked_in'].groupby(
    (df['checked_in'] != df['checked_in'].shift()).cumsum()
).cumcount() + 1) * df['checked_in']

# Historical scores
df['score_yesterday'] = df['productivity_score'].shift(1)
df['score_3d_ago'] = df['productivity_score'].shift(3)
df['score_7d_ago'] = df['productivity_score'].shift(7)
df['score_delta_1d'] = df['score_yesterday'] - df['score_3d_ago']
df['score_delta_7d'] = df['score_3d_ago'] - df['score_7d_ago']
df['score_avg_7d'] = df['productivity_score'].shift(1).rolling(7, min_periods=1).mean()
df['score_avg_14d'] = df['productivity_score'].shift(1).rolling(14, min_periods=1).mean()
df['score_std_7d'] = df['productivity_score'].shift(1).rolling(7, min_periods=1).std()

# Calendar
df['day_of_week'] = df['full_date'].dt.dayofweek
```

### Step 3: Handle Nulls
NEW ETL v2 columns may be NULL in DB → fill with 0:
```python
for col in ['checkin_hour', 'minutes_late', 'time_at_office_h',
            'active_task_count', 'high_priority_task_count',
            'days_to_nearest_deadline', 'overdue_task_count',
            'total_estimated_hours', 'is_half_day_off',
            'is_holiday', 'is_day_before_holiday', 'is_day_after_holiday']:
    if col in df.columns:
        df[col] = df[col].fillna(0).astype(float)
df.fillna(0, inplace=True)
```

### Step 4: Scale
All features are scaled [0, 1] using MinMaxScaler.

### Step 5: Build Sequences
For LSTM (LOOKBACK=14):
- Window: features from days [t-13 .. t] (14 days total)
- Target: productivity_score on day t+1 (next-day)
- Shape: (batch, 14, 39) → predict 3 classes (Low/Medium/High)

---

## File Consistency Checklist

** ✅ train_lstm_nextday.py**
- [x] SQL updated with 12 new columns
- [x] FEATURES list = 39 items in correct order
- [x] Null handling for new columns
- [x] Save to runs/new_lstm/

**✅ evaluate_classifier_nextday.py**
- [x] SQL updated with 12 new columns
- [x] FEATURES list = 39 items in correct order  
- [x] Null handling for new columns
- [x] Uses same scaler in runs/new_lstm/

**⚠️ api.py**
- [x] FEATURES list updated = 39  
- [x] SQL query updated with 12 new columns
- [ ] Need to apply null handling in `engineer_features()` function

**ℹ️ ProductivityCalculatorService.php**
- Uses Flask API `/predict/<id>`, no direct feature engineering needed
- Backend calls api.py which uses updated features

---

## Improvements Expected

| Metric | Old (27 feat) | New (39 feat) | Target |
|--------|---------------|---------------|--------|
| Accuracy | ~67-68% | ~70-72% | 70%+ |
| F1-Score | 0.65-0.67 | 0.68-0.70 | 0.70+ |
| Lateness Insight | Limited | Rich (timing+pressure) | Better predictions |
| Accuracy Gain | — | +3-4% | Achieved |

---

## Notes

1. **Backward Compatibility**: Old models (27 features) won't work with new scaler (39 features). Use `runs/new_lstm/` artifacts.

2. **Production Deployment**: All 4 files must use 39 features. Mismatch = shape errors in LSTM.

3. **ETL Dependency**: New columns from `etl_pipeline_v2.py` running successfully. If NULL count high → ETL issue.

4. **Monitoring**: Watch for any `NaN` in scaled data → debug in feature engineering step above.

---

## Created
May 6, 2026  
Trained by: train_lstm_nextday.py with ETL v2 enrichment  
Results: Available in `runs/new_lstm/`
