import numpy as np
import psycopg2
import pandas as pd
from datetime import date, timedelta
from sqlalchemy import create_engine
from config import MYSQL_CONFIG, PG_CONFIG
import json
import os

mysql_engine = create_engine(
    f"mysql+pymysql://{MYSQL_CONFIG['user']}:{MYSQL_CONFIG['password']}"
    f"@{MYSQL_CONFIG['host']}/{MYSQL_CONFIG['database']}"
)

def get_pg():
    conn = psycopg2.connect(**PG_CONFIG)
    conn.autocommit = False
    return conn

pg_conn = get_pg()
pg_cur  = pg_conn.cursor()

# ════════════════════════════════════════════════════════════
# METADATA TRACKING
# ════════════════════════════════════════════════════════════
METADATA_FILE = "etl_max_ids.json"

def load_metadata():
    """Load the last max IDs that were processed."""
    if os.path.exists(METADATA_FILE):
        with open(METADATA_FILE, 'r') as f:
            return json.load(f)
    return {
        "max_department_id": 0,
        "max_user_id": 0,
        "max_project_id": 0,
        "max_phase_id": 0,
        "max_task_id": 0,
        "max_checkin_id": 0,
        "max_dayoff_id": 0,
        "last_fact_date": None,
    }

def save_metadata(metadata):
    """Save the updated max IDs."""
    with open(METADATA_FILE, 'w') as f:
        json.dump(metadata, f, indent=2, default=str)
    print(f"✅ Metadata saved: {METADATA_FILE}")

# ════════════════════════════════════════════════════════════
# INCREMENTAL DIM LOADS
# ════════════════════════════════════════════════════════════

def load_dim_department_incremental(metadata):
    """Load only new departments since last run."""
    print("Loading new departments...")
    max_id = metadata.get("max_department_id", 0)
    
    df = pd.read_sql(
        f"SELECT id, name FROM departments WHERE id > {max_id}",
        mysql_engine
    )
    
    if df.empty:
        print("  No new departments.")
        return metadata
    
    for _, row in df.iterrows():
        pg_cur.execute("""
            INSERT INTO dim_department (dept_id, dept_name)
            VALUES (%s, %s)
            ON CONFLICT (dept_id) DO UPDATE SET dept_name = EXCLUDED.dept_name
        """, (int(row['id']), row['name']))
    
    pg_conn.commit()
    metadata["max_department_id"] = int(df['id'].max())
    print(f"  ✓ Loaded {len(df)} new departments.")
    return metadata

def load_dim_employee_incremental(metadata):
    """Load only new or updated employees since last run."""
    print("Loading new/updated employees...")
    max_id = metadata.get("max_user_id", 0)
    
    df = pd.read_sql(f"""
        SELECT u.id, u.name, u.username, u.email,
               u.department_id, d.name AS dept_name,
               u.team_leader_id,
               DATE(u.created_at) AS hire_date
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.id > {max_id}
    """, mysql_engine)
    
    if df.empty:
        print("  No new employees.")
        return metadata
    
    for _, row in df.iterrows():
        pg_cur.execute("""
            INSERT INTO dim_employee
                (user_id, name, username, email, department_id,
                 dept_name, team_leader_id, hire_date, valid_from, is_current)
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,TRUE)
        """, (
            int(row['id']), row['name'], row['username'], row['email'],
            int(row['department_id'])  if pd.notna(row['department_id'])  else None,
            row['dept_name'],
            int(row['team_leader_id']) if pd.notna(row['team_leader_id']) else None,
            row['hire_date'], date.today()
        ))
    
    pg_conn.commit()
    metadata["max_user_id"] = int(df['id'].max())
    print(f"  ✓ Loaded {len(df)} new employees.")
    return metadata

