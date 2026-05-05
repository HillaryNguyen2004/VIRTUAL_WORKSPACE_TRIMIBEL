# EC2 Deployment Guide

Full setup guide for deploying this Laravel + Python application to AWS EC2 (Ubuntu "resolute").

---

## Table of Contents

1. [AWS Prerequisites](#1-aws-prerequisites)
2. [EC2 Initial Setup](#2-ec2-initial-setup)
3. [Install Dependencies](#3-install-dependencies)
4. [Configure Nginx](#4-configure-nginx)
5. [Set Up Application Environment](#5-set-up-application-environment)
6. [Configure Sudoers](#6-configure-sudoers)
7. [Create Systemd Services](#7-create-systemd-services)
8. [Directory Permissions](#8-directory-permissions)
9. [Upload ML Models](#9-upload-ml-models)
10. [GitHub Actions Secrets](#10-github-actions-secrets)
11. [First Deployment](#11-first-deployment)
12. [Troubleshooting](#12-troubleshooting)

---

## 1. AWS Prerequisites

### EC2 Instance
- **OS**: Ubuntu (resolute/25.x or later)
- **Instance type**: t3.small minimum (t3.medium recommended if running Ollama)
- **Storage**: 20 GB minimum

### Security Group — Inbound Rules
| Type  | Port | Source    | Purpose          |
|-------|------|-----------|------------------|
| SSH   | 22   | Your IP   | SSH access       |
| HTTP  | 80   | 0.0.0.0/0 | Web traffic      |
| HTTPS | 443  | 0.0.0.0/0 | Web traffic (TLS)|

### Security Group — Outbound Rules
| Type        | Port | Destination | Purpose               |
|-------------|------|-------------|-----------------------|
| All traffic | All  | 0.0.0.0/0   | Package downloads etc |

> **Important**: All outbound traffic must be allowed. PPAs (ondrej, deadsnakes) connect to
> `ppa.launchpadcontent.net:443` which may be blocked if outbound is restricted.

---

## 2. EC2 Initial Setup

### SSH into the instance
```bash
ssh -i your-key.pem ubuntu@<EC2_PUBLIC_IP>
```

### Fix /var/www/html ownership
```bash
sudo mkdir -p /var/www/html
sudo chown -R ubuntu:ubuntu /var/www/html
```

### Switch apt mirror if EC2 regional mirror returns 503
```bash
sudo sed -i 's|http://ap-southeast-1.ec2.archive.ubuntu.com/ubuntu|http://archive.ubuntu.com/ubuntu|g' \
  /etc/apt/sources.list.d/*.sources /etc/apt/sources.list 2>/dev/null || true
sudo apt-get update
```

### Add ubuntu to www-data group (required for shared file ownership)
```bash
sudo usermod -aG www-data ubuntu
# Log out and back in for group change to take effect
```

---

## 3. Install Dependencies

### PHP 8.5 + extensions
```bash
sudo apt-get install -y php8.5 php8.5-fpm php8.5-mysql php8.5-mbstring \
  php8.5-xml php8.5-curl php8.5-zip php8.5-bcmath php8.5-gd \
  php8.5-intl php8.5-redis
php8.5 -v
```

> **Note**: Ubuntu resolute ships PHP 8.5 in its default repos. PPAs (ondrej/php) do not
> support non-LTS Ubuntu releases and will fail with "Connection refused".

### Composer
```bash
curl -sS https://getcomposer.org/installer | php8.5
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### Nginx
```bash
sudo apt-get install -y --fix-missing nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

### Python 3.11.14 (chatbot) and 3.10.20 (ml/etl) via pyenv

> PPAs (deadsnakes) are also blocked. Ubuntu resolute provides only Python 3.14 by default.
> Use pyenv to install the exact versions matching local development.

```bash
# Build dependencies
sudo apt-get install -y --fix-missing build-essential libssl-dev zlib1g-dev libbz2-dev \
  libreadline-dev libsqlite3-dev curl libffi-dev liblzma-dev

# Install pyenv
curl https://pyenv.run | bash

# Add to PATH for current session
export PYENV_ROOT="$HOME/.pyenv"
export PATH="$PYENV_ROOT/bin:$PATH"
eval "$(pyenv init -)"

# Install exact Python versions (compilation takes ~5 min each)
pyenv install 3.11.14
pyenv install 3.10.20

# Symlink so deploy.sh can find them by name
sudo ln -sf ~/.pyenv/versions/3.11.14/bin/python3.11 /usr/local/bin/python3.11
sudo ln -sf ~/.pyenv/versions/3.10.20/bin/python3.10 /usr/local/bin/python3.10

# Verify
python3.11 --version   # Python 3.11.14
python3.10 --version   # Python 3.10.20
```

### Remove broken PPA sources
```bash
sudo rm /etc/apt/sources.list.d/ondrej-ubuntu-php-resolute.sources \
        /etc/apt/sources.list.d/deadsnakes-ubuntu-ppa-resolute.sources 2>/dev/null || true
```

---

## 4. Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/laravel
```

Paste (replace `YOUR_DOMAIN_OR_IP` with your EC2 public IP or domain):
```nginx
server {
    listen 80;
    server_name YOUR_DOMAIN_OR_IP;
    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

---

## 5. Set Up Application Environment

### Laravel .env
```bash
nano /var/www/html/.env
```

Key values to set:
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_KEY_HERE
APP_URL=http://YOUR_DOMAIN_OR_IP

DB_CONNECTION=mysql
DB_HOST=your-rds-host.amazonaws.com
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file

BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
PUSHER_APP_CLUSTER=ap1
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

METERED_DOMAIN=your-domain.metered.live
METERED_SECRET_KEY=your_metered_secret_key
```

> **Note**: Do NOT run `php artisan migrate` — the database schema already exists on the RDS server.
> `VITE_PUSHER_HOST` must NOT be `localhost` in production.

### ETL .env
```bash
nano /var/www/html/etl/.env
```

```env
PG_URL=postgresql://postgres:your_password@db.your_project.supabase.co:5432/postgres

MYSQL_DB_HOST=your-rds-host.amazonaws.com
MYSQL_DB_PORT=3306
MYSQL_DB_USERNAME=your_username
MYSQL_DB_PASSWORD=your_password
MYSQL_DB_DATABASE=your_database
```

### Chatbot .env
```bash
nano /var/www/html/chatbot_service/.env
```

Copy from your local `chatbot_service/.env`.

---

## 6. Configure Sudoers

```bash
sudo tee /etc/sudoers.d/deploy > /dev/null << 'EOF'
ubuntu ALL=(ALL) NOPASSWD: /bin/systemctl restart php8.5-fpm
ubuntu ALL=(ALL) NOPASSWD: /bin/systemctl restart laravel-queue
ubuntu ALL=(ALL) NOPASSWD: /bin/systemctl restart chatbot
ubuntu ALL=(ALL) NOPASSWD: /bin/systemctl restart ml-api
ubuntu ALL=(ALL) NOPASSWD: /bin/chown -R ubuntu\:www-data *
ubuntu ALL=(ALL) NOPASSWD: /bin/chown -R www-data\:www-data *
ubuntu ALL=(ALL) NOPASSWD: /bin/chmod -R 775 *
EOF
sudo chmod 440 /etc/sudoers.d/deploy
```

---

## 7. Create Systemd Services

### Laravel Queue Worker
```bash
sudo tee /etc/systemd/system/laravel-queue.service > /dev/null << 'EOF'
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/html
ExecStart=/usr/bin/php8.5 artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF
```

### Chatbot Service (FastAPI on port 8002)

> Entry point: `api.app:app` (file is `chatbot_service/api/app.py`)

```bash
sudo tee /etc/systemd/system/chatbot.service > /dev/null << 'EOF'
[Unit]
Description=Chatbot FastAPI Service
After=network.target

[Service]
User=ubuntu
WorkingDirectory=/var/www/html/chatbot_service
ExecStart=/var/www/html/chatbot_service/.venv/bin/uvicorn api.app:app --host 0.0.0.0 --port 8002
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF
```

### ML API Service (Flask on port 5001)

> Entry point: `api.py` (not `app.py`)

```bash
sudo tee /etc/systemd/system/ml-api.service > /dev/null << 'EOF'
[Unit]
Description=ML API Flask Service
After=network.target

[Service]
User=ubuntu
WorkingDirectory=/var/www/html/ml
ExecStart=/var/www/html/ml/.venv/bin/python api.py
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF
```

### Enable all services
```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-queue chatbot ml-api php8.5-fpm
```

---

## 8. Directory Permissions

Set shared ownership so both `ubuntu` (deploy) and `www-data` (web server) can write:

```bash
cd /var/www/html
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions \
          storage/framework/views storage/logs bootstrap/cache

sudo chown -R ubuntu:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

> **Why `ubuntu:www-data`**: rsync runs as `ubuntu`, PHP-FPM runs as `www-data`. Both need
> write access. With group `www-data` and mode `775`, both users can read and write.

---

## 9. Upload ML Models

The `ml/models/` directory is excluded from rsync and must be uploaded manually once.
Run this from your **local machine**:

```bash
scp -i your-key.pem \
  /path/to/project/ml/models/* \
  ubuntu@<EC2_PUBLIC_IP>:/var/www/html/ml/models/
```

Files required:
- `lstm_productivity.keras`
- `lstm_productivity_nextday.keras`
- `baseline.pkl`
- `baseline_nextday.pkl`
- `scaler.pkl`
- `scaler_nextday.pkl`
- `metrics.json`

After uploading:
```bash
sudo systemctl restart ml-api
sudo systemctl status ml-api
```

---

## 10. GitHub Actions Secrets

Go to **GitHub → Repository → Settings → Secrets and variables → Actions** and add:

| Secret name        | Value                                      |
|--------------------|--------------------------------------------|
| `EC2_SSH_KEY`      | Full contents of your `.pem` private key   |
| `EC2_HOST`         | EC2 public IP or domain                    |
| `EC2_USER`         | `ubuntu`                                   |
| `EC2_DEPLOY_PATH`  | `/var/www/html`                            |
| `DB_HOST`          | RDS endpoint                               |
| `DB_PORT`          | `3306`                                     |
| `DB_DATABASE`      | Database name                              |
| `DB_USERNAME`      | DB username                                |
| `DB_PASSWORD`      | DB password                                |

> **EC2_SSH_KEY**: Copy the full PEM file contents including the `-----BEGIN/END-----` lines.
> The workflow writes it with `printf '%s\n'` to preserve the trailing newline.

---

## 11. First Deployment

### Trigger via GitHub Actions
Push to the `develop` branch. The workflow will:
1. Run PHPUnit tests against the real DB
2. Build frontend assets (Vite) — includes all JS files in `vite.config.js`
3. rsync files to EC2 (excludes `vendor/`, `storage/`, `bootstrap/cache/`, `.env`, venvs)
4. Run `scripts/deploy.sh` on the server

### Manual run on the server
```bash
ssh -i your-key.pem ubuntu@<EC2_PUBLIC_IP>
cd /var/www/html
bash scripts/deploy.sh
```

### Verify all services
```bash
sudo systemctl status php8.5-fpm nginx laravel-queue chatbot ml-api
```

### Verify the site loads
```bash
curl -I http://YOUR_DOMAIN_OR_IP
# Should return HTTP/1.1 200 OK or 302 Found
```

---

## 12. Troubleshooting

### 500 — "Unable to locate file in Vite manifest"
A JS file is referenced in a Blade view but not registered in `vite.config.js`.
Add the missing file to the `input` array in `vite.config.js` and redeploy.

### 500 — Permission denied on storage/logs/laravel.log
```bash
sudo chown -R ubuntu:www-data /var/www/html/storage /var/www/html/bootstrap/cache
sudo chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```
Run artisan as `www-data` until re-login applies the group change:
```bash
cd /var/www/html
sudo -u www-data php8.5 artisan config:cache
sudo -u www-data php8.5 artisan route:cache
sudo -u www-data php8.5 artisan view:cache
```

### rsync "Operation not permitted" / "cannot delete non-empty directory"
Directories owned by `www-data` block rsync (which runs as `ubuntu`).
Fix ownership and ensure rsync excludes server-managed dirs:
```bash
sudo chown -R ubuntu:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/vendor
```
The workflow excludes `vendor/`, `storage/`, and `bootstrap/cache/` from rsync — never remove these excludes.

### ML API not starting — "No such file or directory: app.py"
The entry point is `api.py`, not `app.py`. Check:
```bash
sudo nano /etc/systemd/system/ml-api.service
# ExecStart=...python api.py
sudo systemctl daemon-reload && sudo systemctl restart ml-api
```

### ML API not starting — "No file or directory found at models/..."
Model files must be uploaded manually (they are excluded from rsync):
```bash
scp -i your-key.pem ml/models/* ubuntu@<EC2_PUBLIC_IP>:/var/www/html/ml/models/
```

### Chatbot not starting — "Could not import module main"
The entry point is `api.app:app` (file `chatbot_service/api/app.py`), not `main`. Check:
```bash
sudo nano /etc/systemd/system/chatbot.service
# ExecStart=...uvicorn api.app:app ...
sudo systemctl daemon-reload && sudo systemctl restart chatbot
```

### LSTM dashboard shows no data — "Failed to connect to localhost port 5001"
ML API is not running. Check:
```bash
sudo journalctl -u ml-api -n 50 --no-pager
sudo systemctl restart ml-api
```

### 502 Bad Gateway
PHP-FPM is not running or socket path is wrong:
```bash
sudo systemctl status php8.5-fpm
ls /run/php/   # should show php8.5-fpm.sock
```

### SSH key error in GitHub Actions ("error in libcrypto")
The workflow writes the key with `printf '%s\n'` to preserve the trailing newline.
Ensure `EC2_SSH_KEY` contains the full PEM without extra blank lines at the end.

### Queue worker not starting
```bash
sudo journalctl -u laravel-queue -n 50 --no-pager
```
Common cause: `.env` missing or `APP_KEY` not set.

### apt-get returns 503 from EC2 regional mirror
```bash
sudo sed -i 's|http://ap-southeast-1.ec2.archive.ubuntu.com/ubuntu|http://archive.ubuntu.com/ubuntu|g' \
  /etc/apt/sources.list.d/*.sources /etc/apt/sources.list 2>/dev/null || true
sudo apt-get update
```

### PPA fails with "Connection refused" to ppa.launchpadcontent.net
Ubuntu resolute is not LTS — neither `ondrej/php` nor `deadsnakes/ppa` support it.
Use packages from Ubuntu's default repos (PHP 8.5) and pyenv (Python 3.11/3.10).

### Composer "lock file does not contain a compatible set of packages"
Local machine uses PHP 8.2 but server runs PHP 8.5. The deploy script uses
`--ignore-platform-reqs` to bypass this mismatch.
