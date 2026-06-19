# Finance Bot

Finance Bot is a personal finance application built with Laravel 12. It lets you record daily income and expenses through a Telegram Bot, then review your cashflow, categories, budgets, and reports from a modern web dashboard.

The project is designed as a simple personal-use monolith with clean service separation, production-oriented security controls, and a code structure that can scale toward future AI-assisted finance features.

---

## Project Overview

Finance Bot solves a common personal finance problem: transaction tracking is often too slow or too manual to become a habit.

Instead of opening a dedicated finance app, you can send short Telegram messages such as:

```text
nasi padang 15k
kopi 25000
gaji 5000000
parkir 5000
```

The bot parses the amount and description, creates a transaction draft, learns category patterns from your confirmation, and stores finalized transactions. The web dashboard provides summaries, charts, reports, budget monitoring, settings, and operational logs.

---

## Features

### Telegram Bot

- Telegram webhook endpoint with secret URL protection.
- Authorized Telegram Chat ID whitelist.
- Transaction parsing from natural short text.
- Amount parsing for formats such as `15k`, `15rb`, `15ribu`, `5jt`, and normal numbers.
- Draft transaction flow before final save.
- Category selection flow when category is unknown.
- Smart category learning without AI.
- Category confidence scoring and selection reason.
- Telegram budget alerts at 80% and 100% usage.

### Dashboard

- Modern responsive dashboard using Tailwind CSS.
- Dark mode support.
- Mobile-first navigation.
- Monthly income, expense, running balance, and savings summary.
- Cashflow chart.
- Top spending categories.
- Recent transactions.
- Transactions page.
- Categories page.
- Budget management page.
- Reports page.
- Settings page.

### Reporting

- Daily report.
- Weekly report.
- Monthly report.
- Yearly report.
- Line Chart for cashflow trend.
- Bar Chart for category spending.
- Pie Chart for spending distribution.
- Largest category analysis.
- Expense trend analysis.
- Daily average expense.
- Monthly average expense.

### Budget Management

- Monthly budget per category.
- Optional total monthly budget.
- Budget usage progress tracking.
- Telegram alert when usage reaches 80%.
- Telegram warning when usage reaches 100%.
- Duplicate alert prevention per budget, month, and threshold.

### Security & Operations

- CSRF protection for dashboard forms.
- Telegram webhook CSRF exception only for the webhook path.
- Rate limiting for dashboard, budget form, and Telegram webhook.
- Security headers middleware.
- Basic Auth protection for dashboard in production.
- Audit logs.
- Activity logs.
- Error logging.
- Request ID tracking.
- SQL injection protection through Eloquent/query bindings.
- XSS protection through Blade escaping, validation, sanitization, and CSP.

---

## Tech Stack

- **Backend:** Laravel 12
- **Language:** PHP 8.2+
- **Database:** MySQL
- **Frontend:** Blade, Tailwind CSS 4, Vite
- **Charts:** Chart.js
- **Bot:** Telegram Bot API
- **Telegram SDK:** `irazasyed/telegram-bot-sdk`
- **Development OS:** WSL Ubuntu 24.04 recommended
- **Formatter:** Laravel Pint
- **Testing Framework:** PHPUnit via Laravel test runner

---

## Installation Guide

### 1. Clone or open the project

```bash
cd /path/to/finance-bot
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install frontend dependencies

```bash
npm install
```

### 4. Create environment file

```bash
cp .env.example .env
```

### 5. Generate application key

```bash
php artisan key:generate
```

### 6. Configure `.env`

Update database, Telegram, dashboard, and security settings. See the Environment Variables section below.

### 7. Run migrations and seeders

```bash
php artisan migrate --seed
```

### 8. Build frontend assets

For development:

```bash
npm run dev
```

For production build:

```bash
npm run build
```

### 9. Start Laravel locally

```bash
php artisan serve
```

Open:

```text
http://127.0.0.1:8000/dashboard
```

---

## Environment Variables

Below are the important environment variables for this project.

### Application

```env
APP_NAME="Finance Bot"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta
```

For production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

### Database

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=finance_bot
DB_USERNAME=root
DB_PASSWORD=
```

### Dashboard Access

Dashboard access is protected with Basic Auth when `DASHBOARD_PASSWORD` is set.

```env
DASHBOARD_USERNAME=owner
DASHBOARD_PASSWORD=change-this-to-a-strong-password
```

In local development, dashboard access can work without `DASHBOARD_PASSWORD`. In production, always set it.

### Telegram

```env
TELEGRAM_BOT_TOKEN=123456789:your-bot-token
TELEGRAM_WEBHOOK_SECRET=change-this-to-a-random-secret
TELEGRAM_ALLOWED_CHAT_ID=123456789
TELEGRAM_ALLOWED_CHAT_IDS=123456789,987654321
```

Notes:

