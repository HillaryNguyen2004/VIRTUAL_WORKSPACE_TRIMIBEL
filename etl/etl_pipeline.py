import numpy as np
import psycopg2
import pandas as pd
from datetime import date, timedelta
from sqlalchemy import create_engine
from config import MYSQL_CONFIG, PG_CONFIG

# ── Connections ──────────────────────────────────────────────
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
# 1. dim_date
# ════════════════════════════════════════════════════════════
def load_dim_date():
    print("Loading dim_date...")
    start = date(2020, 1, 1)
    end   = date(2030, 12, 31)
    d     = start
    while d <= end:
        pg_cur.execute("""
            INSERT INTO dim_date
                (full_date, day_of_week, day_name, week, month,
                 month_name, quarter, year, is_weekend)
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)
            ON CONFLICT (full_date) DO NOTHING
        """, (
            d, d.weekday(), d.strftime("%A"),
            d.isocalendar()[1], d.month, d.strftime("%B"),
            (d.month - 1) // 3 + 1, d.year, d.weekday() >= 5
        ))
        d += timedelta(days=1)
    pg_conn.commit()
    print("dim_date done.")

# ════════════════════════════════════════════════════════════
# 2. dim_department
# ════════════════════════════════════════════════════════════
def load_dim_department():
    print("Loading dim_department...")
    df = pd.read_sql("SELECT id, name FROM departments", mysql_engine)
    for _, row in df.iterrows():
        pg_cur.execute("""
            INSERT INTO dim_department (dept_id, dept_name)
            VALUES (%s, %s)
            ON CONFLICT (dept_id) DO UPDATE SET dept_name = EXCLUDED.dept_name
        """, (int(row['id']), row['name']))
    pg_conn.commit()
    print("dim_department done.")

# ════════════════════════════════════════════════════════════
# 3. dim_employee
# ════════════════════════════════════════════════════════════
def load_dim_employee():
    print("Loading dim_employee...")
    df = pd.read_sql("""
        SELECT u.id, u.name, u.username, u.email,
               u.department_id, d.name AS dept_name,
               u.team_leader_id,
               DATE(u.created_at) AS hire_date
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
    """, mysql_engine)

    for _, row in df.iterrows():
        pg_cur.execute("""
            SELECT employee_sk FROM dim_employee
            WHERE user_id = %s AND is_current = TRUE
        """, (int(row['id']),))
        if not pg_cur.fetchone():
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
    print("dim_employee done.")

# ════════════════════════════════════════════════════════════
# 4. dim_project
# ════════════════════════════════════════════════════════════
def load_dim_project():
    print("Loading dim_project...")
    df = pd.read_sql(
        "SELECT id, title, description, staff_id, status, percentage, start_date, due_date FROM projects",
        mysql_engine
    )
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
    print("dim_project done.")

# ════════════════════════════════════════════════════════════
# 4a. dim_phase
# ════════════════════════════════════════════════════════════
def load_dim_phase():
    print("Loading dim_phase...")
    df = pd.read_sql(
        "SELECT id, project_id, title, start_date, due_date FROM phases",
        mysql_engine
    )
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
    print("dim_phase done.")

# ════════════════════════════════════════════════════════════
# 5. dim_task
# ════════════════════════════════════════════════════════════
def load_dim_task():
    print("Loading dim_task...")
    df = pd.read_sql("""
        SELECT id, title, assigned_user_id, status, priority,
               start_date, due_date, active, estimated_time,
               project_id, phase_id, parent_id
        FROM tasks
    """, mysql_engine)
    for _, row in df.iterrows():
        pg_cur.execute("""
            INSERT INTO dim_task
                (task_id, title, assigned_user_id, status, priority,
                 start_date, due_date, active, estimated_time,
                 project_id, phase_id, parent_id)
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
            ON CONFLICT (task_id) DO UPDATE
                SET title=EXCLUDED.title,
                    assigned_user_id=EXCLUDED.assigned_user_id,
                    status=EXCLUDED.status, priority=EXCLUDED.priority,
                    start_date=EXCLUDED.start_date, due_date=EXCLUDED.due_date,
                    active=EXCLUDED.active, estimated_time=EXCLUDED.estimated_time,
                    project_id=EXCLUDED.project_id, phase_id=EXCLUDED.phase_id,
                    parent_id=EXCLUDED.parent_id
        """, (
            int(row['id']), row['title'],
            int(row['assigned_user_id']) if pd.notna(row['assigned_user_id']) else None,
            row['status'], row['priority'],
            row['start_date'], row['due_date'],
            bool(row['active']) if pd.notna(row['active']) else True,
            float(row['estimated_time']) if pd.notna(row['estimated_time']) else None,
            int(row['project_id']) if pd.notna(row['project_id']) else None,
            int(row['phase_id'])   if pd.notna(row['phase_id'])   else None,
            int(row['parent_id'])  if pd.notna(row['parent_id'])  else None,
        ))
    pg_conn.commit()
    print("dim_task done.")

