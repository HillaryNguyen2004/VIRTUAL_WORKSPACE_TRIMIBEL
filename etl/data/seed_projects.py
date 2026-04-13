import argparse
import math
import random
import sys
from datetime import datetime, timedelta
from pathlib import Path

import pandas as pd
from sqlalchemy import create_engine

# Add parent directory to path to import config
sys.path.insert(0, str(Path(__file__).parent.parent))

from config import MYSQL_CONFIG

engine = create_engine(
    f"mysql+pymysql://{MYSQL_CONFIG['user']}:{MYSQL_CONFIG['password']}@{MYSQL_CONFIG['host']}/{MYSQL_CONFIG['database']}"
)

DEFAULT_NUM_PROJECTS = 300
START_DATE = datetime(2018, 1, 1)
END_DATE = datetime(2026, 4, 30)

TASK_VERBS = [
    "Implement", "Design", "Review", "Test", "Refactor",
    "Document", "Analyze", "Deploy", "Fix", "Optimize",
    "Build", "Integrate", "Migrate", "Validate", "Research",
]
TASK_NOUNS = [
    "API", "UI", "database", "module", "report", "dashboard",
    "authentication", "notification", "export", "import",
    "workflow", "pipeline", "schema", "endpoint", "service",
]
PHASE_NAMES = [
    "Planning", "Design", "Development", "Testing",
    "Deployment", "Review", "Maintenance", "Research",
]
PROJ_ADJ = [
    "Smart", "Advanced", "Integrated", "Automated", "Cloud",
    "Realtime", "Scalable", "Unified", "Intelligent", "Digital",
]
PROJ_NOUN = [
    "Management System", "Analytics Platform", "Dashboard",
    "Monitoring Tool", "Reporting Suite", "Data Pipeline",
    "Tracking System", "Portal", "Gateway", "Processor",
]
PRIORITIES = ["low", "normal", "high", "urgent"]
LEAF_PCTS = [0, 0, 25, 25, 50, 50, 75, 100, 100, 100]


def esc(value: str) -> str:
    return value.replace("'", "\\'")


def pct_avg(values: list[int]) -> int:
    return math.ceil(sum(values) / len(values)) if values else 0


def status_from_pct(pct: int) -> str:
    if pct == 0:
        return "pending"
    if pct == 100:
        return "completed"
    return "in_progress"


def rand_date_dt(start: datetime, end: datetime) -> datetime:
    delta = (end - start).days
    return start + timedelta(days=random.randint(0, max(1, delta)))


def build_teams() -> dict[int, list[int]]:
    users = pd.read_sql(
        """
        SELECT id, department_id, team_leader_id
        FROM users
        ORDER BY id
        """,
        engine,
    )

    teams: dict[int, list[int]] = {}

    # Staff leaders: users without team leader and with a department.
    for _, row in users.iterrows():
        if pd.isna(row["team_leader_id"]) and pd.notna(row["department_id"]):
            leader_id = int(row["id"])
            teams[leader_id] = [leader_id]

    # Assign regular users under their leader.
    for _, row in users.iterrows():
        if pd.notna(row["team_leader_id"]):
            leader_id = int(row["team_leader_id"])
            if leader_id not in teams:
                teams[leader_id] = [leader_id]
            teams[leader_id].append(int(row["id"]))

    return teams