- `TELEGRAM_ALLOWED_CHAT_ID` supports a single chat ID.
- `TELEGRAM_ALLOWED_CHAT_IDS` supports multiple comma-separated chat IDs.
- If both are present, the application reads the multi-ID format with the single ID as fallback.

### Owner Seeder

```env
APP_OWNER_NAME="Finance Owner"
APP_OWNER_EMAIL=owner@example.com
```

### Logging

```env
LOG_CHANNEL=daily
LOG_LEVEL=info
LOG_DAILY_DAYS=14
SECURITY_LOG_LEVEL=warning
SECURITY_LOG_DAYS=90
AUDIT_LOG_LEVEL=info
AUDIT_LOG_DAYS=180
```

### Session Security

For HTTPS production deployments:

```env
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

---

## Database Setup

### Create MySQL database

```sql
CREATE DATABASE finance_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Run migrations

```bash
php artisan migrate
```

### Run seeders

```bash
php artisan db:seed
```

Or run both together:

```bash
php artisan migrate --seed
```

### Important Tables

- `users` — dashboard owner account.
- `telegram_users` — authorized Telegram identities.
- `categories` — income and expense categories.
- `transactions` — finalized financial transactions.
- `transaction_drafts` — parsed but not finalized Telegram transactions.
- `transaction_logs` — Telegram processing logs.
- `category_memories` — learned category patterns.
- `monthly_budgets` — monthly category/total budgets.
- `budget_alerts` — Telegram budget alert history.
- `settings` — application settings.
- `audit_logs` — security and business audit trail.
- `activity_logs` — request activity tracking.

---

## Telegram Bot Setup

### 1. Create bot from BotFather

Open Telegram and chat with `@BotFather`.

Create a bot:

```text
/newbot
```

Copy the generated bot token into `.env`:

```env
TELEGRAM_BOT_TOKEN=your-token
```

### 2. Get your Telegram Chat ID

Send any message to your bot, then temporarily inspect updates:

```bash
curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/getUpdates"
```

Find:

```json
"chat": { "id": 123456789 }
```

Set it in `.env`:

```env
TELEGRAM_ALLOWED_CHAT_ID=123456789
```

### 3. Set webhook secret

```env
TELEGRAM_WEBHOOK_SECRET=your-long-random-secret
```

### 4. Register webhook

For production HTTPS URL:

```bash
curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook?url=https://your-domain.com/telegram/webhook/<TELEGRAM_WEBHOOK_SECRET>"
```

For local testing, expose your local app with a tunneling tool such as ngrok or Cloudflare Tunnel, then register that HTTPS URL.

### 5. Test bot input

Send examples to your bot:

```text
nasi padang 15k
kopi 25000
gaji 5000000
parkir 5000
```

If category is unknown, the bot asks:

```text
Pilih kategori:
1. Makanan
2. Transportasi
3. Belanja
4. Hiburan
5. Lainnya
```

After you choose a category, the system stores the category memory for future automatic categorization.

---

## Local Development

### Recommended development command

The project includes the default Laravel dev script:

```bash
composer run dev
```

This starts:

- Laravel development server
- Queue listener
- Laravel Pail logs
- Vite development server

Alternatively, run services manually:

```bash
php artisan serve
npm run dev
```

### Useful commands

```bash
php artisan route:list
php artisan migrate --pretend
php artisan migrate:fresh --seed
php artisan view:clear
php artisan config:clear
php artisan cache:clear
./vendor/bin/pint
npm run build
```

### Dashboard URLs

```text
/dashboard
/transactions
/categories
/budget
/reports
/settings
```

---

## Production Deployment

### 1. Server requirements

- PHP 8.2+
- Composer
- MySQL 8+
- Node.js compatible with Vite 7
- Web server: Nginx or Apache
- HTTPS certificate
- Supervisor or systemd for queues if queues are used

### 2. Production `.env`

