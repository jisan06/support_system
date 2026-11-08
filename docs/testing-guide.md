# Automated Testing Guide

| 🚀 [Installation Guide](installation.md) | 🔑 [Demo Accounts](demo-accounts.md) | ⚡ [Core Features](features.md) | 🔄 [Application Flow](application-flow.md) | 🧪 [Testing Guide](testing-guide.md) |
| :---: | :---: | :---: | :---: | :---: |

---

This application features a comprehensive automated test suite consisting of **320+ tests** covering unit logic, service layers, security policies, dynamic RBAC, database transactions, and end-to-end user journeys.

---

## 1. Test Environment Setup

### Environment Variables
Testing configurations are defined in [`phpunit.xml`](../phpunit.xml):
- `APP_ENV`: `testing`
- `CACHE_STORE`: `array` (in-memory test caching)
- `MAIL_MAILER`: `array` (suppresses actual email sending)
- `QUEUE_CONNECTION`: `sync` (processes queued jobs synchronously)
- `SESSION_DRIVER`: `array`

### Database Setup for Testing
The application uses the `DatabaseTransactions` trait across all feature tests, ensuring all database operations are executed inside database transactions and rolled back automatically at the end of each test.

Ensure your test database is migrated before running feature tests:

```bash
# Prepare the database for tests
php artisan migrate --force
```

> **Note on Vite Assets**: When running tests locally or in headless CI, frontend asset compilation is required so `public/build/manifest.json` is present. Run `npm run build` prior to running full test suites if you encounter `ViteManifestNotFoundException`.

---

## 2. Running the Test Suite

### Run All Tests via Artisan

```bash
php artisan test
```

### Run All Tests via PHPUnit

```bash
php vendor/bin/phpunit
```

---

## 3. Running Focused Tests

To speed up development feedback loops, you can run specific test files or filter by test method names:

### Run a Specific Test File

```bash
# Run SLA Tracking tests
php vendor/bin/phpunit tests/Feature/TicketSlaTrackingTest.php

# Run Ticket Assignment tests
php vendor/bin/phpunit tests/Feature/TicketAssignmentTest.php

# Run SLA & Timezone Unit tests
php vendor/bin/phpunit tests/Unit/SlaTimezoneTest.php
```

### Run by Filter (Specific Method or Class)

```bash
# Filter by test method name
php vendor/bin/phpunit --filter test_ticket_assignment_service_resolves_least_busy_eligible_agent

# Filter by keyword
php vendor/bin/phpunit --filter Sla
```

---

## 4. Key Test Suites & Coverage Areas

### 1. SLA Engine & Timezones
- **[`tests/Unit/SlaTimezoneTest.php`](../tests/Unit/SlaTimezoneTest.php)**: Validates UTC database persistence alongside business timezone conversions (`Asia/Dhaka`), day rollover math, weekend advancement, and string timestamp handling.
- **[`tests/Feature/TicketSlaTrackingTest.php`](../tests/Feature/TicketSlaTrackingTest.php)**: Verifies first public reply milestone tracking (`first_responded_at`), internal note exclusion, resolution timestamps, and priority change recalculations.
- **[`tests/Feature/TicketSlaEscalationTest.php`](../tests/Feature/TicketSlaEscalationTest.php)**: Tests Level 1 and Level 2 automatic breach escalations, threshold minutes, and the `ticket:escalate-sla` console command.

### 2. Workload-Based Assignment
- **[`tests/Feature/TicketAssignmentTest.php`](../tests/Feature/TicketAssignmentTest.php)**: Enforces the **Least Active Workload** assignment algorithm, active ticket filtering, dynamic RBAC permission eligibility (`receive_ticket_assignments`), manual reassignment, and activity logging.

### 3. Conversations, Notes & Privacy
- **[`tests/Feature/TicketConversationTest.php`](../tests/Feature/TicketConversationTest.php)**: Validates customer/agent reply submission, input validation, and automatic status reopening from `waiting_for_customer` back to `in_progress`.
- **[`tests/Feature/TicketActivityLogPrivacyTest.php`](../tests/Feature/TicketActivityLogPrivacyTest.php)**: Strictly enforces that private internal notes, internal attachments, and staff notes are completely omitted from customer streams.

### 4. Secure File Attachments
- **[`tests/Feature/TicketAttachmentTest.php`](../tests/Feature/TicketAttachmentTest.php)**: Tests multi-file upload, MIME-type and size validation, relational linking, secure downloads, and rejection of cross-customer or internal-note file access.

### 5. Multi-Tenant Customer Portal
- **[`tests/Feature/CustomerPortalTest.php`](../tests/Feature/CustomerPortalTest.php)** & **[`tests/Feature/CustomerTicketListingAndDetailsTest.php`](../tests/Feature/CustomerTicketListingAndDetailsTest.php)**: Validates customer dashboard metrics, tenant isolation (preventing customers from viewing or modifying tickets belonging to other customers), and profile updates.

### 6. Notifications & Global Acknowledgment
- **[`tests/Feature/AdminTicketNotificationTest.php`](../tests/Feature/AdminTicketNotificationTest.php)**: Confirms alert delivery upon ticket creation, customer replies, and verifies that staff public replies mark all admin notifications for that ticket as read across all internal users.
- **[`tests/Feature/CustomerTicketNotificationTest.php`](../tests/Feature/CustomerTicketNotificationTest.php)**: Validates customer notification dispatch on staff replies, status changes, and assignment updates.

### 7. Dynamic RBAC & Admin Permissions
- **[`tests/Feature/Admin/PermissionMiddlewareTest.php`](../tests/Feature/Admin/PermissionMiddlewareTest.php)**: Enforces HTTP 403 authorization barriers across all administrative route groups.
- **[`tests/Feature/Admin/RoleManagementTest.php`](../tests/Feature/Admin/RoleManagementTest.php)** & **[`tests/Feature/Admin/UserManagementTest.php`](../tests/Feature/Admin/UserManagementTest.php)**: Validates dynamic role creation, permission assignment, user-role synchronization, and root super-admin protection.

### 8. Batch CSV Import
- **[`tests/Feature/TicketCsvImportTest.php`](../tests/Feature/TicketCsvImportTest.php)**: Tests batch CSV parsing, auto customer provisioning, automatic SLA deadline calculations, error report generation, and background queue handling.

---

## 5. Continuous Integration (CI)

The project includes an automated GitHub Actions pipeline in [`.github/workflows/ci.yml`](../.github/workflows/ci.yml) that automatically executes on pull requests targeting `main`.

The CI pipeline runs:
1. **PHP 8.3 Environment** with required extensions.
2. **Composer Dependencies Installation**.
3. **Node 20 Setup & Frontend Compilation** (`npm ci && npm run build`).
4. **MySQL 8.0 Service Container Initialization**.
5. **Database Migration** (`php artisan migrate --force`).
6. **Full Test Suite Execution** (`php artisan test`).

