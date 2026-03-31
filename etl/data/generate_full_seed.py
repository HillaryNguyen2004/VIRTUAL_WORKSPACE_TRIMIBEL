import argparse
import random
import uuid
from datetime import datetime, timedelta, date
from pathlib import Path

# ─────────────────────────────────────────────
# DEFAULTS
# ─────────────────────────────────────────────
DEFAULT_NUM_USERS    = 30
DEFAULT_NUM_PROJECTS = 50
DEFAULT_NUM_TASKS    = 10000

DEFAULT_START_DATE = datetime(2018, 1, 1)
DEFAULT_END_DATE   = datetime(2026, 3, 30)

PASSWORD_HASH = "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi"

# ─────────────────────────────────────────────
# REFERENCE DATA
# ─────────────────────────────────────────────
DEPARTMENTS = {2: "HR", 3: "Sales", 4: "Marketing", 5: "Finance", 6: "Engineering"}

# Roles from DB: 1=user, 2=staff, 4=admin, 5=subadmin, 6=substaff
ROLE_IDS = {"admin": 4, "staff": 2, "user": 1}

FIRST_NAMES = ["An", "Binh", "Chi", "Dung", "Hanh", "Khoa", "Linh", "Minh", "Nam", "Phuc",
               "Anh", "Bao", "Chau", "Dat", "Giang", "Hieu", "Khanh", "Long", "Mai", "Nhi"]
LAST_NAMES  = ["Nguyen", "Tran", "Le", "Pham", "Hoang", "Vu", "Dang", "Bui", "Do", "Ho"]

DAYOFF_REASONS = [
    "Personal leave", "Sick leave", "Family emergency",
    "Medical appointment", "Maternity leave", "Wedding ceremony",
    "Funeral", "House moving", "Child care", "Annual leave",
]

PHASE_TITLES = [
    "Planning", "Design", "Development", "Testing",
    "Deployment", "Review", "Maintenance", "Research",
]

TASK_VERBS   = ["Implement", "Design", "Review", "Test", "Refactor", "Document",
                "Analyze", "Deploy", "Fix", "Optimize", "Build", "Integrate"]
TASK_NOUNS   = ["API", "UI", "database", "module", "report", "dashboard",
                "authentication", "notification", "export", "import", "workflow", "pipeline"]

PRIORITIES   = ["low", "normal", "high", "urgent"]
STATUSES     = ["pending", "in_progress", "completed", "cancelled"]

# Vietnamese public holidays (recurring annually — month, day tuples)
ANNUAL_HOLIDAYS = [
    (1,  1,  "New Year's Day"),
    (4,  30, "Reunification Day"),
    (5,  1,  "International Labour Day"),
    (9,  2,  "National Day"),
]
# Multi-day holidays (month, start_day, end_day)
MULTI_HOLIDAYS = [
    (1, 20, 26, "Lunar New Year"),   # approximate Tet
]


# ─────────────────────────────────────────────
# HELPERS
# ─────────────────────────────────────────────
def rand_date(start: datetime, end: datetime) -> datetime:
    delta = (end - start).days
    return start + timedelta(days=random.randint(0, max(delta, 0)))


def rand_time_str(hour: int) -> str:
    return f"{hour:02d}:{random.randint(0, 59):02d}:{random.randint(0, 59):02d}"


def calc_working_hours(t1: str, t2: str) -> str:
    fmt = "%H:%M:%S"
    d1  = datetime.strptime(t1, fmt)
    d2  = datetime.strptime(t2, fmt)
    diff = d2 - d1
    if diff.total_seconds() < 0:
        return "NULL"
    h = int(diff.total_seconds()) // 3600
    m = (int(diff.total_seconds()) % 3600) // 60
    return f"'{h:02d}:{m:02d}'"


def build_holiday_dates(start_year: int, end_year: int) -> set:
    """Return a set of date objects that are holidays."""
    holidays: set = set()
    for year in range(start_year, end_year + 1):
        for month, day, _ in ANNUAL_HOLIDAYS:
            try:
                holidays.add(date(year, month, day))
            except ValueError:
                pass
        for month, sd, ed, _ in MULTI_HOLIDAYS:
            try:
                for d in range(sd, ed + 1):
                    holidays.add(date(year, month, d))
            except ValueError:
                pass
    return holidays


