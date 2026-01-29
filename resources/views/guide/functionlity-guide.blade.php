<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" href="{{ asset('assets/favicon/favicon.png') }}" type="image/x-icon">
  <title>Order System (Layman Guide) — Functionality Overview</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    :root { scroll-behavior: smooth; }
    body { background: #f8f9fa; }
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
    .section-title { scroll-margin-top: 90px; }
    .pill { font-size: .8rem; }
    .step {
      border-left: 4px solid rgba(13,110,253,.35);
      padding-left: 1rem;
      margin-left: .25rem;
    }
    .callout {
      background: rgba(13,110,253,.06);
      border: 1px solid rgba(13,110,253,.12);
      border-radius: .9rem;
      padding: 1rem;
    }
    .mini {
      font-size: .95rem;
    }
  </style>
</head>

<body>
  <!-- Mobile Top Bar -->
  <nav class="navbar navbar-light bg-white border-bottom sticky-top d-lg-none">
    <div class="container-fluid d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
          <i class="bi bi-list"></i> Menu
        </button>
        <span class="navbar-brand ms-2 mb-0 h1">Layman Guide</span>
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
          <i class="bi bi-people-fill text-primary fs-4"></i>
          <div>
            <div class="fw-bold">Order System</div>
            <div class="text-muted small">Simple functionality guide</div>
          </div>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input id="sidebarSearch" type="text" class="form-control" placeholder="Search in menu..." aria-label="Search">
        </div>

        <nav>
          <ul id="tocList" class="nav nav-pills flex-column gap-1">
            <li class="nav-item"><a class="nav-link" href="#what-this-is">What this system does</a></li>
            <li class="nav-item"><a class="nav-link" href="#key-terms">Key terms (simple)</a></li>
            <li class="nav-item"><a class="nav-link" href="#who-uses-it">Who uses it</a></li>
            <li class="nav-item"><a class="nav-link" href="#order-flow">How an order is completed</a></li>
            <li class="nav-item"><a class="nav-link" href="#providers">How providers work (Mailin / PremiumInboxes / Mailrun)</a></li>
            <li class="nav-item"><a class="nav-link" href="#safety-checks">Safety checks</a></li>
            <li class="nav-item"><a class="nav-link" href="#status-meaning">Order statuses (what they mean)</a></li>
            <li class="nav-item"><a class="nav-link" href="#cancellation">How cancellation works</a></li>
            <li class="nav-item"><a class="nav-link" href="#google365">Google / Microsoft 365 cancellation (special case)</a></li>
            <li class="nav-item"><a class="nav-link" href="#what-you-see">What you can see in the admin panel</a></li>
            <li class="nav-item"><a class="nav-link" href="#common-questions">Common questions</a></li>
          </ul>
        </nav>

        <hr class="my-3">
        <div class="small text-muted">
          Tip: Type in search to quickly find a topic, then click to jump.
        </div>
      </aside>

      <!-- Mobile Offcanvas Sidebar -->
      <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="sidebarOffcanvas">
        <div class="offcanvas-header">
          <h5 class="offcanvas-title">Menu</h5>
          <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
          <div class="input-group mb-3">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input id="sidebarSearchMobile" type="text" class="form-control" placeholder="Search in menu...">
          </div>
          <ul id="tocListMobile" class="nav nav-pills flex-column gap-1">
            <li class="nav-item"><a class="nav-link" href="#what-this-is" data-bs-dismiss="offcanvas">What this system does</a></li>
            <li class="nav-item"><a class="nav-link" href="#key-terms" data-bs-dismiss="offcanvas">Key terms (simple)</a></li>
            <li class="nav-item"><a class="nav-link" href="#who-uses-it" data-bs-dismiss="offcanvas">Who uses it</a></li>
            <li class="nav-item"><a class="nav-link" href="#order-flow" data-bs-dismiss="offcanvas">How an order is completed</a></li>
            <li class="nav-item"><a class="nav-link" href="#providers" data-bs-dismiss="offcanvas">How providers work</a></li>
            <li class="nav-item"><a class="nav-link" href="#safety-checks" data-bs-dismiss="offcanvas">Safety checks</a></li>
            <li class="nav-item"><a class="nav-link" href="#status-meaning" data-bs-dismiss="offcanvas">Order statuses</a></li>
            <li class="nav-item"><a class="nav-link" href="#cancellation" data-bs-dismiss="offcanvas">Cancellation</a></li>
            <li class="nav-item"><a class="nav-link" href="#google365" data-bs-dismiss="offcanvas">Google/365</a></li>
            <li class="nav-item"><a class="nav-link" href="#what-you-see" data-bs-dismiss="offcanvas">What you see</a></li>
            <li class="nav-item"><a class="nav-link" href="#common-questions" data-bs-dismiss="offcanvas">Common questions</a></li>
          </ul>
        </div>
      </div>

      <!-- Main -->
      <main class="col-lg-9 p-3 p-lg-4">
        <div class="content-card p-3 p-lg-4">
          <header class="mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
              <div>
                <h1 class="h3 mb-1">Order System — Simple Functionality Guide</h1>
                <p class="text-muted mb-0 mini">
                  A non-technical explanation of how orders are created, split across providers, completed, and cancelled.
                </p>
              </div>
              <div class="d-flex gap-2">
                <span class="badge text-bg-primary pill"><i class="bi bi-lightning-charge"></i> Automation</span>
                <span class="badge text-bg-success pill"><i class="bi bi-shield-check"></i> Safe deletion</span>
              </div>
            </div>
          </header>

          <div class="callout mb-4">
            <div class="d-flex gap-2 align-items-start">
              <i class="bi bi-info-circle text-primary fs-5"></i>
              <div class="mini">
                <div class="fw-semibold">In one line:</div>
                <div>
                  Customer buys an email inbox package → system assigns domains to different providers →
                  system activates domains → creates inboxes → shows results → and if cancelled, removes inboxes safely.
                </div>
              </div>
            </div>
          </div>

          <!-- What this is -->
          <section id="what-this-is" class="mb-5">
            <h2 class="h4 section-title">What this system does</h2>
            <p class="mini">
              This project manages <strong>email inbox orders</strong> for customers. It automatically:
            </p>
            <ul class="mini">
              <li>takes the customer’s requested domains (example: <em>example.com</em>)</li>
              <li>decides which provider will handle which domain</li>
              <li>activates domains (so they are ready for email)</li>
              <li>creates the required number of inboxes (mailboxes)</li>
              <li>stores everything in the system so support/admin can track it</li>
              <li>cancels and deletes inboxes safely when a subscription is cancelled</li>
            </ul>
          </section>

          <!-- Key terms -->
          <section id="key-terms" class="mb-5">
            <h2 class="h4 section-title">Key terms (simple)</h2>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="border rounded-4 p-3 h-100">
                  <div class="fw-semibold"><i class="bi bi-bag-check text-primary"></i> Order</div>
                  <div class="text-muted mini">A customer purchase request (e.g., “create inboxes for these domains”).</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="border rounded-4 p-3 h-100">
                  <div class="fw-semibold"><i class="bi bi-globe2 text-primary"></i> Domain</div>
                  <div class="text-muted mini">The website/email name part like <code>mybrand.com</code>.</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="border rounded-4 p-3 h-100">
                  <div class="fw-semibold"><i class="bi bi-envelope text-primary"></i> Mailbox / Inbox</div>
                  <div class="text-muted mini">An email account like <code>john@mybrand.com</code>.</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="border rounded-4 p-3 h-100">
                  <div class="fw-semibold"><i class="bi bi-arrows-split text-primary"></i> Split</div>
                  <div class="text-muted mini">
                    The system can divide domains across multiple providers (for speed, limits, and reliability).
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="border rounded-4 p-3 h-100">
                  <div class="fw-semibold"><i class="bi bi-boxes text-primary"></i> Provider</div>
                  <div class="text-muted mini">A vendor that actually creates inboxes (Mailin, PremiumInboxes, Mailrun).</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="border rounded-4 p-3 h-100">
                  <div class="fw-semibold"><i class="bi bi-gear text-primary"></i> Automation</div>
                  <div class="text-muted mini">Background processing that completes work step-by-step without manual effort.</div>
                </div>
              </div>
            </div>
          </section>

          <!-- Who uses it -->
          <section id="who-uses-it" class="mb-5">
            <h2 class="h4 section-title">Who uses it</h2>
            <ul class="mini">
              <li><strong>Customer:</strong> places an order and expects inboxes to be delivered.</li>
              <li><strong>Admin/Support team:</strong> monitors progress, checks problems, and cancels when needed.</li>
              <li><strong>The system (automation):</strong> does the heavy work: split, activate, create, verify, delete.</li>
            </ul>
          </section>

          <!-- Order Flow -->
          <section id="order-flow" class="mb-5">
            <h2 class="h4 section-title">How an order is completed</h2>

            <div class="step mini">
              <h3 class="h5 mt-2">Step 1 — Order is created</h3>
              <p class="text-muted mb-2">
                Customer submits domains and required inbox patterns (like names/prefixes). The system saves the order.
              </p>

              <h3 class="h5 mt-4">Step 2 — Domains are split between providers</h3>
              <p class="text-muted mb-2">
                If multiple providers are enabled, the system distributes domains by configured percentages.
                Example: 40% Mailin, 40% PremiumInboxes, 20% Mailrun.
              </p>

              <h3 class="h5 mt-4">Step 3 — Domains are activated</h3>
              <p class="text-muted mb-2">
                The system checks if each domain is ready for email. If not ready, it starts activation steps
                (like transfer/setup or waiting for provider readiness).
              </p>

              <h3 class="h5 mt-4">Step 4 — Mailboxes are created</h3>
              <p class="text-muted mb-2">
                Once domains are active, the system creates the required email inboxes on the correct provider.
                Each created mailbox is saved with username and password so it can be delivered.
              </p>

              <h3 class="h5 mt-4">Step 5 — Final verification & completion</h3>
              <p class="text-muted mb-0">
                The system verifies the expected number of mailboxes exist. Then it marks the order as <strong>Completed</strong>.
              </p>
            </div>

            <div class="alert alert-light border mt-4 mb-0 mini">
              <i class="bi bi-check2-circle me-1"></i>
              Result: customer receives working inboxes, and support can see exactly which provider owns each domain/inbox.
            </div>
          </section>

          <!-- Providers -->
          <section id="providers" class="mb-5">
            <h2 class="h4 section-title">How providers work (Mailin / PremiumInboxes / Mailrun)</h2>

            <div class="row g-3 mini">
              <div class="col-lg-4">
                <div class="border rounded-4 p-3 h-100">
                  <div class="fw-semibold"><i class="bi bi-cloud text-primary"></i> Mailin</div>
                  <div class="text-muted">
                    We directly request domain activation and mailbox creation. Best for real-time creation.
                  </div>
                  <hr>
                  <ul class="mb-0">
                    <li>Activates domains</li>
                    <li>Creates mailboxes via API</li>
                    <li>Deletes mailboxes via API</li>
                  </ul>
                </div>
              </div>
              <div class="col-lg-4">
                <div class="border rounded-4 p-3 h-100">
                  <div class="fw-semibold"><i class="bi bi-building text-primary"></i> PremiumInboxes</div>
                  <div class="text-muted">
                    Mailboxes usually exist once their order is placed; our system “fetches” them and stores the details.
                  </div>
                  <hr>
                  <ul class="mb-0">
                    <li>Uses provider order ID</li>
                    <li>We fetch/list mailboxes</li>
                    <li>Cancellation may be bulk or single</li>
                  </ul>
                </div>
              </div>
              <div class="col-lg-4">
                <div class="border rounded-4 p-3 h-100">
                  <div class="fw-semibold"><i class="bi bi-diagram-2 text-primary"></i> Mailrun</div>
                  <div class="text-muted">
                    Uses an enrollment/setup process. It can be more asynchronous (takes time).
                  </div>
                  <hr>
                  <ul class="mb-0">
                    <li>Enrollment & nameservers</li>
                    <li>Mailboxes appear after provisioning</li>
                    <li>Deletion currently tracked locally (ready for future API)</li>
                  </ul>
                </div>
              </div>
            </div>
          </section>

          <!-- Safety checks -->
          <section id="safety-checks" class="mb-5">
            <h2 class="h4 section-title">Safety checks</h2>
            <ul class="mini">
              <li><strong>Duplicate prevention:</strong> The system checks if mailboxes already exist for a domain to avoid creating duplicates.</li>
              <li><strong>Only create when ready:</strong> Mailboxes are created only when domains are confirmed active.</li>
              <li><strong>Safe deletion:</strong> If a deletion fails due to timeout, it does not mark as deleted (so it can retry safely).</li>
              <li><strong>Clear ownership:</strong> Every domain is attached to one provider split, so support knows who owns what.</li>
            </ul>
          </section>

          <!-- Status meaning -->
          <section id="status-meaning" class="mb-5">
            <h2 class="h4 section-title">Order statuses (what they mean)</h2>

            <div class="table-responsive mini">
              <table class="table table-striped align-middle">
                <thead>
                  <tr>
                    <th>Status</th>
                    <th>Meaning in plain language</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><span class="badge text-bg-secondary">pending</span></td>
                    <td>Order is saved, but automation has not fully started yet.</td>
                  </tr>
                  <tr>
                    <td><span class="badge text-bg-info">in-progress</span></td>
                    <td>System is working: splitting, activating domains, and creating mailboxes.</td>
                  </tr>
                  <tr>
                    <td><span class="badge text-bg-success">completed</span></td>
                    <td>All mailboxes are created and verified. Delivery is ready.</td>
                  </tr>
                  <tr>
                    <td><span class="badge text-bg-warning">cancellation-in-process</span></td>
                    <td>Cancellation started. System is deleting mailboxes in the background.</td>
                  </tr>
                  <tr>
                    <td><span class="badge text-bg-dark">cancelled</span></td>
                    <td>Deletion finished (or for non-365 systems, cancellation completed immediately).</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <!-- Cancellation -->
          <section id="cancellation" class="mb-5">
            <h2 class="h4 section-title">How cancellation works</h2>

            <div class="step mini">
              <h3 class="h5 mt-2">What happens when a customer cancels?</h3>
              <p class="text-muted mb-2">
                The system removes the mailboxes that were created for that order so the customer no longer has active inboxes.
              </p>

              <h3 class="h5 mt-4">Cancellation for SMTP / Private SMTP orders</h3>
              <ul class="mb-0">
                <li>The system looks at the provider splits (which provider owns which domains).</li>
                <li>For each provider, it runs deletion steps for the mailboxes under that provider.</li>
                <li>It records each mailbox as “deleted” only after successful deletion (or confirmed not found).</li>
              </ul>
            </div>

            <div class="alert alert-light border mt-4 mb-0 mini">
              <i class="bi bi-shield-check me-1"></i>
              This prevents “half-deleted” states and supports safe retries if an API times out.
            </div>
          </section>

          <!-- Google/365 -->
          <section id="google365" class="mb-5">
            <h2 class="h4 section-title">Google / Microsoft 365 cancellation (special case)</h2>

            <p class="mini">
              Google/365 orders can contain many mailboxes. Deleting too many at once may time out, so the system deletes them
              in <strong>batches</strong> in the background.
            </p>

            <div class="step mini">
              <h3 class="h5 mt-2">How it works (simple)</h3>
              <ol class="mb-0">
                <li>Order status becomes <strong>cancellation-in-process</strong>.</li>
                <li>The system deletes a portion of mailboxes (example: 50 at a time).</li>
                <li>If more remain, it schedules the next batch automatically.</li>
                <li>When everything is deleted, the order becomes <strong>cancelled</strong>.</li>
              </ol>
            </div>

            <div class="alert alert-warning mt-4 mb-0 mini">
              <i class="bi bi-clock-history me-1"></i>
              Because it runs in batches, cancellation for Google/365 can take longer than standard SMTP cancellation.
            </div>
          </section>

          <!-- What you see -->
          <section id="what-you-see" class="mb-5">
            <h2 class="h4 section-title">What you can see in the admin panel</h2>
            <ul class="mini">
              <li><strong>Order status:</strong> pending / in-progress / completed / cancellation-in-process / cancelled</li>
              <li><strong>Domains list:</strong> which domains are part of the order</li>
              <li><strong>Provider ownership:</strong> which provider is responsible for each domain</li>
              <li><strong>Mailbox output:</strong> mailbox usernames and passwords (where applicable)</li>
              <li><strong>Reason (on cancellation):</strong> stored so support knows why it was cancelled</li>
            </ul>
          </section>

          <!-- Common Questions -->
          <section id="common-questions" class="mb-0">
            <h2 class="h4 section-title">Common questions</h2>

            <div class="accordion" id="faq">
              <div class="accordion-item">
                <h2 class="accordion-header" id="q1">
                  <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#a1">
                    Why do we split domains across multiple providers?
                  </button>
                </h2>
                <div id="a1" class="accordion-collapse collapse show" data-bs-parent="#faq">
                  <div class="accordion-body mini">
                    Splitting helps with reliability and limits. If one provider is slow or hits throttling limits, other providers can still deliver.
                    It also reduces risk of “burning” a domain too fast by spreading load.
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header" id="q2">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a2">
                    What if a provider is temporarily down?
                  </button>
                </h2>
                <div id="a2" class="accordion-collapse collapse" data-bs-parent="#faq">
                  <div class="accordion-body mini">
                    The system stores progress and can retry safely. For cancellations, it does not mark items deleted if the provider times out,
                    so it can retry again later without losing tracking.
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header" id="q3">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a3">
                    Why does Google/365 cancellation take longer?
                  </button>
                </h2>
                <div id="a3" class="accordion-collapse collapse" data-bs-parent="#faq">
                  <div class="accordion-body mini">
                    Because Google/365 can have a large number of mailboxes, the system deletes them in batches to avoid timeouts and failures.
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header" id="q4">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a4">
                    Can support tell which provider owns which mailbox?
                  </button>
                </h2>
                <div id="a4" class="accordion-collapse collapse" data-bs-parent="#faq">
                  <div class="accordion-body mini">
                    Yes. Each order stores provider “splits” so the system can clearly show which provider owns which domains and mailboxes.
                  </div>
                </div>
              </div>
            </div>

            <div class="alert alert-light border mt-4 mb-0 mini">
              <!-- <i class="bi bi-chat-dots me-1"></i> -->
            </div>
          </section>
        </div>

        <footer class="text-center text-muted small mt-3">
          Built with Bootstrap 5 • Sidebar search • Responsive layout
        </footer>
      </main>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Sidebar Search (Desktop + Mobile)
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

    // Active menu highlight based on scroll
    const desktopLinks = Array.from(document.querySelectorAll('#tocList a.nav-link'));
    const mobileLinks  = Array.from(document.querySelectorAll('#tocListMobile a.nav-link'));
    const sections = desktopLinks
      .map(a => document.querySelector(a.getAttribute('href')))
      .filter(Boolean);

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const id = entry.target.getAttribute('id');
        desktopLinks.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#' + id));
        mobileLinks.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#' + id));
      });
    }, { rootMargin: '-30% 0px -65% 0px', threshold: 0.01 });

    sections.forEach(sec => observer.observe(sec));

    // Close offcanvas on click (mobile)
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
