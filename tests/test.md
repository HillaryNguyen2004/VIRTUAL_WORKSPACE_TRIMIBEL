php artisan test 2>&1 | tee storage/logs/all-tests.log

# Export the schema only from manage_user (no data, no auto_increment values)
mysqldump -u root --no-data --skip-add-drop-table manage_user > /tmp/schema.sql

# Drop and recreate testing database cleanly
mysql -u root -e "DROP DATABASE IF EXISTS testing; CREATE DATABASE testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import the schema into testing
mysql -u root testing < /tmp/schema.sql