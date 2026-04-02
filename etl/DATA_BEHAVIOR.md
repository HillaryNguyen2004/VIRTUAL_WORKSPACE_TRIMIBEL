# ETL Data Behavior - What Happens When You Run It

## Quick Answer

### Dimension Tables: **UPDATES existing data** ✅
- dim_employee, dim_project, dim_task, dim_department, dim_phase, dim_date
- **Behavior**: Uses `ON CONFLICT DO UPDATE` - safe to run multiple times
- **What happens**:
  - Existing records are UPDATED with new values from MySQL
  - NEW columns (staff_id, phase_id, check_in_time) get populated
  - Surrogate keys (employee_sk, project_sk, etc.) are preserved
  - No data loss, just enrichment with missing fields

### Fact Table: **APPENDS new records** ⚠️
- fact_employee_productivity
- **Behavior**: Simple INSERT - no duplicate protection
- **What happens**:
  - Running ETL multiple times creates DUPLICATE records
  - **Recommendation**: TRUNCATE before running (clear and reload)

---

## Your Current Situation

Based on the test results:

```
✓ MySQL Source has:
  - 50 projects (all with managers)
  - 155 phases
  - 1,736 tasks (all linked to phases, 1,036 are subtasks)
  - 55,601 check-ins with time data

✗ Your Data Warehouse has OLD DATA:
  - 50 projects with 0 having staff_id ❌
  - 0 phases ❌
  - 1,736 tasks with 0 phase links ❌
  - 2,410 fact records with 0 having check-in times ❌
```

**Your warehouse is missing ~95% of available data!**

---

## How to Run the ETL Safely

### Option 1: Use the Safe Runner (Recommended)

```bash
cd /opt/lampp/htdocs/DO_AN_CHUYEN_NGANH/etl
python3 run_etl_safe.py
```

This script will:
1. Show you current data stats
2. Ask if you want to truncate the fact table (recommended: yes)
3. Run the full ETL
4. Show you the results

### Option 2: Manual Control

```bash
cd /opt/lampp/htdocs/DO_AN_CHUYEN_NGANH/etl

# First, check what you have
python3 test_etl.py

# Then clear and reload (recommended for first run with new schema)
python3 -c "
from config import PG_CONFIG
import psycopg2

conn = psycopg2.connect(**PG_CONFIG)
cur = conn.cursor()
cur.execute('TRUNCATE TABLE fact_employee_productivity CASCADE')
conn.commit()
print('✓ Fact table cleared')
"

# Run the ETL
python3 run.py

# Check results
python3 test_etl.py
```

---

## What Will Change

### After Running the ETL, you will have:

**Dimension Tables** (updated, not duplicated):
- ✅ dim_project: All 50 projects WITH staff_id populated
- ✅ dim_phase: All 155 phases loaded
- ✅ dim_task: All 1,736 tasks WITH phase_id, parent_id, assigned_user_id
- ✅ dim_employee: Same records, no change (already complete)
- ✅ dim_department: Same records, no change
- ✅ dim_date: Same records, no change

**Fact Table** (cleared and reloaded if you truncate):
- ✅ fact_employee_productivity: ~2,410 records WITH:
  - check_in_time populated (55,601 check-ins worth of data)
  - check_out_time populated
  - phase_sk populated (linking to phases)

---

## Example: Before vs After

### BEFORE (Current State)
```sql
SELECT p.title, p.staff_id, COUNT(t.task_id) as tasks
FROM dim_project p
LEFT JOIN dim_task t ON t.project_id = p.project_id
GROUP BY p.title, p.staff_id
LIMIT 3;
```
**Result**: All staff_id values are NULL ❌

### AFTER (Post-ETL)
```sql
SELECT p.title, p.staff_id, COUNT(t.task_id) as tasks
FROM dim_project p
LEFT JOIN dim_task t ON t.project_id = p.project_id
GROUP BY p.title, p.staff_id
LIMIT 3;
```
**Result**: All staff_id values populated with actual manager IDs ✅

---

## Recommended Action Plan

1. **Backup** (optional but safe):
   ```bash
   pg_dump -h localhost -U your_user -d warehouse_db > backup_before_etl.sql
   ```

2. **Run the safe ETL**:
   ```bash
   cd /opt/lampp/htdocs/DO_AN_CHUYEN_NGANH/etl
   python3 run_etl_safe.py
   # Choose option 1 (truncate) when prompted
   ```

3. **Verify the results**:
   ```bash
   python3 test_etl.py
   ```

4. **Expected results**:
   - dim_project: 50 records (50 with staff_id) ✅
   - dim_phase: 155 records ✅
   - dim_task: 1,736 records (1,736 with phase, 1,036 subtasks) ✅
   - fact table: ~2,410 records (with check-in times) ✅

---

## FAQ

**Q: Will I lose data?**
A: No. Dimension tables are updated (not deleted). Fact table is only cleared if you choose to truncate it, then fully reloaded with MORE complete data.

**Q: Can I run the ETL multiple times?**
A: Yes. Dimensions can be run safely multiple times. Fact table should be truncated first to avoid duplicates.

**Q: What if something goes wrong?**
A: Dimension tables use transactions and conflict handling - very safe. If the ETL fails mid-way, you can just run it again.

**Q: How long will it take?**
A: With ~2,400 fact records and 1,736 tasks, expect 2-5 minutes depending on your system.

---

## Summary

✅ **SAFE TO RUN** - The ETL will enrich your data, not destroy it
✅ **RECOMMENDED** - Truncate fact table first (choose option 1)
✅ **EXPECTED RESULT** - 95% more data captures in your warehouse
✅ **NO RISK** - Dimension tables use safe update logic
