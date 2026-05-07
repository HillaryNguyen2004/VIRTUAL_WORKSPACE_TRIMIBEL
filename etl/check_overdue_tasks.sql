-- ════════════════════════════════════════════════════════════
-- Quick Check: Do OVERDUE tasks exist?
-- ════════════════════════════════════════════════════════════

SELECT 
  'Total Tasks' as metric,
  COUNT(*) as count
FROM tasks

UNION ALL

SELECT 'Tasks with assigned_user_id',
  COUNT(*)
FROM tasks
WHERE assigned_user_id IS NOT NULL

UNION ALL

SELECT 'Tasks with due_date',
  COUNT(*)
FROM tasks
WHERE due_date IS NOT NULL

UNION ALL

SELECT 'Tasks NOT completed',
  COUNT(*)
FROM tasks
WHERE status != 'completed'

UNION ALL

SELECT 'Tasks NOT completed + NULL due_date',
  COUNT(*)
FROM tasks
WHERE status != 'completed' AND due_date IS NULL

UNION ALL

SELECT 'Tasks due_date in PAST (potential overdue)',
  COUNT(*)
FROM tasks
WHERE due_date IS NOT NULL
  AND due_date < CURDATE()

UNION ALL

SELECT 'Tasks NOT completed + due_date in PAST',
  COUNT(*)
FROM tasks
WHERE status != 'completed'
  AND due_date IS NOT NULL
  AND due_date < CURDATE()

UNION ALL

SELECT 'Tasks NOT completed + due_date in FUTURE',
  COUNT(*)
FROM tasks
WHERE status != 'completed'
  AND due_date IS NOT NULL
  AND due_date >= CURDATE()

UNION ALL

SELECT 'Sample past-due incomplete task',
  CONCAT('ID=', id, '|user=', assigned_user_id, '|due=', due_date, '|status=', status)
FROM tasks
WHERE status != 'completed'
  AND due_date < CURDATE()
  AND due_date IS NOT NULL
LIMIT 1;
