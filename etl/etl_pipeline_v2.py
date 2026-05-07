import numpy as np
import pandas as pd
import psycopg2
from datetime import date, datetime, timedelta, time
from sqlalchemy import create_engine

from config import MYSQL_CONFIG, PG_CONFIG

# Re-use original ETL helpers
from etl_pipeline import (
    load_dim_date, load_dim_department, load_dim_employee,
    load_dim_project, load_dim_phase, load_dim_task,
    compute_productivity, get_emp_sk, get_date_sk, get_sk,
    mysql_engine, pg_conn, pg_cur,
)

# Standard work-day start time — anything later is "minutes late"
STANDARD_START = time(9, 0, 0)
# Days threshold for "approaching deadline" features
DEADLINE_HORIZON_DAYS = 30


# ════════════════════════════════════════════════════════════
# NEW: load_dim_holiday
# ════════════════════════════════════════════════════════════
def load_dim_holiday():
    """Load the holidays table from MySQL into dim_holiday."""
    print("Loading dim_holiday...")
    df = pd.read_sql(
        "SELECT title, start_date, end_date FROM holidays", mysql_engine
    )
    inserted = 0
    for _, row in df.iterrows():
        start = pd.Timestamp(row['start_date']).date()
        end   = pd.Timestamp(row['end_date']).date() if pd.notna(row['end_date']) else start

        # Some holidays span multiple days (e.g. Tet) — insert one row per day
        d = start
        while d <= end:
            pg_cur.execute("""
                INSERT INTO dim_holiday (full_date, title, end_date)
                VALUES (%s, %s, %s)
                ON CONFLICT (full_date) DO UPDATE
                    SET title = EXCLUDED.title, end_date = EXCLUDED.end_date
            """, (d, row['title'], end))
            d += timedelta(days=1)
            inserted += 1
    pg_conn.commit()
    print(f"dim_holiday done — {inserted} rows.")


def _load_holiday_set() -> set:
    """Return a set of holiday dates for fast lookup."""
    pg_cur.execute("SELECT full_date FROM dim_holiday")
    return {row[0] for row in pg_cur.fetchall()}