def pct_from_children(child_pcts: list[int]) -> int:
    """Parent percentage = average of children, rounded."""
    if not child_pcts:
        return 0
    return round(sum(child_pcts) / len(child_pcts))


def status_from_pct(pct: int) -> str:
    if pct == 0:
        return "pending"
    if pct == 100:
        return "completed"
    return "in_progress"


def esc(s: str) -> str:
    # SQL-safe string escaping for MySQL/MariaDB when using single-quoted literals.
    # Use standard SQL escaping: single quote becomes two single quotes.
    return s.replace("'", "''")


# ─────────────────────────────────────────────
# MAIN GENERATOR
# ─────────────────────────────────────────────
def generate_sql(
    *,
    num_users: int,
    num_projects: int,
    num_tasks_per_phase: int,
    start_date: datetime,
    end_date: datetime,
) -> str:
    sql: list[str] = []
    sql.append("SET FOREIGN_KEY_CHECKS=0;")

    tables_to_clear = [
        "model_has_roles",
        "day_off_requests",
        "check_ins",
        "task_user",
        "tasks",
        "phases",
        "projects",
        "holidays",
        "users",
    ]
    for t in tables_to_clear:
        sql.append(f"TRUNCATE TABLE {t};")
    sql.append("SET FOREIGN_KEY_CHECKS=1;\n")

    # ──────────────────────────────────────────
    # 1. HOLIDAYS
    # ──────────────────────────────────────────
    # Generate realistic Vietnamese holidays across the date range
    holiday_id   = 1
    holiday_dates: set[date] = set()  # all calendar days that are holidays

    sql.append("-- HOLIDAYS")
    start_year = start_date.year
    end_year   = end_date.year

    for year in range(start_year, end_year + 1):
        for month, day, title in ANNUAL_HOLIDAYS:
            try:
                hd = date(year, month, day)
                if start_date.date() <= hd <= end_date.date():
                    hstart = datetime(year, month, day, 0, 0, 0)
                    hend   = datetime(year, month, day, 23, 59, 0)
                    sql.append(
                        f"INSERT INTO holidays (id, title, start_date, end_date, created_at, updated_at) VALUES "
                        f"({holiday_id}, '{esc(title)} {year}', '{hstart}', '{hend}', NOW(), NOW());"
                    )
                    holiday_dates.add(hd)
                    holiday_id += 1
            except ValueError:
                pass

        for month, sd, ed, title in MULTI_HOLIDAYS:
            try:
                hstart = datetime(year, month, sd, 0, 0, 0)
                hend   = datetime(year, month, ed, 23, 59, 0)
                if start_date.date() <= hstart.date() <= end_date.date():
                    sql.append(
                        f"INSERT INTO holidays (id, title, start_date, end_date, created_at, updated_at) VALUES "
                        f"({holiday_id}, '{esc(title)} {year}', '{hstart}', '{hend}', NOW(), NOW());"
                    )
                    for d_offset in range(sd, ed + 1):
                        holiday_dates.add(date(year, month, d_offset))
                    holiday_id += 1
            except ValueError:
                pass

    sql.append("")

    # ──────────────────────────────────────────
    # 2. USERS
    #    Layout:
    #      user 1          → admin (no dept)
    #      users 2..6      → 1 staff per department (dept 2-6)
    #      users 7..30     → regular users distributed across depts
    #    team_leader_id    → staff of the user's department
    # ──────────────────────────────────────────
    sql.append("-- USERS")

    # dept_id → staff_user_id mapping (filled as we create staff)
    dept_staff: dict[int, int] = {}

    # store user records for later use
    # (id, name, username, dept_id, role)
    user_records: list[dict] = []

    user_id = 1

    # --- Admin (user 1) ---
    name     = f"{random.choice(LAST_NAMES)} {random.choice(FIRST_NAMES)}"
    username = "user1"
    created  = rand_date(start_date, start_date + timedelta(days=30))
    sql.append(
        "INSERT INTO users (id, department_id, name, email, username, password, "
        "blocked, login_attempts, is_google_connected, created_at, updated_at) VALUES "
        f"({user_id}, NULL, '{esc(name)}', 'user1@mail.com', '{username}', "
        f"'{PASSWORD_HASH}', 0, 0, 0, '{created}', '{created}');"
    )
    user_records.append({"id": user_id, "name": name, "username": username,
                          "dept_id": None, "role": "admin", "created": created})
    user_id += 1

    # --- 1 Staff per department (users 2-6) ---
    for dept_id in sorted(DEPARTMENTS.keys()):
        name     = f"{random.choice(LAST_NAMES)} {random.choice(FIRST_NAMES)}"
        username = f"user{user_id}"
        created  = rand_date(start_date, start_date + timedelta(days=60))
        sql.append(
            "INSERT INTO users (id, department_id, name, email, username, password, "
            "blocked, login_attempts, is_google_connected, team_leader_id, created_at, updated_at) VALUES "
            f"({user_id}, {dept_id}, '{esc(name)}', 'user{user_id}@mail.com', '{username}', "
            f"'{PASSWORD_HASH}', 0, 0, 0, NULL, '{created}', '{created}');"
        )
        user_records.append({"id": user_id, "name": name, "username": username,
                              "dept_id": dept_id, "role": "staff", "created": created})
        dept_staff[dept_id] = user_id
        user_id += 1

    # --- Regular users to fill up to num_users ---
    dept_list = sorted(DEPARTMENTS.keys())
    while user_id <= num_users:
        dept_id     = random.choice(dept_list)
        team_leader = dept_staff[dept_id]
        name        = f"{random.choice(LAST_NAMES)} {random.choice(FIRST_NAMES)}"
        username    = f"user{user_id}"
        created     = rand_date(start_date + timedelta(days=30), end_date - timedelta(days=90))
        sql.append(
            "INSERT INTO users (id, department_id, name, email, username, password, "
            "blocked, login_attempts, is_google_connected, team_leader_id, created_at, updated_at) VALUES "
            f"({user_id}, {dept_id}, '{esc(name)}', 'user{user_id}@mail.com', '{username}', "
            f"'{PASSWORD_HASH}', 0, 0, 0, {team_leader}, '{created}', '{created}');"
        )
        user_records.append({"id": user_id, "name": name, "username": username,
                              "dept_id": dept_id, "role": "user", "created": created})
        user_id += 1

    sql.append("")

    # ──────────────────────────────────────────
    # 3. ROLES
    # ──────────────────────────────────────────
    sql.append("-- ROLES")
    for u in user_records:
        role_id = ROLE_IDS.get(u["role"], 1)
        sql.append(
            "INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id) VALUES "
            f"({role_id}, 'App\\\\Models\\\\User', {u['id']});"
        )
    sql.append("")

    # ──────────────────────────────────────────
    # 4. DAY OFF REQUESTS + CHECK-INS
    #    Logic:
    #      - Build approved_off: set of (user_id, date_obj) → "FULL" | "AM" | "PM"
    #      - Skip check-in on holidays or approved full-day off
    #      - Adjust check-in window for half-day off
    #      - Half-day AM off → no late mark (user starts afternoon)
    # ──────────────────────────────────────────

    # --- 4a. Generate day-off requests ---
    sql.append("-- DAY OFF REQUESTS")
    approved_off: dict[tuple[int, date], str] = {}  # (user_id, date) → "FULL"|"AM"|"PM"
    all_dayoff_dates: set[tuple[int, date]] = set()  # Track ALL day-off entries (user_id, date)
    dayoff_id = 1

    current = start_date
    while current <= end_date:
        d = current.date()
        if current.weekday() < 5 and d not in holiday_dates:
            for u in user_records:
                if u["role"] == "admin":
                    current += timedelta(days=1)
                    continue
                roll = random.random()

                # ~4% full day off (can span multiple days — handled by group_id)
                if roll < 0.04:
                    # Skip if this user already has day-off on this date
                    if (u['id'], d) in all_dayoff_dates:
                        pass  # Skip this full-day span
                    else:
                        # Decide span (1-3 days)
                        span      = random.randint(1, 3)
                        group_id  = str(uuid.uuid4())
                        status    = random.choices(["APPROVED", "PENDING", "REJECTED"],
                                                   weights=[75, 15, 10])[0]
                        reason    = random.choice(DAYOFF_REASONS)
                        for offset in range(span):
                            span_date = d + timedelta(days=offset)
                            # only weekdays, within range, not holiday
                            if (span_date.weekday() >= 5
                                    or span_date in holiday_dates
                                    or span_date > end_date.date()):
                                continue
                            # Skip if already has day-off on this span_date
                            if (u['id'], span_date) in all_dayoff_dates:
                                continue
                            sql.append(
                                "INSERT INTO day_off_requests "
                                "(id, request_group_id, user_id, date, leave_type, reason, status, "
                                "half_day_period, created_at, updated_at) VALUES "
                                f"({dayoff_id}, '{group_id}', {u['id']}, '{span_date}', "
                                f"'OFF_FULL', '{esc(reason)}', '{status}', NULL, "
                                f"'{current}', '{current}');"
                            )
                            dayoff_id += 1
                            all_dayoff_dates.add((u['id'], span_date))
                            if status == "APPROVED":
                                approved_off[(u['id'], span_date)] = "FULL"

                # ~2% half day off (skip if user already has a day-off on this date)
                elif roll < 0.06:
                    if (u['id'], d) not in all_dayoff_dates:
                        period = random.choice(["AM", "PM"])
                        status = random.choices(["APPROVED", "PENDING", "REJECTED"],
                                                weights=[80, 12, 8])[0]
                        reason = random.choice(DAYOFF_REASONS)
                        sql.append(
                            "INSERT INTO day_off_requests "
                            "(id, request_group_id, user_id, date, leave_type, reason, status, "
                            "half_day_period, created_at, updated_at) VALUES "
                            f"({dayoff_id}, NULL, {u['id']}, '{d}', "
                            f"'OFF_HALF', '{esc(reason)}', '{status}', '{period}', "
                            f"'{current}', '{current}');"
                        )
                        dayoff_id += 1
                        all_dayoff_dates.add((u['id'], d))
                        if status == "APPROVED":
                            key = (u['id'], d)
                            # Don't override a FULL day already set
                            if key not in approved_off:
                                approved_off[key] = period

        current += timedelta(days=1)

    sql.append("")

    # --- 4b. Generate check-ins ---
    sql.append("-- CHECK INS")
    checkin_id = 1
    current    = start_date

    while current <= end_date:
        d = current.date()
        # Skip weekends
        if current.weekday() >= 5:
            current += timedelta(days=1)
            continue
        # Skip holidays — NO check-in for anyone
        if d in holiday_dates:
            current += timedelta(days=1)
            continue

        for u in user_records:
            uid  = u["id"]
            ukey = (uid, d)
            off  = approved_off.get(ukey)  # "FULL" | "AM" | "PM" | None

            # Full day off → no check-in record at all
            if off == "FULL":
                continue

            # ~8% random absent (no check-in, no day-off)
            if random.random() < 0.08:
                sql.append(
                    "INSERT INTO check_ins "
                    "(id, user_name, date, check_in_time, check_out_time, "
                    "working_hours, is_late, created_at, updated_at) VALUES "
                    f"({checkin_id}, '{esc(u['name'])}', '{d}', "
                    "NULL, NULL, NULL, 0, NOW(), NOW());"
                )
                checkin_id += 1
                continue

            # Determine check-in / check-out based on half-day
            if off == "AM":
                # Morning off → user comes in at 13:00 (afternoon only)
                check_in  = rand_time_str(13)
                check_out = rand_time_str(17)
                is_late   = False        # starting at 13 is expected, not late

            elif off == "PM":
                # Afternoon off → user comes in morning, leaves at 12:00
                is_late   = random.random() < 0.25
                check_in  = rand_time_str(9 if is_late else 8)
                check_out = rand_time_str(12)

            else:
                # Normal full day
                is_late   = random.random() < 0.20
                check_in  = rand_time_str(9 if is_late else 8)
                check_out = rand_time_str(random.randint(17, 18))

            hours = calc_working_hours(check_in, check_out)

            sql.append(
                "INSERT INTO check_ins "
                "(id, user_name, date, check_in_time, check_out_time, "
                "working_hours, is_late, created_at, updated_at) VALUES "
                f"({checkin_id}, '{esc(u['name'])}', '{d}', "
                f"'{check_in}', '{check_out}', {hours}, "
                f"{int(is_late)}, NOW(), NOW());"
            )
            checkin_id += 1

        current += timedelta(days=1)

    sql.append("")

    # ──────────────────────────────────────────
    # 5. PROJECTS → PHASES → TASKS → SUBTASKS
    #
    #    project (staff_id = a staff user)
    #      └── phase  (2-4 phases per project)
    #            └── task  (3-6 tasks per phase)
    #                  └── subtask (0-3 subtasks per task)
    #
    #    Percentage logic:
    #      subtask pct  → random leaf (0/25/50/75/100)
    #      task    pct  → avg of its subtasks (or random if no subtasks)
    #      phase   pct  → avg of its tasks
    #      project pct  → avg of its phases
    # ──────────────────────────────────────────
    sql.append("-- PROJECTS, PHASES, TASKS")

    staff_ids   = [u["id"] for u in user_records if u["role"] == "staff"]
    all_user_ids= [u["id"] for u in user_records]

    project_id = 1
    phase_id   = 1
    task_id    = 1

    for p in range(1, num_projects + 1):
        proj_start = rand_date(start_date, end_date - timedelta(days=120))
        proj_end   = proj_start + timedelta(days=random.randint(60, 300))
        staff_id   = random.choice(staff_ids)
        title      = f"Project {p} — {random.choice(TASK_NOUNS).capitalize()} System"

        # Collect phase percentages to roll up to project
        phase_pcts: list[int] = []

        # Number of phases
        num_phases = random.randint(2, 4)
        phase_duration = (proj_end - proj_start).days // num_phases

        phase_rows: list[dict] = []  # buffer so we can compute project pct first

        for ph in range(num_phases):
            ph_start = proj_start + timedelta(days=ph * phase_duration)
            ph_end   = ph_start   + timedelta(days=phase_duration)
            ph_title = f"{random.choice(PHASE_TITLES)} Phase {ph + 1}"

            # Tasks in this phase
            num_tasks_here = random.randint(3, 6)
            task_pcts: list[int] = []
            task_rows: list[dict] = []

            for t in range(num_tasks_here):
                t_start = ph_start + timedelta(days=random.randint(0, max(1, phase_duration // 3)))
                t_end   = t_start  + timedelta(days=random.randint(5, 30))
                t_user  = random.choice(all_user_ids)
                est     = round(random.uniform(2.0, 40.0), 1)

                # Subtasks (0-3)
                num_sub = random.randint(0, 3)
                sub_pcts: list[int] = []
                sub_rows: list[dict] = []

                for s in range(num_sub):
                    sub_pct    = random.choice([0, 25, 50, 75, 100])
                    sub_status = status_from_pct(sub_pct)
                    sub_score  = random.randint(0, 10) if sub_pct > 0 else 0
                    sub_pcts.append(sub_pct)
                    sub_rows.append({
                        "pct": sub_pct, "status": sub_status,
                        "score": sub_score, "user": random.choice(all_user_ids),
                        "est": round(random.uniform(1.0, 8.0), 1),
                        "start": t_start, "end": t_end,
                    })

                # Task pct = avg of subtask pcts (or random leaf if no subtasks)
                if sub_pcts:
                    t_pct = pct_from_children(sub_pcts)
                else:
                    t_pct = random.choice([0, 25, 50, 75, 100])

                t_status = status_from_pct(t_pct)
                t_score  = random.randint(0, 10) if t_pct > 0 else 0
                task_pcts.append(t_pct)
                task_rows.append({
                    "pct": t_pct, "status": t_status, "score": t_score,
                    "user": t_user, "est": est,
                    "start": t_start, "end": t_end,
                    "subtasks": sub_rows,
                })

            ph_pct = pct_from_children(task_pcts)
            phase_pcts.append(ph_pct)
            phase_rows.append({
                "title": ph_title, "start": ph_start, "end": ph_end,
                "pct": ph_pct, "tasks": task_rows,
            })

        proj_pct    = pct_from_children(phase_pcts)
        proj_status = "inactive" if proj_pct == 100 else "active"

        # --- INSERT PROJECT ---
        sql.append(
            "INSERT INTO projects (id, title, description, staff_id, status, percentage, "
            "start_date, due_date, created_at, updated_at) VALUES "
            f"({project_id}, '{esc(title)}', 'Auto-generated project description.', "
            f"{staff_id}, '{proj_status}', {proj_pct}, "
            f"'{proj_start.date()}', '{proj_end.date()}', "
            f"'{proj_start}', '{proj_start}');"
        )

        # --- INSERT PHASES AND TASKS ---
        for ph_row in phase_rows:
            sql.append(
                "INSERT INTO phases (id, project_id, title, start_date, due_date, "
                "created_at, updated_at) VALUES "
                f"({phase_id}, {project_id}, '{esc(ph_row['title'])}', "
                f"'{ph_row['start'].date()}', '{ph_row['end'].date()}', "
                f"'{ph_row['start']}', '{ph_row['start']}');"
            )

            current_phase_id = phase_id
            phase_id += 1

            for t_row in ph_row["tasks"]:
                priority = random.choice(PRIORITIES)
                t_title  = f"{random.choice(TASK_VERBS)} {random.choice(TASK_NOUNS)}"
                sql.append(
                    "INSERT INTO tasks (id, title, assigned_user_id, status, priority, "
                    "start_date, due_date, active, percentage, estimated_time, score, "
                    "project_id, phase_id, parent_id, created_at, updated_at) VALUES "
                    f"({task_id}, '{esc(t_title)}', {t_row['user']}, '{t_row['status']}', "
                    f"'{priority}', '{t_row['start'].date()}', '{t_row['end'].date()}', "
                    f"1, {t_row['pct']}, {t_row['est']}, {t_row['score']}, "
                    f"{project_id}, {current_phase_id}, NULL, "
                    f"'{t_row['start']}', '{t_row['start']}');"
                )
                # task_user pivot
                sql.append(
                    "INSERT IGNORE INTO task_user (task_id, user_id, created_at, updated_at) VALUES "
                    f"({task_id}, {t_row['user']}, NOW(), NOW());"
                )
                parent_task_id = task_id
                task_id += 1

                # --- INSERT SUBTASKS ---
                for s_row in t_row["subtasks"]:
                    s_title = f"Sub: {random.choice(TASK_VERBS)} {random.choice(TASK_NOUNS)}"
                    sql.append(
                        "INSERT INTO tasks (id, title, assigned_user_id, status, priority, "
                        "start_date, due_date, active, percentage, estimated_time, score, "
                        "project_id, phase_id, parent_id, created_at, updated_at) VALUES "
                        f"({task_id}, '{esc(s_title)}', {s_row['user']}, '{s_row['status']}', "
                        f"'{random.choice(PRIORITIES)}', '{s_row['start'].date()}', "
                        f"'{s_row['end'].date()}', 1, {s_row['pct']}, {s_row['est']}, "
                        f"{s_row['score']}, {project_id}, {current_phase_id}, "
                        f"{parent_task_id}, NOW(), NOW());"
                    )
                    sql.append(
                        "INSERT IGNORE INTO task_user (task_id, user_id, created_at, updated_at) VALUES "
                        f"({task_id}, {s_row['user']}, NOW(), NOW());"
                    )
                    task_id += 1

        project_id += 1

    sql.append("")
    sql.append("-- Done")
    return "\n".join(sql) + "\n"


# ─────────────────────────────────────────────
# ENTRY POINT
# ─────────────────────────────────────────────
def main() -> int:
    parser = argparse.ArgumentParser(description="Generate full_seed.sql")
    parser.add_argument("--output",       default="full_seed.sql")
    parser.add_argument("--seed",         type=int, default=42)
    parser.add_argument("--num-users",    type=int, default=DEFAULT_NUM_USERS)
    parser.add_argument("--num-projects", type=int, default=DEFAULT_NUM_PROJECTS)
    parser.add_argument("--num-tasks",    type=int, default=DEFAULT_NUM_TASKS)
    parser.add_argument("--start-date",   default=DEFAULT_START_DATE.strftime("%Y-%m-%d"))
    parser.add_argument("--end-date",     default=DEFAULT_END_DATE.strftime("%Y-%m-%d"))
    args = parser.parse_args()

    random.seed(args.seed)

    start_date = datetime.strptime(args.start_date, "%Y-%m-%d")
    end_date   = datetime.strptime(args.end_date,   "%Y-%m-%d")

    out_path = Path(args.output)
    out_path.parent.mkdir(parents=True, exist_ok=True)

    print(f"Generating seed: {args.num_users} users, {args.num_projects} projects "
          f"from {args.start_date} to {args.end_date} ...")

    sql_text = generate_sql(
        num_users=args.num_users,
        num_projects=args.num_projects,
        num_tasks_per_phase=args.num_tasks,
        start_date=start_date,
        end_date=end_date,
    )

    out_path.write_text(sql_text, encoding="utf-8")

    lines = sql_text.count("\n")
    print(f"✅ Generated: {out_path}  ({lines:,} lines)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())