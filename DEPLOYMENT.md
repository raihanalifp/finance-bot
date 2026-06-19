# Production Deployment Guide

This document describes how to deploy **Finance Bot** to a production Ubuntu 24.04 server using:

- Nginx
- PHP 8.3 FPM
- MySQL 8
- Supervisor
- Redis optional
- Certbot SSL
- Telegram Bot Webhook

The guide assumes a single-server deployment suitable for personal production use.

---

## 0. Deployment Assumptions

Replace the placeholders below with your actual values:

```text
DOMAIN=finance.example.com
APP_DIR=/var/www/finance-bot
APP_USER=www-data
DB_NAME=finance_bot
DB_USER=finance_bot_user
DB_PASSWORD=change-this-strong-db-password
```

Recommended DNS setup:

```text
A record: finance.example.com -> your_server_public_ip
```

Server requirements:

- Ubuntu 24.04 LTS
- Root or sudo user
- Public HTTPS-accessible domain
- Telegram Bot token from BotFather

---

## 1. Install Packages

### 1.1 Update server

```bash
sudo apt update
sudo apt upgrade -y
```

### 1.2 Install common tools

```bash
sudo apt install -y \
  curl \
  unzip \
  git \
  software-properties-common \
  ca-certificates \
  gnupg \
  lsb-release \
  ufw \
  htop
```

### 1.3 Install Nginx

```bash
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

### 1.4 Install PHP 8.3 and extensions

Ubuntu 24.04 includes PHP 8.3 packages.

```bash
sudo apt install -y \
  php8.3 \
  php8.3-fpm \
  php8.3-cli \
  php8.3-common \
  php8.3-mysql \
  php8.3-mbstring \
  php8.3-xml \
  php8.3-curl \
  php8.3-zip \
  php8.3-bcmath \
  php8.3-intl \
  php8.3-gd \
  php8.3-redis
```

Enable and start PHP-FPM:

```bash
sudo systemctl enable php8.3-fpm
sudo systemctl start php8.3-fpm
```

Check version:

```bash
php -v
```

### 1.5 Install MySQL 8

```bash
sudo apt install -y mysql-server
sudo systemctl enable mysql
sudo systemctl start mysql
```

Secure MySQL:

```bash
sudo mysql_secure_installation
```

### 1.6 Install Supervisor

```bash
sudo apt install -y supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

### 1.7 Install Redis optional

Redis is optional. Use it if you want Redis-backed cache, sessions, queues, or rate limiting.

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

Check Redis:

```bash
redis-cli ping
```

Expected output:

```text
PONG
```

### 1.8 Install Composer

```bash
cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### 1.9 Install Node.js

Install Node.js LTS. Example using NodeSource:

```bash
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

---

## 2. Clone Repository

Create application directory:

```bash
sudo mkdir -p /var/www
sudo chown -R $USER:$USER /var/www
```

Clone the repository:

```bash
cd /var/www
git clone <your-repository-url> finance-bot
cd /var/www/finance-bot
```

Set ownership:

```bash
sudo chown -R www-data:www-data /var/www/finance-bot
```

During deployment, you may prefer your deploy user owns source files and `www-data` owns only writable directories. A common setup:

```bash
sudo chown -R $USER:www-data /var/www/finance-bot
sudo chmod -R 755 /var/www/finance-bot
sudo chmod -R ug+rwx /var/www/finance-bot/storage /var/www/finance-bot/bootstrap/cache
```

---

## 3. Setup `.env`

Copy environment file:

```bash
cd /var/www/finance-bot
cp .env.example .env
```

Edit `.env`:

```bash
nano .env
```

Recommended production values:

```env
APP_NAME="Finance Bot"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://finance.example.com
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=finance_bot
DB_USERNAME=finance_bot_user
DB_PASSWORD=change-this-strong-db-password

LOG_CHANNEL=daily
LOG_LEVEL=info
LOG_DAILY_DAYS=14
SECURITY_LOG_LEVEL=warning
SECURITY_LOG_DAYS=90
AUDIT_LOG_LEVEL=info
AUDIT_LOG_DAYS=180

DASHBOARD_USERNAME=owner
DASHBOARD_PASSWORD=change-this-strong-dashboard-password

TELEGRAM_BOT_TOKEN=123456789:your-telegram-bot-token
TELEGRAM_WEBHOOK_SECRET=change-this-long-random-secret
TELEGRAM_ALLOWED_CHAT_IDS=123456789

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database
```

