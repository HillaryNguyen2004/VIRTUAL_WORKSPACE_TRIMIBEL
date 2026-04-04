"""
Full seeder with PRODUCTIVITY PROFILES.

Each user is assigned one of 5 profiles controlling behaviour over time.
This creates real score variance (target std > 15) so the LSTM can
learn to distinguish employees and detect patterns like burnout.

Profiles:
  high_performer  → rarely late, full hours, consistent
  average         → moderate lateness, normal hours
  low_performer   → frequent lateness, short hours, many absences
  burnout         → starts great, gradually declines after 40% of time
  inconsistent    → alternates 6-week good / 3-week bad cycles
  Profile assignments:
  User   1  Tran An               average
  User   2  Hoang Minh            high_performer
  User   3  Le Dung               high_performer
  User   4  Do Chi                average
  User   5  Dang Binh             burnout
  User   6  Tran Linh             average
  User   7  Ho An                 high_performer
  User   8  Do Dat                high_performer
  User   9  Ho Nam                high_performer
  User  10  Dang Anh              average
  User  11  Pham Anh              average
  User  12  Dang Dung             average
  User  13  Ho Nam                average
  User  14  Do Dung               low_performer
  User  15  Do Phuc               low_performer
  User  16  Vu Mai                burnout
  User  17  Nguyen Minh           inconsistent
  User  18  Pham Dung             inconsistent
  User  19  Bui Bao               high_performer
  User  20  Vu Linh               high_performer
  User  21  Tran Nhi              high_performer
  User  22  Do Minh               average
  User  23  Dang Nam              average
  User  24  Pham Anh              average
  User  25  Nguyen Anh            average
  User  26  Tran Linh             low_performer
  User  27  Pham Hieu             low_performer
  User  28  Le Nam                burnout
  User  29  Do Long               inconsistent
  User  30  Dang Mai              inconsistent

Usage:
    python3 generate_seed.py
    python3 generate_seed.py --num-users 30 --num-projects 50 --output full_seed.sql
"""

import argparse
import random
import math
import uuid
from datetime import datetime, timedelta, date
from pathlib import Path

# ─────────────────────────────────────────────────────────
# DEFAULTS
# ─────────────────────────────────────────────────────────
DEFAULT_NUM_USERS    = 30
DEFAULT_NUM_PROJECTS = 300
DEFAULT_START_DATE   = datetime(2018, 1, 1)
DEFAULT_END_DATE     = datetime(2026, 4, 30)

PASSWORD_HASH = "$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi"

DEPARTMENTS = {2: "HR", 3: "Sales", 4: "Marketing", 5: "Finance", 6: "Engineering"}
ROLE_IDS    = {"admin": 4, "staff": 2, "user": 1}

FIRST_NAMES = ["An", "Binh", "Chi", "Dung", "Hanh", "Khoa", "Linh", "Minh", "Nam", "Phuc",
               "Anh", "Bao", "Chau", "Dat", "Giang", "Hieu", "Khanh", "Long", "Mai", "Nhi"]
LAST_NAMES  = ["Nguyen", "Tran", "Le", "Pham", "Hoang", "Vu", "Dang", "Bui", "Do", "Ho"]

DAYOFF_REASONS = ["Personal leave", "Sick leave", "Family emergency",
                  "Medical appointment", "Maternity leave", "Wedding ceremony",
                  "Funeral", "House moving", "Child care", "Annual leave"]

TASK_VERBS  = ["Implement", "Design", "Review", "Test", "Refactor",
               "Document", "Analyze", "Deploy", "Fix", "Optimize",
               "Build", "Integrate", "Migrate", "Validate", "Research"]
TASK_NOUNS  = ["API", "UI", "database", "module", "report", "dashboard",
               "authentication", "notification", "export", "import",
               "workflow", "pipeline", "schema", "endpoint", "service"]
PHASE_NAMES = ["Planning", "Design", "Development", "Testing",
               "Deployment", "Review", "Maintenance", "Research"]
PROJ_ADJ    = ["Smart", "Advanced", "Integrated", "Automated", "Cloud",
               "Realtime", "Scalable", "Unified", "Intelligent", "Digital"]
PROJ_NOUN   = ["Management System", "Analytics Platform", "Dashboard",
               "Monitoring Tool", "Reporting Suite", "Data Pipeline",
               "Tracking System", "Portal", "Gateway", "Processor"]
