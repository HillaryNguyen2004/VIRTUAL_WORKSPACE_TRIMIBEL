#!/usr/bin/env bash
# =============================================================================
# ONE-TIME EC2 Server Setup Script
# Run this ONCE on a fresh Ubuntu 22.04 EC2 instance as root or with sudo.
# After this, the automated deploy.sh handles all subsequent deploys.
# =============================================================================
set -euo pipefail

DEPLOY_PATH="${1:-/var/www/html}"
APP_DOMAIN="${2:-your-domain.com}"
PHP_VERSION="8.2"
NODE_VERSION="20"
PYTHON_VERSION="3.11"
DB_NAME="manage_user"
DB_USER="laravel"
DB_PASS="$(openssl rand -base64 20)"

echo "╔════════════════════════════════════════════╗"
echo "║      EC2 Initial Setup — Ubuntu 22.04      ║"
echo "╚════════════════════════════════════════════╝"
echo ""
echo "Deploy path : $DEPLOY_PATH"
echo "App domain  : $APP_DOMAIN"
echo ""

# ── System packages ───────────────────────────────────────────────────────────
echo "▶ Updating system..."
apt-get update -qq && apt-get upgrade -y -qq

echo "▶ Installing base packages..."
apt-get install -y -qq \
  curl wget git unzip zip supervisor cron \
  build-essential software-properties-common \
  nginx certbot python3-certbot-nginx

# ── PHP 8.2 ───────────────────────────────────────────────────────────────────
echo "▶ Installing PHP $PHP_VERSION..."
add-apt-repository -y ppa:ondrej/php
apt-get update -qq
apt-get install -y -qq \
  php${PHP_VERSION}-fpm \
  php${PHP_VERSION}-cli \
  php${PHP_VERSION}-mysql \
  php${PHP_VERSION}-pgsql \
  php${PHP_VERSION}-redis \
  php${PHP_VERSION}-gd \
  php${PHP_VERSION}-zip \
  php${PHP_VERSION}-mbstring \
  php${PHP_VERSION}-bcmath \
  php${PHP_VERSION}-xml \
  php${PHP_VERSION}-curl \
  php${PHP_VERSION}-intl \
  php${PHP_VERSION}-imagick

# Raise PHP upload limits
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 512M/' /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 520M/'             /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i 's/memory_limit = .*/memory_limit = 512M/'               /etc/php/${PHP_VERSION}/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 600/'    /etc/php/${PHP_VERSION}/fpm/php.ini

# ── Composer ──────────────────────────────────────────────────────────────────
echo "▶ Installing Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ── MySQL 8.0 ─────────────────────────────────────────────────────────────────
echo "▶ Installing MySQL 8.0..."
apt-get install -y -qq mysql-server
systemctl enable mysql && systemctl start mysql

mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL

echo ""
echo "╔══════════════════════════════════════════╗"
echo "║  SAVE THESE DATABASE CREDENTIALS NOW:    ║"
echo "║  DB_USER: $DB_USER                       ║"
echo "║  DB_PASS: $DB_PASS                       ║"
echo "╚══════════════════════════════════════════╝"
echo ""

# ── Redis ─────────────────────────────────────────────────────────────────────
echo "▶ Installing Redis..."
apt-get install -y -qq redis-server
systemctl enable redis-server && systemctl start redis-server

# ── Node.js 20 ────────────────────────────────────────────────────────────────
echo "▶ Installing Node.js $NODE_VERSION..."
curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash -
apt-get install -y -qq nodejs

# ── Python 3.11 ───────────────────────────────────────────────────────────────
echo "▶ Installing Python $PYTHON_VERSION..."
apt-get install -y -qq \
  python${PYTHON_VERSION} \
  python${PYTHON_VERSION}-venv \
  python${PYTHON_VERSION}-dev \
  python3-pip

# ── Application directory ─────────────────────────────────────────────────────
echo "▶ Creating application directory..."
mkdir -p "$DEPLOY_PATH"
chown -R www-data:www-data "$DEPLOY_PATH"

