-- ════════════════════════════════════════════════════════════
-- Quick ETL v2 Health Check
-- Run this quickly to see if ETL worked
-- ════════════════════════════════════════════════════════════

SELECT
  '📊 QUICK HEALTH CHECK' as check_type,
  COUNT(*) as fact_rows,
  COUNT(DISTINCT employee_sk) as unique_employees,
  COUNT(DISTINCT date_sk) as unique_dates,
  MIN(f.employee_sk) as min_emp_sk,
  MAX(f.employee_sk) as max_emp_sk,
  ROUND(AVG(productivity_score), 2) as avg_productivity
FROM fact_employee_productivity f;

-- Are new columns populated?
SELECT
  '✅ NEW COLUMNS' as check_type,
  CASE WHEN COUNT(CASE WHEN checkin_hour IS NOT NULL THEN 1 END) > 0 
       THEN '✓ checkin_hour' ELSE '✗ checkin_hour' END,
  CASE WHEN COUNT(CASE WHEN active_task_count IS NOT NULL THEN 1 END) > 0 
       THEN '✓ active_task_count' ELSE '✗ active_task_count' END,
  CASE WHEN COUNT(CASE WHEN days_to_nearest_deadline IS NOT NULL THEN 1 END) > 0 
       THEN '✓ days_to_nearest_deadline' ELSE '✗ days_to_nearest_deadline' END,
  CASE WHEN COUNT(CASE WHEN is_holiday IS NOT NULL THEN 1 END) > 0 
       THEN '✓ is_holiday' ELSE '✗ is_holiday' END,
  CASE WHEN COUNT(CASE WHEN active_phase_title IS NOT NULL THEN 1 END) > 0 
       THEN '✓ active_phase_title' ELSE '✗ active_phase_title' END
FROM fact_employee_productivity;