PRIORITIES  = ["low", "normal", "high", "urgent"]

ANNUAL_HOLIDAYS = [(1,1,"New Year's Day"), (4,30,"Reunification Day"),
                   (5,1,"Labour Day"), (9,2,"National Day")]
MULTI_HOLIDAYS  = [(1, 20, 26, "Lunar New Year")]

# ─────────────────────────────────────────────────────────
# PRODUCTIVITY PROFILES
# ─────────────────────────────────────────────────────────
PROFILES = {
    "high_performer": {
        "late_rate": 0.04, "absent_rate": 0.02,
        "hours_min": 8.5,  "hours_max": 10.0,
        "dayoff_rate": 0.015, "temporal": "stable"
    },
    "average": {
        "late_rate": 0.18, "absent_rate": 0.07,
        "hours_min": 7.0,  "hours_max": 9.0,
        "dayoff_rate": 0.03, "temporal": "stable"
    },
    "low_performer": {
        "late_rate": 0.40, "absent_rate": 0.15,
        "hours_min": 5.0,  "hours_max": 7.5,
        "dayoff_rate": 0.05, "temporal": "stable"
    },
    "burnout": {
        # Starts like high_performer, degrades after 40% of date range
        "late_rate": 0.05, "absent_rate": 0.03,
        "hours_min": 8.0,  "hours_max": 10.0,
        "dayoff_rate": 0.02, "temporal": "declining"
    },
    "inconsistent": {
        # 6-week good cycle → 3-week bad cycle → repeat
        "late_rate": 0.25, "absent_rate": 0.10,
        "hours_min": 6.0,  "hours_max": 9.5,
        "dayoff_rate": 0.04, "temporal": "swings"
    },
}

# Fixed profile assignments for users 1-6
BASE_PROFILES = {
    1: "average",          # admin
    2: "high_performer",   # staff HR
    3: "high_performer",   # staff Sales
    4: "average",          # staff Marketing
    5: "burnout",          # staff Finance — declining trend for LSTM to detect
    6: "average",          # staff Engineering
}

# Cycle for users 7-30 — ensures every profile appears multiple times
PROFILE_CYCLE = [
    "high_performer", "high_performer", "high_performer",
    "average",        "average",        "average",        "average",
    "low_performer",  "low_performer",
    "burnout",
    "inconsistent",   "inconsistent",
]

# ─────────────────────────────────────────────────────────
# HELPERS
# ─────────────────────────────────────────────────────────
def rand_date_dt(start: datetime, end: datetime) -> datetime:
    delta = max((end - start).days, 1)
    return start + timedelta(days=random.randint(0, delta))

def rand_time_str(hour: int) -> str:
    return f"{hour:02d}:{random.randint(0,59):02d}:{random.randint(0,59):02d}"

def calc_hours(t1: str, t2: str) -> str:
    fmt = "%H:%M:%S"
    d1  = datetime.strptime(t1, fmt)
    d2  = datetime.strptime(t2, fmt)
    secs = (d2 - d1).total_seconds()
    if secs <= 0:
        return "NULL"
    h = int(secs) // 3600
    m = (int(secs) % 3600) // 60
    return f"'{h:02d}:{m:02d}'"

def esc(s: str) -> str:
    return s.replace("'", "''")

def pct_avg(vals: list) -> int:
    return math.ceil(sum(vals) / len(vals)) if vals else 0

def status_from_pct(pct: int) -> str:
    if pct == 0:   return "pending"
    if pct == 100: return "completed"
    return "in_progress"

def build_holidays(sy: int, ey: int) -> set:
    h = set()
    for yr in range(sy, ey + 1):
        for m, d, _ in ANNUAL_HOLIDAYS:
            try: h.add(date(yr, m, d))
            except ValueError: pass
        for m, sd, ed, _ in MULTI_HOLIDAYS:
            for dd in range(sd, ed + 1):
                try: h.add(date(yr, m, dd))
                except ValueError: pass
    return h

