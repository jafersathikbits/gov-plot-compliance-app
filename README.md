# Government Plot Compliance App

A Laravel web application for managing government industrial plot allotments, compliance milestones, evidence submissions, incentive and refund claims, audit trails, and legal escalation workflows.

## Overview

The Government Plot Compliance App provides role-based workflows for administrators, district officers, inspection officers, and allottees. Cases can be created from policy templates, compliance milestones can be tracked against due dates, and officers can review submitted evidence. Scheduled checks help identify approaching deadlines and overdue milestones.

The application uses two databases:

- **SQLite:** Stores relational application data such as users, roles and permissions, cache, and queue records.
- **MongoDB:** Stores domain records such as plots, allotment cases, milestones, submissions, incentive claims, policy templates, and audit logs.

The application is intended as a demonstration of compliance workflow management. Production use requires appropriate security, data protection, operational controls, and validation against the applicable policies and legal requirements.

## Key Features

- Plot and allotment-case setup using policy templates.
- Milestone-based compliance tracking and due-date monitoring.
- Evidence submission and officer review, including acceptance or return with remarks.
- Role-based dashboards and review queues.
- Incentive and refund claim workflows.
- PDF reports, claim memos, and case certificates.
- Audit logging and case lifecycle tracking.
- Legal notices, extensions, legal review, and enforcement workflows.
- Scheduled milestone checks with optional email and Telegram notifications.
- Responsive interface with search, filters, and pagination.

## Tech Stack

- **Backend:** PHP 8.2+, Laravel 12
- **Relational database:** SQLite
- **Document/domain database:** MongoDB using `mongodb/laravel-mongodb`
- **Frontend:** Blade, Vite, Tailwind CSS, Alpine.js, Chart.js
- **Authorization:** Spatie Laravel Permission
- **PDF generation:** barryvdh/laravel-dompdf
- **Authentication scaffolding:** Laravel Breeze
- **Testing:** PHPUnit through Laravel's testing tools
- **Dependency management:** Composer and npm

## System Architecture

The application combines Laravel's web and authentication features with a dual-database design.

1. **Authentication:** Users sign in through Laravel's authentication workflows.
2. **Authorization:** Role-based permissions control access to administrative and compliance actions.
3. **Case management:** Authorized users create and manage allotment cases using policy templates.
4. **Milestone tracking:** Case milestones are monitored against due dates.
5. **Evidence review:** Allottees submit supporting evidence, and authorized officers review submissions.
6. **Claims and legal workflows:** Authorized staff process claims, generate documents, and manage legal escalation actions.
7. **Scheduled monitoring:** A scheduled command checks milestones and raises applicable warnings or overdue alerts.
8. **Data storage:** SQLite stores relational application data, while MongoDB stores the principal compliance-domain records.

The exact deployment topology and data consistency requirements should be reviewed before production use.

## Project Structure

```text
gov-plot-compliance-app/
├── app/
│   ├── Console/Commands/       # Scheduled milestone checker
│   ├── Http/Controllers/       # Web and authentication controllers
│   ├── Http/Requests/          # Request validation
│   ├── Models/                 # User and compliance-domain models
│   ├── Services/               # Case lifecycle, audit, notifications
│   ├── Support/                # Shared helpers
│   └── View/Components/        # Blade layout components
├── bootstrap/                  # Laravel bootstrap and scheduling
├── config/                     # Application configuration
├── database/
│   ├── factories/              # Model factories
│   ├── migrations/             # Relational schema migrations
│   └── seeders/                # Demo data and roles
├── public/                     # Web root and compiled assets
├── resources/
│   ├── css/                    # Stylesheets
│   ├── js/                     # Frontend JavaScript
│   └── views/                  # Blade templates
├── routes/                     # Web and authentication routes
├── storage/                    # Runtime files and logs
├── tests/                      # PHPUnit tests
├── .env.example                # Environment template
├── artisan                     # Laravel command-line entry point
├── composer.json               # PHP dependencies
├── package.json                # Frontend dependencies
└── vite.config.js              # Frontend build configuration
```

## Prerequisites

- PHP 8.2 or newer, with Laravel-required extensions, including `ext-mongodb` and `pdo_sqlite`.
- Composer 2.
- A reachable MongoDB instance, local or hosted.
- Node.js and npm if rebuilding frontend assets or running the Vite development server.
- Git.

The GD extension is recommended for PDF-related functionality.

## Installation and Setup

Run these commands from the project root.

### 1. Install dependencies

```bash
composer install
npm ci
```

### 2. Configure the environment

Create your local environment file:

```bash
# macOS/Linux
cp .env.example .env

# Windows PowerShell
Copy-Item .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 3. Create the SQLite database

```bash
# macOS/Linux
touch database/database.sqlite
```

For Windows PowerShell:

```powershell
New-Item -ItemType File -Force database/database.sqlite
```

### 4. Configure MongoDB

Edit `.env` and set `MONGODB_URI` and `MONGODB_DATABASE` to match your MongoDB instance. Keep credentials private and never commit your real `.env` file.

### 5. Run migrations and optional seed data

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
```

