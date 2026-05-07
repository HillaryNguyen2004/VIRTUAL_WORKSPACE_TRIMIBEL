-- ════════════════════════════════════════════════════════════
-- Debug leave_type NULL and overdue_task_count = 0
-- ════════════════════════════════════════════════════════════

\echo '1️⃣  CHECK: Are there ANY approved day_off_requests?'
SELECT 
  COUNT(*) as total_dayoff_records,
  COUNT(DISTINCT user_id) as users_with_dayoff,
  COUNT(DISTINCT date) as dates_with_dayoff,
  COUNT(DISTINCT status) as unique_statuses
FROM day_off_requests
WHERE status = 'APPROVED';

\echo '\n2️⃣  CHECK: What leave_types exist?'
SELECT 
  leave_type,
  COUNT(*) as count,
  COUNT(DISTINCT user_id) as users,
  COUNT(DISTINCT date) as dates
FROM day_off_requests
WHERE status = 'APPROVED'
GROUP BY leave_type
ORDER BY count DESC;

\echo '\n3️⃣  CHECK: Sample day_off_requests data'
SELECT 
  user_id,
  date,
  leave_type,
  half_day_period,
  status
FROM day_off_requests
WHERE status = 'APPROVED'
LIMIT 10;

\echo '\n4️⃣  CHECK: Are there tasks with status != "completed" and due_date < today?'
SELECT 
  COUNT(*) as potentially_overdue,
  COUNT(DISTINCT assigned_user_id) as employees,
  MIN(due_date) as earliest_overdue_date,
  MAX(due_date) as latest_overdue_date
FROM tasks
WHERE status != 'completed' 
  AND due_date IS NOT NULL
  AND due_date < CURDATE();

\echo '\n5️⃣  CHECK: Sample potentially overdue tasks'
SELECT 
  id as task_id,
  assigned_user_id,
  title,
  status,
  due_date,
  DATE_SUB(CURDATE(), INTERVAL 0 DAY) as today,
  DATEDIFF(CURDATE(), due_date) as days_overdue
FROM tasks
WHERE status != 'completed'
  AND due_date IS NOT NULL
  AND due_date < CURDATE()
LIMIT 10;

\echo '\n6️⃣  CHECK: Current fact_employee_productivity data'
SELECT 
  COUNT(*) as total_rows,
  SUM(CASE WHEN leave_type IS NOT NULL THEN 1 ELSE 0 END) as rows_with_leave_type,
  SUM(CASE WHEN overdue_task_count > 0 THEN 1 ELSE 0 END) as rows_with_overdue,
  MAX(overdue_task_count) as max_overdue_count,
  MIN(overdue_task_count) as min_overdue_count,
  AVG(overdue_task_count) as avg_overdue_count,
  COUNT(DISTINCT leave_type) as unique_leave_types
FROM fact_employee_productivity;

\echo '\n✅ Diagnostics complete'
