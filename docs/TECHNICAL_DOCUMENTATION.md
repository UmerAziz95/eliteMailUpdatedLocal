# Elite Mail System — Technical Documentation

**Project:** Elite Mail (Laravel application)  
**Framework:** Laravel 10.x (`laravel/framework`)  
**PHP:** 8.1+  

This document is the technical companion to:
- `docs/NON_TECHNICAL_GUIDE.md` (business overview)
- `docs/DEPLOYMENT_GUIDE.md` (server + deploy steps)
- `docs/API_DOCUMENTATION.md` (HTTP endpoints + webhooks)

---

## System overview

Elite Mail is a subscription-based platform for ordering and fulfilling email inbox creation at scale. It combines:
- **Billing/subscriptions** (Chargebee)
- **Order intake and fulfillment workflow** (Admin/Contractor tooling)
- **Panel and capacity management** (operational throughput)
- **Pool orders** (customer-provided domain lists, allocation to pool panels)
- **Support ticketing** (IMAP ingestion + portal)
- **Notifications** (in-app + optional Slack/Discord + realtime via Pusher)

The UI is server-rendered (Blade) and uses common “JSON for tables” endpoints for DataTables-style screens.

---

## Technology stack

### Backend
- **Laravel 10.x** (MVC)
- **MySQL** (primary database)
- **Queues**: Laravel Queue (database driver by default; Redis optional)
- **Scheduler/Cron**: Laravel Scheduler (`php artisan schedule:run`)

### Frontend
- **Blade templates**
- **Vite** (`vite`, `laravel-vite-plugin`)
- **Bootstrap/jQuery/DataTables** (inferred from dependencies + route patterns)
- **Realtime**: Laravel Echo + Pusher (`laravel-echo`, `pusher-js`, `pusher/pusher-php-server`)

### Key PHP packages (from `composer.json`)
- **Chargebee**: `chargebee/chargebee-php`
- **IMAP mail ingestion**: `webklex/laravel-imap`
- **RBAC**: `spatie/laravel-permission` (plus an additional custom role system)
- **PDF generation**: `barryvdh/laravel-dompdf`
- **DataTables server-side**: `yajra/laravel-datatables-oracle`
- **Auth tokens**: `laravel/sanctum` (API scaffolding; API routes are minimal)

---

## Repository structure (high-level)

- `app/Http/Controllers/`
  - `Admin/*` admin & operations UI endpoints
  - `Customer/*` customer dashboard & ordering
  - `Contractor/*` contractor queue and fulfillment tooling
  - `Webhook/*` inbound integrations (Chargebee, GoHighLevel)
- `app/Models/` Eloquent models for orders, pools, panels, invoices, support tickets, notifications, etc.
- `app/Console/Commands/` scheduled + maintenance artisan commands
- `routes/web.php` primary application routes (HTML + JSON endpoints)
- `routes/webhook.php` webhook endpoints (no auth/CSRF)
- `routes/api.php` minimal API route (Sanctum user endpoint)
- `database/migrations/` schema
- `database/seeders/` initial data including roles
- `resources/` views and frontend assets

---

## Roles, access control, and authentication

This project uses **two** role systems:

### 1) Custom role IDs (primary for UI routing)
Many route groups are protected by `custom_role:<ids>` (implemented by `app/Http/Middleware/RoleMiddleware.php`).

Seeded roles (from `database/seeders/CustomRoleSeeder.php`):
- **1**: Admin
- **2**: Sub-Admin
- **3**: Customer
- **4**: Contractor
- **5**: Mod

Key behavior of `custom_role` middleware:
- Requires authentication (`Auth::check()`), otherwise redirects (or returns JSON 401).
- Blocks **inactive** users (`users.status == 0`) with a 403/redirect + logout.
- Checks `Auth::user()->role->id` against the allowed IDs.

### 2) Spatie permissions (secondary, “super-admin”)
Seeders also create a `roles` table role `super-admin` and assign it to seeded super admin users (`database/seeders/AssignRoleSeeder.php`, `UsersTableSeeder.php`).

### Authentication flows (routes)
`routes/web.php` defines:
- Login/logout
- Registration
- Password reset
- Email verification + onboarding

---

## Core domains and data model (conceptual)

The exact schema lives in `database/migrations/`, but the major concepts map to these models:

- **Users & roles**
  - `App\Models\User`
  - `App\Models\Role` (maps to `custom_roles`)
- **Plans and billing**
  - `App\Models\Plan`, `App\Models\PoolPlan`, `App\Models\MasterPlan`
  - `App\Models\Subscription`, `App\Models\Invoice`, `App\Models\PoolInvoice`
  - Chargebee is the system of record for subscription lifecycle; the app stores synchronized state.
- **Orders**
  - `App\Models\Order`, `App\Models\OrderPanel`, `App\Models\OrderPanelSplit`, `App\Models\OrderEmail`
  - Assignment + tracking: `UserOrderPanelAssignment`, `OrderTracking`, `PanelReassignmentHistory`
- **Panels and capacity**
  - `App\Models\Panel`, `PanelCapacityNotificationRecord`
  - Pool equivalents: `PoolPanel`, `PoolPanelSplit`, `PoolPanelReassignmentHistory`
- **Pools / pool orders**
  - `App\Models\Pool`, `PoolOrder`, `PoolOrderMigrationTask`
- **Support**
  - `SupportTicket`, `TicketReply`, `TicketsImapEmails`
- **Notifications & ops**
  - `Notification`, `ErrorLog`, `Log` (application logs viewer)
  - Settings/config: `Configuration`, `GhlSetting`, `SlackSettings`, `DiscordSettings`

---

## Integrations

