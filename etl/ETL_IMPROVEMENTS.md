# ETL Pipeline Improvements

## Summary
Fixed the ETL pipeline to capture all available data from the full_seed.sql file. The data warehouse was not fully utilizing the rich dataset available in the source MySQL database.

## What Was Missing

### 1. **New Dimension Table: `dim_phase`**
   - **Before**: Phase information was completely ignored
   - **After**: New dimension table created with:
     - `phase_sk` (surrogate key)
     - `phase_id` (business key)
     - `project_id` (relationship to project)
     - `title` (phase name)
     - `start_date`, `due_date`

### 2. **Enhanced `dim_project` Table**
   - **Added Fields**:
     - `description` - Project description for context
     - `staff_id` - Project manager/owner ID (critical for project ownership tracking)

### 3. **Enhanced `dim_task` Table**
   - **Added Fields**:
     - `phase_id` - Links tasks to project phases
     - `parent_id` - Enables task hierarchy (parent/subtask relationships)
     - `start_date`, `due_date` - Task scheduling dates
     - `active` - Whether task is currently active
     - `assigned_user_id` - Who the task is assigned to
     - `status` - Current task status (for historical tracking)

### 4. **Enhanced `fact_employee_productivity` Table**
   - **Added Fields**:
     - `check_in_time` - Exact check-in time (not just working hours)
     - `check_out_time` - Exact check-out time
     - `phase_sk` - Link to project phase dimension

## Files Modified

1. **`etl_pipeline.py`**
   - Added `load_dim_phase()` function
   - Enhanced `load_dim_project()` to extract description and staff_id
   - Enhanced `load_dim_task()` to extract all task attributes
   - Enhanced `load_fact()` to:
     - Extract check_in_time and check_out_time
     - Extract phase_id from tasks
     - Link to dim_phase for phase_sk
   - Updated `run_full_etl()` to include phase loading

2. **`add_missing_columns.sql`** (new file)
   - SQL migration script to add all missing columns
   - Creates dim_phase table
   - Adds indexes for performance

## Benefits of These Changes

### Better Analytics Capabilities
- **Project Management**: Now can track project ownership (staff_id) and detailed project descriptions
- **Phase Tracking**: Can analyze productivity by project phase
- **Task Hierarchies**: Can understand parent/child task relationships
- **Detailed Time Tracking**: Check-in/check-out times enable precise attendance analysis
- **Task Assignment**: Can track who tasks are assigned to over time

### Improved Reporting
- Performance by project phase
- Task completion rates by parent task
- Project manager effectiveness metrics
- Detailed attendance patterns (not just hours worked)
- Task lifecycle analysis

## How to Use

### First Time Setup (Already Done)
```bash
# 1. Apply schema changes
cd /opt/lampp/htdocs/DO_AN_CHUYEN_NGANH/etl
python3 -c "exec(open('apply_schema.py').read())"

# Already executed - schema is updated!
```

### Running the Enhanced ETL
```bash
cd /opt/lampp/htdocs/DO_AN_CHUYEN_NGANH/etl
python3 run.py
```

### Clearing Old Data (if needed)
```python
from etl.config import PG_CONFIG
import psycopg2

conn = psycopg2.connect(**PG_CONFIG)
cur = conn.cursor()

# Truncate fact table (preserves structure)
cur.execute("TRUNCATE TABLE fact_employee_productivity CASCADE")
conn.commit()

# Run ETL
from etl_pipeline import run_full_etl
run_full_etl()
```

## Data Completeness Comparison

### Before
| Table | Fields Extracted | Fields Available | Coverage |
|-------|-----------------|------------------|----------|
| dim_project | 6 | 8 | 75% |
| dim_task | 5 | 12 | 42% |
| dim_phase | 0 | 5 | 0% |
| fact_table | 16 | 19 | 84% |

### After
| Table | Fields Extracted | Fields Available | Coverage |
|-------|-----------------|------------------|----------|
| dim_project | 8 | 8 | 100% |
| dim_task | 12 | 12 | 100% |
| dim_phase | 5 | 5 | 100% |
| fact_table | 19 | 19 | 100% |

## New Query Capabilities

### Example: Performance by Project Phase
```sql
SELECT
    p.title AS project_name,
    ph.title AS phase_name,
    AVG(f.productivity_score) AS avg_productivity,
    COUNT(*) AS records
FROM fact_employee_productivity f
JOIN dim_phase ph ON f.phase_sk = ph.phase_sk
JOIN dim_project p ON ph.project_id = p.project_id
GROUP BY p.title, ph.title
ORDER BY avg_productivity DESC;
```

### Example: Task Hierarchy Analysis
```sql
-- Parent tasks with their subtasks
SELECT
    parent.title AS parent_task,
    child.title AS subtask,
    child.status,
    child.percentage
FROM dim_task child
JOIN dim_task parent ON child.parent_id = parent.task_id
WHERE child.parent_id IS NOT NULL;
```

### Example: Project Manager Performance
```sql
SELECT
    e.name AS project_manager,
    p.title AS project,
    AVG(f.productivity_score) AS team_avg_productivity
FROM dim_project p
JOIN dim_employee e ON p.staff_id = e.user_id AND e.is_current = TRUE
JOIN fact_employee_productivity f ON f.project_sk = p.project_sk
GROUP BY e.name, p.title;
```

### Example: Check-in/Check-out Patterns
```sql
-- Average check-in times by employee
SELECT
    e.name,
    AVG(EXTRACT(HOUR FROM f.check_in_time) * 60 + EXTRACT(MINUTE FROM f.check_in_time)) AS avg_checkin_minutes,
    COUNT(*) AS days_worked
FROM fact_employee_productivity f
JOIN dim_employee e ON f.employee_sk = e.employee_sk
WHERE f.check_in_time IS NOT NULL
GROUP BY e.name
ORDER BY avg_checkin_minutes;
```

## Next Steps

1. **Update Dashboards**: Modify existing dashboards to leverage the new fields
2. **Create New Reports**: Build reports for:
   - Phase-based productivity
   - Task hierarchy visualization
   - Project manager scorecards
   - Detailed attendance reports

3. **Data Quality**: Monitor the new fields to ensure data quality:
   ```sql
   -- Check phase coverage
   SELECT COUNT(*) AS tasks_with_phase FROM dim_task WHERE phase_id IS NOT NULL;

   -- Check task hierarchy
   SELECT COUNT(*) AS subtasks FROM dim_task WHERE parent_id IS NOT NULL;
   ```

## Notes
- The ETL is now idempotent - safe to run multiple times
- Schema uses `ADD COLUMN IF NOT EXISTS` for safety
- Foreign key constraints ensure referential integrity
- Indexes added for optimal query performance
