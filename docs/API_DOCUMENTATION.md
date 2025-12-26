# Elite Mail System — API / HTTP Documentation

This project is primarily a **server-rendered web application** (Blade) but exposes many JSON endpoints for datatables and operations screens, plus inbound webhooks.

For a complete list in any environment, run:

```bash
php artisan route:list
```

---

## Base URL and route mounting

Routes are mounted by `App\Providers\RouteServiceProvider`:
- **Web UI + JSON endpoints**: `routes/web.php` (no prefix)
- **API**: `routes/api.php` (prefix `/api`)
- **Webhooks**: `routes/webhook.php` (prefix `/webhook`)

---

## Authentication

### Session authentication (primary)
Most endpoints are protected by role middleware:
- `custom_role:1,2,5` → admin/sub-admin/mod
- `custom_role:3` → customer
- `custom_role:4` → contractor

Login is via:
- `GET /` (login form)
- `POST /login`
- `GET /logout`

### Sanctum API authentication (minimal)
`routes/api.php` defines:
- `GET /api/user` (requires `auth:sanctum`)

---

## Webhook endpoints (no auth + CSRF-exempt)

Webhook requests are excluded from CSRF verification in `app/Http/Middleware/VerifyCsrfToken.php`.

### Chargebee
Mounted under `/webhook` (from `routes/webhook.php`):
- `POST /webhook/chargebee/billing-cycle`  
  Controller: `App\Http\Controllers\Webhook\ChargebeeWebhookController@handle`  
  Purpose: subscription lifecycle events (billing cycle related).

- `POST /webhook/chargebee/webhook`  
  Controller: `App\Http\Controllers\Webhook\ChargebeeWebhookController@handle`  
  Purpose: alternate endpoint (same handler).

Also defined in `routes/web.php`:
- `POST /webhook/chargebee/master-plan`  
  Controller: `App\Http\Controllers\Admin\MasterPlanController@handleChargebeeWebhook`  
  Purpose: master plan synchronization events.

### GoHighLevel (GHL)
- `POST /webhook/ghl/workflow`  
  Controller: `App\Http\Controllers\Webhook\GhlWorkflowController@handleWorkflow`

- `POST /webhook/ghl/workflow/check-payment-counter`  
  Controller: `App\Http\Controllers\Webhook\GhlWorkflowController@checkFailedPaymentCounter`

### Webhook test endpoints (GET)
For connectivity testing:
- `GET /webhook/chargebee/test`
- `GET /webhook/ghl/test`

---

## Public (unauthenticated) utility endpoints

### CSRF refresh
- `GET /refresh-csrf` → returns `{ token: "..." }`

### Disclaimer fetch
- `GET /api/disclaimer/{type}`  
Returns the active disclaimer for a given type (note this is under `/api/...` but lives in `routes/web.php`).

### Static plans / short links
- `GET /static-plans/{encrypted?}`
- `POST /static-plans/select`
- `POST /static-plans/clear-session`
- `GET /go/{slug}` (short encrypted link redirector)

---

## Admin endpoints (HTML + JSON)

All admin endpoints are under:
- Prefix: `/admin`
- Middleware: `custom_role:1,2,5`

The admin area includes modules such as:
- **Dashboard**: `/admin/dashboard` + stats endpoints
- **Plans**: CRUD via `Route::resource('plans', ...)` plus feature lists
- **Pools**: CRUD via `Route::resource('pools', ...)` + assignment/capacity checks
- **Pool panels**: CRUD + reassignment + capacity alerts
- **Orders**: list/cards/splits, assignments, exports, bulk imports
- **Order queue**: queue listing, assign/reject, split info
- **Invoices**: listings + PDF downloads (regular + pool)
- **Support tickets**: list, show, reply, status changes
- **Settings**: system configuration history, backups, Slack/GHL/Discord settings
- **Logs**: application logs viewer, error logs viewer

Tip: most “`/data`” endpoints return JSON formatted for DataTables.

---

## Customer endpoints (HTML + JSON)

All customer endpoints are under:
- Prefix: `/customer`
- Middleware: `custom_role:3`

Major areas:
- **Dashboard**: `/customer/dashboard`
- **Plans/subscription management**: upgrade/cancel/update payment
- **Orders**: list/view/edit + “reorder” flows
- **Pool orders**: list/view/edit/cancel + invoices data/export
- **Invoices**: list/show/download (regular + pool)
- **Support tickets**: create/list/show/reply

---

## Contractor endpoints (HTML + JSON)

All contractor endpoints are under:
- Prefix: `/contractor`
- Middleware: `custom_role:4`

Major areas:
- **Order queue**: view/assign/reject
- **Assigned orders**: view/split view + status changes
- **Bulk import + exports**: panel CSV downloads, split exports
- **Pool orders**: queue/list/view/change status + domain csv download
- **Task queue**: claim tasks + complete tasks
- **Support tickets**: list/show/reply/status
- **Panels dashboard**: capacity/order data

---

## Operational/maintenance HTTP routes (use with care)

`routes/web.php` includes several “manual trigger” routes intended for maintenance/testing (e.g. running artisan commands via HTTP). These should be locked down in production and/or removed if not required.

Examples include:
- triggering `orders:send-draft-notifications`
- triggering `domains:process-removal-queue`
- triggering `domain-removal:send-slack-alerts`

---

## Versioning and compatibility notes

- The HTTP surface is largely **internal UI-oriented**; treat endpoints as coupled to the Blade/JS frontend rather than as a stable public API.
- For integrations, prefer the webhook endpoints documented above, and keep secrets/signature validation in the webhook controllers.

