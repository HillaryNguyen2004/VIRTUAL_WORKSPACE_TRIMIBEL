#!/usr/bin/env bash
# =============================================================================
# ONE-TIME Homelab Setup Script (Vagrant VM / Ubuntu 22.04)
# Assumes already installed: PHP 8.2, Python 3.10, Python 3.11, Node 20, Ollama
# Run as root or with sudo inside the Vagrant VM.
# =============================================================================
set -euo pipefail

DEPLOY_PATH="${1:-/var/www/html}"
APP_DOMAIN="${2:-homelab.local}"
PHP_VERSION="8.2"
DEPLOY_USER="${SUDO_USER:-vagrant}"
DB_NAME="manage_user"
DB_USER="laravel"
DB_PASS="$(openssl rand -base64 20)"

echo "╔════════════════════════════════════════════╗"
echo "║    Homelab Initial Setup — Ubuntu 22.04    ║"
echo "╚════════════════════════════════════════════╝"
echo ""
echo "Deploy path  : $DEPLOY_PATH"
echo "App domain   : $APP_DOMAIN"
echo "Deploy user  : $DEPLOY_USER"
echo "PHP version  : $PHP_VERSION"
echo ""

# ── System packages ───────────────────────────────────────────────────────────
echo "▶ Updating system..."
apt-get update -qq && apt-get upgrade -y -qq

echo "▶ Installing base packages..."
apt-get install -y -qq \
  curl wget git unzip zip cron \
  build-essential software-properties-common \
  nginx rsync

# ── PHP 8.2 extensions (PHP itself already installed) ────────────────────────
echo "▶ Installing PHP $PHP_VERSION extensions..."
add-apt-repository -y ppa:ondrej/php 2>/dev/null || true
apt-get update -qq
apt-get install -y -qq \
  php${PHP_VERSION}-fpm \
  php${PHP_VERSION}-cli \
  php${PHP_VERSION}-mysql \
  php${PHP_VERSION}-redis \
  php${PHP_VERSION}-gd \
  php${PHP_VERSION}-zip \
  php${PHP_VERSION}-mbstring \
  php${PHP_VERSION}-bcmath \
  php${PHP_VERSION}-xml \
  php${PHP_VERSION}-curl \
  php${PHP_VERSION}-intl \
  php${PHP_VERSION}-pcntl

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
echo "║  DB_DATABASE : $DB_NAME                  ║"
echo "║  DB_USERNAME : $DB_USER                  ║"
echo "║  DB_PASSWORD : $DB_PASS                  ║"
echo "╚══════════════════════════════════════════╝"
echo ""

# ── Redis ─────────────────────────────────────────────────────────────────────
echo "▶ Installing Redis..."
apt-get install -y -qq redis-server
systemctl enable redis-server && systemctl start redis-server

# ── Tailscale ─────────────────────────────────────────────────────────────────
echo "▶ Installing Tailscale..."
curl -fsSL https://tailscale.com/install.sh | sh
echo ""
echo "  Run after setup: sudo tailscale up"
echo "  Then note the Tailscale hostname for HOMELAB_TS_HOST secret."
echo ""

# ── Application directory ─────────────────────────────────────────────────────
echo "▶ Creating application directory..."
mkdir -p "$DEPLOY_PATH"
chown -R "$DEPLOY_USER":www-data "$DEPLOY_PATH"
chmod -R 775 "$DEPLOY_PATH"

# Allow deploy user to write, www-data (PHP-FPM) to read
usermod -aG www-data "$DEPLOY_USER"

