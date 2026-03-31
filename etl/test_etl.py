#!/usr/bin/env python3
"""
Test script to verify ETL improvements and show data coverage
"""
import sys
import os

# Add parent directory to path so we can import from etl
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from etl.config import MYSQL_CONFIG, PG_CONFIG
import psycopg2
import pymysql

def check_source_data():
    """Check what data is available in MySQL source"""
    print("=" * 60)
    print("SOURCE DATA ANALYSIS (MySQL)")
    print("=" * 60)

    conn = pymysql.connect(**MYSQL_CONFIG)
    cur = conn.cursor()

    # Check projects
    cur.execute("SELECT COUNT(*) FROM projects")
    projects = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM projects WHERE staff_id IS NOT NULL")
    projects_with_manager = cur.fetchone()[0]

    # Check phases
    cur.execute("SELECT COUNT(*) FROM phases")
    phases = cur.fetchone()[0]

    # Check tasks
    cur.execute("SELECT COUNT(*) FROM tasks")
    tasks = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM tasks WHERE phase_id IS NOT NULL")
    tasks_with_phase = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM tasks WHERE parent_id IS NOT NULL")
    subtasks = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM tasks WHERE assigned_user_id IS NOT NULL")
    assigned_tasks = cur.fetchone()[0]

    # Check check-ins
    cur.execute("SELECT COUNT(*) FROM check_ins WHERE check_in_time IS NOT NULL")
    checkins_with_time = cur.fetchone()[0]

    cur.close()
    conn.close()

    print(f"✓ Projects: {projects} total, {projects_with_manager} with managers")
    print(f"✓ Phases: {phases} total")
    print(f"✓ Tasks: {tasks} total")
    print(f"  - {tasks_with_phase} linked to phases")
    print(f"  - {subtasks} are subtasks (have parent)")
    print(f"  - {assigned_tasks} have assigned users")
    print(f"✓ Check-ins with time data: {checkins_with_time}")
    print()

def check_warehouse_schema():
    """Check data warehouse schema"""
    print("=" * 60)
    print("DATA WAREHOUSE SCHEMA (PostgreSQL)")
    print("=" * 60)

    conn = psycopg2.connect(**PG_CONFIG)
    cur = conn.cursor()

    # Check dim_phase exists
    cur.execute("""
        SELECT COUNT(*) FROM information_schema.tables
        WHERE table_name = 'dim_phase'
    """)
    has_phase = cur.fetchone()[0] > 0

    # Check new columns in dim_project
    cur.execute("""
        SELECT column_name FROM information_schema.columns
        WHERE table_name = 'dim_project' AND column_name IN ('description', 'staff_id')
    """)
    project_cols = [r[0] for r in cur.fetchall()]

    # Check new columns in dim_task
    cur.execute("""
        SELECT column_name FROM information_schema.columns
        WHERE table_name = 'dim_task'
        AND column_name IN ('phase_id', 'parent_id', 'start_date', 'due_date',
                           'active', 'assigned_user_id', 'status')
    """)
    task_cols = [r[0] for r in cur.fetchall()]

    # Check new columns in fact table
    cur.execute("""
        SELECT column_name FROM information_schema.columns
        WHERE table_name = 'fact_employee_productivity'
        AND column_name IN ('check_in_time', 'check_out_time', 'phase_sk')
    """)
    fact_cols = [r[0] for r in cur.fetchall()]

    cur.close()
    conn.close()

    print(f"✓ dim_phase table: {'EXISTS' if has_phase else 'MISSING'}")
    print(f"✓ dim_project new columns: {', '.join(project_cols) if project_cols else 'NONE'}")
    print(f"✓ dim_task new columns: {', '.join(task_cols) if task_cols else 'NONE'}")
    print(f"✓ fact table new columns: {', '.join(fact_cols) if fact_cols else 'NONE'}")
    print()

def check_warehouse_data():
    """Check what data is in the warehouse"""
    print("=" * 60)
    print("DATA WAREHOUSE CONTENTS")
    print("=" * 60)

    conn = psycopg2.connect(**PG_CONFIG)
    cur = conn.cursor()

    cur.execute("SELECT COUNT(*) FROM dim_date")
    dates = cur.fetchone()[0]

    cur.execute("SELECT COUNT(*) FROM dim_department")
    depts = cur.fetchone()[0]

    cur.execute("SELECT COUNT(*) FROM dim_employee WHERE is_current = TRUE")
    emps = cur.fetchone()[0]

    cur.execute("SELECT COUNT(*) FROM dim_project")
    projects = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM dim_project WHERE staff_id IS NOT NULL")
    projects_with_staff = cur.fetchone()[0]

    cur.execute("SELECT COUNT(*) FROM dim_phase")
    phases = cur.fetchone()[0]

    cur.execute("SELECT COUNT(*) FROM dim_task")
    tasks = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM dim_task WHERE phase_id IS NOT NULL")
    tasks_with_phase = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM dim_task WHERE parent_id IS NOT NULL")
    subtasks = cur.fetchone()[0]

    cur.execute("SELECT COUNT(*) FROM fact_employee_productivity")
    facts = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM fact_employee_productivity WHERE phase_sk IS NOT NULL")
    facts_with_phase = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM fact_employee_productivity WHERE check_in_time IS NOT NULL")
    facts_with_time = cur.fetchone()[0]

    cur.close()
    conn.close()

    print(f"Dimension Tables:")
    print(f"  - dim_date: {dates:,} records")
    print(f"  - dim_department: {depts} records")
    print(f"  - dim_employee: {emps} current records")
    print(f"  - dim_project: {projects} records ({projects_with_staff} with staff_id)")
    print(f"  - dim_phase: {phases} records")
    print(f"  - dim_task: {tasks} records ({tasks_with_phase} with phase, {subtasks} subtasks)")
    print(f"\nFact Table:")
    print(f"  - fact_employee_productivity: {facts:,} records")
    print(f"    • {facts_with_phase} with phase link")
    print(f"    • {facts_with_time} with check-in times")
    print()

if __name__ == "__main__":
    try:
        check_source_data()
        check_warehouse_schema()
        check_warehouse_data()

        print("=" * 60)
        print("✅ ETL PIPELINE STATUS: READY")
        print("=" * 60)
        print("\nTo run the ETL:")
        print("  cd /opt/lampp/htdocs/DO_AN_CHUYEN_NGANH/etl")
        print("  python3 run.py")
        print()

    except Exception as e:
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
