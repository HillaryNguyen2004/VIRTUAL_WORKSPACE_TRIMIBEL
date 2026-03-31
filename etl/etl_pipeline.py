import psycopg2
import pandas as pd
from datetime import date, timedelta
from sqlalchemy import create_engine
from config import MYSQL_CONFIG, PG_CONFIG

# ── MySQL via SQLAlchemy (fixes pandas warning + row bug) ───
mysql_engine = create_engine(
    f"mysql+pymysql://{MYSQL_CONFIG['user']}:{MYSQL_CONFIG['password']}@{MYSQL_CONFIG['host']}/{MYSQL_CONFIG['database']}"
)

# ── PostgreSQL via psycopg2 ──────────────────────────────────

def get_pg():
    conn = psycopg2.connect(**PG_CONFIG)
    conn.autocommit = False
    return conn

pg_conn = get_pg()
pg_cur = pg_conn.cursor()

# ════════════════════════════════════════════════════════════
# 1. LOAD dim_date  (fill 2020 → 2030)
# ════════════════════════════════════════════════════════════

def load_dim_date():
    print("Loading dim_date...")
    start = date(2020, 1, 1)
    end = date(2030, 12, 31)
    d = start
    while d <= end:
        pg_cur.execute("""
            INSERT INTO dim_date
                (full_date, day_of_week, day_name, week, month, month_name, quarter, year, is_weekend)
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s)
            ON CONFLICT (full_date) DO NOTHING
        """, (
            d,
            d.weekday(),
            d.strftime("%A"),
            d.isocalendar()[1],
            d.month,
            d.strftime("%B"),
            (d.month - 1) // 3 + 1,
            d.year,
            d.weekday() >= 5
        ))
        d += timedelta(days=1)
    pg_conn.commit()
    print("dim_date done.")

# ════════════════════════════════════════════════════════════
# 2. LOAD dim_department  (from departments)
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
# 3. LOAD dim_employee  (from users JOIN departments)
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
        existing = pg_cur.fetchone()

        if not existing:
            pg_cur.execute("""
                INSERT INTO dim_employee
                    (user_id, name, username, email, department_id,
                     dept_name, team_leader_id, hire_date, valid_from, is_current)
                VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,TRUE)
            """, (
                int(row['id']),
                row['name'],
                row['username'],
                row['email'],
                int(row['department_id']) if pd.notna(row['department_id']) else None,
                row['dept_name'],
                int(row['team_leader_id']) if pd.notna(row['team_leader_id']) else None,
                row['hire_date'],
                date.today()
            ))
    pg_conn.commit()
    print("dim_employee done.")

# ════════════════════════════════════════════════════════════
# 4. LOAD dim_project  (from projects)
# ════════════════════════════════════════════════════════════

def load_dim_project():
    print("Loading dim_project...")
    df = pd.read_sql(
        "SELECT id, title, description, staff_id, status, percentage, start_date, due_date FROM projects",
        mysql_engine
    )
    for _, row in df.iterrows():
        pg_cur.execute("""
            INSERT INTO dim_project (project_id, title, description, staff_id, status, percentage, start_date, due_date)
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s)
            ON CONFLICT (project_id) DO UPDATE
                SET title=EXCLUDED.title,
                    description=EXCLUDED.description,
                    staff_id=EXCLUDED.staff_id,
                    status=EXCLUDED.status,
                    percentage=EXCLUDED.percentage,
                    start_date=EXCLUDED.start_date,
                    due_date=EXCLUDED.due_date
        """, (
            int(row['id']),
            row['title'],
            row['description'],
            int(row['staff_id']) if pd.notna(row['staff_id']) else None,
            row['status'],
            int(row['percentage']) if row['percentage'] else 0,
            row['start_date'],
            row['due_date']
        ))
    pg_conn.commit()
    print("dim_project done.")

