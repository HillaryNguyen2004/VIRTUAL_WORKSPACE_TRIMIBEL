#!/usr/bin/env python3
"""
Safe ETL runner that handles fact table properly
"""
import sys
import os

# Add parent directory to path
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from etl.config import PG_CONFIG
from etl.etl_pipeline import run_full_etl
import psycopg2

def run_etl_with_cleanup():
    """
    Run ETL with proper fact table handling
    """
    print("=" * 60)
    print("RUNNING ETL PIPELINE WITH CLEANUP")
    print("=" * 60)
    print()

    # Connect to PostgreSQL
    conn = psycopg2.connect(**PG_CONFIG)
    cur = conn.cursor()

    # Check current fact table size
    cur.execute("SELECT COUNT(*) FROM fact_employee_productivity")
    old_count = cur.fetchone()[0]
    print(f"📊 Current fact table records: {old_count:,}")
    print()

    # Ask user what to do
    print("Options:")
    print("  1. TRUNCATE fact table (recommended) - Clear and reload all data")
    print("  2. APPEND to fact table - Add new records (may create duplicates)")
    print()

    choice = input("Enter choice (1 or 2): ").strip()

    if choice == "1":
        print("\n🗑️  Truncating fact table...")
        cur.execute("TRUNCATE TABLE fact_employee_productivity CASCADE")
        conn.commit()
        print("✓ Fact table cleared")
    else:
        print("\n➕ Will append to existing data")

    cur.close()
    conn.close()

    print()
    print("=" * 60)
    print("STARTING ETL PROCESS")
    print("=" * 60)
    print()

    # Run the ETL
    run_full_etl()

    # Show results
    conn = psycopg2.connect(**PG_CONFIG)
    cur = conn.cursor()

    cur.execute("SELECT COUNT(*) FROM dim_phase")
    phases = cur.fetchone()[0]

    cur.execute("SELECT COUNT(*) FROM dim_project WHERE staff_id IS NOT NULL")
    projects_with_staff = cur.fetchone()[0]

    cur.execute("SELECT COUNT(*) FROM dim_task WHERE phase_id IS NOT NULL")
    tasks_with_phase = cur.fetchone()[0]

    cur.execute("SELECT COUNT(*) FROM fact_employee_productivity")
    new_fact_count = cur.fetchone()[0]

    cur.execute("SELECT COUNT(*) FROM fact_employee_productivity WHERE check_in_time IS NOT NULL")
    facts_with_times = cur.fetchone()[0]

    cur.close()
    conn.close()

    print()
    print("=" * 60)
    print("✅ ETL COMPLETE - RESULTS")
    print("=" * 60)
    print(f"✓ Phases loaded: {phases}")
    print(f"✓ Projects with staff_id: {projects_with_staff}")
    print(f"✓ Tasks linked to phases: {tasks_with_phase}")
    print(f"✓ Fact records: {new_fact_count:,} (was {old_count:,})")
    print(f"✓ Facts with check-in times: {facts_with_times:,}")
    print()
    print("🎉 Data warehouse now fully populated with all available data!")
    print()

if __name__ == "__main__":
    try:
        run_etl_with_cleanup()
    except KeyboardInterrupt:
        print("\n\n⚠️  ETL cancelled by user")
    except Exception as e:
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
