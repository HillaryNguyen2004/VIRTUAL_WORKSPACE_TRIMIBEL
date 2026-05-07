-- ════════════════════════════════════════════════════════════
-- ETL v2 Verification Script
-- Check if new columns exist and are populated
-- ════════════════════════════════════════════════════════════

-- 1. Check table structure — new columns present?
-- ════════════════════════════════════════════════════════════
\echo '--- TABLE STRUCTURE CHECK ---'
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_name = 'fact_employee_productivity'
  AND column_name IN (
    'checkin_hour', 'checkout_hour', 'minutes_late', 'time_at_office_h',
    'active_task_count', 'high_priority_task_count',
    'days_to_nearest_deadline', 'overdue_task_count', 'total_estimated_hours',
    'is_half_day_off', 'half_day_period',
    'is_holiday', 'is_day_before_holiday', 'is_day_after_holiday',
    'active_phase_title'
  )
ORDER BY ordinal_position;

-- 2. Row count — was anything inserted?
-- ════════════════════════════════════════════════════════════
\echo '\n--- ROW COUNT ---'
SELECT COUNT(*) as total_rows FROM fact_employee_productivity;

-- 3. Sample data — first 5 rows with new columns
-- ════════════════════════════════════════════════════════════
\echo '\n--- SAMPLE DATA (first 5 rows) ---'
SELECT
  employee_sk,
  date_sk,
  hours_worked,
  checkin_hour,
  checkout_hour,
  minutes_late,
  time_at_office_h,
  active_task_count,
  high_priority_task_count,
  days_to_nearest_deadline,
  overdue_task_count,
  total_estimated_hours,
  is_half_day_off,
  half_day_period,
  is_holiday,
  is_day_before_holiday,
  is_day_after_holiday,
  active_phase_title,
  productivity_score
FROM fact_employee_productivity
LIMIT 5;

-- 4. Data completeness — null counts for new columns
-- ════════════════════════════════════════════════════════════
\echo '\n--- NULL VALUE COUNTS (new columns) ---'
SELECT
  COUNT(*) - COUNT(checkin_hour) as null_checkin_hour,
  COUNT(*) - COUNT(checkout_hour) as null_checkout_hour,
  COUNT(*) - COUNT(minutes_late) as null_minutes_late,
  COUNT(*) - COUNT(time_at_office_h) as null_time_at_office_h,
  COUNT(*) - COUNT(active_task_count) as null_active_task_count,
  COUNT(*) - COUNT(high_priority_task_count) as null_high_priority_task_count,
  COUNT(*) - COUNT(days_to_nearest_deadline) as null_days_to_nearest_deadline,
  COUNT(*) - COUNT(overdue_task_count) as null_overdue_task_count,
  COUNT(*) - COUNT(total_estimated_hours) as null_total_estimated_hours,
  COUNT(*) - COUNT(is_half_day_off) as null_is_half_day_off,
  COUNT(*) - COUNT(is_holiday) as null_is_holiday,
  COUNT(*) - COUNT(is_day_before_holiday) as null_is_day_before_holiday,
  COUNT(*) - COUNT(is_day_after_holiday) as null_is_day_after_holiday,
  COUNT(*) - COUNT(active_phase_title) as null_active_phase_title
FROM fact_employee_productivity;

-- 5. Check-in timing distribution
-- ════════════════════════════════════════════════════════════
\echo '\n--- CHECK-IN TIMING STATS ---'
SELECT
  MIN(checkin_hour) as min_checkin_hour,
  MAX(checkin_hour) as max_checkin_hour,
  AVG(checkin_hour) as avg_checkin_hour,
  PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY checkin_hour) as median_checkin_hour,
  MIN(minutes_late) as min_minutes_late,
  MAX(minutes_late) as max_minutes_late,
  AVG(minutes_late) as avg_minutes_late,
  MIN(time_at_office_h) as min_time_at_office,
  MAX(time_at_office_h) as max_time_at_office,
  AVG(time_at_office_h) as avg_time_at_office
FROM fact_employee_productivity
WHERE checkin_hour IS NOT NULL;

