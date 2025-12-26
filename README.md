# Elite Mail (Laravel)

Elite Mail is a Laravel-based platform for subscription-driven email inbox ordering and fulfillment, with operational tooling for admins and contractors (orders, panels/capacity, pools), plus billing (Chargebee), support tickets (IMAP), and notifications (Pusher/Slack/Discord).

## Documentation

Start here: `docs/README.md`

Key docs:
- `docs/TECHNICAL_DOCUMENTATION.md`
- `docs/API_DOCUMENTATION.md`
- `docs/DEPLOYMENT_GUIDE.md`
- `docs/NON_TECHNICAL_GUIDE.md`

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