def get_params(profile: str, current_d: date,
               start_d: date, end_d: date) -> dict:
    """Returns effective behavioural params adjusted for temporal pattern."""
    p = dict(PROFILES[profile])   # copy

    total   = max((end_d - start_d).days, 1)
    elapsed = max((current_d - start_d).days, 0)
    prog    = elapsed / total     # 0.0 → 1.0

    if p["temporal"] == "declining":
        # Starts good, deteriorates after 40%
        decay = max(0.0, (prog - 0.4) / 0.6)
        p["late_rate"]   = min(0.05 + decay * 0.45, 0.55)
        p["absent_rate"] = min(0.03 + decay * 0.20, 0.25)
        p["hours_min"]   = max(8.0  - decay * 4.0,  3.5)
        p["hours_max"]   = max(10.0 - decay * 3.5,  6.0)

    elif p["temporal"] == "swings":
        # 6-week good, 3-week bad, repeat (63-day cycle)
        cycle = elapsed % 63
        if cycle < 42:   # good weeks
            p["late_rate"]   *= 0.4
            p["absent_rate"] *= 0.4
            p["hours_min"]    = min(p["hours_min"] + 1.5, 9.5)
            p["hours_max"]    = min(p["hours_max"] + 0.5, 10.5)
        else:            # bad weeks
            p["late_rate"]   = min(p["late_rate"] * 2.8, 0.60)
            p["absent_rate"] = min(p["absent_rate"] * 2.8, 0.35)
            p["hours_min"]   = max(p["hours_min"] - 2.5, 3.5)
            p["hours_max"]   = max(p["hours_max"] - 2.0, 5.0)
    return p