def load_dim_project_incremental(metadata):
    """Load only new projects since last run."""
    print("Loading new projects...")
    max_id = metadata.get("max_project_id", 0)
    
    df = pd.read_sql(
        f"""SELECT id, title, description, staff_id, status, percentage, start_date, due_date 
           FROM projects WHERE id > {max_id}""",
        mysql_engine
    )
    
    if df.empty:
        print("  No new projects.")
        return metadata
    
    for _, row in df.iterrows():
        pg_cur.execute("""
            INSERT INTO dim_project
                (project_id, title, description, staff_id, status, percentage, start_date, due_date)
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s)
            ON CONFLICT (project_id) DO UPDATE
                SET title=EXCLUDED.title, description=EXCLUDED.description,
                    staff_id=EXCLUDED.staff_id, status=EXCLUDED.status,
                    percentage=EXCLUDED.percentage,
                    start_date=EXCLUDED.start_date, due_date=EXCLUDED.due_date
        """, (
            int(row['id']), row['title'], row['description'],
            int(row['staff_id']) if pd.notna(row['staff_id']) else None,
            row['status'],
            int(row['percentage']) if pd.notna(row['percentage']) else 0,
            row['start_date'], row['due_date']
        ))
    
    pg_conn.commit()
    metadata["max_project_id"] = int(df['id'].max())
    print(f"  ✓ Loaded {len(df)} new projects.")
    return metadata

def load_dim_phase_incremental(metadata):
    """Load only new phases since last run."""
    print("Loading new phases...")
    max_id = metadata.get("max_phase_id", 0)
    
    df = pd.read_sql(
        f"SELECT id, project_id, title, start_date, due_date FROM phases WHERE id > {max_id}",
        mysql_engine
    )
    
    if df.empty:
        print("  No new phases.")
        return metadata
    
    for _, row in df.iterrows():
        pg_cur.execute("""
            INSERT INTO dim_phase (phase_id, project_id, title, start_date, due_date)
            VALUES (%s,%s,%s,%s,%s)
            ON CONFLICT (phase_id) DO UPDATE
                SET project_id=EXCLUDED.project_id, title=EXCLUDED.title,
                    start_date=EXCLUDED.start_date, due_date=EXCLUDED.due_date
        """, (
            int(row['id']),
            int(row['project_id']) if pd.notna(row['project_id']) else None,
            row['title'], row['start_date'], row['due_date']
        ))
    
    pg_conn.commit()
    metadata["max_phase_id"] = int(df['id'].max())
    print(f"  ✓ Loaded {len(df)} new phases.")
    return metadata

def load_dim_task_incremental(metadata):
    """Load only new tasks since last run."""
    print("Loading new tasks...")
    max_id = metadata.get("max_task_id", 0)
    
    df = pd.read_sql(f"""
        SELECT id, title, assigned_user_id, status, priority,
               start_date, due_date, active, estimated_time,
               score, percentage,
               project_id, phase_id, parent_id
        FROM tasks
        WHERE id > {max_id}
    """, mysql_engine)
    
    if df.empty:
        print("  No new tasks.")
        return metadata
    
    for _, row in df.iterrows():
        pg_cur.execute("""
            INSERT INTO dim_task
                (task_id, title, assigned_user_id, status, priority,
                 start_date, due_date, active, estimated_time,
                 score, percentage,
                 project_id, phase_id, parent_id)
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
            ON CONFLICT (task_id) DO UPDATE
                SET title=EXCLUDED.title,
                    assigned_user_id=EXCLUDED.assigned_user_id,
                    status=EXCLUDED.status, priority=EXCLUDED.priority,
                    start_date=EXCLUDED.start_date, due_date=EXCLUDED.due_date,
                    active=EXCLUDED.active, estimated_time=EXCLUDED.estimated_time,
                    score=EXCLUDED.score, percentage=EXCLUDED.percentage,
                    project_id=EXCLUDED.project_id, phase_id=EXCLUDED.phase_id,
                    parent_id=EXCLUDED.parent_id
        """, (
            int(row['id']), row['title'],
            int(row['assigned_user_id']) if pd.notna(row['assigned_user_id']) else None,
            row['status'], row['priority'],
            row['start_date'] if pd.notna(row['start_date']) else None,
            row['due_date']   if pd.notna(row['due_date'])   else None,
            bool(row['active']) if pd.notna(row['active']) else True,
            float(row['estimated_time']) if pd.notna(row['estimated_time']) else None,
            float(row['score'])      if pd.notna(row['score'])      else 0.0,
            int(row['percentage'])   if pd.notna(row['percentage'])  else 0,
            int(row['project_id'])   if pd.notna(row['project_id'])  else None,
            int(row['phase_id'])     if pd.notna(row['phase_id'])    else None,
            int(row['parent_id'])    if pd.notna(row['parent_id'])   else None,
        ))
    
    pg_conn.commit()
    metadata["max_task_id"] = int(df['id'].max())
    print(f"  ✓ Loaded {len(df)} new tasks.")
    return metadata

