#!/usr/bin/env python3
"""
Clear all data warehouse data and reload from scratch
Safe clearing script with proper FK order
"""
import sys
import os

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from config import PG_CONFIG
import psycopg2

def clear_warehouse():
    """
    Clear all data warehouse tables in the correct order
    (respecting foreign key constraints)
    """
    print("=" * 60)
    print("🗑️  CLEARING DATA WAREHOUSE")
    print("=" * 60)
    print()

    conn = psycopg2.connect(**PG_CONFIG)
    cur = conn.cursor()

    # Disable FK checks during truncate
    print("⏸️  Disabling foreign key constraints...")
    cur.execute("SET session_replication_role = 'replica'")

    # Truncate tables (fact table first, then dimensions)
    tables = [
        ("fact_employee_productivity", "Fact table"),
        ("dim_task", "Task dimension"),
        ("dim_phase", "Phase dimension"),
        ("dim_project", "Project dimension"),
        ("dim_department", "Department dimension"),
        ("dim_employee", "Employee dimension"),
        ("dim_date", "Date dimension"),
    ]

    for table, label in tables:
        try:
            print(f"  • Truncating {label}...", end=" ")
            cur.execute(f"TRUNCATE TABLE {table} CASCADE")
            conn.commit()
            print("✓")
        except Exception as e:
            print(f"⚠️  {e}")
            conn.rollback()

    # Re-enable FK checks
    print()
    print("▶️  Re-enabling foreign key constraints...")
    cur.execute("SET session_replication_role = 'origin'")
    conn.commit()

    # Show table sizes
    print()
    print("=" * 60)
    print("📊 TABLE SIZES AFTER CLEARING")
    print("=" * 60)

    for table, label in tables:
        cur.execute(f"SELECT COUNT(*) FROM {table}")
        count = cur.fetchone()[0]
        print(f"  {label:30} : {count:,} records")

    cur.close()
    conn.close()

    print()
    print("✅ All tables cleared and ready for fresh ETL!")
    print()

if __name__ == "__main__":
    try:
        # Confirm with user
        print()
        print("⚠️  WARNING: This will DELETE ALL data from your data warehouse!")
        print()
        response = input("Are you SURE? Type 'yes' to confirm: ").strip().lower()

        if response != "yes":
            print("❌ Cancelled - no data was deleted")
            sys.exit(0)

        print()
        clear_warehouse()

    except Exception as e:
        print(f"\n❌ Error: {e}")
        import traceback
        traceback.print_exc()
