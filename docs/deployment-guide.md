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
9. [GitHub Actions Secrets](#9-github-actions-secrets)
10. [First Deployment](#10-first-deployment)
11. [Troubleshooting](#11-troubleshooting)

---

## 1. AWS Prerequisites

### EC2 Instance
- **OS**: Ubuntu (resolute/25.x or later)
- **Instance type**: t2.micro or larger
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

---

## 2. EC2 Initial Setup

### SSH into the instance
```bash
ssh -i your-key.pem ubuntu@<EC2_PUBLIC_IP>
```

### Fix /var/www/html ownership (so ubuntu user can write files)
```bash
sudo mkdir -p /var/www/html
sudo chown -R ubuntu:ubuntu /var/www/html
```

### Update apt mirror if EC2 regional mirror returns 503
```bash
sudo sed -i 's|http://ap-southeast-1.ec2.archive.ubuntu.com/ubuntu|http://archive.ubuntu.com/ubuntu|g' \
  /etc/apt/sources.list.d/*.sources /etc/apt/sources.list 2>/dev/null || true
sudo apt-get update
```

---

## 3. Install Dependencies

### PHP 8.5 + extensions
```bash
sudo apt-get install -y php8.5 php8.5-fpm php8.5-mysql php8.5-mbstring \
  php8.5-xml php8.5-curl php8.5-zip php8.5-bcmath php8.5-gd \
  php8.5-intl php8.5-redis
```

Verify:
```bash
php8.5 -v
```

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

### Python 3.11.14 (chatbot) and 3.10.15 (ml/etl) via pyenv

> PPAs (deadsnakes, ondrej) are blocked on this EC2 — use pyenv instead.
> Ubuntu resolute only provides Python 3.13 by default.

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

# Install exact Python versions matching local dev
pyenv install 3.11.14
pyenv install 3.10.20

# Symlink so deploy.sh can find them
sudo ln -sf ~/.pyenv/versions/3.11.14/bin/python3.11 /usr/local/bin/python3.11
sudo ln -sf ~/.pyenv/versions/3.10.20/bin/python3.10 /usr/local/bin/python3.10

# Verify
python3.11 --version   # should print Python 3.11.14
python3.10 --version   # should print Python 3.10.20
```

> **Note**: Also remove broken PPA sources to keep apt-get update clean:
> ```bash
> sudo rm /etc/apt/sources.list.d/ondrej-ubuntu-php-resolute.sources \
>         /etc/apt/sources.list.d/deadsnakes-ubuntu-ppa-resolute.sources 2>/dev/null || true
> ```

---

## 4. Configure Nginx

### Create site config
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

### Enable the site
```bash
sudo ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

---

## 5. Set Up Application Environment

### Create the .env file
```bash
sudo nano /var/www/html/.env
```

Copy your production `.env` content here. Key values to set:
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
```

> **Note**: Do NOT run `php artisan migrate` — the database schema already exists on the RDS server.

---

## 6. Configure Sudoers

Allow the `ubuntu` deploy user to restart services without a password prompt:

```bash
sudo tee /etc/sudoers.d/deploy > /dev/null << 'EOF'
ubuntu ALL=(ALL) NOPASSWD: /bin/systemctl restart php8.5-fpm
ubuntu ALL=(ALL) NOPASSWD: /bin/systemctl restart laravel-queue
ubuntu ALL=(ALL) NOPASSWD: /bin/systemctl restart chatbot
ubuntu ALL=(ALL) NOPASSWD: /bin/systemctl restart ml-api
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

### Enable and reload all services
```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-queue chatbot ml-api
sudo systemctl enable php8.5-fpm
```

---

## 8. Directory Permissions

Run after first rsync (or manually before first deploy):
```bash
cd /var/www/html
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions \
          storage/framework/views storage/logs bootstrap/cache

chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# ubuntu user must also be able to write (for rsync)
sudo usermod -aG www-data ubuntu
```

---

## 9. GitHub Actions Secrets

Go to **GitHub → Repository → Settings → Secrets and variables → Actions** and add:

| Secret name        | Value                                      |
|--------------------|--------------------------------------------|
| `EC2_SSH_KEY`      | Contents of your `.pem` private key file   |
| `EC2_HOST`         | EC2 public IP or domain                    |
| `EC2_USER`         | `ubuntu`                                   |
| `EC2_DEPLOY_PATH`  | `/var/www/html`                            |
| `DB_HOST`          | RDS endpoint                               |
| `DB_PORT`          | `3306`                                     |
| `DB_DATABASE`      | Database name                              |
| `DB_USERNAME`      | DB username                                |
| `DB_PASSWORD`      | DB password                                |

> **EC2_SSH_KEY format**: Copy the full contents of the `.pem` file including `-----BEGIN RSA PRIVATE KEY-----` and `-----END RSA PRIVATE KEY-----` lines.

---

## 10. First Deployment

### Option A — Trigger via GitHub Actions
Push to the `develop` branch. The workflow will:
1. Run PHPUnit tests against the real DB
2. Build frontend assets (Vite)
3. rsync files to EC2
4. Run `scripts/deploy.sh` on the server

### Option B — Manual test run on the server
```bash
ssh -i your-key.pem ubuntu@<EC2_PUBLIC_IP>
cd /var/www/html
bash scripts/deploy.sh
```

### Verify services are running
```bash
sudo systemctl status php8.5-fpm
sudo systemctl status nginx
sudo systemctl status laravel-queue
sudo systemctl status chatbot
sudo systemctl status ml-api
```

### Check Laravel is reachable
```bash
curl -I http://YOUR_DOMAIN_OR_IP
# Should return HTTP/1.1 200 OK or 302 Found
```

---

## 11. Troubleshooting

### 502 Bad Gateway from Nginx
PHP-FPM is not running or socket path is wrong.
```bash
sudo systemctl status php8.5-fpm
ls /run/php/  # should show php8.5-fpm.sock
```

### 500 Server Error
Check Laravel logs:
```bash
tail -f /var/www/html/storage/logs/laravel.log
```

### Permission denied on storage/
```bash
sudo chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
sudo chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

### Composer not found in deploy script
```bash
which composer   # should return /usr/local/bin/composer
```
If missing, reinstall:
```bash
curl -sS https://getcomposer.org/installer | php8.5
sudo mv composer.phar /usr/local/bin/composer
```

### SSH key error in GitHub Actions ("error in libcrypto")
The secret must be written with a trailing newline. The workflow uses:
```yaml
printf '%s\n' "${{ secrets.EC2_SSH_KEY }}" > ~/.ssh/deploy_key
```
Make sure `EC2_SSH_KEY` contains the full PEM content (no extra blank lines at the end).

### Queue worker not starting
```bash
sudo journalctl -u laravel-queue -n 50
```
Common cause: `.env` not present or `APP_KEY` not set.

### Chatbot or ML API not starting
```bash
sudo journalctl -u chatbot -n 50
sudo journalctl -u ml-api -n 50
```
Common cause: Python venv not created yet — the first deploy run will create it automatically via `deploy.sh`.

### apt-get returns 503 from EC2 regional mirror
Switch to the main archive:
```bash
sudo sed -i 's|http://ap-southeast-1.ec2.archive.ubuntu.com/ubuntu|http://archive.ubuntu.com/ubuntu|g' \
  /etc/apt/sources.list.d/*.sources /etc/apt/sources.list 2>/dev/null || true
sudo apt-get update
```

### ondrej PPA fails with "Connection refused"
Ubuntu "resolute" is not an LTS release — the ondrej PPA does not support it.
Use the PHP version available in Ubuntu's default repos (`php8.5`) instead.
