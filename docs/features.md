# Core Features Documentation

| 🚀 [Installation Guide](installation.md) | 🔑 [Demo Accounts](demo-accounts.md) | ⚡ [Core Features](features.md) | 🔄 [Application Flow](application-flow.md) | 🧪 [Testing Guide](testing-guide.md) |
| :---: | :---: | :---: | :---: | :---: |

---

This document details the actual features implemented in the Support Ticket & SLA Management System.

---

## 1. Ticket Creation & Lifecycle Management

Tickets can be created both by **Customers** via the self-service portal (`/customer/tickets/create`) and by **Staff/Admins** on behalf of customers (`/admin/tickets/create`).

### Automatic Ticket Numbering
- Generates unique human-readable ticket identifiers: `TCK-YYYYMMDD-XXXX` (e.g., `TCK-20261108-RRPL`).

### Dynamic Category / Department System
- Categories are dynamically retrieved from configured system settings and merged with existing database values (e.g., *Technical Support*, *Billing*, *General Inquiry*).
- System respects existing category taxonomy without rigid enums.

---

## 2. Ticket Statuses & Priorities

### Ticket Statuses
- **`open`**: Ticket is newly submitted and waiting for internal agent review.
- **`in_progress`**: Agent has acknowledged or actively replied to the inquiry.
- **`waiting_for_customer`**: Agent has requested further information from the customer.
  - Automatically pauses or adjusts agent response expectations.
  - Automatically transitions back to `in_progress` the moment the customer submits a reply.
- **`resolved`**: Issue has been addressed. Records `resolved_at` milestone timestamp. Customers can reopen resolved tickets if necessary.
- **`closed`**: Ticket is permanently archived. Neither customers nor staff can submit further replies.

### Urgency Priorities
Configurable priority levels that directly govern SLA response and resolution targets:
- **`low`**: Standard non-critical questions (e.g., 8h first response / 48h resolution target).
- **`medium`**: Standard issues (e.g., 4h first response / 24h resolution target).
- **`high`**: Significant impact (e.g., 2h first response / 8h resolution target).
- **`urgent`**: Critical outage or blocker (e.g., 1h first response / 4h resolution target).

---

## 3. Ticket Assignment (Manual & Automatic)

### Agent Eligibility via Dynamic RBAC
The system uses a flexible, permission-driven eligibility rule:
- Any active internal staff user (`account_type = 'admin'`) who holds the `receive_ticket_assignments` permission is eligible.
- No hardcoded role names (such as "Support Agent" or "Agent") are used. Administrators can grant this permission to any custom-created role.

### Automatic Assignment (Least Active Workload Strategy)
When a ticket is created without an assigned agent:
1. Queries all active internal users with the `receive_ticket_assignments` permission.
2. Counts active assigned tickets (`open`, `in_progress`, `waiting_for_customer`) for each eligible user.
3. Excludes `resolved` and `closed` tickets.
4. Automatically assigns the new ticket to the eligible agent with the lowest active workload. Ties are broken deterministically by user ID.

### Manual Assignment & Reassignment
- Authorized staff with `assign_ticket` permission can assign an unassigned ticket, reassign an existing ticket, or unassign a ticket.
- Ineligible or inactive users are rejected on the server side with validation errors.
- Every assignment change is permanently logged to the ticket audit trail.

---

## 4. Conversations & Internal Notes

### Public Replies
- Visible to both staff and customers.
- When an authorized staff member posts the first public reply, `first_responded_at` is permanently recorded, fulfilling the First Response SLA milestone.
- Customer public replies on a `waiting_for_customer` ticket automatically revert its status to `in_progress`.

### Private Internal Notes (Staff-Only)
- Internal staff can post private notes marked with a distinct visual banner.
- **Strict Privacy**: Internal notes and their attachments are **never** rendered in the customer portal, included in customer API responses, or exposed in customer activity feeds.
- Internal notes do **not** trigger customer notifications, nor do they satisfy the public First Response SLA milestone.

---

## 5. Secure Ticket Attachments

- **Multi-File Uploads**: Supports simultaneous file uploads on both ticket creation and conversation replies.
- **Security & Validation**: Enforces strict MIME-type whitelists (PDF, images, documents) and file size boundaries.
- **Storage & Disk Isolation**: Files are stored securely on the configured disk (`local` or private storage) via Laravel's Media pipeline.
- **Authorization Enforcement**:
  - Customers can only download attachments from tickets they own.
  - Customers cannot download attachments belonging to internal notes, even on their own tickets.
  - Staff require `view_ticket` permission to download attachments.
  - Direct URL access to attachments validates ticket, message, and media relationships to prevent ID-tampering attacks.

---

## 6. SLA Targets, Business Hours & Escalation

### Configurable Business Hours & Timezones
Configured via `/admin/settings/sla`:
- **Business Timezone**: Independent timezone setting (e.g., `Asia/Dhaka`), allowing the database to maintain standard UTC timestamps while calculating and displaying deadlines in business time.
- **Working Days**: Selectable workweek (e.g., Sunday–Thursday for Middle East/South Asia, or Monday–Friday).
- **Working Hours**: Daily operating window (e.g., `09:00 AM` to `05:00 PM`).
- **Holidays**: Configurable list of non-working dates.

