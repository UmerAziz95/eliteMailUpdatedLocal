# Elite Mail (Laravel)

Elite Mail is a Laravel-based platform for subscription-driven email inbox ordering and fulfillment, with operational tooling for admins and contractors (orders, panels/capacity, pools), plus billing (Chargebee), support tickets (IMAP), and notifications (Pusher/Slack/Discord).

## Documentation

Start here: `docs/README.md`

Key docs:
- `docs/TECHNICAL_DOCUMENTATION.md`
- `docs/API_DOCUMENTATION.md`
- `docs/DEPLOYMENT_GUIDE.md`
- `docs/NON_TECHNICAL_GUIDE.md`
- `PRIVATE_SMTP_ORDER_FLOW.md` - Complete guide for Private SMTP order handling flow

### Private SMTP Order Flow

The [`PRIVATE_SMTP_ORDER_FLOW.md`](./PRIVATE_SMTP_ORDER_FLOW.md) document provides a comprehensive guide for handling customer orders when **Private SMTP** is set for normal orders (not pool orders).

**What it covers:**
- ✅ Complete step-by-step flow from order creation to completion
- ✅ Mailin.ai API integration details
- ✅ Domain transfer automation (Spaceship/Namecheap)
- ✅ Automated mailbox creation process
- ✅ Error handling and edge cases
- ✅ Status management and transitions
- ✅ Database schema and table structures
- ✅ Configuration and setup requirements
- ✅ Troubleshooting guide

**Key Features:**
- **Fully Automated**: No manual panel assignment required
- **Real-time Processing**: Immediate status updates and notifications
- **Error Recovery**: Automatic retry mechanisms
- **Complete Transparency**: Detailed logging and activity tracking

**When to use:**
- Customer has domains on Spaceship or Namecheap
- Customer wants automated mailbox creation
- Customer can provide valid API credentials
- Fast order processing is required

**Quick Reference:**
- Order status flow: `draft` → `in-progress` → `completed`
- Panel assignment: **SKIPPED** for Private SMTP orders
- Domain transfer: Automated via Mailin.ai API
- Mailbox creation: Automated via Mailin.ai API
- Customer notifications: Automatic at each step

For detailed implementation, API endpoints, error scenarios, and troubleshooting, see the complete guide: [`PRIVATE_SMTP_ORDER_FLOW.md`](./PRIVATE_SMTP_ORDER_FLOW.md)

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

## Maintenance

### Fix legacy pool domain JSON

```bash
php artisan pools:migrate-prefix-statuses
```

### Migrate/enrich pool orders domains

```bash
php artisan pool-orders:fix-domains
```

