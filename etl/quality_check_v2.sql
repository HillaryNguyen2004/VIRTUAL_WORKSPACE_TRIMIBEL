-- ════════════════════════════════════════════════════════════
-- ETL v2 Data Quality Report
-- Check for anomalies and issues in new feature columns
-- ════════════════════════════════════════════════════════════

\echo '═══════════════════════════════════════════════════════════'
\echo 'ETL v2 DATA QUALITY REPORT'
\echo '═══════════════════════════════════════════════════════════'

-- Missing data summary
\echo '\n1️⃣  MISSING DATA SUMMARY (should be low %)'
SELECT
  'checkin_hour' as column_name,
  COUNT(*) as total,
  COUNT(CASE WHEN checkin_hour IS NULL THEN 1 END) as null_count,
  ROUND(100.0 * COUNT(CASE WHEN checkin_hour IS NULL THEN 1 END) / COUNT(*), 2) as null_pct
FROM fact_employee_productivity
UNION ALL
SELECT
  'checkout_hour',
  COUNT(*),
  COUNT(CASE WHEN checkout_hour IS NULL THEN 1 END),
  ROUND(100.0 * COUNT(CASE WHEN checkout_hour IS NULL THEN 1 END) / COUNT(*), 2)
FROM fact_employee_productivity
UNION ALL
SELECT
  'active_task_count',
  COUNT(*),
  COUNT(CASE WHEN active_task_count IS NULL THEN 1 END),
  ROUND(100.0 * COUNT(CASE WHEN active_task_count IS NULL THEN 1 END) / COUNT(*), 2)
FROM fact_employee_productivity
UNION ALL
SELECT
  'days_to_nearest_deadline',
  COUNT(*),
  COUNT(CASE WHEN days_to_nearest_deadline IS NULL THEN 1 END),
  ROUND(100.0 * COUNT(CASE WHEN days_to_nearest_deadline IS NULL THEN 1 END) / COUNT(*), 2)
FROM fact_employee_productivity
UNION ALL
SELECT
  'total_estimated_hours',
  COUNT(*),
  COUNT(CASE WHEN total_estimated_hours IS NULL THEN 1 END),
  ROUND(100.0 * COUNT(CASE WHEN total_estimated_hours IS NULL THEN 1 END) / COUNT(*), 2)
FROM fact_employee_productivity
ORDER BY null_pct DESC;

-- Out of range checks
\echo '\n2️⃣  OUT OF RANGE VALUES (potential issues)'
SELECT
  'ISSUE' as type,
  'checkin_hour < 0 or > 24' as issue_desc,
  COUNT(*) as count
FROM fact_employee_productivity
WHERE checkin_hour IS NOT NULL AND (checkin_hour < 0 OR checkin_hour > 24)
UNION ALL
SELECT
  'ISSUE',
  'checkout_hour < 0 or > 24',
  COUNT(*)
FROM fact_employee_productivity
WHERE checkout_hour IS NOT NULL AND (checkout_hour < 0 OR checkout_hour > 24)
UNION ALL
SELECT
  'ISSUE',
  'time_at_office_h < 0 or > 24',
  COUNT(*)
FROM fact_employee_productivity
WHERE time_at_office_h IS NOT NULL AND (time_at_office_h < 0 OR time_at_office_h > 24)
UNION ALL
SELECT
  'ISSUE',
  'active_task_count < 0',
  COUNT(*)
FROM fact_employee_productivity
WHERE active_task_count < 0
UNION ALL
SELECT
  'ISSUE',
  'overdue_task_count > active_task_count',
  COUNT(*)
FROM fact_employee_productivity
WHERE overdue_task_count > active_task_count;

-- Logic inconsistencies
\echo '\n3️⃣  LOGIC CHECKS (sanity tests)'
SELECT
  'CHECK' as type,
  CASE 
    WHEN COUNT(CASE WHEN minutes_late IS NOT NULL AND is_late = FALSE AND minutes_late > 10 THEN 1 END) > 0
    THEN '⚠️ is_late=FALSE but minutes_late>10'
    ELSE '✓ is_late flag consistent'
  END as check_result,
  COUNT(CASE WHEN minutes_late IS NOT NULL AND is_late = FALSE AND minutes_late > 10 THEN 1 END) as count