# ════════════════════════════════════════════════════════════
# HELPERS
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
# 6. PRODUCTIVITY FORMULA
#    Two modes:
#    - has_tasks  → attendance(35%) + hours(25%) + task_pct(25%) + task_score(15%)
#    - no tasks   → attendance(60%) + hours(40%)
#    task_score is 0–10 in your DB so we divide by 10 not 100
# ════════════════════════════════════════════════════════════
def compute_productivity(hours_worked, is_late, checked_in,
                          had_day_off, tasks_completed,
                          avg_task_score, avg_task_pct):
    if had_day_off and not checked_in:
        return 0.0

    hours_score     = min(hours_worked / 8.0, 1.0)
    attendance      = 1.0 if (checked_in and not is_late) else (0.5 if checked_in else 0.0)
    task_score_norm = min(avg_task_score / 10.0,  1.0)   # score is 0-10 in your DB
    task_pct_norm   = min(avg_task_pct   / 100.0, 1.0)

    has_tasks = tasks_completed > 0 or avg_task_score > 0 or avg_task_pct > 0

    if has_tasks:
        score = (
            0.35 * attendance     +
            0.25 * hours_score    +
            0.25 * task_pct_norm  +
            0.15 * task_score_norm
        ) * 100
    else:
        score = (
            0.60 * attendance  +
            0.40 * hours_score
        ) * 100

    return round(score, 2)