The seeder creates demonstration data and requires the relevant database services to be configured. Review the seeder before using it outside a local development environment.

Compiled frontend assets are included in `public/build/`. If you change the frontend source, rebuild them with:

```bash
npm run build
```

## Environment Variables

Copy `.env.example` to `.env` and configure the values for your environment.

| Variable | Purpose |
|---|---|
| `APP_NAME` | Application display name |
| `APP_URL` | Base application URL |
| `APP_ENV` | Application environment |
| `APP_DEBUG` | Debug mode; disable in production |
| `APP_KEY` | Application encryption key generated with Artisan |
| `DB_CONNECTION` | Relational database connection; use `sqlite` for the included setup |
| `MONGODB_URI` | MongoDB connection URI |
| `MONGODB_DATABASE` | MongoDB database name |
| `SESSION_DRIVER` | Session storage driver |
| `CACHE_STORE` | Cache backend |
| `QUEUE_CONNECTION` | Queue backend |
| `MAIL_*` | Mail transport and sender configuration |
| `TELEGRAM_BOT_TOKEN` | Optional Telegram bot token |
| `TELEGRAM_CHAT_ID` | Optional Telegram destination |

The example configuration uses SQLite for the relational connection and a local MongoDB URI. Review all environment values before deploying.

The example mail configuration uses the `log` mailer, which writes messages to application logs instead of sending them. Configure a real mail transport if email notifications are required.

## Usage

### Start the application

```bash
php artisan serve
```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000) in your browser.

For frontend development with hot reload, start the Vite development server in a second terminal:

```bash
npm run dev
```

### Demonstration accounts

Running `php artisan db:seed` creates demonstration accounts for roles such as super administrator, state administrator, district officer, inspection officer, and allottee.

The demo accounts share a password defined in `database/seeders/DatabaseSeeder.php`. Inspect the seeder to find the configured value. Change or remove all demonstration credentials before exposing the application beyond local development.

### Milestone checker

Run the milestone checker manually:

```bash
php artisan compliance:check-milestones
```

The command is registered with Laravel's scheduler to run daily at 09:00. During local development, the scheduler can be run with:

```bash
php artisan schedule:work
```

For deployment, configure the system scheduler to invoke Laravel's scheduler as described below.

### Tests

Run the available PHPUnit tests with:

```bash
php artisan test
```

The test configuration uses an in-memory SQLite database. Tests involving MongoDB-backed models or workflows may require a running MongoDB instance and additional configuration.

## Deployment

This is a server-rendered Laravel application that requires PHP, SQLite, MongoDB, and writable runtime storage. It cannot be deployed as a static site on GitHub Pages.

A typical deployment involves:

1. Provisioning PHP 8.2 or newer with the required extensions, including MongoDB and SQLite support.
2. Provisioning MongoDB and configuring the application to connect to it securely.
3. Setting production environment variables, including `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY`, `APP_URL`, `MONGODB_URI`, and `MONGODB_DATABASE`.
4. Installing production dependencies:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

5. Building frontend assets if needed:

   ```bash
   npm ci
   npm run build
   ```

6. Creating the SQLite database file and running migrations:

   ```bash
   php artisan migrate --force
   ```

7. Configuring the web server document root to point to `public/`, and ensuring `storage/` and `bootstrap/cache/` are writable.
8. Creating the storage link:

   ```bash
   php artisan storage:link
   ```

9. Configuring the scheduler to run every minute:

   ```cron
   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
   ```

10. Running a queue worker under a process manager if the database queue is used.

Back up SQLite and MongoDB separately. Do not run the demo seeder unchanged in production. Restrict access to uploaded evidence, generated documents, audit records, and administrative workflows. Review logging, access control, retention, backups, and recovery procedures before handling real data.

## Future Improvements

- Expand automated tests for case lifecycle, permissions, milestone escalation, and claims.
- Add production-ready monitoring and structured operational logging.
- Improve notification delivery, retry handling, and failure reporting.
- Add documented backup and restore procedures for both databases.
- Strengthen security reviews for uploads, access control, and sensitive records.
- Improve deployment automation and environment-specific configuration.
- Add workflow and API documentation for maintainers.
- Validate policy templates and legal workflows with the relevant domain experts before real-world use.

## Contributing

1. Create a feature branch for your changes.
2. Keep changes focused and follow existing Laravel conventions.
3. Run `php artisan test` and, if frontend files change, `npm run build`.
4. Never commit `.env`, production credentials, real allottee records, private evidence, or generated runtime data.
5. Describe the change and how you verified it when opening a pull request.

## License

A standalone `LICENSE` file has not yet been added. The project’s `composer.json` currently declares MIT, but repository-level licensing should be finalized before redistribution. A license file will be added when licensing is finalized across the project repositories.
