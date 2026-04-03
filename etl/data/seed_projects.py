import argparse
import random
import math
import sys
from datetime import datetime, timedelta, date
from pathlib import Path
from sqlalchemy import create_engine
import pandas as pd

# Add parent directory to path to import config
sys.path.insert(0, str(Path(__file__).parent.parent))

# ─────────────────────────────────────────────
# DB CONFIG (reuse yours)
# ─────────────────────────────────────────────
from config import MYSQL_CONFIG

engine = create_engine(
    f"mysql+pymysql://{MYSQL_CONFIG['user']}:{MYSQL_CONFIG['password']}@{MYSQL_CONFIG['host']}/{MYSQL_CONFIG['database']}"
)

# ─────────────────────────────────────────────
# BUILD TEAMS DYNAMICALLY FROM USERS TABLE
# ─────────────────────────────────────────────
def build_teams():
    df = pd.read_sql("""
        SELECT id, team_leader_id
        FROM users
    """, engine)

    teams = {}

    # First: every leader includes themselves
    for _, row in df.iterrows():
        if row['team_leader_id'] is None:
            teams[int(row['id'])] = [int(row['id'])]

    # Then: assign members
    for _, row in df.iterrows():
        leader = row['team_leader_id']
        if pd.notna(leader):
            leader = int(leader)
            if leader not in teams:
                teams[leader] = [leader]
            teams[leader].append(int(row['id']))

    return teams


TEAMS = build_teams()
STAFF_IDS = list(TEAMS.keys())

# ─────────────────────────────────────────────
# DEFAULTS
# ─────────────────────────────────────────────
DEFAULT_PROJECTS = 20
DEFAULT_PHASES_PER_PROJ = (2, 4)
DEFAULT_TASKS_PER_PHASE = (4, 7)
DEFAULT_SUBTASKS_PER_TASK = (1, 4)

START_DATE = datetime(2020, 1, 1)
END_DATE   = datetime(2026, 3, 30)

TASK_VERBS = ["Implement", "Design", "Review", "Test", "Refactor",
              "Document", "Analyze", "Deploy", "Fix", "Optimize"]

TASK_NOUNS = ["API", "UI", "database", "module", "dashboard",
              "workflow", "pipeline", "endpoint", "service"]

PHASE_NAMES = ["Planning", "Design", "Development", "Testing", "Deployment"]

PROJ_ADJECTIVES = ["Smart", "Advanced", "Cloud", "Realtime", "Scalable"]
PROJ_NOUNS = ["System", "Platform", "Dashboard", "Pipeline"]

PRIORITIES = ["low", "normal", "high", "urgent"]

# 🔥 FIXED: more completed tasks
LEAF_PCTS = [0, 25, 50, 75, 100, 100, 100]

# ─────────────────────────────────────────────
# HELPERS
# ─────────────────────────────────────────────
def rand_date(start, end):
    # Convert date objects to datetime if needed
    if isinstance(start, date) and not isinstance(start, datetime):
        start = datetime.combine(start, datetime.min.time())
    if isinstance(end, date) and not isinstance(end, datetime):
        end = datetime.combine(end, datetime.min.time())
    
    delta = (end - start).days
    result = start + timedelta(days=random.randint(0, delta))
    
    # Return as date object
    return result.date() if isinstance(result, datetime) else result

def pct_avg(values):
    return math.ceil(sum(values) / len(values)) if values else 0

def status_from_pct(pct):
    if pct == 0:
        return "pending"
    elif pct == 100:
        return "completed"
    return "in_progress"

def esc(s):
    return s.replace("'", "\\'")