# ════════════════════════════════════════════════════════════
# 4a. LOAD dim_phase  (from phases)
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
                SET project_id=EXCLUDED.project_id,
                    title=EXCLUDED.title,
                    start_date=EXCLUDED.start_date,
                    due_date=EXCLUDED.due_date
        """, (
            int(row['id']),
            int(row['project_id']) if pd.notna(row['project_id']) else None,
            row['title'],
            row['start_date'],
            row['due_date']
        ))
    pg_conn.commit()
    print("dim_phase done.")

# ════════════════════════════════════════════════════════════
# 5. LOAD dim_task  (from tasks)
# ════════════════════════════════════════════════════════════

def load_dim_task():
    print("Loading dim_task...")
    df = pd.read_sql(
        """SELECT id, title, assigned_user_id, status, priority,
                  start_date, due_date, active, estimated_time,
                  project_id, phase_id, parent_id
           FROM tasks""",
        mysql_engine
    )
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
                    status=EXCLUDED.status,
                    priority=EXCLUDED.priority,
                    start_date=EXCLUDED.start_date,
                    due_date=EXCLUDED.due_date,
                    active=EXCLUDED.active,
                    estimated_time=EXCLUDED.estimated_time,
                    project_id=EXCLUDED.project_id,
                    phase_id=EXCLUDED.phase_id,
                    parent_id=EXCLUDED.parent_id
        """, (
            int(row['id']),
            row['title'],
            int(row['assigned_user_id']) if pd.notna(row['assigned_user_id']) else None,
            row['status'],
            row['priority'],
            row['start_date'],
            row['due_date'],
            bool(row['active']) if pd.notna(row['active']) else True,
            float(row['estimated_time']) if pd.notna(row['estimated_time']) else None,
            int(row['project_id']) if pd.notna(row['project_id']) else None,
            int(row['phase_id']) if pd.notna(row['phase_id']) else None,
            int(row['parent_id']) if pd.notna(row['parent_id']) else None
        ))
    pg_conn.commit()
    print("dim_task done.")

# ════════════════════════════════════════════════════════════
# HELPER: lookup surrogate keys
# ════════════════════════════════════════════════════════════

# Explicit map because dim_department → dept_sk (not department_sk)
SK_COL_MAP = {
    "dim_department": "dept_sk",
    "dim_employee":   "employee_sk",
    "dim_task":       "task_sk",
    "dim_project":    "project_sk",
    "dim_phase":      "phase_sk",
    "dim_date":       "date_sk",
}

def get_sk(table, id_col, id_val):
    sk_col = SK_COL_MAP.get(table)
    if not sk_col:
        raise ValueError(f"Unknown table: {table}")
    pg_cur.execute(
        f"SELECT {sk_col} FROM {table} WHERE {id_col} = %s", (id_val,)
    )
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
# 6. COMPUTE PRODUCTIVITY SCORE
#    Weights (adjust as you like):
#      40% task completion rate
#      20% avg task score (normalized to 100)
#      20% attendance (checked_in and not late)
#      20% hours worked (normalized to 8h)
# ════════════════════════════════════════════════════════════
def compute_productivity(hours_worked, is_late, checked_in,
                          had_day_off, tasks_completed,
                          avg_task_score, avg_task_pct):
    if had_day_off and not checked_in:
        return 0.0   # absent/off day → no productivity score

    hours_score = min(hours_worked / 8.0, 1.0)
    attendance = 1.0 if (checked_in and not is_late) else (0.5 if checked_in else 0.0)
    task_score_norm = min(avg_task_score / 100.0, 1.0)
    task_pct_norm = min(avg_task_pct / 100.0, 1.0)

    score = (
        0.30 * task_pct_norm +
        0.25 * task_score_norm +
        0.25 * attendance +
        0.20 * hours_score
    ) * 100

    return round(score, 2)