def generate_projects_sql(*, num_projects: int, teams: dict[int, list[int]]) -> str:
    if not teams:
        raise RuntimeError("No teams found in users table.")

    sql: list[str] = []
    sql.append("SET FOREIGN_KEY_CHECKS=0;")
    sql.append("TRUNCATE TABLE task_user;")
    sql.append("TRUNCATE TABLE tasks;")
    sql.append("TRUNCATE TABLE phases;")
    sql.append("TRUNCATE TABLE projects;")
    sql.append("SET FOREIGN_KEY_CHECKS=1;")
    sql.append("")
    sql.append("-- PROJECTS, PHASES, TASKS")

    perf_pcts = {
        "high_performer": [75, 100, 100, 100, 100, 100],
        "average": [25, 50, 75, 100, 100],
        "low_performer": [0, 0, 25, 25, 50, 75],
    }
    profile_cycle = [
        "high_performer",
        "average",
        "low_performer",
        "burnout",
        "inconsistent",
    ]
    user_profiles = {
        uid: profile_cycle[(uid - 1) % len(profile_cycle)]
        for team in teams.values()
        for uid in team
    }

    def get_task_outcome(uid: int, t_date) -> tuple[int, int]:
        prof = user_profiles.get(uid, "average")
        eff_prof = prof
        total_days = max((END_DATE.date() - START_DATE.date()).days, 1)
        elapsed = max((t_date - START_DATE.date()).days, 0)
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

        pct = random.choice(perf_pcts[eff_prof])

        score = 0
        if pct == 100:
            if eff_prof == "high_performer":
                score = random.randint(80, 100)
            elif eff_prof == "average":
                score = random.randint(50, 80)
            else:
                score = random.randint(10, 50)
        return pct, score

    project_id = 1
    phase_id = 1
    task_id = 1

    num_teams = len(teams)
    projs_per_team = max(1, num_projects // num_teams)

    for staff_id, team in teams.items():
        total_team_days = (END_DATE - START_DATE).days
        avg_proj_days = max(10, total_team_days // projs_per_team)
        current_proj_start = START_DATE

        for p_idx in range(projs_per_team):
            if current_proj_start >= END_DATE:
                break

            proj_end = current_proj_start + timedelta(days=avg_proj_days)
            if p_idx == projs_per_team - 1 or proj_end > END_DATE:
                proj_end = END_DATE

            proj_title = f"{random.choice(PROJ_ADJ)} {random.choice(PROJ_NOUN)} {project_id}"

            num_phases = random.randint(2, 4)
            phase_dur = max(1, (proj_end - current_proj_start).days // num_phases)
            phase_pcts: list[int] = []
            phase_rows: list[dict] = []

            for ph in range(num_phases):
                ph_start = current_proj_start + timedelta(days=ph * phase_dur)
                ph_end = ph_start + timedelta(days=phase_dur)
                if ph == num_phases - 1:
                    ph_end = proj_end
                ph_title = f"{random.choice(PHASE_NAMES)} Phase {ph + 1}"

                task_pcts: list[int] = []
                task_rows: list[dict] = []

                for t_user in team:
                    curr_task_start = ph_start

                    while curr_task_start < ph_end:
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

                        sub_pcts: list[int] = []
                        sub_rows: list[dict] = []
                        for _ in range(random.randint(0, 2)):
                            sp, s_score = get_task_outcome(t_user, curr_task_start.date())
                            ss = status_from_pct(sp)
                            sub_pcts.append(sp)
                            sub_rows.append(
                                {
                                    "pct": sp,
                                    "status": ss,
                                    "score": s_score,
                                    "user": t_user,
                                    "est": round(random.uniform(1.0, 8.0), 1),
                                    "start": curr_task_start,
                                    "end": t_end,
                                }
                            )

                        if sub_pcts:
                            t_pct = pct_avg(sub_pcts)
                            _, t_score = get_task_outcome(t_user, curr_task_start.date()) if t_pct == 100 else (0, 0)
                        else:
                            t_pct, t_score = get_task_outcome(t_user, curr_task_start.date())

                        t_status = status_from_pct(t_pct)
                        task_pcts.append(t_pct)
                        task_rows.append(
                            {
                                "pct": t_pct,
                                "status": t_status,
                                "score": t_score if t_status == "completed" else 0,
                                "user": t_user,
                                "est": est,
                                "title": t_title,
                                "start": curr_task_start,
                                "end": t_end,
                                "subtasks": sub_rows,
                            }
                        )

                        curr_task_start = t_end + timedelta(days=1)

                ph_pct = pct_avg(task_pcts) if task_pcts else 0
                phase_pcts.append(ph_pct)
                phase_rows.append(
                    {
                        "title": ph_title,
                        "start": ph_start,
                        "end": ph_end,
                        "pct": ph_pct,
                        "tasks": task_rows,
                    }
                )

            proj_pct = pct_avg(phase_pcts) if phase_pcts else 0
            proj_status = "inactive" if proj_pct == 100 else "active"

            sql.append(
                f"INSERT INTO projects (id,title,description,staff_id,status,percentage,start_date,due_date,created_at,updated_at) VALUES "
                f"({project_id},'{esc(proj_title)}','Auto-generated.',{staff_id},'{proj_status}',{proj_pct}," 
                f"'{current_proj_start.date()}','{proj_end.date()}',NOW(),NOW());"
            )

            for ph_row in phase_rows:
                sql.append(
                    f"INSERT INTO phases (id,project_id,title,start_date,due_date,created_at,updated_at) VALUES "
                    f"({phase_id},{project_id},'{esc(ph_row['title'])}','{ph_row['start'].date()}'," 
                    f"'{ph_row['end'].date()}',NOW(),NOW());"
                )
                cur_phase = phase_id
                phase_id += 1

                for t_row in ph_row["tasks"]:
                    sql.append(
                        f"INSERT INTO tasks (id,title,assigned_user_id,status,priority,start_date,due_date,active,percentage,estimated_time,score,project_id,phase_id,parent_id,created_at,updated_at) VALUES "
                        f"({task_id},'{esc(t_row['title'])}',{t_row['user']},'{t_row['status']}'," 
                        f"'{random.choice(PRIORITIES)}','{t_row['start'].date()}','{t_row['end'].date()}'," 
                        f"1,{t_row['pct']},{t_row['est']},{t_row['score']},{project_id},{cur_phase},NULL,NOW(),NOW());"
                    )
                    sql.append(
                        f"INSERT IGNORE INTO task_user (task_id,user_id,created_at,updated_at) VALUES "
                        f"({task_id},{t_row['user']},NOW(),NOW());"
                    )
                    parent_id = task_id
                    task_id += 1

                    for s_row in t_row["subtasks"]:
                        s_title = f"Sub: {random.choice(TASK_VERBS)} {random.choice(TASK_NOUNS)}"
                        sql.append(
                            f"INSERT INTO tasks (id,title,assigned_user_id,status,priority,start_date,due_date,active,percentage,estimated_time,score,project_id,phase_id,parent_id,created_at,updated_at) VALUES "
                            f"({task_id},'{esc(s_title)}',{s_row['user']},'{s_row['status']}'," 
                            f"'{random.choice(PRIORITIES)}','{s_row['start'].date()}','{s_row['end'].date()}'," 
                            f"1,{s_row['pct']},{s_row['est']},{s_row['score']},{project_id},{cur_phase},{parent_id},NOW(),NOW());"
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


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", default="seed_projects.sql")
    parser.add_argument("--seed", type=int, default=42)
    parser.add_argument("--num-projects", type=int, default=DEFAULT_NUM_PROJECTS)
    args = parser.parse_args()

    random.seed(args.seed)

    teams = build_teams()
    if not teams:
        raise SystemExit("No teams found. Make sure users are already seeded.")

    output_path = Path(__file__).parent / args.output
    output_path.parent.mkdir(parents=True, exist_ok=True)

    sql_text = generate_projects_sql(num_projects=args.num_projects, teams=teams)
    output_path.write_text(sql_text, encoding="utf-8")

    print(f"Generated {args.num_projects} projects")
    print(f"Teams detected: {len(teams)}")
    print(f"SQL file: {output_path}")
    print("Import with: mysql -u root -p manage_user < etl/data/seed_projects.sql")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())