Minimum recommended production values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
LOG_CHANNEL=daily
SESSION_SECURE_COOKIE=true
DASHBOARD_USERNAME=owner
DASHBOARD_PASSWORD=strong-random-password
TELEGRAM_BOT_TOKEN=your-token
TELEGRAM_WEBHOOK_SECRET=strong-random-secret
TELEGRAM_ALLOWED_CHAT_IDS=your-chat-id
```

### 3. Install dependencies

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 4. Run database migration

```bash
php artisan migrate --force
```

### 5. Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Set permissions

Ensure the web server can write to:

```text
storage/
bootstrap/cache/
```

Example:

```bash
chmod -R ug+rwx storage bootstrap/cache
```

### 7. Configure web server

Point the document root to:

```text
public/
```

### 8. Register Telegram webhook

```bash
curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/setWebhook?url=https://your-domain.com/telegram/webhook/<TELEGRAM_WEBHOOK_SECRET>"
```

---

## Troubleshooting

### Dashboard returns 401

Dashboard Basic Auth is active.

Check:

```env
DASHBOARD_USERNAME=owner
DASHBOARD_PASSWORD=your-password
```

Use those credentials in the browser Basic Auth prompt.

### Telegram bot does not reply

Check:

```bash
php artisan route:list --path=telegram
```

Verify:

- `TELEGRAM_BOT_TOKEN` is correct.
- `TELEGRAM_WEBHOOK_SECRET` matches the webhook URL.
- `TELEGRAM_ALLOWED_CHAT_ID` or `TELEGRAM_ALLOWED_CHAT_IDS` contains your chat ID.
- Your app is reachable over HTTPS in production.

Check Telegram webhook status:

```bash
curl "https://api.telegram.org/bot<TELEGRAM_BOT_TOKEN>/getWebhookInfo"
```

### Unauthorized Telegram chat

If the bot ignores a message, your chat ID may not be authorized.

Check `.env`:

```env
TELEGRAM_ALLOWED_CHAT_IDS=123456789
```

Also ensure `telegram_users` has an authorized row. The seeder can create one if `TELEGRAM_ALLOWED_CHAT_ID` is set.

### Database migration fails

Check database credentials:

```bash
php artisan migrate --pretend
```

Common causes:

- MySQL service is not running.
- Database does not exist.
- User does not have privileges.
- `.env` has stale cached config.

Clear config:

```bash
php artisan config:clear
```

### Assets not loading

Run:

```bash
npm install
npm run build
```

For local development:

```bash
npm run dev
```

### Logs to inspect

```text
storage/logs/laravel.log
storage/logs/security.log
storage/logs/audit.log
```

Database logs:

- `transaction_logs`
- `audit_logs`
- `activity_logs`
- `budget_alerts`

---

## Folder Structure

Important application folders:

```text
app/
  DTOs/
    Budgets/
    Categories/
    Reports/
    Telegram/
    Transactions/
  Enums/
  Exceptions/
    Telegram/
  Http/
    Controllers/
      Dashboard/
      Telegram/
    Middleware/
    Requests/
  Models/
  Providers/
  Services/
    Budgets/
    Categories/
    Dashboard/
    Reports/
    Security/
    Telegram/
    Transactions/

config/
  security.php
  services.php
  logging.php

database/
  factories/
  migrations/
  seeders/

resources/
  css/
  js/
  views/
    components/
    dashboard/

routes/
  web.php
  console.php
```

### Layering Strategy

The application follows a Laravel monolith architecture with service separation:

```text
HTTP / Telegram Webhook
↓
Controller / Request Validation
↓
Service Layer
↓
DTO / Enum / Model
↓
Database
```

Controllers are intended to stay thin. Business logic belongs in services.

---

## Security Notes

### Implemented Controls

- Dashboard Basic Auth for production access.
- CSRF protection for web forms.
- Telegram webhook secret path.
- Telegram Chat ID whitelist.
- Rate limiting for dashboard, budget form, and Telegram webhook.
- Security headers middleware.
- Audit logging for important events.
- Activity tracking for requests.
- Request ID correlation.
- Error logging with sanitized context.
- SQL injection protection through Eloquent and parameterized queries.
- XSS protection through Blade escaping, input validation, note sanitization, and CSP.

### Production Recommendations

- Always use HTTPS.
- Set `APP_DEBUG=false`.
- Use strong `DASHBOARD_PASSWORD` and `TELEGRAM_WEBHOOK_SECRET`.
- Restrict Telegram usage to known chat IDs.
- Rotate Telegram bot token if exposed.
- Do not commit `.env`.
- Back up the database regularly.
- Review `audit_logs` and `activity_logs` periodically.
- Keep dependencies updated.

---

## Future Roadmap

### Short Term

- Full CRUD forms for transactions and categories.
- Better account/cash wallet support.
- CSV export for reports.
- Recurring transactions.
- Budget history and budget comparison.
- More Telegram commands such as `/today`, `/month`, `/last`, and `/undo`.

### Medium Term

- Multi-account support: cash, bank, e-wallet.
- Advanced filtering and search.
- Scheduled daily and weekly Telegram summaries.
- More detailed audit dashboard.
- Database backup command and retention policy.
- Import from CSV or bank statement.

### Long Term

- Optional AI transaction parser.
- AI category suggestion.
- AI monthly insights.
- Anomaly detection for unusual spending.
- OCR receipt scanning.
- Multi-user SaaS hardening if the project evolves beyond personal use.

---

## Maintenance Checklist

Before deploying changes:

```bash
./vendor/bin/pint
php artisan route:list
php artisan migrate --pretend
php artisan view:cache
npm run build
php artisan view:clear
```

For production deployment:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## License

This project is intended for personal finance use. Add a project-specific license before public distribution.