### Smart Deadline Calculation Engine
- **Business Moment Advancement**: If a ticket is submitted outside operating hours (e.g., 8:00 PM or on a Saturday), the clock automatically starts at the beginning of the next active working day (e.g., Sunday 09:00 AM).
- **Day Rollover**: Deadlines that exceed the remaining working minutes of a business day automatically carry over remaining target minutes into the following working day at opening time.
- **Priority Re-Calculations**: If ticket priority is adjusted before milestones are reached, remaining deadlines are automatically recalculated from creation time while historical timestamps are preserved.

### SLA Escalation Engine
- Monitored via scheduled artisan command (`ticket:escalate-sla`).
- **Level 1 Escalation**: Triggers when a target is breached (Action: Notify Assigned Staff or Super Admin).
- **Level 2 Escalation**: Triggers after a configurable breach threshold in minutes (Action: Reassign to next least-busy agent & notify).

---

## 7. Dedicated Customer Portal

Accessible under `/customer/*` for authenticated users with `account_type = 'customer'`:
- **Dashboard (`/customer/dashboard`)**: Metric cards for Total, Open, In Progress, Resolved, and Closed tickets.
- **Ticket Listing (`/customer/tickets`)**: Clean, responsive table with real-time status badges, SLA indicators, and pagination.
- **Ticket Submission (`/customer/tickets/create`)**: Multi-step submission form with dynamic category choices and support SLA guarantees card.
- **Conversation Stream (`/customer/tickets/{id}`)**: Real-time chat stream with timeline timestamps, attachment download buttons, and reopen controls.
- **Cross-Tenant Isolation**: Customers are strictly prohibited from viewing, searching, replying to, or downloading files from tickets belonging to other customers.

---

## 8. Admin Dashboard & Reporting

Accessible at `/admin` for internal staff:
- **Comprehensive Key Metrics**: 8 real-time metric cards (Total Tickets, Open, In Progress, Waiting, Resolved, Closed, Breached SLA, Approaching Breach).
- **Date Range Filtering**: Dynamically recalculates metrics and charts across custom date ranges.
- **Status & Priority Breakdowns**: Visual distribution of current workload across low, medium, high, and urgent tiers.
- **Agent Workload Grid**: Real-time breakdown of tickets assigned per agent.
- **Permission Guarding**: Staff without `view_ticket` permission cannot see ticket reporting cards.

---

## 9. Notification Lifecycle

- **New Ticket Notifications**: Automatically alerts all active internal staff possessing `view_ticket` permission when a customer creates a ticket.
- **Customer Reply Notifications**: Automatically alerts internal staff when a customer posts a reply.
- **Customer Status & Reply Alerts**: Dispatches notifications to the customer whenever an agent replies publicly, changes status, or assigns an agent.
- **Global Read Acknowledgment**: When any authorized agent posts a public reply to a ticket, the system automatically marks all existing unread admin-side notifications for that specific ticket as read for **all** internal users, eliminating redundant notification noise.

---

## 10. Search, Filtering & Pagination

- **Search Capabilities**: Instant search by Ticket Number (`TCK-...`), Subject keyword, Customer Name, or Customer Email.
- **Multi-Parameter Filtering**: Filter simultaneously by Status, Priority, Category/Department, Assigned Agent, and SLA Status (`on_track`, `approaching_breach`, `breached`, `completed`).
- **Server-Side Pagination**: Standardized 10/15/25 items per page with preserved query string parameters.

---

## 11. Bulk CSV Ticket Import

Accessible via `/admin/tickets/import`:
- **Template Download**: One-click sample CSV template with expected column headers.
- **Batch Processing**: Handles bulk ticket creation with automatic customer provisioning (creates customer accounts if emails are not yet registered).
- **Auto SLA Initialization**: Calculates first response and resolution deadlines for every imported record based on its priority.
- **Error Tracking**: Detailed error reporting allowing staff to download a CSV containing only failed rows with specific validation error messages.

---

## 12. Activity Logging & Full Audit History

Powered by `spatie/laravel-activitylog`:
- **Ticket Lifecycle Audit**: Tracks creation, assignment changes, priority shifts, status changes, and SLA deadline recalculations.
- **Chronological Timeline**: Interactive timeline showing exact timestamp, author/causer, and diff of old versus new attribute values.
- **Strict Privacy Scoping**: Customer timelines only display public milestone events.

---

## 13. Dynamic Role-Based Access Control (RBAC)

- **Dynamic Roles**: Create unlimited custom roles with custom authority levels.
- **Granular Permission Slugs**: Over 40 discrete permission slugs spanning Users, Roles, Media, Activity Logs, Settings, Backups, and Tickets (`view_ticket`, `create_ticket`, `edit_ticket`, `reply_ticket`, `internal_note_ticket`, `assign_ticket`, `receive_ticket_assignments`, etc.).
- **Account Type Separation**: Strict architectural division between `admin` (internal staff) and `customer` accounts. Customers are forbidden from accessing any administrative route.