# ════════════════════════════════════════════════════════════
# NEW: enriched fact loader
# ════════════════════════════════════════════════════════════
def load_fact_v2():
    print("Loading fact table v2 (enriched)...")

    FALLBACK_START = date(2018, 1, 1)
    FALLBACK_END   = date(2030, 12, 31)

    # ── Holiday calendar ────────────────────────────────────
    holiday_dates = _load_holiday_set()
    print(f"  Loaded {len(holiday_dates)} holiday dates.")

    # ── Roles ───────────────────────────────────────────────
    roles_df = pd.read_sql("""
        SELECT u.id AS user_id, r.name AS role_name
        FROM users u
        LEFT JOIN model_has_roles mhr
               ON u.id = mhr.model_id
              AND mhr.model_type = 'App\\\\Models\\\\User'
        LEFT JOIN roles r ON mhr.role_id = r.id
    """, mysql_engine)
    user_role_map = {
        int(row['user_id']): str(row['role_name']) if pd.notna(row['role_name']) else 'user'
        for _, row in roles_df.iterrows()
    }

    # ── Check-ins ───────────────────────────────────────────
    checkins_df = pd.read_sql("""
        SELECT user_id, date,
               CAST(working_hours AS CHAR) AS working_hours,
               is_late, check_in_time, check_out_time
        FROM check_ins
        WHERE user_id IS NOT NULL
    """, mysql_engine)

    # ── Tasks (unchanged from v1, but used differently below) ─
    tasks_df = pd.read_sql("""
        SELECT id AS task_id, assigned_user_id, project_id, phase_id, parent_id,
               status, score, percentage, start_date, due_date, estimated_time, priority
        FROM tasks
        WHERE assigned_user_id IS NOT NULL
    """, mysql_engine)

    tasks_df['score']         = tasks_df['score'].fillna(0).astype(float)
    tasks_df['percentage']    = tasks_df['percentage'].fillna(0).astype(float)
    tasks_df['estimated_time'] = tasks_df['estimated_time'].fillna(1.0).astype(float)
    tasks_df['priority']      = tasks_df['priority'].fillna('medium').astype(str)

    # Roll up child scores to parents (same as v1)
    child_score_rollup = (
        tasks_df[tasks_df['score'] > 0]
        .groupby('parent_id')['score'].mean()
        .reset_index()
        .rename(columns={'parent_id': 'task_id', 'score': 'child_avg_score'})
    )
    tasks_df = tasks_df.merge(child_score_rollup, on='task_id', how='left')
    tasks_df['score'] = tasks_df.apply(
        lambda r: r['child_avg_score']
                  if r['score'] == 0 and pd.notna(r['child_avg_score'])
                  else r['score'],
        axis=1
    )
    tasks_df.drop(columns=['child_avg_score'], inplace=True)

    def to_date(val, fallback):
        if pd.isna(val): return fallback
        try: return pd.Timestamp(val).date()
        except Exception: return fallback

    tasks_df['start_py'] = tasks_df['start_date'].apply(lambda v: to_date(v, FALLBACK_START))
    tasks_df['end_py']   = tasks_df['due_date'].apply(lambda v: to_date(v, FALLBACK_END))

    # Phase title lookup
    phases_df = pd.read_sql("SELECT id, title FROM phases", mysql_engine)
    phase_title_map = {int(r['id']): r['title'] for _, r in phases_df.iterrows()}

    # ── Day-off (now with leave_type + half_day_period) ─────
    dayoff_df = pd.read_sql("""
        SELECT user_id, date, leave_type, half_day_period, status
        FROM day_off_requests
        WHERE status = 'APPROVED'
    """, mysql_engine)
    
    print(f"  Day-off records loaded: {len(dayoff_df)} rows")
    if len(dayoff_df) > 0:
        print(f"    Sample leave_types: {dayoff_df['leave_type'].unique()[:5]}")
    else:
        print("    WARNING: No approved day_off_requests found!")

    # ── Build per-user task lookup ──────────────────────────
    print("  Building task lookup by user...")
    task_lookup = {}
    for _, t in tasks_df.iterrows():
        uid = int(t['assigned_user_id'])
        task_lookup.setdefault(uid, []).append({
            'task_id':    int(t['task_id']),
            'project_id': int(t['project_id']) if pd.notna(t['project_id']) else None,
            'phase_id':   int(t['phase_id'])   if pd.notna(t['phase_id'])   else None,
            'status':     str(t['status']),
            'priority':   str(t['priority']).lower(),
            'score':      float(t['score']),
            'percentage': float(t['percentage']),
            'start_date': t['start_py'],
            'due_date':   t['end_py'],
            'est':        float(t['estimated_time']),
        })

    # ── (user, date) pairs ──────────────────────────────────
    date_user_pairs = set()
    checkin_index = {}
    for _, row in checkins_df.iterrows():
        uid = int(row['user_id'])
        date_user_pairs.add((uid, row['date']))
        checkin_index[(uid, row['date'])] = row

    dayoff_index = {}
    for _, row in dayoff_df.iterrows():
        uid = int(row['user_id'])
        date_user_pairs.add((uid, row['date']))
        dayoff_index.setdefault((uid, row['date']), []).append(row)

    print(f"  Processing {len(date_user_pairs)} (user, date) pairs...")
    inserted = 0

    for (user_id, record_date) in date_user_pairs:
        emp_sk  = get_emp_sk(user_id)
        date_sk = get_date_sk(record_date)
        if not emp_sk or not date_sk:
            continue

        r_date = pd.Timestamp(record_date).date()

        # ════════════════════════════════════════════════════
        # CHECK-IN — extract continuous timing signals
        # ════════════════════════════════════════════════════
        ci_row = checkin_index.get((user_id, record_date))
        check_in_time = check_out_time = None
        checkin_hour = checkout_hour = None
        minutes_late = None
        time_at_office_h = None

        if ci_row is not None:
            try:
                parts = str(ci_row['working_hours']).split(":")
                hours_worked = float(parts[0]) + float(parts[1]) / 60
            except Exception:
                hours_worked = 0.0

            is_late    = bool(ci_row['is_late'])
            checked_in = True
            check_in_time  = ci_row['check_in_time']  if pd.notna(ci_row['check_in_time'])  else None
            check_out_time = ci_row['check_out_time'] if pd.notna(ci_row['check_out_time']) else None

            # ── Timing signals ─────────────────────────────────────────
            # Convert TIME columns to decimal hours (e.g., 8:49 AM → 8.82)
            if check_in_time is not None:
                t = check_in_time
                if hasattr(t, 'seconds'):
                    # Timedelta: extract total seconds
                    total_seconds = t.seconds
                else:
                    # time object: compute from h:m:s
                    total_seconds = t.hour * 3600 + t.minute * 60 + t.second
                checkin_hour = total_seconds / 3600.0

                # Standard start time = 9:00 AM = 9.0 hours
                WORK_START = 9.0
                minutes_late = max(0, (checkin_hour - WORK_START) * 60)

            if check_out_time is not None:
                t = check_out_time
                if hasattr(t, 'seconds'):
                    total_seconds = t.seconds
                else:
                    total_seconds = t.hour * 3600 + t.minute * 60 + t.second
                checkout_hour = total_seconds / 3600.0

            if checkin_hour is not None and checkout_hour is not None:
                time_at_office_h = max(0, checkout_hour - checkin_hour)

        else:
            hours_worked = 0.0
            is_late = False
            checked_in = False

        # ════════════════════════════════════════════════════
        # DAY-OFF — full/half + period
        # ════════════════════════════════════════════════════
        do_rows = dayoff_index.get((user_id, record_date), [])
        had_day_off    = len(do_rows) > 0
        leave_type     = do_rows[0]['leave_type']      if had_day_off else None
        half_day_period = do_rows[0]['half_day_period'] if had_day_off else None
        is_half_day_off = had_day_off and leave_type == 'OFF_HALF'

        # ════════════════════════════════════════════════════
        # ACTIVE TASKS — extract pressure signals
        # Include tasks that started <= today (even if past due)
        # This allows overdue task counting
        # ════════════════════════════════════════════════════════
        active_tasks = [
            t for t in task_lookup.get(user_id, [])
            if t['start_date'] <= r_date
        ]

        # Counts (existing — unchanged)
        tasks_completed   = sum(1 for t in active_tasks if t['status'] == 'completed')
        tasks_in_progress = sum(1 for t in active_tasks if t['status'] == 'in_progress')

        # Score / percentage — only from "currently in date range" tasks
        currently_active = [t for t in active_tasks if t['start_date'] <= r_date <= t['due_date']]
        scored_tasks = [t for t in currently_active if t['score'] > 0]
        avg_task_score = (float(np.mean([t['score'] for t in scored_tasks]))
                          if scored_tasks else 0.0)
        avg_task_pct   = (float(np.mean([t['percentage'] for t in currently_active]))
                          if currently_active else 0.0)

        # NEW: pressure signals
        # Use "currently_active" for current workload, "active_tasks" for overdue detection
        active_task_count        = len(currently_active)
        high_priority_task_count = sum(
            1 for t in currently_active if t['priority'] in ('high', 'urgent')
        )
        # Sum estimated hours of currently active tasks
        total_estimated_hours = float(sum(t['est'] for t in currently_active))
        # Days to nearest open deadline + overdue count (from ALL active tasks)
        open_tasks = [t for t in active_tasks if t['status'] != 'completed']
        overdue_task_count = 0
        days_to_nearest_deadline = None
        
        if open_tasks:
            # Future deadlines for non-overdue tasks
            future_deadlines = []
            for t in open_tasks:
                days_diff = (t['due_date'] - r_date).days
                # Overdue: task not completed AND due_date < today (days_diff < 0)
                if days_diff < 0:
                    overdue_task_count += 1
                else:
                    future_deadlines.append(days_diff)
            
            # Nearest deadline from remaining future deadlines
            days_to_nearest_deadline = (
                min(future_deadlines) if future_deadlines else None
            )

        # Active phase title (uses last currently active task)
        active_phase_title = None
        if currently_active:
            latest = currently_active[-1]
            if latest['phase_id'] is not None:
                # First try the map, then fall back to direct DB lookup
                active_phase_title = phase_title_map.get(latest['phase_id'])
                if active_phase_title is None:
                    pg_cur.execute(
                        "SELECT title FROM dim_phase WHERE phase_id = %s",
                        (latest['phase_id'],)
                    )
                    phase_row = pg_cur.fetchone()
                    if phase_row:
                        active_phase_title = phase_row[0]

        # ════════════════════════════════════════════════════
        # CALENDAR — holiday context
        # ════════════════════════════════════════════════════
        is_holiday            = r_date in holiday_dates
        is_day_before_holiday = (r_date + timedelta(days=1)) in holiday_dates
        is_day_after_holiday  = (r_date - timedelta(days=1)) in holiday_dates

        # ════════════════════════════════════════════════════
        # DIMENSION KEYS (existing) — from currently active tasks
        # ════════════════════════════════════════════════════════
        task_sk = project_sk = phase_sk = None
        if currently_active:
            latest = currently_active[-1]
            task_sk = get_sk("dim_task", "task_id", latest['task_id'])
            project_sk = (get_sk("dim_project", "project_id", latest['project_id'])
                          if latest['project_id'] else None)
            phase_sk = (get_sk("dim_phase", "phase_id", latest['phase_id'])
                        if latest['phase_id'] else None)

        pg_cur.execute(
            "SELECT department_id FROM dim_employee WHERE employee_sk = %s", (emp_sk,)
        )
        dept_row = pg_cur.fetchone()
        dept_sk = (get_sk("dim_department", "dept_id", dept_row[0])
                   if dept_row and dept_row[0] else None)

        # ════════════════════════════════════════════════════
        # PRODUCTIVITY SCORE — UNCHANGED FORMULA
        # (this is the target variable; must not change)
        # ════════════════════════════════════════════════════
        user_role = user_role_map.get(user_id, 'user')
        prod_score = compute_productivity(
            hours_worked, is_late, checked_in,
            had_day_off, tasks_completed,
            avg_task_score, avg_task_pct,
            role_name=user_role
        )

        # ════════════════════════════════════════════════════
        # INSERT — with new columns
        # ════════════════════════════════════════════════════
        pg_cur.execute("""
            INSERT INTO fact_employee_productivity (
                employee_sk, date_sk, dept_sk, task_sk, project_sk, phase_sk,
                hours_worked, is_late, checked_in,
                had_day_off, leave_type,
                tasks_completed, tasks_in_progress,
                avg_task_score, avg_task_percentage,
                productivity_score, check_in_time, check_out_time,
                -- NEW columns:
                checkin_hour, checkout_hour, minutes_late, time_at_office_h,
                active_task_count, high_priority_task_count,
                days_to_nearest_deadline, overdue_task_count,
                total_estimated_hours,
                is_half_day_off, half_day_period,
                is_holiday, is_day_before_holiday, is_day_after_holiday,
                active_phase_title
            )
            VALUES (
                %s, %s, %s, %s, %s, %s,
                %s, %s, %s,
                %s, %s,
                %s, %s,
                %s, %s,
                %s, %s, %s,
                %s, %s, %s, %s,
                %s, %s,
                %s, %s,
                %s,
                %s, %s,
                %s, %s, %s,
                %s
            )
        """, (
            emp_sk, date_sk, dept_sk, task_sk, project_sk, phase_sk,
            hours_worked, is_late, checked_in,
            had_day_off, leave_type,
            tasks_completed, tasks_in_progress,
            avg_task_score, avg_task_pct,
            prod_score, check_in_time, check_out_time,
            # NEW:
            checkin_hour, checkout_hour, minutes_late, time_at_office_h,
            active_task_count, high_priority_task_count,
            days_to_nearest_deadline, overdue_task_count,
            total_estimated_hours,
            is_half_day_off, half_day_period,
            is_holiday, is_day_before_holiday, is_day_after_holiday,
            active_phase_title,
        ))
        inserted += 1
        if inserted % 5000 == 0:
            pg_conn.commit()
            print(f"    {inserted} rows committed...")

    pg_conn.commit()
    print(f"fact table v2 done — {inserted} rows inserted.")


# ════════════════════════════════════════════════════════════
# RUN ALL (v2)
# ════════════════════════════════════════════════════════════
def run_full_etl_v2():
    """
    Run the full ETL v2. Assumes migration_add_features.sql has been run.

    Truncates fact table first since column count changed — this is a
    rebuild, not an incremental update.
    """
    print("\n⚠️  This will TRUNCATE fact_employee_productivity and reload.")
    print("   Make sure migration_add_features.sql has been applied first.\n")

    pg_cur.execute("TRUNCATE TABLE fact_employee_productivity")
    pg_conn.commit()

    load_dim_date()
    load_dim_department()
    load_dim_employee()
    load_dim_project()
    load_dim_phase()
    load_dim_task()
    load_dim_holiday()
    load_fact_v2()
    print("\n✅ Full ETL v2 complete.")


if __name__ == "__main__":
    run_full_etl_v2()
