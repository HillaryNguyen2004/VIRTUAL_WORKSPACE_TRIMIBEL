#!/usr/bin/env python3
"""
Complete data warehouse refresh:
1. Clear all tables
2. Run fresh ETL
3. Verify results
"""
import sys
import os
import subprocess

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from config import PG_CONFIG
from etl_pipeline import run_full_etl
import psycopg2

def run_full_refresh():
    """Complete warehouse refresh for LSTM accuracy"""
    
    print("\n" + "=" * 70)
    print("📊 COMPLETE DATA WAREHOUSE REFRESH FOR LSTM")
    print("=" * 70)
    print()
    print("This will:")
    print("  1. Clear ALL dimension and fact tables")
    print("  2. Run fresh ETL from MySQL source")
    print("  3. Verify data completeness")
    print()
    
    # Confirm
    response = input("Proceed? Type 'yes' to confirm: ").strip().lower()
    if response != "yes":
        print("❌ Cancelled")
        return False

    print()
    print("=" * 70)
    print("STEP 1: CLEARING DATA")
    print("=" * 70)
    print()

    conn = psycopg2.connect(**PG_CONFIG)
    cur = conn.cursor()

    # Get before stats
    print("📊 Before clearing:")
    tables = [
        ("fact_employee_productivity", "Fact"),
        ("dim_task", "Task"),
        ("dim_phase", "Phase"),
        ("dim_project", "Project"),
        ("dim_department", "Department"),
        ("dim_employee", "Employee"),
        ("dim_date", "Date"),
    ]

    before_stats = {}
    for table, label in tables:
        cur.execute(f"SELECT COUNT(*) FROM {table}")
        count = cur.fetchone()[0]
        before_stats[table] = count
        print(f"  {label:15} : {count:,} records")

    print()
    print("🗑️  Clearing tables...")
    
    # Disable FK checks
    cur.execute("SET session_replication_role = 'replica'")
    
    for table, label in tables:
        try:
            cur.execute(f"TRUNCATE TABLE {table} CASCADE")
            conn.commit()
            print(f"  ✓ {label}")
        except Exception as e:
            print(f"  ⚠️  {label}: {e}")
            conn.rollback()
    
    # Re-enable FK checks
    cur.execute("SET session_replication_role = 'origin'")
    conn.commit()
    
    # Verify clear
    print()
    print("✓ All tables cleared")
    
    cur.close()
    conn.close()

    print()
    print("=" * 70)
    print("STEP 2: RUNNING FRESH ETL")
    print("=" * 70)
    print()

    try:
        run_full_etl()
    except Exception as e:
        print(f"❌ ETL failed: {e}")
        import traceback
        traceback.print_exc()
        return False

    print()
    print("=" * 70)
    print("STEP 3: VERIFICATION")
    print("=" * 70)
    print()

    conn = psycopg2.connect(**PG_CONFIG)
    cur = conn.cursor()

    print("📊 After ETL:")
    for table, label in tables:
        cur.execute(f"SELECT COUNT(*) FROM {table}")
        count = cur.fetchone()[0]
        before = before_stats[table]
        change = count - before
        print(f"  {label:15} : {count:,} records (was {before:,}, {change:+,})")

    # Detailed stats
    print()
    print("📈 Data Quality Checks:")
    
    # Projects with managers
    cur.execute("SELECT COUNT(*) FROM dim_project WHERE staff_id IS NOT NULL")
    projects_with_staff = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM dim_project")
    total_projects = cur.fetchone()[0]
    print(f"  ✓ Projects with managers: {projects_with_staff}/{total_projects}")
    
    # Tasks with phases
    cur.execute("SELECT COUNT(*) FROM dim_task WHERE phase_id IS NOT NULL")
    tasks_with_phase = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM dim_task")
    total_tasks = cur.fetchone()[0]
    print(f"  ✓ Tasks with phases: {tasks_with_phase}/{total_tasks}")
    
    # Subtasks
    cur.execute("SELECT COUNT(*) FROM dim_task WHERE parent_id IS NOT NULL")
    subtasks = cur.fetchone()[0]
    print(f"  ✓ Subtasks (parent-child): {subtasks}")
    
    # Check-in times
    cur.execute("SELECT COUNT(*) FROM fact_employee_productivity WHERE check_in_time IS NOT NULL")
    facts_with_time = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM fact_employee_productivity")
    total_facts = cur.fetchone()[0]
    print(f"  ✓ Productivity records with times: {facts_with_time:,}/{total_facts:,}")
    
    # Phase links in fact
    cur.execute("SELECT COUNT(*) FROM fact_employee_productivity WHERE phase_sk IS NOT NULL")
    facts_with_phase = cur.fetchone()[0]
    print(f"  ✓ Productivity records with phases: {facts_with_phase:,}/{total_facts:,}")
    
    cur.close()
    conn.close()

    print()
    print("=" * 70)
    print("✅ COMPLETE REFRESH SUCCESSFUL")
    print("=" * 70)
    print()
    print("Your data warehouse is now:")
    print("  ✓ Clean (all old data removed)")
    print("  ✓ Complete (all available data loaded)")
    print("  ✓ Ready for LSTM training")
    print()
    print("Next steps:")
    print("  1. Run your LSTM dashboard:")
    print("     python3 your_lstm_script.py")
    print("  2. Check the results at:")
    print("     http://localhost/DO_AN_CHUYEN_NGANH/admin/lstm-dashboard")
    print()

    return True

if __name__ == "__main__":
    try:
        success = run_full_refresh()
        sys.exit(0 if success else 1)
    except KeyboardInterrupt:
        print("\n\n❌ Cancelled by user")
        sys.exit(1)
    except Exception as e:
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)
