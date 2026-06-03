#!/usr/bin/env bash
# =============================================================================
# EC2 Deployment Script — runs on the server after rsync
# Called by GitHub Actions after files are synced.
# =============================================================================
set -euo pipefail

# ── Config ────────────────────────────────────────────────────────────────────
DEPLOY_PATH="${EC2_DEPLOY_PATH:-${REMOTE_DEPLOY_PATH:-/var/www/html}}"
PHP="${PHP_BIN:-php8.5}"
COMPOSER=$(which composer || echo "/usr/local/bin/composer")
WEB_USER="www-data"
DEPLOY_USER="${DEPLOY_USER:-ubuntu}"
PYTHON_CHATBOT="python3.11"
PYTHON_ML="python3.10"

cd "$DEPLOY_PATH"

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║         DEPLOYING — $(date '+%Y-%m-%d %H:%M:%S')           ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""

# ── 1. PHP dependencies ───────────────────────────────────────────────────────
echo "▶ [1/10] Installing PHP dependencies..."
export COMPOSER_ALLOW_SUPERUSER=1

$COMPOSER install \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader \
  --ignore-platform-reqs

# ── 2. Node dependencies (docx converter tool) ───────────────────────────────
echo "▶ [2/10] Installing Node dependencies (docx-converter)..."
(
  cd "$DEPLOY_PATH/tools/docx-converter"
  npm install --production --silent
) || echo "⚠  docx-converter npm install failed — not blocking deploy."

# ── 3. Database migrations ────────────────────────────────────────────────────
echo "▶ [3/11] Running database migrations..."
$PHP artisan migrate --force

# ── 4. Laravel optimizations ──────────────────────────────────────────────────
echo "▶ [4/11] Optimizing Laravel (config, routes, views, events)..."
$PHP artisan config:cache
# route:cache is intentionally skipped: web.php contains Closure routes that
# cannot be serialized. Routes load from files (~5 ms overhead, negligible).
$PHP artisan route:clear
$PHP artisan view:cache
$PHP artisan event:cache

# ── 5. Storage & permissions ──────────────────────────────────────────────────
echo "▶ [5/11] Fixing permissions..."
$PHP artisan storage:link --force 2>/dev/null || true
sudo chown -R "$DEPLOY_USER":"$WEB_USER" storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R "$DEPLOY_USER":"$WEB_USER" public/build 2>/dev/null || true
sudo chmod -R 775 public/build 2>/dev/null || true
# ChromaDB must be writable by both deploy user (chatbot service) and www-data (PHP ingest subprocess)
if [ -d "chatbot_service/var" ]; then
  sudo chown -R "$DEPLOY_USER":"$WEB_USER" chatbot_service/var
  sudo chmod -R 775 chatbot_service/var
  find chatbot_service/var -type d -exec sudo chmod g+s {} \;
fi

# ── 4. Restart PHP-FPM ────────────────────────────────────────────────────────
echo "▶ [6/11] Restarting PHP-FPM..."
sudo systemctl restart php8.2-fpm

# ── 5. Restart Laravel queue worker ──────────────────────────────────────────
echo "▶ [7/11] Restarting Laravel queue worker..."
if [ ! -f "/etc/systemd/system/laravel-queue.service" ] && [ -f "$DEPLOY_PATH/scripts/services/laravel-queue.service" ]; then
  echo "  laravel-queue.service missing, installing it now"
  sudo cp "$DEPLOY_PATH/scripts/services/laravel-queue.service" /etc/systemd/system/laravel-queue.service
fi
sudo systemctl daemon-reload
sudo systemctl restart laravel-queue

# ── 6. Python Chatbot service (FastAPI/uvicorn on :8002) ─────────────────────
echo "▶ [8/11] Updating & restarting chatbot service..."
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

# ── 8. Nginx config ───────────────────────────────────────────────────────────
echo "▶ [9/11] Syncing Nginx config..."
(
  SRC="$DEPLOY_PATH/scripts/nginx/laravel.conf"
  DST="/etc/nginx/sites-available/laravel"
  if [ -f "$SRC" ] && ! diff -q "$SRC" "$DST" > /dev/null 2>&1; then
    sudo cp "$SRC" "$DST"
    sudo nginx -t && sudo systemctl reload nginx
    echo "  Nginx config updated and reloaded"
  fi
) || echo "⚠  Nginx config sync failed — not blocking deploy."

echo "▶ [10/11] Syncing systemd service files..."
(
  CHANGED_SVCS=()
  for SVC in laravel-queue chatbot ml-api whitebophir face-detection; do
    SRC="$DEPLOY_PATH/scripts/services/${SVC}.service"
    DST="/etc/systemd/system/${SVC}.service"
    # Generate effective service file with runtime substitutions applied
    EFFECTIVE=$(mktemp)
    sed \
      -e "s|User=ubuntu|User=$DEPLOY_USER|g" \
      -e "s|Group=ubuntu|Group=$DEPLOY_USER|g" \
      -e "s|php8\.[0-9][0-9]*|$PHP|g" \
      -e "s|/var/www/html|$DEPLOY_PATH|g" \
      "$SRC" > "$EFFECTIVE"
    if [ -f "$SRC" ] && ! diff -q "$EFFECTIVE" "$DST" > /dev/null 2>&1; then
      sudo cp "$EFFECTIVE" "$DST"
      CHANGED_SVCS+=("$SVC")
      echo "  updated ${SVC}.service"
    fi
    rm -f "$EFFECTIVE"
  done
  if [ "${#CHANGED_SVCS[@]}" -gt 0 ]; then
    sudo systemctl daemon-reload
    for SVC in "${CHANGED_SVCS[@]}"; do
      sudo systemctl restart "$SVC" && echo "  restarted ${SVC}" || true
    done
  fi
) || echo "⚠  Service file sync failed — not blocking deploy."

echo "▶ [11/12] Updating & restarting ML API..."
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

echo "▶ [12/12] Updating & restarting Face Detection service..."
(
  PYTHON_FACE="python3.11"
  REQ_HASH_FILE="face_detection/.venv/.req_hash"
  REQ_HASH=$(md5sum face_detection/requirements.txt | cut -d' ' -f1)
  if [ -d "face_detection/.venv" ] && [ -f "$REQ_HASH_FILE" ] && [ "$(cat $REQ_HASH_FILE)" = "$REQ_HASH" ]; then
    echo "  face-detection deps unchanged, skipping pip install"
  else
    if [ ! -d "face_detection/.venv" ]; then
      $PYTHON_FACE -m venv face_detection/.venv
    fi
    face_detection/.venv/bin/pip install -r face_detection/requirements.txt --quiet
    echo "$REQ_HASH" > "$REQ_HASH_FILE"
  fi
  sudo systemctl restart face-detection
) || echo "⚠  Face Detection service update failed — not blocking deploy."

echo ""
echo "✅  Deployment complete at $(date '+%Y-%m-%d %H:%M:%S')"
echo ""
