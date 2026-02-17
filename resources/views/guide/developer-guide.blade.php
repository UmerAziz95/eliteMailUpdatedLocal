<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" href="{{ asset('assets/favicon/favicon.png') }}" type="image/x-icon">
    <title>EliteMail Developer Guide</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root { scroll-behavior: smooth; }
        body { background: #f4f6fa; }
        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            overflow: auto;
            border-right: 1px solid #e9ecef;
            background: #fff;
        }
        .doc-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 1rem;
            box-shadow: 0 10px 24px rgba(0,0,0,.04);
        }
        .section-title { scroll-margin-top: 90px; }
        pre {
            background: #0f172a;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: .75rem;
            overflow: auto;
        }
        .flow-wrap {
            border: 1px solid #d9e7ff;
            border-radius: 1rem;
            padding: 1rem;
            background: linear-gradient(180deg,#f8fbff,#eef4ff);
        }
        .flow-track { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }
        .flow-node {
            border:1px solid #bfd7ff;
            background:#fff;
            border-radius:.75rem;
            padding:.45rem .7rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size:.82rem;
            font-weight:600;
        }
        .flow-arrow { color:#0d6efd; animation:pulseShift 1.1s ease-in-out infinite; }
        @keyframes pulseShift {
            0%,100%{ transform:translateX(0); opacity:.55; }
            50%{ transform:translateX(6px); opacity:1; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row g-0">
        <aside class="col-lg-3 d-none d-lg-block sidebar p-3">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary w-100 mb-3"><i class="bi bi-arrow-left-circle"></i> Return to Dashboard</a>
            <h5 class="mb-3">Developer Guide Index</h5>
            <nav class="nav flex-column nav-pills gap-1 small">
                <a class="nav-link" href="#overview">Overview</a>
                <a class="nav-link" href="#architecture">Architecture Flow</a>
                <a class="nav-link" href="#project-flow">Project flow (module-level)</a>
                <a class="nav-link" href="#rbac">Auth + RBAC</a>
                <a class="nav-link" href="#api">API Contract Notes</a>
                <a class="nav-link" href="#queue">Queue / Horizon</a>
                <a class="nav-link" href="#notifications">Notifications</a>
                <a class="nav-link" href="#storage">Files & Storage</a>
                <a class="nav-link" href="#env">Environment Setup</a>
                <a class="nav-link" href="#deploy">Deployment Checklist</a>
                <a class="nav-link" href="#logging">Logging & Debugging</a>
                <a class="nav-link" href="#security">Security Best Practices</a>
                <a class="nav-link" href="#faq">Technical FAQ</a>
                <a class="nav-link" href="#glossary">Glossary</a>
            </nav>
        </aside>

        <main class="col-lg-9 p-3 p-lg-4">
            <section class="doc-card p-4 mb-4" id="overview">
                <h1 class="h3 section-title">EliteMail Developer & DevOps Guide</h1>
                <p>This document is for maintainers implementing, operating, and troubleshooting the order lifecycle and provider integrations.</p>
                <h2 class="h6 text-uppercase text-muted">Purpose</h2>
                <ul>
                    <li>Standardize implementation details for API, queues, and provider workflows.</li>
                    <li>Document expected runtime behavior for cancellations, notifications, and background processing.</li>
                    <li>Provide repeatable procedures for deployment, debugging, and incident response.</li>
                </ul>
            </section>

            <section class="doc-card p-4 mb-4" id="architecture">
                <h2 class="h5 section-title">System Architecture & Flow</h2>
                <ol>
                    <li>Client submits authenticated order request.</li>
                    <li>Order persists with initial status and provider metadata.</li>
                    <li>Queue job dispatches provider-specific execution path.</li>
                    <li>Provider response updates order/split records.</li>
                    <li>Notification channel emits status events.</li>
                </ol>
                <div class="flow-wrap">
                    <div class="flow-track">
                        <span class="flow-node">HTTP Request</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Controller/Service</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">DB Transaction</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Queue Job</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Provider API</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Status Sync + Notify</span>
                    </div>
                </div>
            </section>


            <section class="doc-card p-4 mb-4" id="project-flow">
                <h2 class="h5 section-title">Project Flow (Module-Level)</h2>
                <p>Use this as the canonical execution order when validating incidents or onboarding new engineers.</p>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light"><tr><th>Stage</th><th>Primary module</th><th>Output</th></tr></thead>
                        <tbody>
                            <tr><td>1. Intake</td><td>Routes + Controller + Request validation</td><td>Validated payload / rejection (422)</td></tr>
                            <tr><td>2. Authorization</td><td>Auth guard + RBAC policies</td><td>Allowed action or 401/403</td></tr>
                            <tr><td>3. Persistence</td><td>Order + split write operations</td><td>Order ID, split metadata, initial status</td></tr>
                            <tr><td>4. Async execution</td><td>Queue/Horizon workers</td><td>Provider call attempts + retry state</td></tr>
                            <tr><td>5. Reconciliation</td><td>Status updater/webhook handlers</td><td>Domain/mailbox/order status sync</td></tr>
                            <tr><td>6. Outbound updates</td><td>Notification channels</td><td>Email/SMS/Webhook events</td></tr>
                            <tr><td>7. Operations</td><td>Admin tools + logs + dashboards</td><td>Support action, cancellation, audit trail</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="flow-wrap">
                    <div class="flow-track">
                        <span class="flow-node">Validation</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Auth/RBAC</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Persist Order</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Queue Work</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Provider Sync</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Notify + Operate</span>
                    </div>
                </div>
            </section>

            <section class="doc-card p-4 mb-4" id="rbac">
                <h2 class="h5 section-title">Authentication & RBAC</h2>
                <ul>
                    <li>Authenticate via Laravel guard(s) configured for web/API contexts.</li>
                    <li>Enforce role/policy checks at route middleware and domain service layers.</li>
                    <li>Use least-privilege defaults: deny sensitive actions unless explicit permission exists.</li>
                </ul>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light"><tr><th>Layer</th><th>Control</th><th>Expected failure code</th></tr></thead>
                        <tbody>
                            <tr><td>Route middleware</td><td>auth, role checks</td><td>401 / 403</td></tr>
                            <tr><td>Controller</td><td>policy/ability checks</td><td>403</td></tr>
                            <tr><td>Service</td><td>business rule authorization</td><td>422 / domain exception</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="doc-card p-4 mb-4" id="api">
                <h2 class="h5 section-title">API Usage Notes</h2>
                <h3 class="h6">Headers</h3>
                <pre>Authorization: Bearer &lt;token&gt;
Accept: application/json
Content-Type: application/json</pre>
                <h3 class="h6">Common Status Codes</h3>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-light"><tr><th>Code</th><th>Meaning</th><th>Action</th></tr></thead>
                        <tbody>
                            <tr><td>200/201</td><td>Success</td><td>Proceed</td></tr>
                            <tr><td>401</td><td>Unauthenticated</td><td>Refresh/login and retry</td></tr>
                            <tr><td>403</td><td>Unauthorized</td><td>Check role/permission mapping</td></tr>
                            <tr><td>422</td><td>Validation/business failure</td><td>Inspect payload and rule output</td></tr>
                            <tr><td>429</td><td>Rate limited</td><td>Backoff and retry with jitter</td></tr>
                            <tr><td>500</td><td>Unhandled server error</td><td>Trace logs + failed jobs</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="mb-0">If throttling is active, clients should respect retry windows and implement idempotency keys for order creation endpoints.</p>
            </section>

            <section class="doc-card p-4 mb-4" id="queue">
                <h2 class="h5 section-title">Queue / Horizon Behavior</h2>
                <ul>
                    <li>Provider calls should run asynchronously to avoid long request latency.</li>
                    <li>Configure queue worker timeouts and retries per provider SLA.</li>
                    <li>Move poisoned messages to failed jobs and expose replay process.</li>
                </ul>
                <pre>php artisan queue:work --queue=default,providers --tries=3 --timeout=120
php artisan horizon
php artisan queue:failed
php artisan queue:retry all</pre>
                <div class="flow-wrap">
                    <div class="flow-track">
                        <span class="flow-node">Dispatch Job</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Worker Picks Job</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Provider Call</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Update DB</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Notify/Complete</span>
                    </div>
                </div>
            </section>

            <section class="doc-card p-4 mb-4" id="notifications">
                <h2 class="h5 section-title">Notifications (Email / SMS / Webhooks)</h2>
                <ul>
                    <li>Emit events on order created, status transition, and cancellation finalized.</li>
                    <li>Ensure webhook handlers are idempotent (dedupe by event/order ID).</li>
                    <li>Persist provider delivery attempts and failures for auditability.</li>
                </ul>
            </section>

            <section class="doc-card p-4 mb-4" id="storage">
                <h2 class="h5 section-title">File Uploads / Storage / Limits</h2>
                <ul>
                    <li>Validate MIME type and size at request layer.</li>
                    <li>Store in non-public disk unless explicit download access is required.</li>
                    <li>Record file metadata (owner, checksum, path, created_at) for traceability.</li>
                    <li>Apply lifecycle cleanup for temporary/import artifacts.</li>
                </ul>
            </section>

            <section class="doc-card p-4 mb-4" id="env">
                <h2 class="h5 section-title">Environment Configuration</h2>
                <pre>APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain
DB_CONNECTION=mysql
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
MAIL_MAILER=smtp
HORIZON_PREFIX=elitemail</pre>
                <ul>
                    <li>Use <code>.env</code> per environment; never commit secrets.</li>
                    <li>Keep provider credentials in secured secret manager when possible.</li>
                    <li>Run config/cache optimization after any config change.</li>
                </ul>
            </section>

            <section class="doc-card p-4 mb-4" id="deploy">
                <h2 class="h5 section-title">Deployment Checklist</h2>
                <ol>
                    <li>Pull/tag release and install dependencies (<code>composer install --no-dev --optimize-autoloader</code>).</li>
                    <li>Run migrations in maintenance window (<code>php artisan migrate --force</code>).</li>
                    <li>Build/refresh caches (<code>php artisan config:cache route:cache view:cache</code>).</li>
                    <li>Restart queues/Horizon (<code>php artisan queue:restart</code>).</li>
                    <li>Run smoke tests for login, order create, status update, and cancellation.</li>
                    <li>Monitor logs and Horizon dashboards for first 30 minutes post-release.</li>
                </ol>
            </section>

            <section class="doc-card p-4 mb-4" id="logging">
                <h2 class="h5 section-title">Logging & Debugging Playbook</h2>
                <ul>
                    <li>Start with <code>storage/logs/laravel.log</code> and failed jobs table.</li>
                    <li>Trace by order ID and provider slug through service/job logs.</li>
                    <li>Capture request/response envelope (redacted) for provider incidents.</li>
                </ul>
                <pre># Common checks
php artisan queue:failed
php artisan horizon:status
php artisan tinker
php artisan route:list | rg guide</pre>
            </section>

            <section class="doc-card p-4 mb-4" id="security">
                <h2 class="h5 section-title">Security Best Practices</h2>
                <ul>
                    <li>Never expose API tokens, provider credentials, or private keys in source control.</li>
                    <li>Rotate secrets periodically and immediately after suspected exposure.</li>
                    <li>Restrict filesystem permissions for <code>storage</code> and <code>bootstrap/cache</code>.</li>
                    <li>Disable debug mode in production and sanitize error responses.</li>
                    <li>Use HTTPS everywhere and validate webhook signatures.</li>
                </ul>
            </section>

            <section class="doc-card p-4 mb-4" id="faq">
                <h2 class="h5 section-title">Technical FAQs</h2>
                <p><strong>Why is an order stuck in In Progress?</strong><br>Check queue backlog, failed jobs, provider API latency, and domain-specific retry logic.</p>
                <p><strong>How should we handle duplicate webhook events?</strong><br>Use an idempotency table keyed by provider event ID + order ID before applying mutations.</p>
                <p><strong>What if provider credentials expire?</strong><br>Rotate secrets, flush config cache, and run a controlled retry of failed operations.</p>
            </section>

            <section class="doc-card p-4" id="glossary">
                <h2 class="h5 section-title">Glossary (Technical)</h2>
                <ul class="mb-0">
                    <li><strong>RBAC:</strong> Role-Based Access Control enforced via middleware/policies.</li>
                    <li><strong>Idempotency:</strong> Safe repeat execution without duplicated side effects.</li>
                    <li><strong>Dead-letter / failed job:</strong> Message that exhausted retries and requires intervention.</li>
                    <li><strong>Webhook signature:</strong> Cryptographic proof request originated from trusted sender.</li>
                    <li><strong>Split provider allocation:</strong> Distribution of order workload across provider slugs.</li>
                </ul>
            </section>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