# ════════════════════════════════════════════════════════════
# HELPERS (from etl_pipeline.py)
# ════════════════════════════════════════════════════════════
SK_COL_MAP = {
    "dim_department": "dept_sk",
    "dim_employee":   "employee_sk",
    "dim_task":       "task_sk",
    "dim_project":    "project_sk",
    "dim_phase":      "phase_sk",
    "dim_date":       "date_sk",
}

def get_sk(table, id_col, id_val):
    sk_col = SK_COL_MAP[table]
    pg_cur.execute(f"SELECT {sk_col} FROM {table} WHERE {id_col} = %s", (id_val,))
    row = pg_cur.fetchone()
    return row[0] if row else None

def get_emp_sk(user_id):
    pg_cur.execute(
        "SELECT employee_sk FROM dim_employee WHERE user_id=%s AND is_current=TRUE",
        (user_id,)
    )
    row = pg_cur.fetchone()
    return row[0] if row else None

def get_date_sk(d):
    pg_cur.execute("SELECT date_sk FROM dim_date WHERE full_date=%s", (d,))
    row = pg_cur.fetchone()
    return row[0] if row else None

# ════════════════════════════════════════════════════════════
# PRODUCTIVITY FORMULA (from etl_pipeline.py)
# ════════════════════════════════════════════════════════════
def compute_productivity(hours_worked, is_late, checked_in,
                          had_day_off, tasks_completed,
                          avg_task_score, avg_task_pct):
    if had_day_off and not checked_in:
        return 0.0

    hours_score     = min(hours_worked / 8.0, 1.0)
    attendance      = 1.0 if (checked_in and not is_late) else (0.5 if checked_in else 0.0)
    task_score_norm = min(avg_task_score / 10.0,  1.0)
    task_pct_norm   = min(avg_task_pct   / 100.0, 1.0)

    has_tasks = tasks_completed > 0 or avg_task_score > 0 or avg_task_pct > 0

    if has_tasks:
        score = (
            0.25 * attendance     +
            0.25 * hours_score    +
            0.30 * task_pct_norm  +
            0.20 * task_score_norm
        ) * 100
    else:
        score = (
            0.60 * attendance  +
            0.40 * hours_score
        ) * 100

    return round(score, 2)