# ════════════════════════════════════════════════════════════
# 7. LOAD fact_employee_productivity
#    Joins: check_ins + tasks + day_off_requests per user per day
# ════════════════════════════════════════════════════════════
def load_fact():
    print("Loading fact table...")

    # Use 'name' field to match check_ins.user_name (display name, not login username)
    users_df = pd.read_sql("SELECT id, name, username FROM users", mysql_engine)

    checkins_df = pd.read_sql("""
        SELECT user_name, date,
               CAST(working_hours AS CHAR) AS working_hours,
               is_late, check_in_time, check_out_time
        FROM check_ins
    """, mysql_engine)

    tasks_df = pd.read_sql("""
        SELECT assigned_user_id, DATE(updated_at) AS task_date,
               status, score, percentage, project_id, phase_id, id AS task_id
        FROM tasks
        WHERE assigned_user_id IS NOT NULL
    """, mysql_engine)

    dayoff_df = pd.read_sql("""
        SELECT user_id, date, leave_type, status
        FROM day_off_requests
        WHERE status = 'APPROVED'
    """, mysql_engine)

    # Map both name (display) and username (login) to user ID for flexibility
    name_map = {}
    username_map = {}
    for _, u in users_df.iterrows():
        name_map[u['name'].lower().replace(" ", "")] = int(u['id'])
        username_map[u['username'].lower()] = int(u['id'])

    date_user_pairs = set()

    for _, row in checkins_df.iterrows():
        # Match check_ins.user_name (display name) to users.name
        uname = str(row['user_name']).lower().replace(" ", "")
        if uname in name_map:
            date_user_pairs.add((name_map[uname], row['date']))

    for _, row in tasks_df.iterrows():
        if row['assigned_user_id']:
            date_user_pairs.add((int(row['assigned_user_id']), row['task_date']))

    for _, row in dayoff_df.iterrows():
        date_user_pairs.add((int(row['user_id']), row['date']))

    print(f"  Processing {len(date_user_pairs)} (user, date) pairs...")

    inserted = 0
    for (user_id, record_date) in date_user_pairs:
        emp_sk = get_emp_sk(user_id)
        date_sk = get_date_sk(record_date)
        if not emp_sk or not date_sk:
            continue

        uname_key = None
        for _, u in users_df.iterrows():
            if int(u['id']) == user_id:
                # Use 'name' (display name) to match check_ins.user_name
                uname_key = u['name'].lower().replace(" ", "")
                break

        ci = checkins_df[
            (checkins_df['user_name'].astype(str).str.lower().str.replace(" ", "") == uname_key) &
            (checkins_df['date'] == record_date)
        ]

        check_in_time = None
        check_out_time = None
        if not ci.empty:
            ci = ci.iloc[0]
            try:
                parts = str(ci['working_hours']).split(":")
                hours_worked = float(parts[0]) + float(parts[1]) / 60
            except Exception:
                hours_worked = 0.0
            is_late = bool(ci['is_late'])
            checked_in = True
            check_in_time = ci['check_in_time'] if pd.notna(ci['check_in_time']) else None
            check_out_time = ci['check_out_time'] if pd.notna(ci['check_out_time']) else None
        else:
            hours_worked = 0.0
            is_late = False
            checked_in = False

        do = dayoff_df[
            (dayoff_df['user_id'] == user_id) &
            (dayoff_df['date'] == record_date)
        ]
        had_day_off = not do.empty
        leave_type = do.iloc[0]['leave_type'] if had_day_off else None

        t = tasks_df[
            (tasks_df['assigned_user_id'] == user_id) &
            (tasks_df['task_date'] == record_date)
        ]
        tasks_completed = int(len(t[t['status'] == 'completed']))
        tasks_in_progress = int(len(t[t['status'] == 'in_progress']))
        avg_task_score = float(t['score'].mean()) if not t.empty else 0.0
        avg_task_percentage = float(t['percentage'].mean()) if not t.empty else 0.0

        task_sk = None
        project_sk = None
        phase_sk = None
        if not t.empty:
            latest_task = t.iloc[-1]
            task_sk = get_sk("dim_task", "task_id", int(latest_task['task_id']))
            project_sk = get_sk("dim_project", "project_id", int(latest_task['project_id'])) if pd.notna(latest_task['project_id']) else None
            phase_sk = get_sk("dim_phase", "phase_id", int(latest_task['phase_id'])) if pd.notna(latest_task['phase_id']) else None

        pg_cur.execute(
            "SELECT department_id FROM dim_employee WHERE employee_sk = %s", (emp_sk,)
        )
        dept_row = pg_cur.fetchone()
        dept_sk = get_sk("dim_department", "dept_id", dept_row[0]) if dept_row and dept_row[0] else None

        prod_score = compute_productivity(
            hours_worked, is_late, checked_in,
            had_day_off, tasks_completed,
            avg_task_score, avg_task_percentage
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
            avg_task_score, avg_task_percentage,
            prod_score, check_in_time, check_out_time
        ))
        inserted += 1

    pg_conn.commit()
    print(f"fact table done — {inserted} rows inserted.")

# ════════════════════════════════════════════════════════════
# RUN ALL
# ════════════════════════════════════════════════════
def run_full_etl():
    load_dim_date()
    load_dim_department()
    load_dim_employee()
    load_dim_project()
    load_dim_phase()
    load_dim_task()
    load_fact()
    print("✅ Full ETL complete.")