# ─────────────────────────────────────────────
# GENERATOR
# ─────────────────────────────────────────────
def generate(num_projects):
    sql = []

    sql.append("SET FOREIGN_KEY_CHECKS=0;")
    sql.append("TRUNCATE TABLE tasks;")
    sql.append("TRUNCATE TABLE phases;")
    sql.append("TRUNCATE TABLE projects;")
    sql.append("SET FOREIGN_KEY_CHECKS=1;\n")

    project_id = 1
    phase_id = 1
    task_id = 1

    for p in range(num_projects):

        staff_id = random.choice(STAFF_IDS)
        team = TEAMS[staff_id]

        proj_start = rand_date(START_DATE, END_DATE - timedelta(days=120))
        proj_end = proj_start + timedelta(days=random.randint(90, 365))

        proj_title = f"{random.choice(PROJ_ADJECTIVES)} {random.choice(PROJ_NOUNS)} {p+1}"

        phase_pcts = []

        num_phases = random.randint(2, 4)

        phase_rows = []

        for _ in range(num_phases):

            ph_start = proj_start
            ph_end = proj_end

            task_pcts = []
            task_rows = []

            for _ in range(random.randint(4, 7)):

                t_user = random.choice(team)
                t_start = rand_date(ph_start, ph_end)
                t_end = t_start + timedelta(days=random.randint(5, 30))
                t_title = f"{random.choice(TASK_VERBS)} {random.choice(TASK_NOUNS)}"

                sub_pcts = []
                sub_rows = []

                for _ in range(random.randint(1, 4)):

                    pct = random.choice(LEAF_PCTS)
                    status = status_from_pct(pct)
                    score = random.randint(50, 100) if status == "completed" else 0

                    sub_pcts.append(pct)

                    sub_rows.append({
                        "pct": pct,
                        "status": status,
                        "score": score,
                        "user": random.choice(team)
                    })

                t_pct = pct_avg(sub_pcts)
                t_status = status_from_pct(t_pct)
                t_score = random.randint(60, 100) if t_status == "completed" else 0

                task_pcts.append(t_pct)

                task_rows.append({
                    "pct": t_pct,
                    "status": t_status,
                    "score": t_score,
                    "user": t_user,
                    "title": t_title,
                    "priority": random.choice(PRIORITIES),
                    "subs": sub_rows
                })

            ph_pct = pct_avg(task_pcts)
            phase_pcts.append(ph_pct)

            phase_rows.append({
                "pct": ph_pct,
                "tasks": task_rows
            })

        proj_pct = pct_avg(phase_pcts)
        proj_status = "inactive" if proj_pct == 100 else "active"

        # INSERT PROJECT
        sql.append(f"""
        INSERT INTO projects (id, title, staff_id, status, percentage, start_date, due_date)
        VALUES ({project_id}, '{esc(proj_title)}', {staff_id}, '{proj_status}', {proj_pct}, '{proj_start}', '{proj_end}');
        """)

        for ph_idx, ph in enumerate(phase_rows):
            phase_title = random.choice(PHASE_NAMES)

            sql.append(f"""
            INSERT INTO phases (id, project_id, title)
            VALUES ({phase_id}, {project_id}, '{esc(phase_title)}');
            """)

            current_phase = phase_id
            phase_id += 1

            for t in ph["tasks"]:

                sql.append(f"""
                INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id)
                VALUES ({task_id}, '{esc(t['title'])}', {t['user']}, '{t['status']}', '{t['priority']}', {t['pct']}, {t['score']}, {project_id}, {current_phase});
                """)

                parent_id = task_id
                task_id += 1

                for s in t["subs"]:
                    sub_title = f"Sub - {random.choice(TASK_VERBS)}"

                    sql.append(f"""
                    INSERT INTO tasks (id, title, assigned_user_id, status, priority, percentage, score, project_id, phase_id, parent_id)
                    VALUES ({task_id}, '{esc(sub_title)}', {s['user']}, '{s['status']}', '{random.choice(PRIORITIES)}', {s['pct']}, {s['score']}, {project_id}, {current_phase}, {parent_id});
                    """)

                    task_id += 1

        project_id += 1

    return "\n".join(sql)

# ─────────────────────────────────────────────
# MAIN
# ─────────────────────────────────────────────
def main():
    print("🚀 Generating seed data...")

    # You can change this number easily here
    NUM_PROJECTS = 20

    sql = generate(NUM_PROJECTS)

    output_path = Path(__file__).parent / "seed_projects.sql"
    output_path.write_text(sql, encoding="utf-8")

    print("✅ Seeder generated successfully")
    print(f"📄 File saved at: {output_path}")
    print(f"👥 Teams detected: {TEAMS}")


if __name__ == "__main__":
    main()