# ── Nginx virtual host ────────────────────────────────────────────────────────
echo "▶ Configuring Nginx..."
cat > /etc/nginx/sites-available/laravel <<NGINX
server {
    listen 80;
    server_name $APP_DOMAIN;
    root $DEPLOY_PATH/public;
    index index.php;

    client_max_body_size 520M;

    location = /api/chat-bot/stop {
        rewrite ^ /chat/cancel break;
        proxy_pass         http://127.0.0.1:8002;
        proxy_http_version 1.1;
        proxy_set_header   Host \$host;
        proxy_set_header   X-Real-IP \$remote_addr;
        proxy_read_timeout 10s;
        proxy_buffering    off;
    }

    location /api/chat-bot {
        rewrite ^/api/chat-bot(/.*)$ /chat\$1 break;
        proxy_pass         http://127.0.0.1:8002;
        proxy_http_version 1.1;
        proxy_set_header   Host \$host;
        proxy_set_header   X-Real-IP \$remote_addr;
        proxy_set_header   Connection '';
        proxy_read_timeout 600s;
        proxy_send_timeout 600s;
        proxy_buffering    off;
        proxy_cache        off;
        chunked_transfer_encoding on;
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

    location ~ /\.(?!well-known).* { deny all; }

    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
NGINX

ln -sf /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/laravel
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# ── Systemd service files ─────────────────────────────────────────────────────
echo "▶ Installing systemd services..."
for SVC in laravel-queue chatbot ml-api whitebophir face-detection; do
  SRC="$DEPLOY_PATH/scripts/services/${SVC}.service"
  DST="/etc/systemd/system/${SVC}.service"
  if [ -f "$SRC" ]; then
    sed \
      -e "s|User=ubuntu|User=$DEPLOY_USER|g" \
      -e "s|Group=ubuntu|Group=$DEPLOY_USER|g" \
      -e "s|php8\.[0-9][0-9]*|php${PHP_VERSION}|g" \
      -e "s|/var/www/html|$DEPLOY_PATH|g" \
      "$SRC" > "$DST"
    echo "  installed ${SVC}.service"
  fi
done

systemctl daemon-reload
systemctl enable laravel-queue chatbot ml-api face-detection

# ── sudo permissions for deploy user ─────────────────────────────────────────
echo "▶ Granting deploy-time sudo permissions..."
cat > /etc/sudoers.d/deploy-services <<SUDOERS
$DEPLOY_USER ALL=(ALL) NOPASSWD: /bin/systemctl restart php${PHP_VERSION}-fpm
$DEPLOY_USER ALL=(ALL) NOPASSWD: /bin/systemctl restart laravel-queue
$DEPLOY_USER ALL=(ALL) NOPASSWD: /bin/systemctl restart chatbot
$DEPLOY_USER ALL=(ALL) NOPASSWD: /bin/systemctl restart ml-api
$DEPLOY_USER ALL=(ALL) NOPASSWD: /bin/systemctl restart whitebophir
$DEPLOY_USER ALL=(ALL) NOPASSWD: /bin/systemctl restart face-detection
$DEPLOY_USER ALL=(ALL) NOPASSWD: /bin/systemctl daemon-reload
$DEPLOY_USER ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx
$DEPLOY_USER ALL=(ALL) NOPASSWD: /usr/sbin/nginx -t
$DEPLOY_USER ALL=(ALL) NOPASSWD: /bin/cp $DEPLOY_PATH/scripts/nginx/laravel.conf /etc/nginx/sites-available/laravel
$DEPLOY_USER ALL=(ALL) NOPASSWD: /bin/cp * /etc/systemd/system/*.service
$DEPLOY_USER ALL=(ALL) NOPASSWD: /bin/chown -R * storage *
$DEPLOY_USER ALL=(ALL) NOPASSWD: /bin/chmod -R * storage *
$DEPLOY_USER ALL=(ALL) NOPASSWD: /bin/chown -R * bootstrap/cache
$DEPLOY_USER ALL=(ALL) NOPASSWD: /bin/chmod -R * bootstrap/cache
SUDOERS
chmod 440 /etc/sudoers.d/deploy-services

# ── SSH: add GitHub Actions deploy key ───────────────────────────────────────
echo ""
echo "▶ SSH setup..."
SSH_DIR="/home/$DEPLOY_USER/.ssh"
mkdir -p "$SSH_DIR"
chmod 700 "$SSH_DIR"
touch "$SSH_DIR/authorized_keys"
chmod 600 "$SSH_DIR/authorized_keys"
chown -R "$DEPLOY_USER":"$DEPLOY_USER" "$SSH_DIR"
echo ""
echo "  Paste your GitHub Actions deploy public key into:"
echo "  $SSH_DIR/authorized_keys"
echo ""

# ── Cron jobs ─────────────────────────────────────────────────────────────────
echo "▶ Setting up cron jobs..."
(crontab -u www-data -l 2>/dev/null; echo "0 */6 * * * cd $DEPLOY_PATH/etl && $DEPLOY_PATH/chatbot_service/.venv/bin/python incremental_etl.py >> /var/log/etl.log 2>&1") | crontab -u www-data -
(crontab -u www-data -l 2>/dev/null; echo "* * * * * cd $DEPLOY_PATH && php${PHP_VERSION} artisan schedule:run >> /dev/null 2>&1") | crontab -u www-data -

echo ""
echo "✅  Homelab setup complete!"
echo ""
echo "Next steps:"
echo "  1. sudo tailscale up  (then copy hostname → HOMELAB_TS_HOST secret)"
echo "  2. Place .env at $DEPLOY_PATH/.env"
echo "  3. Add deploy public key to $SSH_DIR/authorized_keys"
echo "  4. Add GitHub secrets: HOMELAB_SSH_KEY, HOMELAB_TS_HOST, HOMELAB_USER ($DEPLOY_USER),"
echo "     HOMELAB_DEPLOY_PATH ($DEPLOY_PATH), TS_OAUTH_CLIENT_ID, TS_OAUTH_SECRET"
echo ""
