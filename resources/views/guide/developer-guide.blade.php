<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" href="{{ asset('assets/favicon/favicon.png') }}" type="image/x-icon">
  <title>Order System — Developer Guide (Technical)</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- Mermaid.js for flow diagrams -->
  <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>

  <style>
    :root { scroll-behavior: smooth; }
    body { background: #f8f9fa; }
    pre { background: #0b1020; color: #e6edf3; padding: 1rem; border-radius: .75rem; overflow: auto; }
    code { color: inherit; }
    .sidebar {
      position: sticky;
      top: 0;
      height: 100vh;
      overflow: auto;
      background: #fff;
      border-right: 1px solid rgba(0,0,0,.08);
    }
    .nav-link { color: #212529; }
    .nav-link:hover { background: rgba(13,110,253,.08); }
    .nav-link.active { background: rgba(13,110,253,.12); font-weight: 600; color: #0d6efd; }
    .content-card {
      background: #fff;
      border: 1px solid rgba(0,0,0,.08);
      border-radius: 1rem;
      box-shadow: 0 6px 18px rgba(0,0,0,.04);
    }
    .section-title {
      scroll-margin-top: 90px; /* so headings don't hide under sticky navbar on mobile */
    }
    .toc-badge { font-size: .75rem; }
  </style>
</head>

<body>
  <!-- Top bar (mobile) -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top d-lg-none">
    <div class="container-fluid d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas">
          <i class="bi bi-list"></i> Menu
        </button>
        <span class="navbar-brand ms-2 mb-0 h1">Architecture</span>
      </div>
      <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left-circle"></i> Return to project
      </a>
    </div>
  </nav>

  <div class="container-fluid">
    <div class="row g-0">
      <!-- Desktop Sidebar -->
      <aside class="col-lg-3 d-none d-lg-block sidebar p-3">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary w-100 mb-3">
          <i class="bi bi-arrow-left-circle"></i> Return to project
        </a>
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-diagram-3 text-primary fs-4"></i>
          <div>
            <div class="fw-bold">Order Architecture</div>
            <div class="text-muted small">Creation • Splits • Cancellation</div>
          </div>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input id="sidebarSearch" type="text" class="form-control" placeholder="Search sections..." aria-label="Search sections">
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="text-muted small">Table of Contents</span>
          <span class="badge text-bg-primary toc-badge">13</span>
        </div>

        <nav>
          <ul id="tocList" class="nav nav-pills flex-column gap-1">
            <li class="nav-item"><a class="nav-link" href="#1-overview">1. Overview</a></li>
            <li class="nav-item"><a class="nav-link" href="#flow-diagrams">Flow diagrams (technical)</a></li>
            <li class="nav-item"><a class="nav-link" href="#provider-split-logic">Provider split logic</a></li>
            <li class="nav-item"><a class="nav-link" href="#2-code-structure-interface--services">2. Code Structure</a></li>
            <li class="nav-item"><a class="nav-link" href="#3-database-schema--data-storage">3. Database Schema</a></li>
            <li class="nav-item"><a class="nav-link" href="#4-order-creation-with-automation">4. Order Creation & Automation</a></li>
            <li class="nav-item"><a class="nav-link" href="#scheduler-commands">Scheduler & Commands</a></li>
            <li class="nav-item"><a class="nav-link" href="#5-provider-specific-creation-mailin-premiuminboxes-mailrun">5. Provider-Specific Creation</a></li>
            <li class="nav-item"><a class="nav-link" href="#6-checks-during-creation">6. Checks During Creation</a></li>
            <li class="nav-item"><a class="nav-link" href="#7-legacy-vs-new-system">7. Legacy vs New System</a></li>
            <li class="nav-item"><a class="nav-link" href="#8-benefits-of-the-new-architecture">8. Benefits</a></li>
            <li class="nav-item"><a class="nav-link" href="#9-cancellation-process">9. Cancellation Process</a></li>
            <li class="nav-item"><a class="nav-link" href="#10-google365-legacy-job-based-deletion">10. Google/365 Legacy</a></li>
            <li class="nav-item"><a class="nav-link" href="#11-key-code-snippets">11. Key Code Snippets</a></li>
            <li class="nav-item"><a class="nav-link" href="#functionality-guide-link">Functionality guide</a></li>
          </ul>
        </nav>

        <hr class="my-3">

        <div class="small text-muted">
          Tip: Use the search to filter sections, then click a link to jump.
        </div>
      </aside>

      <!-- Mobile Offcanvas Sidebar -->
      <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
        <div class="offcanvas-header">
          <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">Table of Contents</h5>
          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
          <div class="input-group mb-3">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input id="sidebarSearchMobile" type="text" class="form-control" placeholder="Search sections...">
          </div>

          <ul id="tocListMobile" class="nav nav-pills flex-column gap-1">
            <li class="nav-item"><a class="nav-link" href="#1-overview" data-bs-dismiss="offcanvas">1. Overview</a></li>
            <li class="nav-item"><a class="nav-link" href="#flow-diagrams" data-bs-dismiss="offcanvas">Flow diagrams</a></li>
            <li class="nav-item"><a class="nav-link" href="#provider-split-logic" data-bs-dismiss="offcanvas">Provider split logic</a></li>
            <li class="nav-item"><a class="nav-link" href="#2-code-structure-interface--services" data-bs-dismiss="offcanvas">2. Code Structure</a></li>
            <li class="nav-item"><a class="nav-link" href="#3-database-schema--data-storage" data-bs-dismiss="offcanvas">3. Database Schema</a></li>
            <li class="nav-item"><a class="nav-link" href="#4-order-creation-with-automation" data-bs-dismiss="offcanvas">4. Automation</a></li>
            <li class="nav-item"><a class="nav-link" href="#scheduler-commands" data-bs-dismiss="offcanvas">Scheduler & Commands</a></li>
            <li class="nav-item"><a class="nav-link" href="#5-provider-specific-creation-mailin-premiuminboxes-mailrun" data-bs-dismiss="offcanvas">5. Providers</a></li>
            <li class="nav-item"><a class="nav-link" href="#6-checks-during-creation" data-bs-dismiss="offcanvas">6. Checks</a></li>
            <li class="nav-item"><a class="nav-link" href="#7-legacy-vs-new-system" data-bs-dismiss="offcanvas">7. Legacy vs New</a></li>
            <li class="nav-item"><a class="nav-link" href="#8-benefits-of-the-new-architecture" data-bs-dismiss="offcanvas">8. Benefits</a></li>
            <li class="nav-item"><a class="nav-link" href="#9-cancellation-process" data-bs-dismiss="offcanvas">9. Cancellation</a></li>
            <li class="nav-item"><a class="nav-link" href="#10-google365-legacy-job-based-deletion" data-bs-dismiss="offcanvas">10. Google/365</a></li>
            <li class="nav-item"><a class="nav-link" href="#11-key-code-snippets" data-bs-dismiss="offcanvas">11. Snippets</a></li>
            <li class="nav-item"><a class="nav-link" href="#functionality-guide-link" data-bs-dismiss="offcanvas">Functionality guide</a></li>
          </ul>
        </div>
      </div>

      <!-- Main content -->
      <main class="col-lg-9 p-3 p-lg-4">
        <div class="content-card p-3 p-lg-4">
          <header class="mb-4">
            <h1 class="h3 mb-2">Order Creation &amp; Cancellation — Developer Guide</h1>
            <p class="text-muted mb-0">
              Technical architecture: order creation with automation, provider splitting (Mailin, PremiumInboxes, Mailrun),
              code structure (interfaces and services), database schema, scheduler/commands, and cancellation (including Google/365 batch job).
            </p>
          </header>

          <hr class="my-4">

          <!-- 1 -->
          <section id="1-overview" class="mb-5">
            <h2 class="h4 section-title">1. Overview</h2>
            <ul>
              <li>
                <strong>New system (SMTP / Private SMTP):</strong> Orders are split across multiple SMTP providers (Mailin, PremiumInboxes, Mailrun).
                Domains are assigned by <code>split_percentage</code>; each provider gets its own row in <code>order_provider_splits</code>.
                Domain activation (transfer + status check) and mailbox creation are done per split via <code>SmtpProviderInterface</code> implementations.
              </li>
              <li>
                <strong>Legacy system:</strong> Single-provider orders (e.g. Mailin-only) or <strong>Google/Microsoft 365</strong> orders use
                <code>order_emails</code> and/or generated emails; Google/365 deletion runs in background via <code>DeleteGoogle365MailboxesJob</code> (batched).
              </li>
              <li>
                <strong>Cancellation:</strong> For SMTP with splits, each split’s mailboxes are deleted through
                <code>$provider-&gt;deleteMailboxesFromSplit($order, $split)</code>. For SMTP without splits, legacy <code>order_emails</code> are deleted.
                For Google/365, status is set to <code>cancellation-in-process</code> and a job processes batches until done, then status is set to <code>cancelled</code>.
              </li>
            </ul>
          </section>

          <!-- Flow diagrams (technical) -->
          <section id="flow-diagrams" class="mb-5">
            <h2 class="h4 section-title">Flow diagrams (technical)</h2>
            <p class="mb-3">End-to-end flows for order creation, domain activation, mailbox creation, and cancellation.</p>

            <h3 class="h5 mt-3">Order creation (ProcessMailAutomationJob)</h3>
            <div class="border rounded-3 p-3 bg-light mb-4">
              <div class="mermaid">
flowchart TD
  A[ProcessMailAutomationJob::handle] --> B[splitAndSaveDomains]
  B --> C[DomainActivationService::activateDomainsForOrder]
  C --> D{rejected?}
  D -->|Yes| E[return]
  D -->|No| F[OrderProviderSplit::areAllDomainsActiveForOrder]
  F --> G{all active?}
  G -->|No| H[Wait for scheduler / transfer]
  G -->|Yes| I[createMailboxes]
  I --> J[MailboxCreationService::createMailboxesForOrder]
  J --> K[Validate & complete order]
              </div>
            </div>

            <h3 class="h5 mt-4">Domain activation per split</h3>
            <div class="border rounded-3 p-3 bg-light mb-4">
              <div class="mermaid">
flowchart LR
  O[Order] --> S[OrderProviderSplit 1..N]
  S --> A[DomainActivationService::activateDomainsForSplit]
  A --> P[Provider::activateDomainsForSplit]
  P --> M[Mailin]
  P --> PI[PremiumInboxes]
  P --> MR[Mailrun]
  M --> U[update domain_statuses, all_domains_active]
  PI --> U
  MR --> U
              </div>
            </div>

            <h3 class="h5 mt-4">Cancellation: SMTP vs Google/365</h3>
            <div class="border rounded-3 p-3 bg-light mb-4">
              <div class="mermaid">
flowchart TD
  E[OrderCancelledService::cancelSubscription] --> CB[ChargeBee cancel]
  CB --> DOM[deleteOrderMailboxes]
  DOM --> T{provider_type?}
  T -->|SMTP / Private SMTP| SPLIT{splits exist?}
  SPLIT -->|Yes| DS[deleteMailboxesFromProviderSplits]
  SPLIT -->|No| LEG[deleteSmtpOrderMailboxes legacy]
  DS --> DONE[status = cancelled]
  LEG --> DONE
  T -->|Google / Microsoft 365| G365[status = cancellation-in-process]
  G365 --> JOB[DeleteGoogle365MailboxesJob::dispatch]
  JOB --> BATCH[deleteGoogle365OrderMailboxes batch]
  BATCH --> MORE{has_more?}
  MORE -->|Yes| JOB
  MORE -->|No| DONE
              </div>
            </div>
          </section>

          <!-- Provider split logic (technical) -->
          <section id="provider-split-logic" class="mb-5">
            <h2 class="h4 section-title">Provider split logic — how it works</h2>
            <p class="mb-3">The flow is: <strong>split domains across providers</strong> → <strong>activate domains</strong> (per split) → create mailboxes when all active. Below is how it’s implemented.</p>

            <div class="border rounded-3 p-3 bg-light mb-3">
              <div class="d-flex flex-wrap align-items-center justify-content-center gap-2 py-2 small">
                <code>ProcessMailAutomationJob</code>
                <span class="text-muted">→</span>
                <code>DomainSplitService::splitDomains()</code>
                <span class="text-muted">→</span>
                <code>order_provider_splits</code> (per provider)
                <span class="text-muted">→</span>
                <code>DomainActivationService::activateDomainsForOrder()</code>
                <span class="text-muted">→</span>
                <code>Provider::activateDomainsForSplit()</code>
                <span class="text-muted">→</span>
                <code>domain_statuses</code> / <code>all_domains_active</code>
              </div>
            </div>

            <h3 class="h5 mt-3">1. Split domains across providers</h3>
            <ul>
              <li><strong>Service:</strong> <code>App\Services\DomainSplitService</code> (<code>splitDomains(array $domains): array</code>).</li>
              <li><strong>Config:</strong> Active providers from <code>smtp_provider_splits</code> (where <code>is_active = true</code>), ordered by <code>priority</code>. Each row has <code>split_percentage</code> (e.g. 40, 40, 20).</li>
              <li><strong>Logic:</strong> For each provider, <code>domainCount = round(totalDomains * percentage / 100)</code>. Domains are assigned in order; remaining domains (from rounding) are assigned round-robin by priority.</li>
              <li><strong>Output:</strong> <code>['mailin' => ['d1.com','d2.com'], 'premiuminboxes' => [...], 'mailrun' => [...]]</code>. The job then <code>updateOrCreate</code>s one <code>order_provider_splits</code> row per provider with that order’s <code>domains</code> (JSON), <code>domain_statuses = null</code>, <code>all_domains_active = false</code>.</li>
            </ul>

            <h3 class="h5 mt-4">2. Activate domains</h3>
            <ul>
              <li><strong>Service:</strong> <code>DomainActivationService::activateDomainsForOrder($order)</code>. For each <code>OrderProviderSplit</code>, it loads credentials from <code>smtp_provider_splits</code>, builds the provider via <code>CreatesProviders::createProvider()</code>, and calls <code>$provider->activateDomainsForSplit($order, $split, ...)</code>.</li>
              <li><strong>Per provider:</strong> Mailin: transfer + status check + conflict check. PremiumInboxes: order/status handling. Mailrun: enrollment, nameservers, provision status. Each implementation updates the split’s <code>domain_statuses</code> and sets <code>all_domains_active</code> when every domain in that split is active.</li>
              <li><strong>After activation:</strong> <code>OrderProviderSplit::areAllDomainsActiveForOrder($orderId)</code> must be true for every split. If yes, <code>MailboxCreationService::createMailboxesForOrder()</code> runs; if no, the job ends and the scheduler (<code>mailin:check-pending-domains</code>) will retry activation and then create mailboxes later.</li>
            </ul>

            <p class="mb-0"><strong>Summary:</strong> Split assigns domains to providers and persists them in <code>order_provider_splits</code>. Activate runs per-split provider logic and fills <code>domain_statuses</code> / <code>all_domains_active</code>. Only when all splits report all domains active does mailbox creation run.</p>
          </section>

          <!-- 2 -->
          <section id="2-code-structure-interface--services" class="mb-5">
            <h2 class="h4 section-title">2. Code Structure: Interface &amp; Services</h2>

            <h3 class="h5 mt-3">2.1 SmtpProviderInterface</h3>
            <p>All SMTP providers implement this contract. Creation and deletion both go through the same interface.</p>
            <p class="mb-2"><strong>Location:</strong> <code>app/Contracts/Providers/SmtpProviderInterface.php</code></p>

            <pre><code class="language-php">interface SmtpProviderInterface
{
    public function authenticate(): ?string;
    public function transferDomain(string $domain): array;
    public function checkDomainStatus(string $domain): array;
    public function createMailboxes(array $mailboxes): array;
    public function deleteMailbox(int $mailboxId): array;
    public function deleteMailboxesFromSplit(Order $order, OrderProviderSplit $split): array;
    public function getMailboxesByDomain(string $domain): array;
    public function getProviderName(): string;
    public function getProviderSlug(): string;
    public function isAvailable(): bool;
}</code></pre>

            <ul>
              <li><strong>Creation / activation:</strong> <code>authenticate</code>, <code>transferDomain</code>, <code>checkDomainStatus</code>, <code>createMailboxes</code>, <code>getMailboxesByDomain</code>, <code>activateDomainsForSplit</code> (per-split domain activation with callbacks).</li>
              <li><strong>Deletion:</strong> <code>deleteMailbox</code>, <code>deleteMailboxesFromSplit</code> (per-split bulk delete; updates split JSON and calls provider API as needed).</li>
            </ul>

            <h3 class="h5 mt-4">2.2 CreatesProviders Trait</h3>
            <p>Single place to resolve provider slug → implementation. Used by <strong>DomainActivationService</strong>, <strong>MailboxCreationService</strong>, and <strong>OrderCancelledService</strong>.</p>
            <p class="mb-2"><strong>Location:</strong> <code>app/Services/Providers/CreatesProviders.php</code></p>

            <pre><code class="language-php">trait CreatesProviders
{
    protected function createProvider(string $slug, array $credentials): SmtpProviderInterface
    {
        return match ($slug) {
            'mailin' =&gt; new MailinProviderService($credentials),
            'premiuminboxes' =&gt; new PremiuminboxesProviderService($credentials),
            'mailrun' =&gt; new MailrunProviderService($credentials),
            default =&gt; throw new InvalidArgumentException("Unknown provider: {$slug}")
        };
    }
}</code></pre>

            <h3 class="h5 mt-4">2.3 Implementations</h3>

            <div class="table-responsive">
              <table class="table table-sm table-striped align-middle">
                <thead>
                  <tr>
                    <th>Provider</th>
                    <th>Service Class</th>
                    <th>Creation</th>
                    <th>Deletion (split)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Mailin</td>
                    <td><code>MailinProviderService</code></td>
                    <td>Yes</td>
                    <td>Yes (API + JSON)</td>
                  </tr>
                  <tr>
                    <td>PremiumInboxes</td>
                    <td><code>PremiuminboxesProviderService</code></td>
                    <td>Yes (fetch/list)</td>
                    <td>Yes (bulk + single)</td>
                  </tr>
                  <tr>
                    <td>Mailrun</td>
                    <td><code>MailrunProviderService</code></td>
                    <td>Yes (enrollment)</td>
                    <td>Local-only; marks <code>deleted_at</code> in JSON</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <p class="mb-0">Credentials come from <strong>SmtpProviderSplit</strong> (table <code>smtp_provider_splits</code>), keyed by slug.</p>
          </section>

          <!-- 3 -->
          <section id="3-database-schema--data-storage" class="mb-5">
            <h2 class="h4 section-title">3. Database Schema &amp; Data Storage</h2>

            <h3 class="h5 mt-3">3.1 Table: <code>orders</code></h3>
            <p>Relevant columns for creation/cancellation and provider flow:</p>

            <div class="table-responsive">
              <table class="table table-sm table-striped align-middle">
                <thead>
                  <tr>
                    <th>Column</th>
                    <th>Type</th>
                    <th>Purpose</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>id</code></td><td>bigint PK</td><td>Order ID</td></tr>
                  <tr><td><code>user_id</code></td><td>bigint FK</td><td>Owner</td></tr>
                  <tr><td><code>chargebee_subscription_id</code></td><td>string</td><td>Subscription link</td></tr>
                  <tr><td><code>provider_type</code></td><td>string</td><td>e.g. <code>'Private SMTP'</code>, <code>'SMTP'</code>, <code>'Google'</code>, <code>'Microsoft 365'</code></td></tr>
                  <tr><td><code>status_manage_by_admin</code></td><td>string</td><td><code>pending</code>, <code>in-progress</code>, <code>completed</code>, <code>cancelled</code>, <code>cancellation-in-process</code>, etc.</td></tr>
                  <tr><td><code>reason</code></td><td>text</td><td>Cancellation reason</td></tr>
                  <tr><td><code>completed_at</code></td><td>timestamp</td><td>When order was completed</td></tr>
                  <tr><td><code>meta</code></td><td>json</td><td>Extra metadata</td></tr>
                </tbody>
              </table>
            </div>

            <ul>
              <li><strong>Creation:</strong> Order is created first; automation job uses <code>order_id</code>, <code>provider_type</code>, and reorder_info (domains, prefix variants).</li>
              <li><strong>Cancellation:</strong> <code>status_manage_by_admin</code> set to <code>cancelled</code> or <code>cancellation-in-process</code>; <code>reason</code> stored when cancelling.</li>
            </ul>

            <h3 class="h5 mt-4">3.2 Table: <code>order_provider_splits</code></h3>
            <p>One row per (order, provider_slug). Holds domain list, mailbox JSON, domain statuses, and provider-specific IDs.</p>

            <div class="table-responsive">
              <table class="table table-sm table-striped align-middle">
                <thead>
                  <tr>
                    <th>Column</th>
                    <th>Type</th>
                    <th>Purpose</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>id</code></td><td>bigint PK</td><td>Split ID</td></tr>
                  <tr><td><code>order_id</code></td><td>bigint FK</td><td>Order</td></tr>
                  <tr><td><code>provider_slug</code></td><td>string</td><td><code>mailin</code>, <code>premiuminboxes</code>, <code>mailrun</code></td></tr>
                  <tr><td><code>provider_name</code></td><td>string</td><td>Display name</td></tr>
                  <tr><td><code>split_percentage</code></td><td>decimal(5,2)</td><td>% of order assigned to this provider</td></tr>
                  <tr><td><code>domain_count</code></td><td>integer</td><td>Number of domains in this split</td></tr>
                  <tr><td><code>domains</code></td><td>json</td><td>Array of domain names</td></tr>
                  <tr><td><code>mailboxes</code></td><td>json</td><td>Nested per domain/prefix (id, name, mailbox, password, mailbox_id, deleted_at)</td></tr>
                  <tr><td><code>domain_statuses</code></td><td>json</td><td>Per-domain status + domain_id + nameservers</td></tr>
                  <tr><td><code>all_domains_active</code></td><td>boolean</td><td>True when every domain in this split is active</td></tr>
                  <tr><td><code>priority</code></td><td>integer</td><td>Provider priority</td></tr>
                  <tr><td><code>external_order_id</code></td><td>string</td><td>PremiumInboxes/Mailrun order ID from provider</td></tr>
                  <tr><td><code>client_order_id</code></td><td>string</td><td>Provider client reference</td></tr>
                  <tr><td><code>order_status</code></td><td>string</td><td>Provider order status</td></tr>
                  <tr><td><code>webhook_received_at</code></td><td>timestamp</td><td>When webhook was received</td></tr>
                  <tr><td><code>metadata</code></td><td>json</td><td>Provider-specific metadata</td></tr>
                  <tr><td><code>timestamps</code></td><td>timestamps</td><td><code>created_at</code>, <code>updated_at</code></td></tr>
                </tbody>
              </table>
            </div>

            <ul>
              <li><strong>Creation:</strong> Rows created/updated by <strong>ProcessMailAutomationJob</strong> using <strong>DomainSplitService</strong>.</li>
              <li><strong>Deletion:</strong> Provider’s <code>deleteMailboxesFromSplit</code> updates <code>mailboxes</code> (sets <code>deleted_at</code>, optionally <code>mailbox_id</code>); no row delete.</li>
            </ul>

            <h3 class="h5 mt-4">3.3 Table: <code>smtp_provider_splits</code></h3>
            <p>Configuration per provider (credentials and split %). Not per order.</p>

            <div class="table-responsive">
              <table class="table table-sm table-striped align-middle">
                <thead>
                  <tr>
                    <th>Column</th>
                    <th>Type</th>
                    <th>Purpose</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>slug</code></td><td>string unique</td><td><code>mailin</code>, <code>premiuminboxes</code>, <code>mailrun</code></td></tr>
                  <tr><td><code>api_endpoint</code></td><td>string</td><td>Optional base URL</td></tr>
                  <tr><td><code>email</code></td><td>string</td><td>Mailin auth</td></tr>
                  <tr><td><code>password</code></td><td>string</td><td>Auth/token</td></tr>
                  <tr><td><code>api_secret</code></td><td>string</td><td>PremiumInboxes/Mailrun secret</td></tr>
                  <tr><td><code>additional_config</code></td><td>json</td><td>Extra settings</td></tr>
                  <tr><td><code>split_percentage</code></td><td>decimal(5,2)</td><td>% of domains to assign</td></tr>
                  <tr><td><code>priority</code></td><td>integer</td><td>Assignment order</td></tr>
                  <tr><td><code>is_active</code></td><td>boolean</td><td>Whether used in splitting</td></tr>
                  <tr><td><code>deleted_at</code></td><td>timestamp</td><td>Soft delete</td></tr>
                </tbody>
              </table>
            </div>

            <h3 class="h5 mt-4">3.4 Table: <code>order_emails</code> (Legacy / single-provider SMTP &amp; Google/365)</h3>
            <p>Used for legacy SMTP (no splits) and for Google/365 mailbox list.</p>

            <div class="table-responsive">
              <table class="table table-sm table-striped align-middle">
                <thead>
                  <tr>
                    <th>Column</th>
                    <th>Type</th>
                    <th>Purpose</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>order_id</code></td><td>bigint FK</td><td>Order</td></tr>
                  <tr><td><code>email</code></td><td>string</td><td>Email address</td></tr>
                  <tr><td><code>password</code></td><td>string</td><td>Mailbox password</td></tr>
                  <tr><td><code>mailin_mailbox_id</code></td><td>unsignedBigInteger nullable</td><td>Mailin.ai mailbox ID (deletion)</td></tr>
                  <tr><td><code>mailin_domain_id</code></td><td>unsignedBigInteger nullable</td><td>Mailin domain ID</td></tr>
                  <tr><td><code>provider_type</code></td><td>string nullable</td><td>Google / Microsoft 365</td></tr>
                  <tr><td><code>domain</code></td><td>string nullable</td><td>Domain part</td></tr>
                </tbody>
              </table>
            </div>

            <h3 class="h5 mt-4">3.5 Table: <code>reorder_infos</code></h3>
            <p>Stores order-level form data (domains, prefix variants, credentials, etc.). One-to-many with orders.</p>

            <div class="table-responsive">
              <table class="table table-sm table-striped align-middle">
                <thead>
                  <tr>
                    <th>Column</th>
                    <th>Type</th>
                    <th>Purpose</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>domains</code></td><td>json/array</td><td>Domain list for the order</td></tr>
                  <tr><td><code>prefix_variants</code></td><td>array</td><td><code>prefix_variant_1</code>, <code>prefix_variant_2</code>, ...</td></tr>
                  <tr><td><code>prefix_variants_details</code></td><td>json</td><td>Per-variant first_name, last_name, etc.</td></tr>
                  <tr><td><code>inboxes_per_domain</code></td><td>int</td><td>How many mailboxes per domain</td></tr>
                </tbody>
              </table>
            </div>
          </section>

          <!-- 4 -->
          <section id="4-order-creation-with-automation" class="mb-5">
            <h2 class="h4 section-title">4. Order Creation with Automation</h2>

            <h3 class="h5 mt-3">4.1 Trigger</h3>
            <p>After an order is created/updated (and not saved as draft), the app dispatches <strong>ProcessMailAutomationJob</strong> with:</p>
            <ul>
              <li><code>order_id</code></li>
              <li><code>domains</code> (from reorder_info)</li>
              <li><code>prefix_variants</code> (from reorder_info)</li>
              <li><code>user_id</code></li>
              <li><code>provider_type</code></li>
            </ul>

            <p class="mb-2"><strong>Example (Customer OrderController):</strong></p>
            <pre><code class="language-php">\App\Jobs\MailAutomation\ProcessMailAutomationJob::dispatch(
    $order-&gt;id,
    $mailboxJobData['domains'],
    $mailboxJobData['prefix_variants'],
    $mailboxJobData['user_id'],
    $mailboxJobData['provider_type']
);</code></pre>

            <h3 class="h5 mt-4">4.2 Job Flow (ProcessMailAutomationJob)</h3>
            <p class="mb-2"><strong>Location:</strong> <code>app/Jobs/MailAutomation/ProcessMailAutomationJob.php</code></p>

            <ol>
              <li>
                <strong>Split and save domains</strong><br>
                <code>DomainSplitService::splitDomains($domains)</code> returns an array keyed by provider slug.
                The job updateOrCreates a row in <code>order_provider_splits</code> per provider that got domains.
              </li>
              <li class="mt-2">
                <strong>Activate domains</strong><br>
                <code>DomainActivationService::activateDomainsForOrder($order)</code> runs for each split:
                conflict check, domain status check, and domain transfer if needed. Updates <code>domain_statuses</code> and <code>all_domains_active</code>.
              </li>
              <li class="mt-2">
                <strong>Create mailboxes (if all domains active)</strong><br>
                If <code>OrderProviderSplit::areAllDomainsActiveForOrder($orderId)</code> is true (and PremiumInboxes order is active),
                call <code>MailboxCreationService::createMailboxesForOrder(...)</code> and write into <code>order_provider_splits.mailboxes</code>.
              </li>
              <li class="mt-2">
                <strong>Complete order</strong><br>
                When all mailboxes are created and validated, update <code>orders.status_manage_by_admin = 'completed'</code> and set <code>completed_at</code>.
              </li>
            </ol>
          </section>

          <!-- Scheduler & Commands -->
          <section id="scheduler-commands" class="mb-5">
            <h2 class="h4 section-title">Scheduler &amp; Commands</h2>
            <p>Background tasks that keep orders moving when domains become active later or when manual intervention is needed.</p>

            <h3 class="h5 mt-3">mailin:check-pending-domains (scheduled every 5 minutes)</h3>
            <p class="mb-2"><strong>Command:</strong> <code>php artisan mailin:check-pending-domains</code></p>
            <p class="mb-2"><strong>Location:</strong> <code>app/Console/Commands/CheckPendingDomainsCommand.php</code></p>
            <ul>
              <li>Finds orders with <code>status_manage_by_admin = 'in-progress'</code> that have <code>order_provider_splits</code>.</li>
              <li>For each order: calls <code>DomainActivationService::activateDomainsForOrder($order)</code> to re-check domain status.</li>
              <li>If <code>OrderProviderSplit::areAllDomainsActiveForOrder($orderId)</code> is true, calls <code>MailboxCreationService::createMailboxesForOrder(...)</code> and completes the order.</li>
            </ul>
            <p class="mb-0">So orders that could not create mailboxes in the first job run (domains not yet active) are picked up automatically when domains become ready.</p>

            <h3 class="h5 mt-4">Artisan commands (manual)</h3>
            <div class="table-responsive">
              <table class="table table-sm table-striped align-middle">
                <thead>
                  <tr>
                    <th>Command</th>
                    <th>Purpose</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td><code>mailin:activate-domains</code></td><td>Activate domains for a single order (by ID). Optional <code>--bypass-existing-mailbox-check</code>.</td></tr>
                  <tr><td><code>mailin:create-mailboxes</code></td><td>Create mailboxes for an order when all domains are already active.</td></tr>
                  <tr><td><code>mailin:check-pending-domains --dry-run</code></td><td>Show which orders would be processed without making changes.</td></tr>
                </tbody>
              </table>
            </div>
            <p class="mb-0">Other scheduled tasks (draft notifications, panel capacity, domain removal, pool commands, etc.) are defined in <code>app/Console/Kernel.php</code>.</p>
          </section>

          <!-- 5 -->
          <section id="5-provider-specific-creation-mailin-premiuminboxes-mailrun" class="mb-5">
            <h2 class="h4 section-title">5. Provider-Specific Creation (Mailin, PremiumInboxes, Mailrun)</h2>

            <h3 class="h5 mt-3">5.1 Mailin</h3>
            <ul>
              <li><strong>Domain activation:</strong> <code>transferDomain</code>, <code>checkDomainStatus</code>, <code>getMailboxesByDomain</code> (conflict check). Stored in split <code>domain_statuses</code>.</li>
              <li><strong>Mailbox creation:</strong> Build payload per domain/prefix variant → <code>$provider-&gt;createMailboxes(...)</code> → write into split via <code>OrderProviderSplit::addMailbox()</code>.</li>
              <li><strong>Data in DB:</strong> <code>order_provider_splits.mailboxes</code>, <code>domain_statuses</code>, <code>all_domains_active</code>.</li>
            </ul>

            <h3 class="h5 mt-4">5.2 PremiumInboxes</h3>
            <ul>
              <li><strong>Domain activation:</strong> Handled via PremiumInboxes flow; we may only mark domains active when order is active.</li>
              <li><strong>Mailbox creation:</strong> Created on PremiumInboxes when order is placed; we fetch/list accounts and map into split JSON.</li>
              <li><strong>Data in DB:</strong> <code>external_order_id</code>, <code>order_status</code>, <code>mailboxes</code>, optionally <code>webhook_received_at</code>.</li>
            </ul>

            <h3 class="h5 mt-4">5.3 Mailrun</h3>
            <ul>
              <li><strong>Domain activation:</strong> Enrollment (domain setup, nameservers, begin/status/provision). Store status + nameservers in <code>domain_statuses</code> and enrollment UUID in <code>metadata</code>.</li>
              <li><strong>Mailbox creation:</strong> Async enrollment; mailboxes appear after provision; service updates <code>mailboxes</code> and <code>metadata</code> after webhook/polling.</li>
              <li><strong>Data in DB:</strong> <code>domains</code>, <code>domain_statuses</code>, <code>all_domains_active</code>, <code>mailboxes</code>, <code>metadata</code>.</li>
            </ul>
          </section>

          <!-- 6 -->
          <section id="6-checks-during-creation" class="mb-5">
            <h2 class="h4 section-title">6. Checks During Creation</h2>
            <ul>
              <li><strong>Domain split:</strong> Active providers from <code>smtp_provider_splits</code>; percentages total ~100%; distribute by <code>split_percentage</code> and <code>priority</code>.</li>
              <li><strong>Conflict check (Mailin):</strong> Before transfer, call <code>getMailboxesByDomain</code>; if prefix variants already exist, order is rejected.</li>
              <li><strong>Domain status:</strong> After transfer, call <code>checkDomainStatus</code>; only when status is “active” set split <code>domain_statuses</code> to active.</li>
              <li><strong>All domains active:</strong> <code>OrderProviderSplit::areAllDomainsActiveForOrder($orderId)</code> must be true before creating mailboxes.</li>
              <li><strong>Mailbox completion:</strong> Validate expected mailboxes exist, then mark order completed.</li>
            </ul>
          </section>

          <!-- 7 -->
          <section id="7-legacy-vs-new-system" class="mb-5">
            <h2 class="h4 section-title">7. Legacy vs New System</h2>

            <div class="table-responsive">
              <table class="table table-striped align-middle">
                <thead>
                  <tr>
                    <th>Aspect</th>
                    <th>Legacy</th>
                    <th>New (Splits)</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><strong>Provider</strong></td>
                    <td>Single (e.g. Mailin only)</td>
                    <td>Multiple (Mailin, PremiumInboxes, Mailrun)</td>
                  </tr>
                  <tr>
                    <td><strong>Domain assignment</strong></td>
                    <td>All domains to one provider</td>
                    <td>Split by <code>smtp_provider_splits.split_percentage</code> and priority</td>
                  </tr>
                  <tr>
                    <td><strong>Storage</strong></td>
                    <td><code>order_emails</code> (row per mailbox)</td>
                    <td><code>order_provider_splits.mailboxes</code> (JSON per domain/variant)</td>
                  </tr>
                  <tr>
                    <td><strong>Creation flow</strong></td>
                    <td>Direct Mailin (or single provider)</td>
                    <td>Job → Split → Activate → Create per split</td>
                  </tr>
                  <tr>
                    <td><strong>Deletion</strong></td>
                    <td><code>OrderCancelledService</code> deletes <code>order_emails</code> + Mailin API</td>
                    <td>Per split: <code>deleteMailboxesFromSplit</code>; updates split JSON + provider API</td>
                  </tr>
                  <tr>
                    <td><strong>Google/365</strong></td>
                    <td>Uses <code>order_emails</code>; deletion via batch job</td>
                    <td>N/A (not split across SMTP providers)</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <!-- 8 -->
          <section id="8-benefits-of-the-new-architecture" class="mb-5">
            <h2 class="h4 section-title">8. Benefits of the New Architecture</h2>
            <ul>
              <li><strong>Single interface:</strong> All providers implement <code>SmtpProviderInterface</code>; consistent activation/creation/deletion.</li>
              <li><strong>One factory:</strong> <code>CreatesProviders::createProvider($slug, $credentials)</code> used everywhere.</li>
              <li><strong>Per-provider logic:</strong> Provider-specific semantics remain encapsulated.</li>
              <li><strong>Clear data model:</strong> One row per provider split with domains, statuses, and mailboxes.</li>
              <li><strong>Safe deletion:</strong> Track <code>deleted_at</code> and avoid marking deleted on timeouts.</li>
              <li><strong>Scalability:</strong> Add provider = new implementation + config + factory entry.</li>
            </ul>
          </section>

          <!-- 9 -->
          <section id="9-cancellation-process" class="mb-5">
            <h2 class="h4 section-title">9. Cancellation Process</h2>

            <h3 class="h5 mt-3">9.1 Entry (OrderCancelledService::cancelSubscription)</h3>
            <ul>
              <li><strong>Non–Google/365:</strong> set <code>status_manage_by_admin = 'cancelled'</code> and <code>reason</code>, then call <code>deleteOrderMailboxes(...)</code>.</li>
              <li><strong>Google/365:</strong> don’t set to cancelled immediately; call <code>deleteOrderMailboxes(...)</code> which sets <code>cancellation-in-process</code> and dispatches the job.</li>
            </ul>

            <h3 class="h5 mt-4">9.2 deleteOrderMailboxes(Order $order, $reason = null)</h3>
            <p class="mb-2"><strong>Location:</strong> <code>app/Services/OrderCancelledService.php</code></p>
            <ul>
              <li>If already <code>cancellation-in-process</code>: skip.</li>
              <li>If automation disabled: skip.</li>
              <li>
                <strong>SMTP / Private SMTP:</strong>
                <ul>
                  <li>If provider splits exist: <code>deleteMailboxesFromProviderSplits(...)</code></li>
                  <li>Else: legacy <code>deleteSmtpOrderMailboxes(...)</code> using <code>order_emails</code></li>
                </ul>
              </li>
              <li>
                <strong>Google / Microsoft 365:</strong> set <code>cancellation-in-process</code>, store reason, dispatch job with batch params.
              </li>
            </ul>

            <h3 class="h5 mt-4">9.3 deleteMailboxesFromProviderSplits (New System)</h3>
            <ol>
              <li>Load credentials: <code>SmtpProviderSplit::getBySlug($split-&gt;provider_slug)-&gt;getCredentials()</code></li>
              <li>Create provider: <code>$this-&gt;createProvider($providerSlug, $credentials)</code></li>
              <li>Call: <code>$provider-&gt;deleteMailboxesFromSplit($order, $split)</code></li>
            </ol>

            <p class="mb-2"><strong>Implementation notes:</strong></p>
            <ul>
              <li><strong>Mailin:</strong> pre-fill missing <code>mailbox_id</code> via lookup; delete by <code>mailbox_id</code>; mark deleted only on success/not-found.</li>
              <li><strong>PremiumInboxes:</strong> bulk cancel by <code>external_order_id</code> and/or single delete; update split JSON.</li>
              <li><strong>Mailrun:</strong> no external API; only marks deleted in split JSON; logs for future API use.</li>
            </ul>
          </section>

          <!-- 10 -->
          <section id="10-google365-legacy-job-based-deletion" class="mb-5">
            <h2 class="h4 section-title">10. Google/365 Legacy: Job-Based Deletion</h2>

            <h3 class="h5 mt-3">10.1 Why a Job?</h3>
            <p>Google/365 orders can have many mailboxes; deletion is done in batches to avoid timeouts and to allow retries.</p>

            <h3 class="h5 mt-4">10.2 Flow</h3>
            <ol>
              <li>
                <strong>Dispatch job</strong> after setting <code>cancellation-in-process</code>:
                <pre><code class="language-php">\App\Jobs\MailinAi\DeleteGoogle365MailboxesJob::dispatch($order-&gt;id, 50, 0);</code></pre>
              </li>
              <li>
                <strong>Job processes a batch</strong> using service method, deletes via MailinAiService, returns pagination info.
              </li>
              <li>
                <strong>If has_more</strong>: dispatch next batch job with new offset.
              </li>
              <li>
                <strong>If done</strong>: update order status to <code>cancelled</code> (only if still <code>cancellation-in-process</code>).
              </li>
            </ol>
          </section>

          <!-- 11 -->
          <section id="11-key-code-snippets" class="mb-0">
            <h2 class="h4 section-title">11. Key Code Snippets</h2>

            <h3 class="h5 mt-3">11.1 Domain Split (DomainSplitService)</h3>
            <pre><code class="language-php">public function splitDomains(array $domains): array
{
    $activeProviders = SmtpProviderSplit::getActiveProviders(); // is_active = true, ordered by priority
    $sortedProviders = $activeProviders-&gt;sortBy('priority')-&gt;values();

    foreach ($sortedProviders as $provider) {
        $percentage = (float) $provider-&gt;split_percentage;
        $domainCount = (int) round($totalDomains * ($percentage / 100));
        $domainCount = min($domainCount, $remainingDomains);
        if ($domainCount &gt; 0) {
            $providerDomains[$provider-&gt;slug] = array_slice($domains, $domainIndex, $domainCount);
            $assignedCount += $domainCount;
            $domainIndex += $domainCount;
        }
    }
    // Remaining domains (rounding) assigned by priority round-robin
    return $providerDomains; // e.g. ['mailin' =&gt; [...], 'premiuminboxes' =&gt; [...], 'mailrun' =&gt; [...]]
}</code></pre>

            <h3 class="h5 mt-4">11.2 Splitting and Saving Splits (ProcessMailAutomationJob)</h3>
            <pre><code class="language-php">private function splitAndSaveDomains(Order $order): void
{
    $domainSplitService = new DomainSplitService();
    $domainSplit = $domainSplitService-&gt;splitDomains($this-&gt;domains);

    foreach ($domainSplit as $providerSlug =&gt; $providerDomains) {
        if (empty($providerDomains)) continue;

        $provider = \App\Models\SmtpProviderSplit::getBySlug($providerSlug);
        $providerName = $provider ? $provider-&gt;name : $providerSlug;

        OrderProviderSplit::updateOrCreate(
            ['order_id' =&gt; $order-&gt;id, 'provider_slug' =&gt; $providerSlug],
            [
                'provider_name' =&gt; $providerName,
                'split_percentage' =&gt; count($providerDomains) / count($this-&gt;domains) * 100,
                'domain_count' =&gt; count($providerDomains),
                'domains' =&gt; $providerDomains,
                'mailboxes' =&gt; null,
                'domain_statuses' =&gt; null,
                'all_domains_active' =&gt; false,
                'priority' =&gt; $provider ? $provider-&gt;priority : 0,
            ]
        );
    }
}</code></pre>

            <h3 class="h5 mt-4">11.3 Domain Activation Per Split (DomainActivationService)</h3>
            <pre><code class="language-php">public function activateDomainsForSplit(Order $order, OrderProviderSplit $split, bool $bypassExistingMailboxCheck = false): array
{
    $providerConfig = SmtpProviderSplit::getBySlug($split-&gt;provider_slug);
    if (!$providerConfig) { /* ... return failed */ }
    $credentials = $providerConfig-&gt;getCredentials();
    $provider = $this-&gt;createProvider($split-&gt;provider_slug, $credentials);

    return $provider-&gt;activateDomainsForSplit($order, $split, $bypassExistingMailboxCheck, $this);
}
// Provider-specific logic lives in each provider (MailinProviderService, PremiuminboxesProviderService, MailrunProviderService).
// Providers use $activationService-&gt;updateNameservers() and $activationService-&gt;rejectOrder() for callbacks.</code></pre>

            <h3 class="h5 mt-4">11.4 Mailbox Creation Per Split (MailboxCreationService)</h3>
            <pre><code class="language-php">public function createMailboxesForSplit(Order $order, OrderProviderSplit $split, array $prefixVariants, array $prefixVariantsDetails): array
{
    if ($split-&gt;provider_slug === 'premiuminboxes') {
        return $this-&gt;fetchMailboxesFromPremiumInboxes($order, $split, $prefixVariants);
    }
    if ($split-&gt;provider_slug === 'mailrun') {
        return $this-&gt;createMailboxesForMailrun($order, $split, $prefixVariants, $prefixVariantsDetails);
    }

    $providerConfig = SmtpProviderSplit::getBySlug($split-&gt;provider_slug);
    $credentials = $providerConfig-&gt;getCredentials();
    $provider = $this-&gt;createProvider($split-&gt;provider_slug, $credentials);

    if (!$provider-&gt;authenticate()) { /* ... */ }

    foreach ($split-&gt;domains ?? [] as $domain) {
        $domainResult = $this-&gt;createMailboxesForDomain($order, $split, $domain, $prefixVariants, $prefixVariantsDetails, $provider);
        $results['created'] = array_merge($results['created'], $domainResult['created']);
        $results['failed'] = array_merge($results['failed'], $domainResult['failed']);
    }
    return $results;
}</code></pre>

            <h3 class="h5 mt-4">11.5 Deletion Per Split (OrderCancelledService)</h3>
            <pre><code class="language-php">private function deleteMailboxesFromProviderSplits(Order $order, $splits)
{
    foreach ($splits as $split) {
        $providerSlug = $split-&gt;provider_slug;
        $providerConfig = \App\Models\SmtpProviderSplit::getBySlug($providerSlug);
        if (!$providerConfig) continue;

        $credentials = $providerConfig-&gt;getCredentials();
        $provider = $this-&gt;createProvider($providerSlug, $credentials);
        $result = $provider-&gt;deleteMailboxesFromSplit($order, $split);
        // Log $result['deleted'], $result['failed'], $result['skipped']
    }
    return $hasAsyncOperations;
}</code></pre>

            <h3 class="h5 mt-4">11.6 OrderProviderSplit: addMailbox &amp; markMailboxAsDeleted</h3>
            <pre><code class="language-php">// Creation: add mailbox to split JSON
public function addMailbox(string $domain, string $prefixKey, array $mailboxData): void
{
    $mailboxes = $this-&gt;mailboxes ?? [];
    if (!isset($mailboxes[$domain])) $mailboxes[$domain] = [];
    $mailboxes[$domain][$prefixKey] = $mailboxData; // id, name, mailbox, password, status, mailbox_id
    $this-&gt;mailboxes = $mailboxes;
    $this-&gt;save();
}

// Deletion: set deleted_at in split JSON (and optionally mailbox_id)
public function markMailboxAsDeleted(string $domain, string $prefixKey, $mailboxId = null): void
{
    $mailboxes = $this-&gt;mailboxes ?? [];
    $mailbox = $mailboxes[$domain][$prefixKey] ?? null;
    if (!$mailbox) return;
    $mailbox['deleted_at'] = now()-&gt;toISOString();
    if ($mailboxId !== null) $mailbox['mailbox_id'] = $mailboxId;
    $mailboxes[$domain][$prefixKey] = $mailbox;
    $this-&gt;mailboxes = $mailboxes;
    $this-&gt;save();
}</code></pre>

            <h3 class="h5 mt-4">11.7 Mailin deleteMailboxesFromSplit (Pass 1 &amp; Pass 2)</h3>
            <pre><code class="language-php">public function deleteMailboxesFromSplit(Order $order, OrderProviderSplit $split): array
{
    $mailboxes = $split-&gt;mailboxes ?? [];
    // Pass 1: Pre-fill missing mailbox_id via $this-&gt;service-&gt;lookupMailboxIdByEmail($email)
    //   → update $mailboxes[$domain][$prefixKey]['mailbox_id'] or ['deleted_at']
    if ($needsSave) {
        $split-&gt;mailboxes = $mailboxes;
        $split-&gt;save();
    }
    // Pass 2: For each mailbox with mailbox_id, call $this-&gt;service-&gt;deleteMailbox((int) $mailboxId)
    //   On success or not_found: $split-&gt;markMailboxAsDeleted($domain, $prefixKey, $mailboxId)
    return ['deleted' =&gt; $deletedCount, 'failed' =&gt; $failedCount, 'skipped' =&gt; $skippedCount];
}</code></pre>

            <h3 class="h5 mt-4">11.8 Google/365 Job Completion (DeleteGoogle365MailboxesJob)</h3>
            <pre><code class="language-php">if ($result['has_more']) {
    self::dispatch($this-&gt;orderId, $this-&gt;batchSize, $result['next_offset']);
} else {
    $this-&gt;updateOrderStatusToCancelled();
}

protected function updateOrderStatusToCancelled(): void
{
    $order = Order::find($this-&gt;orderId);
    if ($order && $order-&gt;status_manage_by_admin === 'cancellation-in-process') {
        $order-&gt;update(['status_manage_by_admin' =&gt; 'cancelled']);
    }
}</code></pre>

            <div class="alert alert-light border mt-4 mb-0">
              <i class="bi bi-info-circle me-1"></i>
              This page reflects the current design: automation + provider splits for SMTP, and a batched job for Google/365 legacy deletion.
            </div>
          </section>

          <!-- Functionality guide link -->
          <section id="functionality-guide-link" class="mb-0">
            <h2 class="h4 section-title">Functionality guide (non-technical)</h2>
            <p class="mb-2">
              For a product-level explanation of order flow, providers, statuses, and cancellation — for support and non-developers — see the Functionality Guide.
            </p>
            <a href="{{ route('admin.functionlity-guide') }}" class="btn btn-outline-primary">
              <i class="bi bi-book me-1"></i> Open Functionality Guide
            </a>
          </section>
        </div>

        <footer class="text-center text-muted small mt-3">
          Developer Guide • Flow diagrams • <a href="{{ route('admin.functionlity-guide') }}">Functionality Guide</a>
        </footer>
      </main>
    </div>
  </div>

  <!-- Bootstrap 5 JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Mermaid: render flow diagrams
    mermaid.initialize({ startOnLoad: true, theme: 'neutral', flowchart: { useMaxWidth: true } });

    // ----- Sidebar Search (Desktop + Mobile) -----
    function setupFilter(inputEl, listEl) {
      if (!inputEl || !listEl) return;
      inputEl.addEventListener('input', () => {
        const q = inputEl.value.trim().toLowerCase();
        const items = listEl.querySelectorAll('li.nav-item');
        items.forEach(li => {
          const a = li.querySelector('a');
          const text = (a?.textContent || '').toLowerCase();
          li.style.display = text.includes(q) ? '' : 'none';
        });
      });
    }

    setupFilter(document.getElementById('sidebarSearch'), document.getElementById('tocList'));
    setupFilter(document.getElementById('sidebarSearchMobile'), document.getElementById('tocListMobile'));

    // ----- Active Link Highlight (Scroll Spy) -----
    const tocLinks = Array.from(document.querySelectorAll('#tocList a.nav-link'));
    const tocLinksMobile = Array.from(document.querySelectorAll('#tocListMobile a.nav-link'));
    const sections = tocLinks
      .map(a => document.querySelector(a.getAttribute('href')))
      .filter(Boolean);

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const id = entry.target.getAttribute('id');

        // Desktop
        tocLinks.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#' + id));
        // Mobile
        tocLinksMobile.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#' + id));
      });
    }, { rootMargin: '-30% 0px -65% 0px', threshold: 0.01 });

    sections.forEach(sec => observer.observe(sec));

    // ----- Close offcanvas when clicking a link (extra safety) -----
    document.querySelectorAll('#tocListMobile a').forEach(a => {
      a.addEventListener('click', () => {
        const offcanvasEl = document.getElementById('sidebarOffcanvas');
        const instance = bootstrap.Offcanvas.getInstance(offcanvasEl);
        if (instance) instance.hide();
      });
    });
  </script>
</body>
</html>
