# Installation & Local Setup Guide

| 🚀 [Installation Guide](installation.md) | 🔑 [Demo Accounts](demo-accounts.md) | ⚡ [Core Features](features.md) | 🔄 [Application Flow](application-flow.md) | 🧪 [Testing Guide](testing-guide.md) |
| :---: | :---: | :---: | :---: | :---: |

---

Follow these standard Laravel setup steps to install and run **support_system** locally.

---

## 1. System Requirements

Ensure your local development environment meets standard Laravel 12 requirements:

- **PHP**: `^8.2` or `^8.3`
- **Composer**: `^2.2` or newer
- **Node.js & NPM**: `^18.x` or `^20.x` (LTS recommended)
- **Database**: MySQL `8.0+` or MariaDB `10.4+`

---

## 2. Clone the Repository

Clone the repository and navigate into the project directory:

```bash
git clone https://github.com/jisan06/support_system.git
cd support_system
```

---

## 3. Install PHP Dependencies

Install backend dependencies using Composer:

```bash
composer install
```

---

## 4. Environment Configuration

Copy the example environment configuration file to create your `.env` file:

```bash
cp .env.example .env
```

---

## 5. Generate Application Key

Generate the application encryption key:

```bash
php artisan key:generate
```

---

## 6. Configure Database Connection

Open `.env` in your editor and configure the database connection settings to match your local database environment:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

Create the specified database on your local MySQL/MariaDB server using your preferred database administration tool.

---

## 7. Run Database Migrations

Run database migrations to create all required tables:

```bash
php artisan migrate
```

---

## 8. Run Database Seeders

Seed the database with default roles, permissions, settings, and demo user accounts:

```bash
php artisan db:seed
```

> [!IMPORTANT]
> **REMINDER**: The pre-configured Administrator, Support Agent, and Customer demo accounts are populated during this seeding step. You **must run `php artisan db:seed`** before attempting to log in with demo credentials.

> **Tip**: You can also run migrations and seeders together in a single command:
> ```bash
> php artisan migrate --seed
> ```
>
> If you need to reset your local database to a clean default state:
> ```bash
> php artisan migrate:fresh --seed
> ```
> *(Warning: `migrate:fresh` drops all existing tables and data before recreating them).*

---

## 9. Install Frontend Dependencies & Build Assets

Install npm packages and compile frontend assets:

```bash
npm install
npm run build
```

> **Development Mode**: If you are actively modifying CSS or JavaScript assets, run `npm run dev` for Vite hot module replacement.

---

## 10. Start the Laravel Development Server

Start the local PHP development server:

```bash
php artisan serve
```

The application is now accessible at:  
👉 **`http://localhost:8000`**

To log in, see the **[Demo Accounts Guide](demo-accounts.md)** *(Note: Demo accounts are accessible only after running `php artisan db:seed`)*.

---

## 11. Queue Worker & Task Scheduler (Background Tasks)

### Queue Worker
Certain background operations (such as batch CSV ticket imports and email notifications) run via Laravel queues. To process queued jobs locally, run:

```bash
php artisan queue:work
```

*(By default, `.env.example` sets `QUEUE_CONNECTION=sync`, which handles jobs immediately in-memory for local testing. If switched to `database` or another queue driver, run the worker).*

### Task Scheduler
The system includes scheduled tasks to monitor SLA deadlines (`ticket:escalate-sla`) and clean up stale batch imports (`tickets:recover-stale-imports`).

To run scheduled tasks during local development:

```bash
php artisan schedule:work
```

For production deployment, add a single cron entry to your server:

```bash
* * * * * cd /path/to/support_system && php artisan schedule:run >> /dev/null 2>&1
```
