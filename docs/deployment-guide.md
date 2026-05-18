# Deployment Guide

## Architecture Overview

```
Internet
   │
   ▼
Nginx (80 → 443 redirect, SSL via Let's Encrypt)
   │
   ├── /* ──────────────── PHP-FPM 8.5 (Laravel)
   ├── /api/chat-bot/* ─── FastAPI chatbot service  :8002
   └── /onlyoffice-ds/* ── OnlyOffice Docker        :8081

Background services (systemd):
  laravel-queue   Laravel queue worker
  chatbot         FastAPI / uvicorn (RAG + LLM)
  ml-api          Flask / gunicorn (LSTM predictions) :5001
  whitebophir     Node.js collaborative whiteboard    :3002

Local daemons:
  ollama          LLM server                          :11434
  mysql           Application database                :3307
  redis           Cache / sessions / queues           :6379
  onlyoffice      Docker container                    :8081
```

---

## 1. GitHub Secrets

Go to **GitHub → Repository → Settings → Secrets and variables → Actions** and add:

| Secret | Value |
|--------|-------|
| `EC2_HOST` | EC2 public IP or DNS |
| `EC2_USER` | `ubuntu` |
| `EC2_SSH_KEY` | Contents of the private SSH key |
| `EC2_DEPLOY_PATH` | `/var/www/html` |
| `PUSHER_APP_KEY` | From Pusher dashboard |
| `PUSHER_APP_SECRET` | From Pusher dashboard |
| `PUSHER_APP_CLUSTER` | e.g. `mt1` |
| `PUSHER_HOST` | `sockjs-mt1.pusher.com` |
| `PUSHER_PORT` | `443` |
| `PUSHER_SCHEME` | `https` |

---

## 2. One-Time Server Setup

Run **once** on a fresh Ubuntu 22.04 EC2 instance:

```bash
sudo bash scripts/setup-ec2.sh /var/www/html trimibel.com
```

This installs: PHP 8.5, Composer, MySQL, Redis, Node.js 20, Python 3.11, Nginx, Docker, Certbot, and all systemd services.

Save the printed DB credentials and OnlyOffice JWT secret — you will need them for `.env`.

### 2.1 Install Ollama + models

```bash
curl -fsSL https://ollama.com/install.sh | sh
ollama pull llama3.1
ollama pull bge-m3
```

### 2.2 Run OnlyOffice Document Server

```bash
newgrp docker   # apply docker group without re-login
docker run -d --name onlyoffice-document-server \
  --restart unless-stopped \
  -p 127.0.0.1:8081:80 \
  -e JWT_ENABLED=true \
  -e JWT_SECRET=<your-jwt-secret> \
  -v onlyoffice_data:/var/www/onlyoffice/Data \
  onlyoffice/documentserver
```

This is a one-time command. The container auto-starts on server reboot via `--restart unless-stopped`.

### 2.3 Set up HTTPS with Let's Encrypt

```bash
sudo certbot --nginx -d trimibel.com -d www.trimibel.com
```

Select **Redirect** when asked about HTTP traffic.

### 2.4 Enable laravel-queue service

```bash
sudo systemctl enable laravel-queue
sudo systemctl start laravel-queue
```

### 2.5 Fix Python ingest permissions

PHP-FPM runs as `www-data` but the venv Python resolves through pyenv in `/home/ubuntu`. Allow traversal once:

```bash
sudo chmod o+x /home/ubuntu
```

### 2.6 Place environment files

```bash
nano /var/www/html/.env                      # Laravel
nano /var/www/html/chatbot_service/.env      # Chatbot service
nano /var/www/html/etl/.env                  # ETL / ML API
```

See section 3 for required values.

### 2.7 Bootstrap the application

```bash
cd /var/www/html
php8.5 artisan key:generate
php8.5 artisan migrate --force
php8.5 artisan config:cache
php8.5 artisan storage:link
```

---

## 3. Environment Files