# ════════════════════════════════════════════════════════════
# INCREMENTAL FACT TABLE LOAD
# Only loads data for recent dates (today + last N days)
# ════════════════════════════════════════════════════════════
def load_fact_incremental(metadata, days_back=30):
    """Load fact table only for recent dates.
    
    Args:
        metadata: Tracking dict
        days_back: How many days back to load (default: last 30 days)
    """
    print(f"Loading fact table for last {days_back} days...")
    
    FALLBACK_START = date(2018, 1, 1)
    FALLBACK_END   = date(2030, 12, 31)
    
    # Date range to load
    end_date = date.today()
    start_date = end_date - timedelta(days=days_back)
    
    print(f"  Loading dates: {start_date} to {end_date}")
    
    users_df = pd.read_sql(
        "SELECT id, name, username FROM users", mysql_engine
    )

    checkins_df = pd.read_sql(f"""
        SELECT user_name, date,
               CAST(working_hours AS CHAR) AS working_hours,
               is_late, check_in_time, check_out_time
        FROM check_ins
        WHERE date >= '{start_date}' AND date <= '{end_date}'
    """, mysql_engine)

    tasks_df = pd.read_sql("""
        SELECT
            id            AS task_id,
            assigned_user_id,
            project_id,
            phase_id,
            status,
            score,
            percentage,
            start_date,
            due_date,
            estimated_time
        FROM tasks
        WHERE assigned_user_id IS NOT NULL
    """, mysql_engine)

    tasks_df['score']      = tasks_df['score'].fillna(0).astype(float)
    tasks_df['percentage'] = tasks_df['percentage'].fillna(0).astype(float)

    def to_date(val, fallback: date) -> date:
        if pd.isna(val):
            return fallback
        try:
            return pd.Timestamp(val).date()
        except Exception:
            return fallback

    tasks_df['start_py'] = tasks_df['start_date'].apply(lambda v: to_date(v, FALLBACK_START))
    tasks_df['end_py']   = tasks_df['due_date'].apply(lambda v: to_date(v, FALLBACK_END))

    dayoff_df = pd.read_sql(f"""
        SELECT user_id, date, leave_type, status
        FROM day_off_requests
        WHERE status = 'APPROVED' AND date >= '{start_date}' AND date <= '{end_date}'
    """, mysql_engine)

    # ── Name → user_id map ─────────────────────────────────
    name_map: dict[str, int] = {
        u['name'].lower().replace(" ", ""): int(u['id'])
        for _, u in users_df.iterrows()
    }

    # ── Task lookup: user_id → list of task dicts ──────────
    print("  Building task lookup by user...")
    task_lookup: dict[int, list] = {}
    for _, t in tasks_df.iterrows():
        uid = int(t['assigned_user_id'])
        task_lookup.setdefault(uid, []).append({
            'task_id':    int(t['task_id']),
            'project_id': int(t['project_id']) if pd.notna(t['project_id']) else None,
            'phase_id':   int(t['phase_id'])   if pd.notna(t['phase_id'])   else None,
            'status':     str(t['status']),
            'score':      float(t['score']),
            'percentage': float(t['percentage']),
            'start_date': t['start_py'],
            'due_date':   t['end_py'],
            'est':        float(t['estimated_time']) if pd.notna(t['estimated_time']) else 1.0,
        })

    # ── Fast indexes ───────────────────────────────────────
    checkin_index: dict = {}
    for _, row in checkins_df.iterrows():
        checkin_index[(str(row['user_name']).lower().replace(" ", ""), row['date'])] = row

    dayoff_index: dict = {}
    for _, row in dayoff_df.iterrows():
        dayoff_index.setdefault((int(row['user_id']), row['date']), []).append(row)

    uid_to_name: dict[int, str] = {
        int(u['id']): u['name'].lower().replace(" ", "")
        for _, u in users_df.iterrows()
    }

    # ── Generate all (user_id, date) pairs for the date range ──
    date_user_pairs: set = set()
    
    # From check-ins
    for _, row in checkins_df.iterrows():
        uname = str(row['user_name']).lower().replace(" ", "")
        if uname in name_map:
            date_user_pairs.add((name_map[uname], row['date']))
    
    # From day-off requests
    for _, row in dayoff_df.iterrows():
        date_user_pairs.add((int(row['user_id']), row['date']))
    
    # From all users × date range (to ensure coverage)
    for _, row in users_df.iterrows():
        user_id = int(row['id'])
        current = start_date
        while current <= end_date:
            date_user_pairs.add((user_id, current))
            current += timedelta(days=1)

    print(f"  Processing {len(date_user_pairs)} (user, date) pairs...")

    inserted = 0

    for (user_id, record_date) in date_user_pairs:
        emp_sk  = get_emp_sk(user_id)
        date_sk = get_date_sk(record_date)
        if not emp_sk or not date_sk:
            continue

        # Delete existing record for this employee-date to avoid duplicates
        pg_cur.execute(
            "DELETE FROM fact_employee_productivity WHERE employee_sk=%s AND date_sk=%s",
            (emp_sk, date_sk)
        )

        # ── Check-in ───────────────────────────────────────
        ci_row = checkin_index.get((uid_to_name.get(user_id, ""), record_date))

        check_in_time = check_out_time = None
        if ci_row is not None:
            try:
                parts        = str(ci_row['working_hours']).split(":")
                hours_worked = float(parts[0]) + float(parts[1]) / 60
            except Exception:
                hours_worked = 0.0
            is_late        = bool(ci_row['is_late'])
            checked_in     = True
            check_in_time  = ci_row['check_in_time']  if pd.notna(ci_row['check_in_time'])  else None
            check_out_time = ci_row['check_out_time'] if pd.notna(ci_row['check_out_time']) else None
        else:
            hours_worked = 0.0
            is_late      = False
            checked_in   = False

        # ── Day-off ────────────────────────────────────────
        do_rows     = dayoff_index.get((user_id, record_date), [])
        had_day_off = len(do_rows) > 0
        leave_type  = do_rows[0]['leave_type'] if had_day_off else None

        # ── Active tasks ───────────────────────────────────
        r_date = pd.Timestamp(record_date).date()
        active_tasks = [
            t for t in task_lookup.get(user_id, [])
            if t['start_date'] <= r_date <= t['due_date']
        ]

        tasks_completed   = sum(1 for t in active_tasks if t['status'] == 'completed')
        tasks_in_progress = sum(1 for t in active_tasks if t['status'] == 'in_progress')

        completed = [t for t in active_tasks if t['status'] == 'completed']
        avg_task_score = float(np.mean([t['score'] for t in completed])) \
                         if completed else 0.0
        avg_task_pct   = float(np.mean([t['percentage'] for t in active_tasks])) \
                         if active_tasks else 0.0

        # Dimension keys from latest active task
        task_sk = project_sk = phase_sk = None
        if active_tasks:
            latest     = active_tasks[-1]
            task_sk    = get_sk("dim_task",    "task_id",    latest['task_id'])
            project_sk = get_sk("dim_project", "project_id", latest['project_id']) \
                         if latest['project_id'] else None
            phase_sk   = get_sk("dim_phase",   "phase_id",   latest['phase_id']) \
                         if latest['phase_id'] else None

        pg_cur.execute(
            "SELECT department_id FROM dim_employee WHERE employee_sk = %s", (emp_sk,)
        )
        dept_row = pg_cur.fetchone()
        dept_sk  = get_sk("dim_department", "dept_id", dept_row[0]) \
                   if dept_row and dept_row[0] else None

        prod_score = compute_productivity(
            hours_worked, is_late, checked_in,
            had_day_off, tasks_completed,
            avg_task_score, avg_task_pct
        )

        pg_cur.execute("""
            INSERT INTO fact_employee_productivity
                (employee_sk, date_sk, dept_sk, task_sk, project_sk, phase_sk,
                 hours_worked, is_late, checked_in,
                 had_day_off, leave_type,
                 tasks_completed, tasks_in_progress,
                 avg_task_score, avg_task_percentage,
                 productivity_score, check_in_time, check_out_time)
            VALUES (%s,%s,%s,%s,%s,%s, %s,%s,%s, %s,%s, %s,%s, %s,%s, %s, %s,%s)
        """, (
            emp_sk, date_sk, dept_sk, task_sk, project_sk, phase_sk,
            hours_worked, is_late, checked_in,
            had_day_off, leave_type,
            tasks_completed, tasks_in_progress,
            avg_task_score, avg_task_pct,
            prod_score, check_in_time, check_out_time
        ))
        inserted += 1

        if inserted % 5000 == 0:
            pg_conn.commit()
            print(f"    {inserted} rows committed...")

    pg_conn.commit()
    metadata["last_fact_date"] = str(end_date)
    print(f"  ✓ Fact table done — {inserted} rows inserted/updated.")
    return metadata

# ════════════════════════════════════════════════════════════
# RUN INCREMENTAL ETL
# ════════════════════════════════════════════════════════════
def run_incremental_etl(days_back=30):
    """Run incremental ETL - only load new data.
    
    Args:
        days_back: How many days back to load for fact table (default: 30)
    """
    print("🔄 Starting Incremental ETL...")
    print("=" * 60)
    
    metadata = load_metadata()
    
    # Dimensions - only load new records
    metadata = load_dim_department_incremental(metadata)
    metadata = load_dim_employee_incremental(metadata)
    metadata = load_dim_project_incremental(metadata)
    metadata = load_dim_phase_incremental(metadata)
    metadata = load_dim_task_incremental(metadata)
    
    # Fact table - reload for recent date range
    metadata = load_fact_incremental(metadata, days_back=days_back)
    
    save_metadata(metadata)
    
    print("=" * 60)
    print("✅ Incremental ETL complete!")

if __name__ == "__main__":
    # Run incremental ETL for last 30 days
    # Adjust days_back as needed
    run_incremental_etl(days_back=30)