### Chargebee (billing)
- Config: `config/services.php` → `chargebee.*`
- Env vars (see `.env.example`): `CHARGEBEE_SITE`, `CHARGEBEE_API_KEY`, `CHARGEBEE_ITEM_FAMILY_ID`
- Webhooks:
  - `POST /webhook/chargebee/billing-cycle`
  - `POST /webhook/chargebee/webhook`
  - (Plus a specific master-plan handler in `routes/web.php`: `POST /webhook/chargebee/master-plan`)

### GoHighLevel (GHL)
- Config: `config/services.php` → `ghl.*`
- Env vars: `GHL_ENABLED`, `GHL_BASE_URL`, `GHL_API_TOKEN`, `GHL_LOCATION_ID`, etc.
- Webhooks:
  - `POST /webhook/ghl/workflow`
  - `POST /webhook/ghl/workflow/check-payment-counter`

### IMAP (support tickets)
- Package: `webklex/laravel-imap`
- Scheduled commands:
  - `emails:fetch`
  - `tickets:process-imap` (triggered after fetch in scheduler)

### Realtime notifications (Pusher)
- Laravel broadcasting config: `config/broadcasting.php`
- Frontend packages: `laravel-echo`, `pusher-js`
- Env vars: `PUSHER_*`, `BROADCAST_DRIVER`

### Slack / Discord notifications
Slack and Discord appear as operational notification channels (settings screens + scheduled “Discord message sender” + Slack alerts for domain removal tasks).

---

## Background jobs and scheduled tasks (cron)

Scheduled in `app/Console/Kernel.php`. Notable recurring tasks include:
- Draft order reminders: `orders:send-draft-notifications` (every 10 minutes)
- Panel capacity checks: `panels:check-capacity` (every 10 minutes)
- Panel capacity notifications: `panels:capacity-notifications` (every 5 minutes)
- Domain removal processing: `domains:process-removal-queue` (every minute)
- Order countdown notifications: `orders:countdown-notifications` (every 5 minutes)
- Domain removal Slack alerts: `domain-removal:send-slack-alerts` (every minute)
- Daily DB backup: `backup:daily` (03:00 America/New_York)
- Discord message cron: calls `SettingController@discorSendMessageCron` (every minute)
- Payment failure processing: `payments:process-failures` (hourly)
- Failed payment emails: `emails:send-failed-payments` (hourly)
- Domain health check: `domain:check-health` (daily at 22:00 UTC)
- Pool lifecycle:
  - `pools:update-status --force` (every 2 hours)
  - `pool:assigned-panel` (every minute)
  - `pool:process-completed-cancellations` (hourly)
  - `pool:retry-assignment` (hourly)

### Cron setup
Production should run Laravel Scheduler every minute (see `docs/DEPLOYMENT_GUIDE.md`):

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

---

## Maintenance / one-off artisan commands

The repo’s root `README.md` calls out two important maintenance tasks:

- Normalize legacy pool domain JSON into the new schema:
  - `php artisan pools:migrate-prefix-statuses`
- Enrich existing pool orders with rich domain data:
  - `php artisan pool-orders:fix-domains`

Other operational commands exist in `app/Console/Commands/` (panel capacity checks, domain removal queue, IMAP processing, backups, etc.). For a full list:

```bash
php artisan list
```

---

## Routing and module layout

### Web UI routes (`routes/web.php`)
Routes are primarily grouped by role:
- **Admin/Sub-Admin/Mod**: `Route::middleware(['custom_role:1,2,5'])->prefix('admin')...`
- **Customer**: `Route::middleware(['custom_role:3'])->prefix('customer')...`
- **Contractor**: `Route::middleware(['custom_role:4'])->prefix('contractor')...`

Within those groups, the system exposes:
- Plan management (including special/discounted/pool plans)
- Order queue + order processing + splits + exports
- Pool management + pool panels + pool orders
- Invoices (regular + pool)
- Support ticketing (portal + replies + status changes)
- System settings (Slack/Discord/GHL/configurations/backups)
- Logs and error logs viewer

### Webhook routes (`routes/webhook.php`)
Mounted at `/webhook/*` by `RouteServiceProvider`.
See `docs/API_DOCUMENTATION.md` for endpoint details.

### API routes (`routes/api.php`)
Currently only includes `GET /api/user` guarded by Sanctum.

---

## Environment configuration (developer reference)

See `.env.example` for the full list. Common ones:
- **App**: `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY`
- **DB**: `DB_*`
- **Mail**: `MAIL_*` (used for notifications + scheduler failure emails)
- **Chargebee**: `CHARGEBEE_*`
- **Pusher/Broadcast**: `BROADCAST_DRIVER`, `PUSHER_*`
- **Panel capacity defaults**: `PANEL_CAPACITY`

---

## Local development (typical)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev
php artisan serve
```

If you need background processing locally:
- run scheduler manually: `php artisan schedule:work` (development)
- run queue worker: `php artisan queue:work`

---

## Operational notes / pitfalls

- **Webhook security**: webhook endpoints are excluded from CSRF checks (`app/Http/Middleware/VerifyCsrfToken.php`). Ensure you validate signatures/secrets in controllers where applicable.
- **Role checks**: UI authorization is heavily tied to custom role IDs. Ensure seeded `custom_roles` exist in all environments.
- **Queue driver**: `.env.example` defaults to `QUEUE_CONNECTION=sync`; production should use `database` or `redis` with supervised workers.
- **Logs**: the app includes log viewer endpoints; ensure those routes remain access controlled in production.

---

## Further reading
- **HTTP endpoints + webhooks**: `docs/API_DOCUMENTATION.md`
- **Deployment**: `docs/DEPLOYMENT_GUIDE.md`
- **Business guide**: `docs/NON_TECHNICAL_GUIDE.md`