### 3.1 Laravel — `/var/www/html/.env`

```env
APP_NAME="Your App Name"
APP_ENV=production
APP_KEY=base64:...          # php8.5 artisan key:generate --show
APP_DEBUG=false
APP_URL=https://trimibel.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=manage_user
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=pusher
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=sockjs-mt1.pusher.com
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=
FILESYSTEM_DISK=s3

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://trimibel.com/auth/google/callback

METERED_DOMAIN=<subdomain>.metered.live
METERED_SECRET_KEY=

ONLYOFFICE_DOCUMENT_SERVER_URL=http://127.0.0.1:8081
ONLYOFFICE_JWT_SECRET=<same-secret-used-in-docker-run>
ONLYOFFICE_PUBLIC_URL=https://trimibel.com/onlyoffice-ds

FACE_SERVICE_URL=http://localhost:8001

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@trimibel.com
MAIL_FROM_NAME="Your App Name"
```

### 3.2 Chatbot service — `/var/www/html/chatbot_service/.env`

```env
EMBED_MODEL=bge-m3:latest
EMBED_DIM=1024
GEN_MODEL=gemini-2.5-flash-lite
GOOGLE_API_KEY=

OLLAMA_BASE_URL=http://localhost:11434

CHROMA_DIR=./var/chroma_db
COLLECTION=kb_collection

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=manage_user
DB_USERNAME=root
DB_PASSWORD=

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=

ANONYMIZED_TELEMETRY=False
GEN_MAX_TOKENS=4096
TIMEZONE=Asia/Ho_Chi_Minh
```

### 3.3 ETL / ML API — `/var/www/html/etl/.env`

```env
PG_URL=postgresql://...     # Supabase connection string
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=manage_user
DB_USERNAME=root
DB_PASSWORD=
```

---

## 4. Automated Deployment Pipeline

Every push to the `develop` branch triggers the GitHub Actions workflow (`.github/workflows/deploy-develop.yml`):

```
push to develop
       │
       ├── Job 1: PHP Tests (PHPUnit on PHP 8.5)
       │
       └── Job 2: Build Frontend (Vite / npm)
                  │
                  └── Job 3: Deploy (only if jobs 1+2 pass)
                             │
                             ├── rsync files → EC2
                             │   (excludes: .env, vendor, .venv,
                             │    storage, chroma_db)
                             │
                             └── bash scripts/deploy.sh
```

### What `deploy.sh` does (10 steps):

| Step | Action |
|------|--------|
| 1/10 | `composer install` — no-dev, optimized |
| 2/10 | `php artisan migrate --force` |
| 3/10 | `config:cache`, `route:clear`, `view:cache`, `event:cache` |
| 4/10 | Fix storage/cache permissions (`775`, `ubuntu:www-data`) |
| 5/10 | Restart `php8.5-fpm` |
| 6/10 | Restart `laravel-queue` |
| 7/10 | `pip install` chatbot deps if `requirements.txt` changed → restart `chatbot` |
| 8/10 | Sync `scripts/nginx/laravel.conf` if changed → reload nginx |
| 9/10 | Sync `scripts/services/*.service` if changed → `daemon-reload` → restart changed services |
| 10/10 | `pip install` ML deps if changed → restart `ml-api` |

---

## 5. Service Management

### View logs

```bash
sudo journalctl -u chatbot -f
sudo journalctl -u laravel-queue -f
sudo journalctl -u ml-api -f
sudo journalctl -u whitebophir -f
tail -f /var/www/html/storage/logs/laravel.log
```

### Restart services

```bash
sudo systemctl restart chatbot
sudo systemctl restart laravel-queue
sudo systemctl restart ml-api
sudo systemctl restart php8.5-fpm
sudo systemctl restart nginx
```

### Check all services at once

```bash
systemctl status chatbot laravel-queue ml-api whitebophir php8.5-fpm nginx
```

---

## 6. Nginx