FROM fact_employee_productivity
UNION ALL
SELECT
  'CHECK',
  CASE
    WHEN COUNT(CASE WHEN is_half_day_off = TRUE AND half_day_period IS NULL THEN 1 END) > 0
    THEN '⚠️ is_half_day_off=TRUE but half_day_period NULL'
    ELSE '✓ half_day_off period consistent'
  END,
  COUNT(CASE WHEN is_half_day_off = TRUE AND half_day_period IS NULL THEN 1 END)
FROM fact_employee_productivity
UNION ALL
SELECT
  'CHECK',
  CASE
    WHEN COUNT(CASE WHEN days_to_nearest_deadline < 0 AND active_task_count = 0 THEN 1 END) > 0
    THEN '⚠️ negative deadline but no active tasks'
    ELSE '✓ deadline pressure consistent'
  END,
  COUNT(CASE WHEN days_to_nearest_deadline < 0 AND active_task_count = 0 THEN 1 END)
FROM fact_employee_productivity;

-- Feature distribution
\echo '\n4️⃣  FEATURE DISTRIBUTIONS'
SELECT
  'checkin_hour' as feature,
  'min=' || COALESCE(ROUND(MIN(checkin_hour)::numeric, 1), 'N/A') ||
  ', max=' || COALESCE(ROUND(MAX(checkin_hour)::numeric, 1), 'N/A') ||
  ', avg=' || COALESCE(ROUND(AVG(checkin_hour)::numeric, 2), 'N/A') as distribution
FROM fact_employee_productivity
UNION ALL
SELECT
  'minutes_late',
  'min=' || COALESCE(ROUND(MIN(minutes_late)::numeric, 1), 'N/A') ||
  ', max=' || COALESCE(ROUND(MAX(minutes_late)::numeric, 1), 'N/A') ||
  ', avg=' || COALESCE(ROUND(AVG(minutes_late)::numeric, 2), 'N/A')
FROM fact_employee_productivity
UNION ALL
SELECT
  'active_task_count',
  'min=' || COALESCE(MIN(active_task_count), 'N/A')::text ||
  ', max=' || COALESCE(MAX(active_task_count), 'N/A')::text ||
  ', avg=' || COALESCE(ROUND(AVG(active_task_count)::numeric, 2), 'N/A')::text
FROM fact_employee_productivity
UNION ALL
SELECT
  'days_to_nearest_deadline',
  'min=' || COALESCE(MIN(days_to_nearest_deadline), 'N/A')::text ||
  ', max=' || COALESCE(MAX(days_to_nearest_deadline), 'N/A')::text ||
  ', avg=' || COALESCE(ROUND(AVG(days_to_nearest_deadline)::numeric, 2), 'N/A')::text
FROM fact_employee_productivity
UNION ALL
SELECT
  'total_estimated_hours',
  'min=' || COALESCE(ROUND(MIN(total_estimated_hours)::numeric, 2), 'N/A') ||
  ', max=' || COALESCE(ROUND(MAX(total_estimated_hours)::numeric, 2), 'N/A') ||
  ', avg=' || COALESCE(ROUND(AVG(total_estimated_hours)::numeric, 2), 'N/A')
FROM fact_employee_productivity;

-- Holiday & day-off coverage
\echo '\n5️⃣  HOLIDAY & DAY-OFF COVERAGE'
SELECT
  COUNT(*) as total_rows,
  COUNT(CASE WHEN is_holiday THEN 1 END) as holiday_rows,
  ROUND(100.0 * COUNT(CASE WHEN is_holiday THEN 1 END) / COUNT(*), 2) as holiday_pct,
  COUNT(CASE WHEN is_day_before_holiday THEN 1 END) as day_before_holiday_rows,
  COUNT(CASE WHEN is_half_day_off THEN 1 END) as half_day_off_rows,
  COUNT(CASE WHEN had_day_off THEN 1 END) as any_day_off_rows
FROM fact_employee_productivity;

\echo '\n✅ Quality check complete!'