If using Redis:

```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Generate application key:

```bash
php artisan key:generate
```

---

## 4. Composer Install

Install production PHP dependencies:

```bash
cd /var/www/finance-bot
composer install --no-dev --optimize-autoloader
```

Install frontend dependencies and build assets:

```bash
npm ci
npm run build
```

Set final permissions:

```bash
sudo chown -R www-data:www-data /var/www/finance-bot/storage /var/www/finance-bot/bootstrap/cache
sudo chmod -R ug+rwx /var/www/finance-bot/storage /var/www/finance-bot/bootstrap/cache
```

---

## 5. Migrate Database

### 5.1 Create database and user

Login to MySQL:

```bash
sudo mysql
```

Create database and user:

```sql
CREATE DATABASE finance_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'finance_bot_user'@'localhost' IDENTIFIED BY 'change-this-strong-db-password';
GRANT ALL PRIVILEGES ON finance_bot.* TO 'finance_bot_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5.2 Test DB connection

```bash
php artisan migrate --pretend
```

### 5.3 Run migrations

```bash
php artisan migrate --force
```

### 5.4 Run seeders optional

Run seeders if this is a fresh installation:

```bash
php artisan db:seed --force
```

### 5.5 Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 6. Configure Nginx

Create Nginx site config:

```bash
sudo nano /etc/nginx/sites-available/finance-bot
```

Paste:

```nginx
server {
    listen 80;
    listen [::]:80;

    server_name finance.example.com;
    root /var/www/finance-bot/public;
    index index.php index.html;

    charset utf-8;
    client_max_body_size 10M;

    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "camera=(), microphone=(), geolocation=(), payment=()" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    access_log /var/log/nginx/finance-bot-access.log;
    error_log /var/log/nginx/finance-bot-error.log;
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/finance-bot /etc/nginx/sites-enabled/finance-bot
```

Disable default site optional:

```bash
sudo rm -f /etc/nginx/sites-enabled/default
```

Test Nginx:

```bash
sudo nginx -t
```

Reload Nginx:

```bash
sudo systemctl reload nginx
```

---

## 7. SSL Certbot

### 7.1 Install Certbot

```bash
sudo apt install -y certbot python3-certbot-nginx
```

### 7.2 Issue SSL certificate

```bash
sudo certbot --nginx -d finance.example.com
```

Choose redirect HTTP to HTTPS when prompted.

### 7.3 Test renewal

```bash
sudo certbot renew --dry-run
```

### 7.4 Verify HTTPS

Open:

```text
https://finance.example.com/dashboard
```

If `DASHBOARD_PASSWORD` is set, the browser will ask for Basic Auth credentials.

---

## 8. Configure Queue Worker

The application currently can run with `QUEUE_CONNECTION=database` or `redis`.

### 8.1 Database queue setup

If using database queue, ensure queue tables exist:

```bash
php artisan migrate --force
```

Set:

```env
QUEUE_CONNECTION=database
```

### 8.2 Redis queue setup optional

Set:

```env
QUEUE_CONNECTION=redis
```

Then refresh config cache:

```bash
php artisan config:cache
```

### 8.3 Create Supervisor config

```bash
sudo nano /etc/supervisor/conf.d/finance-bot-worker.conf
```

Paste:

```ini
[program:finance-bot-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/finance-bot/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/finance-bot/storage/logs/worker.log
stopwaitsecs=3600
```

Reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start finance-bot-worker:*
```

Check status:

```bash
sudo supervisorctl status
```

Restart worker after deployments:

```bash
php artisan queue:restart
sudo supervisorctl restart finance-bot-worker:*
```

---

## 9. Configure Scheduler

Laravel scheduler should run every minute.

Open crontab for web user:

```bash
sudo crontab -u www-data -e
```

Add:

```cron
* * * * * cd /var/www/finance-bot && php artisan schedule:run >> /dev/null 2>&1
```

Verify cron service:

```bash
sudo systemctl status cron
```

If not running:

```bash
sudo systemctl enable cron
sudo systemctl start cron
```

Current project scheduler may be minimal, but this prepares production for future summary, cleanup, and backup jobs.

---

## 10. Configure Telegram Webhook

### 10.1 Confirm environment values

```env
TELEGRAM_BOT_TOKEN=123456789:your-telegram-bot-token
TELEGRAM_WEBHOOK_SECRET=long-random-secret
TELEGRAM_ALLOWED_CHAT_IDS=123456789
```

Refresh config:

```bash
php artisan config:cache
```

### 10.2 Register webhook

```bash
curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook?url=https://finance.example.com/telegram/webhook/<TELEGRAM_WEBHOOK_SECRET>"
```

Expected response:

```json
{"ok":true,"result":true,"description":"Webhook was set"}
```

### 10.3 Check webhook info

```bash
curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/getWebhookInfo"
```

Check:

- `url` is correct.
- `last_error_message` is empty.
- `pending_update_count` is not continuously growing.

### 10.4 Test bot

Send:

```text
nasi padang 15k
```

If the category is unknown, the bot asks for category selection.

### 10.5 Remove webhook if needed

```bash
curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/deleteWebhook"
```

---

## 11. Backup Strategy

Finance data is sensitive. Backups are mandatory.

### 11.1 Database backup directory

```bash
sudo mkdir -p /var/backups/finance-bot/mysql
sudo chown -R root:root /var/backups/finance-bot
sudo chmod -R 700 /var/backups/finance-bot
```

### 11.2 Manual MySQL backup

```bash
mysqldump \
  --single-transaction \
  --quick \
  --lock-tables=false \
  -u finance_bot_user \
  -p finance_bot \
  | gzip > /var/backups/finance-bot/mysql/finance_bot_$(date +%F_%H-%M-%S).sql.gz
```

### 11.3 Automated backup script

Create script:

```bash
sudo nano /usr/local/bin/finance-bot-backup.sh
```

Paste:

```bash
#!/usr/bin/env bash
set -euo pipefail

APP_NAME="finance-bot"
DB_NAME="finance_bot"
DB_USER="finance_bot_user"
BACKUP_DIR="/var/backups/finance-bot/mysql"
TIMESTAMP="$(date +%F_%H-%M-%S)"

mkdir -p "$BACKUP_DIR"

mysqldump \
  --single-transaction \
  --quick \
  --lock-tables=false \
  -u "$DB_USER" \
  -p "$DB_NAME" \
  | gzip > "$BACKUP_DIR/${APP_NAME}_${TIMESTAMP}.sql.gz"

find "$BACKUP_DIR" -type f -name "*.sql.gz" -mtime +30 -delete
```

Make executable:

```bash
sudo chmod +x /usr/local/bin/finance-bot-backup.sh
```

### 11.4 Cron backup

Run daily at 02:30:

```bash
sudo crontab -e
```

Add:

```cron
30 2 * * * /usr/local/bin/finance-bot-backup.sh >> /var/log/finance-bot-backup.log 2>&1
```

### 11.5 Backup verification

List backups:

```bash
sudo ls -lh /var/backups/finance-bot/mysql
```

Test gzip integrity:

```bash
gzip -t /var/backups/finance-bot/mysql/latest-file.sql.gz
```

### 11.6 Restore example

```bash
gunzip < backup.sql.gz | mysql -u finance_bot_user -p finance_bot
```

### 11.7 Offsite backups recommended

For production, copy backups to external storage:

- S3-compatible storage
- Backblaze B2
- Another VPS
- Encrypted external disk

Always encrypt financial backups before sending them offsite.

---

## 12. Monitoring Strategy

### 12.1 Service monitoring

Check core services:

```bash
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status mysql
sudo systemctl status supervisor
sudo supervisorctl status
```

If using Redis:

```bash
sudo systemctl status redis-server
redis-cli ping
```

### 12.2 Application health

Laravel health route:

```bash
curl -I https://finance.example.com/up
```

Expected:

```text
HTTP/2 200
```

### 12.3 Log files

Application logs:

```bash
tail -f /var/www/finance-bot/storage/logs/laravel.log
tail -f /var/www/finance-bot/storage/logs/security.log
tail -f /var/www/finance-bot/storage/logs/audit.log
```

Queue worker log:

```bash
tail -f /var/www/finance-bot/storage/logs/worker.log
```

Nginx logs:

```bash
tail -f /var/log/nginx/finance-bot-access.log
tail -f /var/log/nginx/finance-bot-error.log
```

Backup log:

```bash
tail -f /var/log/finance-bot-backup.log
```

### 12.4 Database operational checks

```bash
mysqladmin -u finance_bot_user -p ping
```

Check database size:

```sql
SELECT table_schema AS db_name,
       ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