Config file: `scripts/nginx/laravel.conf` — synced to `/etc/nginx/sites-available/laravel` on every deploy.

| Path | Backend |
|------|---------|
| `/*` | PHP-FPM (Laravel) |
| `/api/chat-bot/stop` | `http://127.0.0.1:8002/chat/cancel` |
| `/api/chat-bot/*` | `http://127.0.0.1:8002/chat/*` |
| `/onlyoffice-ds/*` | `http://127.0.0.1:8081/` |

The config includes both the HTTP→HTTPS redirect block and the HTTPS SSL block, so deploys never break SSL.

**Renew SSL manually:**
```bash
sudo certbot renew
sudo systemctl reload nginx
```

---

## 7. Database

```bash
# Run migrations
php8.5 artisan migrate --force

# Access MySQL
mysql -u root -p manage_user

# Rollback last migration
php8.5 artisan migrate:rollback
```

---

## 8. Chatbot / RAG

### Ingest workspace documents (CLI)

```bash
cd /var/www/html/chatbot_service
.venv/bin/python cli/ingest_workspace.py \
  ./var/data \
  /path/to/file.pdf \
  workspace_id \
  "Original Name.pdf" \
  "storage_name.pdf"
```

### Reload ChromaDB after manual changes

```bash
curl -X POST http://localhost:8002/reload-chroma
```

### Inspect ChromaDB collections

```bash
cd /var/www/html/chatbot_service
.venv/bin/python cli/inspect_chroma.py
```

### Update Ollama models

```bash
ollama pull bge-m3:latest
ollama pull llama3.1:latest
ollama list
sudo systemctl restart chatbot   # reload new model into memory
```

---

## 9. OnlyOffice

OnlyOffice runs as a Docker container and is **not** managed by the deploy script — run the `docker run` command once (section 2.2) and Docker handles restarts.

```bash
# Check status
docker ps | grep onlyoffice

# View logs
docker logs onlyoffice-document-server -f

# Restart
docker restart onlyoffice-document-server
```

The `ONLYOFFICE_JWT_SECRET` in `/var/www/html/.env` must match the `-e JWT_SECRET` value used when starting the container.

---

## 10. Troubleshooting

### Ingest fails — `No module named 'pypdf'`

PHP-FPM (`www-data`) cannot traverse `/home/ubuntu` to reach the pyenv Python binary:

```bash
sudo chmod o+x /home/ubuntu
```

### Chatbot uses wrong embedding model

The service caches env at startup. After changing `EMBED_MODEL` in `chatbot_service/.env`:

```bash
sudo systemctl restart chatbot
```

### `env()` returns null in PHP

`env()` returns null when config cache is active. Always use `config('key')` in PHP code. After any `.env` change:

```bash
php8.5 artisan config:clear && php8.5 artisan config:cache
```

### ChromaDB dimension mismatch after switching embed model

Delete old collections and re-ingest:

```bash
rm -rf /var/www/html/chatbot_service/var/chroma_db/workspaces/
# Re-ingest from the frontend or CLI
```

### Queue jobs not processing

```bash
sudo systemctl status laravel-queue
sudo systemctl start laravel-queue
sudo systemctl enable laravel-queue   # persist across reboots
```

### Nginx deploy broke HTTPS

The `scripts/nginx/laravel.conf` in the repo already contains the full HTTPS config. Restore it:

```bash
sudo cp /var/www/html/scripts/nginx/laravel.conf /etc/nginx/sites-available/laravel
sudo nginx -t && sudo systemctl reload nginx
```

### Debug a failed ingest from the UI

Check the exact error stored in the database:

```bash
cd /var/www/html && php8.5 artisan tinker --execute="
\App\Models\AIWorkspaceFile::where('ingest_status','failed')
    ->orderByDesc('updated_at')->limit(5)
    ->get(['original_name','ingest_error'])
    ->each(fn(\$f) => dump(\$f->original_name, \$f->ingest_error));
"
```