# ════════════════════════════════════════════════════════════
# 7. LOAD FACT TABLE
#
#    KEY FIX: tasks are matched by DATE RANGE (start_date..due_date)
#    not by updated_at.  A task assigned to user X is "active" on
#    every day between its start_date and due_date, so we count it
#    toward that employee's productivity on each of those days.
# ════════════════════════════════════════════════════════════
def load_fact():
    print("Loading fact table...")

    # ── Pull source tables ───────────────────────────────────
    users_df = pd.read_sql(
        "SELECT id, name, username FROM users", mysql_engine
    )

    checkins_df = pd.read_sql("""
        SELECT user_name, date,
               CAST(working_hours AS CHAR) AS working_hours,
               is_late, check_in_time, check_out_time
        FROM check_ins
    """, mysql_engine)

    # ── FIXED: load task date RANGE, not updated_at ─────────
    tasks_df = pd.read_sql("""
        SELECT
            id          AS task_id,
            assigned_user_id,
            project_id,
            phase_id,
            status,
            score,
            percentage,
            start_date,
            due_date
        FROM tasks
        WHERE assigned_user_id IS NOT NULL
          AND start_date IS NOT NULL
          AND due_date   IS NOT NULL
    """, mysql_engine)

    # Convert to Python dates for fast comparison
    tasks_df['start_date'] = pd.to_datetime(tasks_df['start_date']).dt.date
    tasks_df['due_date']   = pd.to_datetime(tasks_df['due_date']).dt.date
    tasks_df['score']      = tasks_df['score'].fillna(0).astype(float)
    tasks_df['percentage'] = tasks_df['percentage'].fillna(0).astype(float)

    dayoff_df = pd.read_sql("""
        SELECT user_id, date, leave_type, status
        FROM day_off_requests
        WHERE status = 'APPROVED'
    """, mysql_engine)

    # ── Build name → user_id map (check_ins uses display name) ──
    name_map = {}
    for _, u in users_df.iterrows():
        name_map[u['name'].lower().replace(" ", "")] = int(u['id'])

    # ── Pre-build task lookup: user_id → [list of task dicts] ──
    # This avoids re-scanning the whole tasks_df for every (user, date) pair
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
            'start_date': t['start_date'],
            'due_date':   t['due_date'],
        })

    # ── Collect (user_id, date) pairs from check-ins ─────────
    # These are the dates we actually want to score
    date_user_pairs: set = set()

    for _, row in checkins_df.iterrows():
        uname = str(row['user_name']).lower().replace(" ", "")
        if uname in name_map:
            date_user_pairs.add((name_map[uname], row['date']))

    for _, row in dayoff_df.iterrows():
        date_user_pairs.add((int(row['user_id']), row['date']))

    print(f"  Processing {len(date_user_pairs)} (user, date) pairs...")

    # ── Pre-build a normalised check-in index for fast lookup ──
    # Key: (normalised_name, date) → row dict
    checkin_index: dict = {}
    for _, row in checkins_df.iterrows():
        key = (str(row['user_name']).lower().replace(" ", ""), row['date'])
        checkin_index[key] = row

    # ── Pre-build day-off index ───────────────────────────────
    dayoff_index: dict = {}
    for _, row in dayoff_df.iterrows():
        key = (int(row['user_id']), row['date'])
        dayoff_index.setdefault(key, []).append(row)

    # ── Build user_id → normalised name map for check-in lookup
    uid_to_name: dict[int, str] = {}
    for _, u in users_df.iterrows():
        uid_to_name[int(u['id'])] = u['name'].lower().replace(" ", "")

    inserted = 0

    for (user_id, record_date) in date_user_pairs:
        emp_sk  = get_emp_sk(user_id)
        date_sk = get_date_sk(record_date)
        if not emp_sk or not date_sk:
            continue

        # ── Check-in ─────────────────────────────────────────
        uname_key = uid_to_name.get(user_id, "")
        ci_row    = checkin_index.get((uname_key, record_date))

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

        # ── Day-off ───────────────────────────────────────────
        do_rows    = dayoff_index.get((user_id, record_date), [])
        had_day_off = len(do_rows) > 0
        leave_type  = do_rows[0]['leave_type'] if had_day_off else None

        # ── Active tasks — matched by DATE RANGE ──────────────
        #    A task is active on record_date if:
        #       task.start_date <= record_date <= task.due_date
        r_date = pd.Timestamp(record_date).date()

        active_tasks = []
        for t in task_lookup.get(user_id, []):
            if t['start_date'] <= r_date <= t['due_date']:
                active_tasks.append(t)

        tasks_completed   = sum(1 for t in active_tasks if t['status'] == 'completed')
        tasks_in_progress = sum(1 for t in active_tasks if t['status'] == 'in_progress')
        avg_task_score    = float(np.mean([t['score']      for t in active_tasks])) if active_tasks else 0.0
        avg_task_pct      = float(np.mean([t['percentage'] for t in active_tasks])) if active_tasks else 0.0

        # Dimension keys from latest active task
        task_sk = project_sk = phase_sk = None
        if active_tasks:
            latest     = active_tasks[-1]
            task_sk    = get_sk("dim_task",    "task_id",    latest['task_id'])
            project_sk = get_sk("dim_project", "project_id", latest['project_id']) if latest['project_id'] else None
            phase_sk   = get_sk("dim_phase",   "phase_id",   latest['phase_id'])   if latest['phase_id']   else None

        # Department SK
        pg_cur.execute(
            "SELECT department_id FROM dim_employee WHERE employee_sk = %s", (emp_sk,)
        )
        dept_row = pg_cur.fetchone()
        dept_sk  = get_sk("dim_department", "dept_id", dept_row[0]) if dept_row and dept_row[0] else None

        # Productivity score
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
    print(f"fact table done — {inserted} rows inserted.")


# ════════════════════════════════════════════════════════════
# RUN ALL
# ════════════════════════════════════════════════════════════
def run_full_etl():
    load_dim_date()
    load_dim_department()
    load_dim_employee()
    load_dim_project()
    load_dim_phase()
    load_dim_task()
    load_fact()
    print("✅ Full ETL complete.")