FROM information_schema.tables
WHERE table_schema = 'finance_bot'
GROUP BY table_schema;
```

### 12.5 Telegram webhook monitoring

```bash
curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/getWebhookInfo"
```

Watch:

- `last_error_date`
- `last_error_message`
- `pending_update_count`

### 12.6 Security monitoring

Review database tables:

- `audit_logs`
- `activity_logs`
- `transaction_logs`
- `budget_alerts`

Useful checks:

```sql
SELECT action, COUNT(*)
FROM audit_logs
WHERE created_at >= NOW() - INTERVAL 7 DAY
GROUP BY action;

SELECT status_code, COUNT(*)
FROM activity_logs
WHERE created_at >= NOW() - INTERVAL 24 HOUR
GROUP BY status_code;

SELECT status, COUNT(*)
FROM transaction_logs
WHERE created_at >= NOW() - INTERVAL 24 HOUR
GROUP BY status;
```

### 12.7 External monitoring recommended

Use one or more of:

- Uptime Kuma
- Better Stack
- Healthchecks.io
- Grafana + Prometheus
- Netdata

Recommended checks:

- HTTPS uptime for `/up`
- Disk usage
- MySQL availability
- Queue worker status
- Backup freshness
- Telegram webhook error status

---

## Deployment Workflow

For future deployments:

```bash
cd /var/www/finance-bot
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
sudo supervisorctl restart finance-bot-worker:*
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx
```

---

## Firewall Hardening

Enable UFW:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

Only ports `22`, `80`, and `443` should be publicly accessible for this deployment.

---

## Production Checklist

Before going live:

- [ ] DNS points to the server.
- [ ] `.env` uses `APP_ENV=production`.
- [ ] `.env` uses `APP_DEBUG=false`.
- [ ] `APP_URL` uses HTTPS domain.
- [ ] Strong `APP_KEY` generated.
- [ ] Strong `DASHBOARD_PASSWORD` set.
- [ ] Strong `TELEGRAM_WEBHOOK_SECRET` set.
- [ ] `TELEGRAM_ALLOWED_CHAT_IDS` is set.
- [ ] MySQL database and user configured.
- [ ] `php artisan migrate --force` completed.
- [ ] `npm run build` completed.
- [ ] Nginx config passes `nginx -t`.
- [ ] SSL certificate issued.
- [ ] Queue worker active in Supervisor.
- [ ] Scheduler cron installed.
- [ ] Telegram webhook registered.
- [ ] Backup cron installed and tested.
- [ ] Monitoring checks configured.

---

## Troubleshooting

### 502 Bad Gateway

Check PHP-FPM:

```bash
sudo systemctl status php8.3-fpm
sudo tail -f /var/log/nginx/finance-bot-error.log
```

Verify socket:

```bash
ls -lah /run/php/php8.3-fpm.sock
```

### 500 Application Error

Check Laravel logs:

```bash
tail -f /var/www/finance-bot/storage/logs/laravel.log
```

Common fixes:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
sudo chown -R www-data:www-data storage bootstrap/cache
```

### Telegram Webhook Fails

Check webhook status:

```bash
curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/getWebhookInfo"
```

Common causes:

- Wrong webhook secret in URL.
- Server does not have valid HTTPS.
- `TELEGRAM_ALLOWED_CHAT_IDS` does not include your chat ID.
- Nginx cannot reach PHP-FPM.
- `APP_URL` is incorrect.

### Queue Not Processing

```bash
sudo supervisorctl status
sudo supervisorctl restart finance-bot-worker:*
tail -f /var/www/finance-bot/storage/logs/worker.log
```

### Permission Issues

```bash
sudo chown -R www-data:www-data /var/www/finance-bot/storage /var/www/finance-bot/bootstrap/cache
sudo chmod -R ug+rwx /var/www/finance-bot/storage /var/www/finance-bot/bootstrap/cache
```

---

## Notes

This deployment model is intentionally simple and reliable for personal production use. If the application grows into a multi-user SaaS, consider adding:

- Zero-downtime deployments
- Separate database server
- Separate Redis server
- Managed backups
- Centralized logging
- Metrics dashboard
- WAF or Cloudflare
- CI/CD pipeline
