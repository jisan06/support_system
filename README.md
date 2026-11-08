# Support Ticket & SLA Management System

A robust, enterprise-ready Support Ticket and SLA Management System built with **Laravel 12**, featuring dynamic Role-Based Access Control (RBAC), workload-based automatic ticket assignment, configurable SLA deadline tracking with business hours, real-time activity audit logging, and isolated customer and admin portals.

---

## 📖 Documentation Quick Links

| 🚀 [Installation Guide](docs/installation.md) | 🔑 [Demo Accounts](docs/demo-accounts.md) | ⚡ [Core Features](docs/features.md) | 🔄 [Application Flow](docs/application-flow.md) | 🧪 [Testing Guide](docs/testing-guide.md) |
| :---: | :---: | :---: | :---: | :---: |

---

## 💡 What Problem This Solves

Customer support teams often struggle with:
- **Mismanaged SLA Milestones**: Missing critical response or resolution windows due to fixed clocks that ignore working schedules, weekends, or regional business hours.
- **Unbalanced Workloads**: Overburdening specific agents while others sit idle.
- **Accidental Information Leaks**: Leaking confidential internal discussions, staff assessments, or private notes to end customers.
- **Rigid Role Hierarchies**: Hardcoded role names (`Agent`, `Admin`) that prevent organizations from creating custom support tiers or departmental structures.
- **Audit Deficits**: Lacking traceable, tamper-evident logs for ticket lifecycle events, priority escalations, and status transitions.

This application provides a flexible, auditable, and automated support platform tailored to resolve these challenges out of the box.

---

## 🛠️ Technology Stack

- **Framework**: [Laravel 12](https://laravel.com/) (PHP ^8.2)
- **Frontend / Asset Bundling**: [Blade](https://laravel.com/docs/blade), [Bootstrap 5](https://getbootstrap.com/), [Tailwind CSS](https://tailwindcss.com/), [Vite 5](https://vitejs.dev/)
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Breeze & Session Authentication
- **Authorization**: Dynamic RBAC (Dynamic roles & granular permission slugs)
- **Packages**:
  - `spatie/laravel-activitylog`: Complete audit trail and timeline logging
  - `spatie/laravel-translatable`: Multilingual content support
  - `yajra/laravel-datatables`: Server-side processing for administrative listings
  - `laravel/reverb`: Real-time notification broadcasting

---

## ⚡ Core Capabilities

- **Automated Workload Balancing**: Automatically assigns incoming tickets to the least active eligible internal agent (`receive_ticket_assignments`).
- **Business-Aware SLA Engine**: Calculates First Response and Resolution deadlines using configurable working days, daily hours, and custom business timezones (e.g., `Asia/Dhaka`).
- **Internal Notes & Confidentiality**: Internal notes and their attachments are strictly isolated from customers and customer activity timelines.
- **Bi-Directional Notifications**: Real-time notifications alert staff to new tickets/replies, while agent replies automatically acknowledge ticket notifications across internal staff.
- **CSV Ticket Import**: Ingest bulk tickets from CSV files with background processing, category mapping, SLA auto-calculation, and row-level error reporting.
- **Dedicated Customer Portal**: Isolated, cross-tenant secure workspace for customers to track, reply to, and reopen their support tickets.

---

## 🏗️ Architecture Overview

```
┌────────────────────────────────────────────────────────────────────────┐
│                          Web / HTTP Layer                              │
│   ├── /customer/*  (Customer Portal: Tickets, Profile, Notifications)  │
│   └── /admin/*     (Admin Desk: Tickets, SLA, Users, Roles, Settings)  │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                        Service & Business Layer                        │
│   ├── TicketService              ├── SlaCalculationService            │
│   ├── TicketAssignmentService    ├── BusinessHoursService             │
│   ├── TicketMessageService       ├── SlaEscalationService             │
│   └── NotificationService        └── TicketImportService              │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                       Data & Persistence Layer                         │
│   ├── MySQL (Tickets, Messages, Media, Settings, Activity Logs, RBAC)  │
│   └── Storage Disk (Encrypted / Local Ticket Attachments & Avatars)    │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 🚀 Quick Start

1. **Clone & Install**:
   ```bash
   git clone https://github.com/jisan06/support_system.git
   cd support_system
   composer install
   ```
2. **Setup Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. **Configure Database & Seed**:
   Configure your database credentials in `.env`, then run:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```
4. **Compile Assets & Serve**:
   ```bash
   npm install
   npm run build
   php artisan serve
   ```

👉 For complete step-by-step setup details, see the **[Installation Guide](docs/installation.md)**.  
👉 For ready-to-use login credentials, see **[Demo Accounts](docs/demo-accounts.md)** *(available after running `php artisan db:seed`)*.