-- 6. Task load distribution
-- ════════════════════════════════════════════════════════════
\echo '\n--- TASK LOAD STATS ---'
SELECT
  MIN(active_task_count) as min_active_tasks,
  MAX(active_task_count) as max_active_tasks,
  AVG(active_task_count) as avg_active_tasks,
  MIN(high_priority_task_count) as min_high_priority,
  MAX(high_priority_task_count) as max_high_priority,
  AVG(high_priority_task_count) as avg_high_priority,
  MIN(total_estimated_hours) as min_est_hours,
  MAX(total_estimated_hours) as max_est_hours,
  AVG(total_estimated_hours) as avg_est_hours,
  MIN(overdue_task_count) as min_overdue,
  MAX(overdue_task_count) as max_overdue,
  AVG(overdue_task_count) as avg_overdue
FROM fact_employee_productivity;

-- 7. Deadline pressure distribution
-- ════════════════════════════════════════════════════════════
\echo '\n--- DEADLINE PRESSURE STATS ---'
SELECT
  COUNT(CASE WHEN days_to_nearest_deadline IS NULL THEN 1 END) as no_deadline,
  COUNT(CASE WHEN days_to_nearest_deadline <= 0 THEN 1 END) as overdue_deadlines,
  COUNT(CASE WHEN days_to_nearest_deadline BETWEEN 1 AND 7 THEN 1 END) as week_or_less,
  COUNT(CASE WHEN days_to_nearest_deadline BETWEEN 8 AND 30 THEN 1 END) as within_30_days,
  COUNT(CASE WHEN days_to_nearest_deadline > 30 THEN 1 END) as beyond_30_days,
  PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY days_to_nearest_deadline) as median_days
FROM fact_employee_productivity
WHERE days_to_nearest_deadline IS NOT NULL;

-- 8. Calendar context distribution
-- ════════════════════════════════════════════════════════════
\echo '\n--- CALENDAR CONTEXT ---'
SELECT
  COUNT(CASE WHEN is_holiday THEN 1 END) as holiday_count,
  COUNT(CASE WHEN is_day_before_holiday THEN 1 END) as day_before_holiday_count,
  COUNT(CASE WHEN is_day_after_holiday THEN 1 END) as day_after_holiday_count,
  COUNT(CASE WHEN is_half_day_off THEN 1 END) as half_day_off_count,
  COUNT(CASE WHEN half_day_period IS NOT NULL THEN 1 END) as half_day_period_specified
FROM fact_employee_productivity;

-- 9. Phase context — how many rows have active phase title?
-- ════════════════════════════════════════════════════════════
\echo '\n--- PHASE CONTEXT ---'
SELECT
  COUNT(CASE WHEN active_phase_title IS NOT NULL THEN 1 END) as rows_with_phase,
  COUNT(DISTINCT active_phase_title) as unique_phases,
  COUNT(*) as total_rows,
  ROUND(100.0 * COUNT(CASE WHEN active_phase_title IS NOT NULL THEN 1 END) / 
        COUNT(*), 2) as coverage_pct
FROM fact_employee_productivity;

-- 10. Productivity score still valid?
-- ════════════════════════════════════════════════════════════
\echo '\n--- PRODUCTIVITY SCORE VALIDATION ---'
SELECT
  MIN(productivity_score) as min_score,
  MAX(productivity_score) as max_score,
  AVG(productivity_score) as avg_score,
  PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY productivity_score) as median_score,
  COUNT(CASE WHEN productivity_score < 0 OR productivity_score > 100 THEN 1 END) as invalid_scores,
  COUNT(CASE WHEN productivity_score IS NULL THEN 1 END) as null_scores
FROM fact_employee_productivity;

-- 11. Sample row with all details
-- ════════════════════════════════════════════════════════════
\echo '\n--- FULL SAMPLE ROW (with employee/date context) ---'
SELECT
  f.employee_sk,
  d.full_date,
  e.name,
  f.hours_worked,
  f.checkin_hour,
  f.minutes_late,
  f.is_late,
  f.active_task_count,
  f.days_to_nearest_deadline,
  f.overdue_task_count,
  f.is_holiday,
  f.is_half_day_off,
  f.productivity_score
FROM fact_employee_productivity f
JOIN dim_employee e ON f.employee_sk = e.employee_sk
JOIN dim_date d ON f.date_sk = d.date_sk
LIMIT 1;

\echo '\n✅ ETL v2 verification complete!'