# Add deploy user to www-data so it can write
usermod -aG www-data ubuntu 2>/dev/null || true

# ── Nginx virtual host ────────────────────────────────────────────────────────
echo "▶ Configuring Nginx..."
cat > /etc/nginx/sites-available/laravel <<NGINX
server {
    listen 80;
    server_name $APP_DOMAIN www.$APP_DOMAIN;
    root $DEPLOY_PATH/public;
    index index.php;

    # Upload size limit (must match PHP)
    client_max_body_size 520M;

    # Chatbot proxy (FastAPI on :8002)
    location /api/chat-bot {
        proxy_pass         http://127.0.0.1:8002;
        proxy_http_version 1.1;
        proxy_set_header   Host \$host;
        proxy_set_header   X-Real-IP \$remote_addr;
        proxy_read_timeout 600s;
        proxy_send_timeout 600s;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass   unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include        fastcgi_params;
        fastcgi_read_timeout 600s;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static asset caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
NGINX

ln -sf /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/laravel
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# ── Copy systemd service files ────────────────────────────────────────────────
echo "▶ Installing systemd services..."
cp "$DEPLOY_PATH/scripts/services/laravel-queue.service"  /etc/systemd/system/
cp "$DEPLOY_PATH/scripts/services/chatbot.service"        /etc/systemd/system/
cp "$DEPLOY_PATH/scripts/services/ml-api.service"        /etc/systemd/system/
cp "$DEPLOY_PATH/scripts/services/whitebophir.service"   /etc/systemd/system/

sed -i "s|/var/www/html|$DEPLOY_PATH|g" /etc/systemd/system/laravel-queue.service
sed -i "s|/var/www/html|$DEPLOY_PATH|g" /etc/systemd/system/chatbot.service
sed -i "s|/var/www/html|$DEPLOY_PATH|g" /etc/systemd/system/ml-api.service
sed -i "s|/var/www/html|$DEPLOY_PATH|g" /etc/systemd/system/whitebophir.service

systemctl daemon-reload
systemctl enable laravel-queue chatbot ml-api whitebophir

# ── ETL cron job ──────────────────────────────────────────────────────────────
echo "▶ Setting up ETL cron (every 6 hours)..."
(crontab -u www-data -l 2>/dev/null; echo "0 */6 * * * cd $DEPLOY_PATH/etl && $DEPLOY_PATH/chatbot_service/.venv/bin/python incremental_etl.py >> /var/log/etl.log 2>&1") | crontab -u www-data -

# ── Laravel Scheduler ─────────────────────────────────────────────────────────
echo "▶ Setting up Laravel scheduler cron..."
(crontab -u www-data -l 2>/dev/null; echo "* * * * * cd $DEPLOY_PATH && php8.2 artisan schedule:run >> /dev/null 2>&1") | crontab -u www-data -

# ── sudo permissions for deploy user ─────────────────────────────────────────
echo "▶ Granting deploy-time sudo permissions..."
cat > /etc/sudoers.d/deploy-services <<SUDOERS
ubuntu ALL=(ALL) NOPASSWD: /bin/systemctl restart php${PHP_VERSION}-fpm
ubuntu ALL=(ALL) NOPASSWD: /bin/systemctl restart laravel-queue
ubuntu ALL=(ALL) NOPASSWD: /bin/systemctl restart chatbot
ubuntu ALL=(ALL) NOPASSWD: /bin/systemctl restart ml-api
ubuntu ALL=(ALL) NOPASSWD: /bin/systemctl restart whitebophir
SUDOERS
chmod 440 /etc/sudoers.d/deploy-services

echo ""
echo "✅  Server setup complete!"
echo ""
echo "Next steps:"
echo "  1. Place your .env at $DEPLOY_PATH/.env"
echo "  2. Place your SSH public key in ~/.ssh/authorized_keys"
echo "  3. Run: certbot --nginx -d $APP_DOMAIN   (for HTTPS)"
echo "  4. Set GitHub secrets (see scripts/deploy.sh header)"
echo ""
