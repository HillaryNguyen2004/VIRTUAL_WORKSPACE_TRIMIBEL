# ETL Pipeline Guide (Productivity-Centered)

## 1) What this ETL does

This pipeline moves data from MySQL into a PostgreSQL warehouse and computes one daily productivity record per employee-date event.

Source: manage_user (MySQL)
Target: dw_productivity (PostgreSQL)
Main script: etl_pipeline.py

The design goal is productivity analytics first:
- Daily scoring that combines attendance, time investment, task progress, and task quality.
- Re-runnable dimension loading with upsert behavior.
- Fast-enough fact loading using in-memory lookup maps.

## 2) End-to-end flow

Run order in run_full_etl:
1. load_dim_date
2. load_dim_department
3. load_dim_employee
4. load_dim_project
5. load_dim_phase
6. load_dim_task
7. load_fact

Fact table produced: fact_employee_productivity

## 3) Productivity logic (current behavior)

This section reflects the actual logic in etl_pipeline.py.

### 3.1 Record grain

The pipeline creates fact rows for pairs found from:
- check-ins by employee/date
- approved day-off requests by employee/date

If an employee has only tasks but no check-in/day-off record for a date, that date is not generated as a fact row in the current implementation.

### 3.2 Inputs used by the score

For each employee/date row:
- hours_worked parsed from check_ins.working_hours (HH:MM)
- checked_in and is_late from check_ins
- had_day_off and leave_type from approved day_off_requests
- active_tasks from tasks assigned to the employee and active on that date

Task activity window:

    start_date <= record_date <= due_date

Important fallback behavior for task dates:
- If task start_date is null, fallback start is 2018-01-01.
- If task due_date is null, fallback end is 2030-12-31.

This fallback protects productivity coverage when task dates are missing.

### 3.3 Task-derived metrics

From active tasks:
- tasks_completed: count where status = completed
- tasks_in_progress: count where status = in_progress
- avg_task_score: mean score from completed tasks only
- avg_task_percentage: mean percentage from all active tasks

### 3.4 Score formula

Special rule:
- If had_day_off is true and checked_in is false, score = 0.0 immediately.

Normalization:
- hours_score = min(hours_worked / 8, 1)
- attendance = 1.0 if checked_in and not late, 0.5 if checked_in and late, else 0.0
- task_score_norm = min(avg_task_score / 10, 1)  (task score scale is treated as 0 to 10)
- task_pct_norm = min(avg_task_percentage / 100, 1)

Branch A: has task signal

    productivity = (
        0.25 * attendance +
        0.25 * hours_score +
        0.30 * task_pct_norm +
        0.20 * task_score_norm
    ) * 100

Branch B: no task signal

    productivity = (
        0.60 * attendance +
        0.40 * hours_score
    ) * 100

Where has task signal means at least one of:
- tasks_completed > 0
- avg_task_score > 0
- avg_task_percentage > 0

Final score is rounded to 2 decimals.

### 3.5 Why this is productivity-focused

- Work output influence is explicit: task progress + task quality together contribute 50 percent when task signal exists.
- Presence-only inflation is reduced by lowering attendance-only reliance once task evidence is available.
- Missing task dates do not silently drop work from analysis thanks to broad fallback ranges.

## 4) Data model coverage

Dimensions loaded:
- dim_date
- dim_department
- dim_employee
- dim_project
- dim_phase
- dim_task

Fact loaded:
- fact_employee_productivity with keys and metrics including:
  - employee_sk, date_sk, dept_sk, task_sk, project_sk, phase_sk
  - hours_worked, is_late, checked_in
  - had_day_off, leave_type
  - tasks_completed, tasks_in_progress
  - avg_task_score, avg_task_percentage
  - productivity_score
  - check_in_time, check_out_time

## 5) Productivity-oriented ETL performance design

load_fact builds lookup indexes before the main loop:
- normalized user name to user_id map
- task_lookup keyed by assigned_user_id
- checkin_index keyed by normalized name and date
- dayoff_index keyed by user_id and date

Why it matters:
- Avoids repeated full-table scanning inside the row loop.
- Keeps the expensive work in one preprocessing pass.
- Commits every 5000 inserted rows for stability on larger runs.

## 6) Rerun behavior and safe operation

Dimension tables:
- Upsert logic is used, so repeated runs update or maintain existing business keys.

Fact table:
- Current load_fact inserts rows without duplicate conflict handling.
- Re-running can create duplicates unless you clear fact table first.

Recommended run path:
1. Use run_etl_safe.py
2. Choose truncate fact table option when doing full refresh
3. Run ETL
4. Validate row counts and key coverage

## 7) How to run

From etl folder:

    python3 run.py

Safe runner with guided cleanup:

    python3 run_etl_safe.py

Full warehouse refresh:

    python3 full_refresh.py

## 8) Validation queries

Check score distribution:

    SELECT
      ROUND(productivity_score::numeric, 2) AS score,
      COUNT(*) AS records
    FROM fact_employee_productivity
    GROUP BY ROUND(productivity_score::numeric, 2)
    ORDER BY score DESC;

Check task-linked productivity coverage:

    SELECT
      COUNT(*) AS total_rows,
      COUNT(*) FILTER (WHERE task_sk IS NOT NULL) AS with_task,
      COUNT(*) FILTER (WHERE phase_sk IS NOT NULL) AS with_phase
    FROM fact_employee_productivity;

Check attendance-only branch candidates:

    SELECT
      COUNT(*) AS attendance_branch_rows
    FROM fact_employee_productivity
    WHERE tasks_completed = 0
      AND COALESCE(avg_task_score, 0) = 0
      AND COALESCE(avg_task_percentage, 0) = 0;

## 9) Known current constraints

- Fact rows are created from check-in/day-off event pairs, not a full calendar cross join.
- Task score normalization assumes score scale max is 10.
- Employee dimension currently inserts current rows when missing and marks them current.

## 10) Suggested next productivity upgrades

1. Add conflict key for fact table to prevent duplicates on rerun.
2. Add optional calendar expansion mode for complete day-by-day time series per employee.
3. Add weighted task quality using estimated_time.
4. Add branch-level diagnostics columns to explain why each score was produced.

Last updated: 2026-04-03
Version: 3.0
