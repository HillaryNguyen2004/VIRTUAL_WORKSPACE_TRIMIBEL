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
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan event:cache

# ── 3. Storage & permissions ──────────────────────────────────────────────────
echo "▶ [3/8] Fixing permissions..."
$PHP artisan storage:link --force 2>/dev/null || true
sudo chown -R ubuntu:"$WEB_USER" storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R ubuntu:"$WEB_USER" public/build 2>/dev/null || true
sudo chmod -R 775 public/build 2>/dev/null || true

# ── 4. Restart PHP-FPM ────────────────────────────────────────────────────────
echo "▶ [4/8] Restarting PHP-FPM..."
sudo systemctl restart php8.5-fpm

# ── 5. Restart Laravel queue worker ──────────────────────────────────────────
echo "▶ [5/8] Restarting Laravel queue worker..."
sudo systemctl restart laravel-queue

# ── 6. Python Chatbot service (FastAPI/uvicorn on :8002) ─────────────────────
echo "▶ [6/8] Updating & restarting chatbot service..."
(
  REQ_HASH_FILE="chatbot_service/.venv/.req_hash"
  REQ_HASH=$(md5sum chatbot_service/requirements.txt | cut -d' ' -f1)
  if [ -d "chatbot_service/.venv" ] && [ -f "$REQ_HASH_FILE" ] && [ "$(cat $REQ_HASH_FILE)" = "$REQ_HASH" ]; then
    echo "  chatbot deps unchanged, skipping pip install"
  else
    if [ ! -d "chatbot_service/.venv" ]; then
      $PYTHON_CHATBOT -m venv chatbot_service/.venv
    fi
    chatbot_service/.venv/bin/pip install -r chatbot_service/requirements.txt --quiet
    echo "$REQ_HASH" > "$REQ_HASH_FILE"
  fi
  sudo systemctl restart chatbot
) || echo "⚠  Chatbot service update failed — not blocking deploy."

# ── 7. ML API service (Flask on :5001) ───────────────────────────────────────
echo "▶ [7/8] Updating & restarting ML API..."
(
  REQ_HASH_FILE="ml/.venv/.req_hash"
  REQ_HASH=$(md5sum ml/requirements.txt | cut -d' ' -f1)
  if [ -d "ml/.venv" ] && [ -f "$REQ_HASH_FILE" ] && [ "$(cat $REQ_HASH_FILE)" = "$REQ_HASH" ]; then
    echo "  ml deps unchanged, skipping pip install"
  else
    if [ ! -d "ml/.venv" ]; then
      $PYTHON_ML -m venv ml/.venv
    fi
    ml/.venv/bin/pip install -r ml/requirements.txt --quiet
    echo "$REQ_HASH" > "$REQ_HASH_FILE"
  fi
  sudo systemctl restart ml-api
) || echo "⚠  ML API update failed — not blocking deploy."

# ── 8. Run incremental ETL ────────────────────────────────────────────────────
echo "▶ [8/8] Running incremental ETL sync..."
(
  cd etl
  REQ_HASH_FILE=".venv/.req_hash"
  REQ_HASH=$(md5sum requirements.txt | cut -d' ' -f1)
  if [ ! -d ".venv" ]; then
    $PYTHON_ML -m venv .venv
    .venv/bin/pip install -r requirements.txt --quiet
    echo "$REQ_HASH" > "$REQ_HASH_FILE"
  elif [ ! -f "$REQ_HASH_FILE" ] || [ "$(cat $REQ_HASH_FILE)" != "$REQ_HASH" ]; then
    .venv/bin/pip install -r requirements.txt --quiet
    echo "$REQ_HASH" > "$REQ_HASH_FILE"
  fi
  .venv/bin/python incremental_etl.py
) || echo "⚠  ETL run failed — check etl/logs. Not blocking deploy."

echo ""
echo "✅  Deployment complete at $(date '+%Y-%m-%d %H:%M:%S')"
echo ""
