#!/usr/bin/env bash
# =============================================================================
# EC2 Deployment Script — runs on the server after rsync
# Called by GitHub Actions after files are synced.
# =============================================================================
set -euo pipefail

# ── Config ────────────────────────────────────────────────────────────────────
DEPLOY_PATH="${EC2_DEPLOY_PATH:-/var/www/html}"
PHP="php8.5"
COMPOSER=$(which composer || echo "/usr/local/bin/composer")
WEB_USER="www-data"
PYTHON_CHATBOT="python3.11"
PYTHON_ML="python3.10"

cd "$DEPLOY_PATH"

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║         DEPLOYING — $(date '+%Y-%m-%d %H:%M:%S')           ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""

# ── 1. PHP dependencies ───────────────────────────────────────────────────────
echo "▶ [1/8] Installing PHP dependencies..."
$COMPOSER install \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader \
  --ignore-platform-reqs \
  --quiet

# ── 2. Laravel optimizations ──────────────────────────────────────────────────
echo "▶ [2/8] Optimizing Laravel (config, routes, views, events)..."
sudo -u "$WEB_USER" $PHP artisan config:cache
sudo -u "$WEB_USER" $PHP artisan route:cache
sudo -u "$WEB_USER" $PHP artisan view:cache
sudo -u "$WEB_USER" $PHP artisan event:cache

# ── 3. Storage & permissions ──────────────────────────────────────────────────
echo "▶ [3/8] Fixing permissions..."
sudo -u "$WEB_USER" $PHP artisan storage:link --force 2>/dev/null || true
chmod -R 775 storage bootstrap/cache
chown -R "$WEB_USER:$WEB_USER" storage bootstrap/cache public/build 2>/dev/null || true

# ── 4. Restart PHP-FPM ────────────────────────────────────────────────────────
echo "▶ [4/8] Restarting PHP-FPM..."
sudo systemctl restart php8.5-fpm

# ── 5. Restart Laravel queue worker ──────────────────────────────────────────
echo "▶ [5/8] Restarting Laravel queue worker..."
sudo systemctl restart laravel-queue

# ── 6. Python Chatbot service (FastAPI/uvicorn on :8002) ─────────────────────
echo "▶ [6/8] Updating & restarting chatbot service..."
(
  if [ -d "chatbot_service/.venv" ]; then
    chatbot_service/.venv/bin/pip install \
      -r chatbot_service/requirements.txt \
      --quiet --no-deps 2>/dev/null || \
    chatbot_service/.venv/bin/pip install \
      -r chatbot_service/requirements.txt \
      --quiet
  else
    $PYTHON_CHATBOT -m venv chatbot_service/.venv
    chatbot_service/.venv/bin/pip install \
      -r chatbot_service/requirements.txt --quiet
  fi
  sudo systemctl restart chatbot
) || echo "⚠  Chatbot service update failed — not blocking deploy."

# ── 7. ML API service (Flask on :5001) ───────────────────────────────────────
echo "▶ [7/8] Updating & restarting ML API..."
(
  if [ -d "ml/.venv" ]; then
    ml/.venv/bin/pip install \
      -r ml/requirements.txt \
      --quiet --no-deps 2>/dev/null || \
    ml/.venv/bin/pip install \
      -r ml/requirements.txt --quiet
  else
    $PYTHON_ML -m venv ml/.venv
    ml/.venv/bin/pip install -r ml/requirements.txt --quiet
  fi
  sudo systemctl restart ml-api
) || echo "⚠  ML API update failed — not blocking deploy."

# ── 9. Run incremental ETL ────────────────────────────────────────────────────
echo "▶ [8/8] Running incremental ETL sync..."
(
  cd etl
  if [ -d ".venv" ]; then
    .venv/bin/python incremental_etl.py
  else
    $PYTHON_ML -m venv .venv
    .venv/bin/pip install -r requirements.txt --quiet
    .venv/bin/python incremental_etl.py
  fi
) || echo "⚠  ETL run failed — check etl/logs. Not blocking deploy."

echo ""
echo "✅  Deployment complete at $(date '+%Y-%m-%d %H:%M:%S')"
echo ""