# ─────────────────────────────────────────────────────────
# GENERATOR
# ─────────────────────────────────────────────────────────
def generate_sql(*, num_users: int, num_projects: int,
                 start_date: datetime, end_date: datetime) -> str:

    sql: list[str] = []
    sql.append("SET FOREIGN_KEY_CHECKS=0;")
    for t in ["model_has_roles","day_off_requests","check_ins",
              "task_user","tasks","phases","projects","holidays","users"]:
        sql.append(f"TRUNCATE TABLE {t};")
    sql.append("SET FOREIGN_KEY_CHECKS=1;\n")

    # ── 1. HOLIDAYS ───────────────────────────────────────
    sql.append("-- HOLIDAYS")
    holiday_dates = build_holidays(start_date.year, end_date.year)
    h_id = 1
    for yr in range(start_date.year, end_date.year + 1):
        for m, d, title in ANNUAL_HOLIDAYS:
            try:
                hd = date(yr, m, d)
                if start_date.date() <= hd <= end_date.date():
                    sql.append(
                        f"INSERT INTO holidays (id,title,start_date,end_date,created_at,updated_at) VALUES "
                        f"({h_id},'{esc(title)} {yr}','{datetime(yr,m,d,0,0)}',"
                        f"'{datetime(yr,m,d,23,59)}',NOW(),NOW());"
                    )
                    h_id += 1
            except ValueError: pass
        for m, sd, ed, title in MULTI_HOLIDAYS:
            try:
                if start_date.date() <= date(yr, m, sd) <= end_date.date():
                    sql.append(
                        f"INSERT INTO holidays (id,title,start_date,end_date,created_at,updated_at) VALUES "
                        f"({h_id},'{esc(title)} {yr}','{datetime(yr,m,sd,0,0)}',"
                        f"'{datetime(yr,m,ed,23,59)}',NOW(),NOW());"
                    )
                    h_id += 1
            except ValueError: pass
    sql.append("")

    # ── 2. USERS ──────────────────────────────────────────
    sql.append("-- USERS")
    dept_staff:  dict[int, int]  = {}
    user_records: list[dict]      = []
    uid = 1

    # Admin
    name    = f"{random.choice(LAST_NAMES)} {random.choice(FIRST_NAMES)}"
    created = rand_date_dt(start_date, start_date + timedelta(days=30))
    sql.append(
        f"INSERT INTO users (id,department_id,name,email,username,password,"
        f"blocked,login_attempts,is_google_connected,created_at,updated_at) VALUES "
        f"({uid},NULL,'{esc(name)}','user1@mail.com','user1','{PASSWORD_HASH}',"
        f"0,0,0,'{created}','{created}');"
    )
    user_records.append({"id":uid,"name":name,"username":"user1","dept_id":None,
                          "role":"admin","profile":BASE_PROFILES.get(uid,"average"),
                          "created":created})
    uid += 1

    # 1 staff per dept
    for dept_id in sorted(DEPARTMENTS.keys()):
        name    = f"{random.choice(LAST_NAMES)} {random.choice(FIRST_NAMES)}"
        uname   = f"user{uid}"
        created = rand_date_dt(start_date, start_date + timedelta(days=60))
        sql.append(
            f"INSERT INTO users (id,department_id,name,email,username,password,"
            f"blocked,login_attempts,is_google_connected,team_leader_id,created_at,updated_at) VALUES "
            f"({uid},{dept_id},'{esc(name)}','user{uid}@mail.com','{uname}','{PASSWORD_HASH}',"
            f"0,0,0,NULL,'{created}','{created}');"
        )
        profile = BASE_PROFILES.get(uid, "average")
        user_records.append({"id":uid,"name":name,"username":uname,"dept_id":dept_id,
                              "role":"staff","profile":profile,"created":created})
        dept_staff[dept_id] = uid
        uid += 1

    # Regular users
    dept_list = sorted(DEPARTMENTS.keys())
    p_idx     = 0
    while uid <= num_users:
        dept_id     = random.choice(dept_list)
        team_leader = dept_staff[dept_id]
        name        = f"{random.choice(LAST_NAMES)} {random.choice(FIRST_NAMES)}"
        uname       = f"user{uid}"
        created     = rand_date_dt(start_date + timedelta(days=30),
                                   end_date - timedelta(days=90))
        profile     = PROFILE_CYCLE[p_idx % len(PROFILE_CYCLE)]
        p_idx      += 1
        sql.append(
            f"INSERT INTO users (id,department_id,name,email,username,password,"
            f"blocked,login_attempts,is_google_connected,team_leader_id,created_at,updated_at) VALUES "
            f"({uid},{dept_id},'{esc(name)}','user{uid}@mail.com','{uname}','{PASSWORD_HASH}',"
            f"0,0,0,{team_leader},'{created}','{created}');"
        )
        user_records.append({"id":uid,"name":name,"username":uname,"dept_id":dept_id,
                              "role":"user","profile":profile,"created":created})
        uid += 1
    sql.append("")

    # Print profile summary
    for u in user_records:
        print(f"  User {u['id']:3d}  {u['name']:<20}  {u['profile']}")

    # ── 3. ROLES ──────────────────────────────────────────
    sql.append("-- ROLES")
    for u in user_records:
        sql.append(
            f"INSERT IGNORE INTO model_has_roles (role_id,model_type,model_id) VALUES "
            f"({ROLE_IDS.get(u['role'],1)},'App\\\\Models\\\\User',{u['id']});"
        )
    sql.append("")

    # ── 4. DAY-OFF REQUESTS ───────────────────────────────
    sql.append("-- DAY OFF REQUESTS")
    approved_off: dict[tuple, str] = {}
    all_dayoff:   set               = set()
    dayoff_id = 1
    cur = start_date

    while cur <= end_date:
        d = cur.date()
        if cur.weekday() < 5 and d not in holiday_dates:
            for u in user_records:
                if u["role"] == "admin":
                    continue
                params = get_params(u["profile"], d, start_date.date(), end_date.date())
                roll   = random.random()
                key    = (u["id"], d)

                if roll < params["dayoff_rate"] and key not in all_dayoff:
                    span   = random.randint(1, 3)
                    grp    = str(uuid.uuid4())
                    status = random.choices(["APPROVED","PENDING","REJECTED"],
                                             weights=[75,15,10])[0]
                    reason = random.choice(DAYOFF_REASONS)
                    for off in range(span):
                        sd = d + timedelta(days=off)
                        if sd.weekday() >= 5 or sd in holiday_dates or sd > end_date.date():
                            continue
                        if (u["id"], sd) in all_dayoff:
                            continue
                        sql.append(
                            f"INSERT INTO day_off_requests "
                            f"(id,request_group_id,user_id,date,leave_type,reason,status,"
                            f"half_day_period,created_at,updated_at) VALUES "
                            f"({dayoff_id},'{grp}',{u['id']},'{sd}',"
                            f"'OFF_FULL','{esc(reason)}','{status}',NULL,'{cur}','{cur}');"
                        )
                        dayoff_id += 1
                        all_dayoff.add((u["id"], sd))
                        if status == "APPROVED":
                            approved_off[(u["id"], sd)] = "FULL"

                elif roll < params["dayoff_rate"] * 1.5 and key not in all_dayoff:
                    period = random.choice(["AM","PM"])
                    status = random.choices(["APPROVED","PENDING","REJECTED"],
                                             weights=[80,12,8])[0]
                    sql.append(
                        f"INSERT INTO day_off_requests "
                        f"(id,request_group_id,user_id,date,leave_type,reason,status,"
                        f"half_day_period,created_at,updated_at) VALUES "
                        f"({dayoff_id},NULL,{u['id']},'{d}',"
                        f"'OFF_HALF','{esc(random.choice(DAYOFF_REASONS))}','{status}',"
                        f"'{period}','{cur}','{cur}');"
                    )
                    dayoff_id += 1
                    all_dayoff.add(key)
                    if status == "APPROVED" and key not in approved_off:
                        approved_off[key] = period
        cur += timedelta(days=1)
    sql.append("")

    # ── 5. CHECK-INS (profile-driven) ─────────────────────
    sql.append("-- CHECK INS")
    checkin_id = 1
    cur        = start_date

    while cur <= end_date:
        d = cur.date()
        if cur.weekday() >= 5 or d in holiday_dates:
            cur += timedelta(days=1)
            continue

        for u in user_records:
            off    = approved_off.get((u["id"], d))
            params = get_params(u["profile"], d, start_date.date(), end_date.date())

            if off == "FULL":
                continue

            # Profile-driven absence
            if random.random() < params["absent_rate"]:
                sql.append(
                    f"INSERT INTO check_ins "
                    f"(id,user_name,date,check_in_time,check_out_time,"
                    f"working_hours,is_late,created_at,updated_at) VALUES "
                    f"({checkin_id},'{esc(u['name'])}','{d}',"
                    f"NULL,NULL,NULL,0,NOW(),NOW());"
                )
                checkin_id += 1
                continue

            if off == "AM":
                check_in  = rand_time_str(13)
                check_out = rand_time_str(17)
                is_late   = False
            elif off == "PM":
                is_late   = random.random() < params["late_rate"]
                check_in  = rand_time_str(9 if is_late else 8)
                check_out = rand_time_str(12)
            else:
                is_late   = random.random() < params["late_rate"]
                check_in  = rand_time_str(9 if is_late else 8)
                # Profile-driven hours
                hours_f   = params["hours_min"] + random.random() * (
                            params["hours_max"] - params["hours_min"])
                ci_h = int(check_in[:2])
                ci_m = int(check_in[3:5])
                total_m   = ci_h * 60 + ci_m + int(hours_f * 60)
                co_h = min(total_m // 60, 21)
                co_m = total_m % 60
                check_out = f"{co_h:02d}:{co_m:02d}:{random.randint(0,59):02d}"

            hours = calc_hours(check_in, check_out)
            sql.append(
                f"INSERT INTO check_ins "
                f"(id,user_name,date,check_in_time,check_out_time,"
                f"working_hours,is_late,created_at,updated_at) VALUES "
                f"({checkin_id},'{esc(u['name'])}','{d}',"
                f"'{check_in}','{check_out}',{hours},{int(is_late)},NOW(),NOW());"
            )
            checkin_id += 1
        cur += timedelta(days=1)
    sql.append("")

    # ── 6. PROJECTS → PHASES → TASKS → SUBTASKS ───────────
    sql.append("-- PROJECTS, PHASES, TASKS")

    # Build team map: staff_id → [staff + members]
    teams: dict[int, list] = {u["id"]: [u["id"]] for u in user_records if u["role"] == "staff"}
    for u in user_records:
        if u["role"] == "user" and u["dept_id"]:
            leader = dept_staff.get(u["dept_id"])
            if leader and leader in teams:
                teams[leader].append(u["id"])

    # Completion distributions by profile to improve separability.
    PERF_PCTS = {
        "high_performer": [75, 100, 100, 100, 100, 100],
        "average": [25, 50, 75, 100, 100],
        "low_performer": [0, 0, 25, 25, 50, 75],
    }
    user_profiles = {u["id"]: u["profile"] for u in user_records}

    def get_task_outcome(uid: int, t_date: date) -> tuple[int, int]:
        prof = user_profiles[uid]
        eff_prof = prof
        total_days = max((end_date.date() - start_date.date()).days, 1)
        elapsed = max((t_date - start_date.date()).days, 0)
        prog = elapsed / total_days

        if prof == "burnout":
            if prog > 0.4:
                decay = min((prog - 0.4) / 0.6, 1.0)
                eff_prof = "low_performer" if random.random() < decay + 0.2 else "average"
            else:
                eff_prof = "high_performer"
        elif prof == "inconsistent":
            cycle = elapsed % 63
            eff_prof = "high_performer" if cycle < 42 else "low_performer"

        pct = random.choice(PERF_PCTS[eff_prof])

        score = 0
        if pct == 100:
            if eff_prof == "high_performer":
                score = random.randint(8, 10)
            elif eff_prof == "average":
                score = random.randint(5, 8)
            else:
                score = random.randint(1, 5)

        return pct, score

    project_id = 1
    phase_id   = 1
    task_id    = 1

    num_teams = len(teams)
    projs_per_team = max(1, num_projects // num_teams)

    for staff_id, team in teams.items():
        total_team_days = (end_date - start_date).days
        avg_proj_days = max(10, total_team_days // projs_per_team)

        current_proj_start = start_date

        for p_idx in range(projs_per_team):
            if current_proj_start >= end_date:
                break

            proj_end = current_proj_start + timedelta(days=avg_proj_days)
            if p_idx == projs_per_team - 1 or proj_end > end_date:
                proj_end = end_date

            proj_title = f"{random.choice(PROJ_ADJ)} {random.choice(PROJ_NOUN)} {project_id}"

            num_phases = random.randint(2, 4)
            phase_dur = max(1, (proj_end - current_proj_start).days // num_phases)
            phase_pcts: list = []
            phase_rows: list = []

            for ph in range(num_phases):
                ph_start = current_proj_start + timedelta(days=ph * phase_dur)
                ph_end = ph_start + timedelta(days=phase_dur)
                if ph == num_phases - 1:
                    ph_end = proj_end

                ph_title = f"{random.choice(PHASE_NAMES)} Phase {ph+1}"
                task_pcts: list = []
                task_rows: list = []

                # Schedule tasks back-to-back for high utilization.
                for t_user in team:
                    curr_task_start = ph_start

                    while curr_task_start < ph_end:
                        # Small chance of 1-2 day gap.
                        if random.random() < 0.05:
                            curr_task_start += timedelta(days=random.randint(1, 2))

                        if curr_task_start >= ph_end:
                            break

                        t_dur = random.randint(2, 7)
                        t_end = curr_task_start + timedelta(days=t_dur)
                        if t_end > ph_end:
                            t_end = ph_end

                        est = round(random.uniform(8.0, 40.0), 1)
                        t_title = f"{random.choice(TASK_VERBS)} {random.choice(TASK_NOUNS)}"

                        sub_pcts: list = []
                        sub_rows: list = []

                        for _ in range(random.randint(0, 2)):
                            sp, s_score = get_task_outcome(t_user, curr_task_start.date())
                            ss = status_from_pct(sp)
                            sub_pcts.append(sp)
                            sub_rows.append({
                                "pct": sp,
                                "status": ss,
                                "score": s_score,
                                "user": t_user,
                                "est": round(random.uniform(1.0, 8.0), 1),
                                "start": curr_task_start,
                                "end": t_end,
                            })

                        if sub_pcts:
                            t_pct = pct_avg(sub_pcts)
                            _, t_score = get_task_outcome(t_user, curr_task_start.date()) if t_pct == 100 else (0, 0)
                        else:
                            t_pct, t_score = get_task_outcome(t_user, curr_task_start.date())

                        t_status = status_from_pct(t_pct)
                        task_pcts.append(t_pct)
                        task_rows.append({
                            "pct": t_pct,
                            "status": t_status,
                            "score": t_score if t_status == "completed" else 0,
                            "user": t_user,
                            "est": est,
                            "title": t_title,
                            "start": curr_task_start,
                            "end": t_end,
                            "subtasks": sub_rows,
                        })

                        curr_task_start = t_end + timedelta(days=1)

                ph_pct = pct_avg(task_pcts) if task_pcts else 0
                phase_pcts.append(ph_pct)
                phase_rows.append({
                    "title": ph_title,
                    "start": ph_start,
                    "end": ph_end,
                    "pct": ph_pct,
                    "tasks": task_rows,
                })

            proj_pct = pct_avg(phase_pcts) if phase_pcts else 0
            proj_status = "inactive" if proj_pct == 100 else "active"

            sql.append(
                f"INSERT INTO projects (id,title,description,staff_id,status,percentage,"
                f"start_date,due_date,created_at,updated_at) VALUES "
                f"({project_id},'{esc(proj_title)}','Auto-generated.',{staff_id},"
                f"'{proj_status}',{proj_pct},'{current_proj_start.date()}','{proj_end.date()}',NOW(),NOW());"
            )

            for ph_row in phase_rows:
                sql.append(
                    f"INSERT INTO phases (id,project_id,title,start_date,due_date,"
                    f"created_at,updated_at) VALUES "
                    f"({phase_id},{project_id},'{esc(ph_row['title'])}',"
                    f"'{ph_row['start'].date()}','{ph_row['end'].date()}',NOW(),NOW());"
                )
                cur_phase = phase_id
                phase_id += 1

                for t_row in ph_row["tasks"]:
                    sql.append(
                        f"INSERT INTO tasks (id,title,assigned_user_id,status,priority,"
                        f"start_date,due_date,active,percentage,estimated_time,score,"
                        f"project_id,phase_id,parent_id,created_at,updated_at) VALUES "
                        f"({task_id},'{esc(t_row['title'])}',{t_row['user']},"
                        f"'{t_row['status']}','{random.choice(PRIORITIES)}',"
                        f"'{t_row['start'].date()}','{t_row['end'].date()}',"
                        f"1,{t_row['pct']},{t_row['est']},{t_row['score']},"
                        f"{project_id},{cur_phase},NULL,NOW(),NOW());"
                    )
                    sql.append(
                        f"INSERT IGNORE INTO task_user (task_id,user_id,created_at,updated_at) VALUES "
                        f"({task_id},{t_row['user']},NOW(),NOW());"
                    )
                    parent = task_id
                    task_id += 1

                    for s_row in t_row["subtasks"]:
                        s_title = f"Sub: {random.choice(TASK_VERBS)} {random.choice(TASK_NOUNS)}"
                        sql.append(
                            f"INSERT INTO tasks (id,title,assigned_user_id,status,priority,"
                            f"start_date,due_date,active,percentage,estimated_time,score,"
                            f"project_id,phase_id,parent_id,created_at,updated_at) VALUES "
                            f"({task_id},'{esc(s_title)}',{s_row['user']},"
                            f"'{s_row['status']}','{random.choice(PRIORITIES)}',"
                            f"'{s_row['start'].date()}','{s_row['end'].date()}',"
                            f"1,{s_row['pct']},{s_row['est']},{s_row['score']},"
                            f"{project_id},{cur_phase},{parent},NOW(),NOW());"
                        )
                        sql.append(
                            f"INSERT IGNORE INTO task_user (task_id,user_id,created_at,updated_at) VALUES "
                            f"({task_id},{s_row['user']},NOW(),NOW());"
                        )
                        task_id += 1

            current_proj_start = proj_end + timedelta(days=random.randint(1, 3))
            project_id += 1

    sql.append("\n-- Done")
    return "\n".join(sql) + "\n"


# ─────────────────────────────────────────────────────────
# ENTRY POINT
# ─────────────────────────────────────────────────────────
def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--output",       default="full_seed.sql")
    parser.add_argument("--seed",         type=int, default=42)
    parser.add_argument("--num-users",    type=int, default=DEFAULT_NUM_USERS)
    parser.add_argument("--num-projects", type=int, default=DEFAULT_NUM_PROJECTS)
    parser.add_argument("--start-date",   default=DEFAULT_START_DATE.strftime("%Y-%m-%d"))
    parser.add_argument("--end-date",     default=DEFAULT_END_DATE.strftime("%Y-%m-%d"))
    args = parser.parse_args()

    random.seed(args.seed)
    start_date = datetime.strptime(args.start_date, "%Y-%m-%d")
    end_date   = datetime.strptime(args.end_date,   "%Y-%m-%d")

    out = Path(args.output)
    out.parent.mkdir(parents=True, exist_ok=True)

    print(f"\nGenerating: {args.num_users} users · {args.num_projects} projects\n")
    print("Profile assignments:")

    sql_text = generate_sql(
        num_users=args.num_users,
        num_projects=args.num_projects,
        start_date=start_date,
        end_date=end_date,
    )

    out.write_text(sql_text, encoding="utf-8")
    lines = sql_text.count("\n")
    print(f"\n✅ Generated → {out}  ({lines:,} lines)")
    print("\nExpected score distribution after ETL + retrain:")
    print("  high_performer : ~75–95%")
    print("  average        : ~50–70%")
    print("  low_performer  : ~20–50%")
    print("  burnout        : ~70% → declining to ~30%  (LSTM should detect)")
    print("  inconsistent   : ~30–80% swings")
    print("\nTarget: std > 15,  distinct_scores > 60")
    return 0

if __name__ == "__main__":
    raise SystemExit(main())