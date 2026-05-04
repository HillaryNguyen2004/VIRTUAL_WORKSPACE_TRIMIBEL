#!/usr/bin/env bash
# =============================================================================
# EC2 Deployment Script — runs on the server after rsync
# Called by GitHub Actions after files are synced.
# =============================================================================
set -euo pipefail

# ── Config ────────────────────────────────────────────────────────────────────
DEPLOY_PATH="${EC2_DEPLOY_PATH:-/var/www/html}"
PHP="php8.2"
COMPOSER="/usr/local/bin/composer"
WEB_USER="www-data"

cd "$DEPLOY_PATH"

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║         DEPLOYING — $(date '+%Y-%m-%d %H:%M:%S')           ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""

# ── 1. PHP dependencies ───────────────────────────────────────────────────────
echo "▶ [1/9] Installing PHP dependencies..."
$COMPOSER install \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader \
  --quiet

# ── 2. Database migrations ────────────────────────────────────────────────────
echo "▶ [2/9] Running database migrations..."
$PHP artisan migrate --force

# ── 3. Laravel optimizations ──────────────────────────────────────────────────
echo "▶ [3/9] Optimizing Laravel (config, routes, views, events)..."
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan event:cache

# ── 4. Storage & permissions ──────────────────────────────────────────────────
echo "▶ [4/9] Fixing permissions..."
$PHP artisan storage:link --force 2>/dev/null || true
chmod -R 775 storage bootstrap/cache
chown -R "$WEB_USER:$WEB_USER" storage bootstrap/cache public/build 2>/dev/null || true

# ── 5. Restart PHP-FPM ────────────────────────────────────────────────────────
echo "▶ [5/9] Restarting PHP-FPM..."
sudo systemctl restart php8.2-fpm

# ── 6. Restart Laravel queue worker ──────────────────────────────────────────
echo "▶ [6/9] Restarting Laravel queue worker..."
sudo systemctl restart laravel-queue

# ── 7. Python Chatbot service (FastAPI/uvicorn on :8002) ─────────────────────
echo "▶ [7/9] Updating & restarting chatbot service..."
if [ -d "chatbot_service/.venv" ]; then
  chatbot_service/.venv/bin/pip install \
    -r chatbot_service/requirements.txt \
    --quiet --no-deps 2>/dev/null || \
  chatbot_service/.venv/bin/pip install \
    -r chatbot_service/requirements.txt \
    --quiet
else
  python3 -m venv chatbot_service/.venv
  chatbot_service/.venv/bin/pip install \
    -r chatbot_service/requirements.txt --quiet
fi
sudo systemctl restart chatbot

# ── 8. ML API service (Flask on :5001) ───────────────────────────────────────
echo "▶ [8/9] Updating & restarting ML API..."
if [ -d "ml/.venv" ]; then
  ml/.venv/bin/pip install \
    -r ml/requirements.txt \
    --quiet --no-deps 2>/dev/null || \
  ml/.venv/bin/pip install \
    -r ml/requirements.txt --quiet
else
  python3 -m venv ml/.venv
  ml/.venv/bin/pip install -r ml/requirements.txt --quiet
fi
sudo systemctl restart ml-api

# ── 9. Run incremental ETL ────────────────────────────────────────────────────
echo "▶ [9/9] Running incremental ETL sync..."
(
  cd etl
  if [ -d "../chatbot_service/.venv" ]; then
    ../chatbot_service/.venv/bin/python incremental_etl.py
  else
    python3 incremental_etl.py
  fi
) || echo "⚠  ETL run failed — check etl/logs. Not blocking deploy."

echo ""
echo "✅  Deployment complete at $(date '+%Y-%m-%d %H:%M:%S')"
echo ""
