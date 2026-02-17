<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" href="{{ asset('assets/favicon/favicon.png') }}" type="image/x-icon">
    <title>EliteMail Functionality Guide</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root { scroll-behavior: smooth; }
        body { background: #f5f7fb; }
        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            overflow: auto;
            border-right: 1px solid #e9ecef;
            background: #fff;
        }
        .card-soft {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 1rem;
            box-shadow: 0 8px 20px rgba(0,0,0,.04);
        }
        .section-title { scroll-margin-top: 90px; }
        .flow-wrap {
            border: 1px solid #dbe6ff;
            background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%);
            border-radius: 1rem;
            padding: 1rem;
        }
        .flow-track {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
        }
        .flow-node {
            background: #fff;
            border: 1px solid #cfe2ff;
            border-radius: .75rem;
            padding: .5rem .75rem;
            font-size: .9rem;
            font-weight: 600;
        }
        .flow-arrow {
            font-size: 1.2rem;
            color: #0d6efd;
            animation: slidePulse 1.3s ease-in-out infinite;
        }
        .flow-note {
            font-size: .85rem;
            color: #495057;
            margin-top: .5rem;
        }
        @keyframes slidePulse {
            0%,100% { transform: translateX(0); opacity: .5; }
            50% { transform: translateX(5px); opacity: 1; }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-light bg-white border-bottom sticky-top d-lg-none">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">Functionality Guide</span>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary btn-sm">Back</a>
    </div>
</nav>

<div class="container-fluid">
    <div class="row g-0">
        <aside class="col-lg-3 d-none d-lg-block sidebar p-3">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary w-100 mb-3"><i class="bi bi-arrow-left-circle"></i> Return to Dashboard</a>
            <h5 class="mb-3">Quick Navigation</h5>
            <div class="nav flex-column nav-pills gap-1 small">
                <a class="nav-link" href="#purpose">Purpose</a>
                <a class="nav-link" href="#audience">Who should use this</a>
                <a class="nav-link" href="#roles">Roles & Permissions</a>
                <a class="nav-link" href="#project-flow">Project flow (end-to-end)</a>
                <a class="nav-link" href="#main-flow">Main order flow</a>
                <a class="nav-link" href="#notifications">Notifications</a>
                <a class="nav-link" href="#uploads">Files & uploads</a>
                <a class="nav-link" href="#api-notes">API quick notes</a>
                <a class="nav-link" href="#mistakes">Common mistakes</a>
                <a class="nav-link" href="#troubleshooting">Troubleshooting</a>
                <a class="nav-link" href="#faq">FAQ</a>
                <a class="nav-link" href="#glossary">Glossary</a>
            </div>
        </aside>

        <main class="col-lg-9 p-3 p-lg-4">
            <div class="card-soft p-4 mb-4" id="purpose">
                <h1 class="h3 section-title">EliteMail Functionality Guide (Non-Technical)</h1>
                <p class="mb-2">This guide explains <strong>how to use the system step by step</strong> for both end users and admins.</p>
                <p class="mb-0">Goal: help you place orders, track progress, avoid mistakes, and resolve common issues quickly.</p>
            </div>

            <section class="card-soft p-4 mb-4" id="audience">
                <h2 class="h5 section-title">Who Should Use This Guide</h2>
                <ul>
                    <li><strong>End users:</strong> People placing and managing mailbox/service orders.</li>
                    <li><strong>Admins:</strong> Team members approving, processing, or cancelling orders.</li>
                    <li><strong>Support staff:</strong> Team members handling user questions and issue follow-up.</li>
                </ul>
                <div class="flow-wrap">
                    <div class="flow-track">
                        <span class="flow-node">User creates order</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Admin processes</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">System updates status</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">User receives delivery</span>
                    </div>
                    <div class="flow-note">Animated flow: overall lifecycle from request to completion.</div>
                </div>
            </section>

            <section class="card-soft p-4 mb-4" id="roles">
                <h2 class="h5 section-title">Roles & Permissions (RBAC)</h2>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light"><tr><th>Role</th><th>Can do</th><th>Cannot do</th></tr></thead>
                        <tbody>
                        <tr><td>End User</td><td>Create order, view own order, request cancellation</td><td>Manage other users or global settings</td></tr>
                        <tr><td>Admin</td><td>View all orders, update status, process cancellation, manage providers</td><td>Use protected developer secrets</td></tr>
                        <tr><td>Super Admin (if enabled)</td><td>Full control of users, settings, billing, and workflow policies</td><td>—</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="flow-wrap">
                    <div class="flow-track">
                        <span class="flow-node">Login</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Role is detected</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Allowed pages shown</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Restricted actions blocked</span>
                    </div>
                </div>
            </section>


            <section class="card-soft p-4 mb-4" id="project-flow">
                <h2 class="h5 section-title">Project Flow (End-to-End)</h2>
                <p>This is the full path from first signup to order delivery and after-support.</p>
                <ol>
                    <li><strong>Account setup:</strong> user registers/signs in and profile contact details are confirmed.</li>
                    <li><strong>Plan and provider choice:</strong> user selects service type, quantity, and provider option.</li>
                    <li><strong>Order creation:</strong> order is submitted and gets an initial status.</li>
                    <li><strong>Admin and system processing:</strong> internal checks + provider execution happen in background.</li>
                    <li><strong>Delivery and verification:</strong> completed data is delivered to user and admin can confirm quality.</li>
                    <li><strong>Post-order actions:</strong> support, updates, and cancellation requests are handled.</li>
                </ol>
                <div class="flow-wrap">
                    <div class="flow-track">
                        <span class="flow-node">Signup/Login</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Select Plan/Provider</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Create Order</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Background Processing</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Completed Delivery</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Support/Cancellation</span>
                    </div>
                </div>
            </section>

            <section class="card-soft p-4 mb-4" id="main-flow">
                <h2 class="h5 section-title">Step-by-Step: Main Order Flow</h2>
                <ol>
                    <li><strong>Sign in</strong> with your account.</li>
                    <li><strong>Create order</strong> by selecting service/provider details.</li>
                    <li><strong>Submit payment/subscription</strong> (if required by your plan).</li>
                    <li><strong>Wait for processing</strong> while status changes (Pending → In Progress).</li>
                    <li><strong>Receive completed result</strong> and verify mailbox/domain details.</li>
                    <li><strong>Request support/cancellation</strong> if needed.</li>
                </ol>
                <div class="flow-wrap">
                    <div class="flow-track">
                        <span class="flow-node">Pending</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">In Progress</span><span class="flow-arrow">➜</span>
                        <span class="flow-node">Completed</span>
                    </div>
                    <div class="flow-note">If cancellation is requested: In Progress ➜ Cancellation in Process ➜ Cancelled.</div>
                </div>
            </section>

            <section class="card-soft p-4 mb-4" id="notifications">
                <h2 class="h5 section-title">Notifications (Email/SMS/Webhook)</h2>
                <ul>
                    <li><strong>Email:</strong> usually sent for order created, status changed, and cancellation updates.</li>
                    <li><strong>SMS:</strong> optional in some setups for critical updates.</li>
                    <li><strong>Webhook:</strong> used for system-to-system notifications in integrated setups.</li>
                </ul>
                <p class="mb-0">If you did not receive updates, check spam, contact support, and confirm your profile contact details.</p>
            </section>

            <section class="card-soft p-4 mb-4" id="uploads">
                <h2 class="h5 section-title">File Uploads & Storage</h2>
                <ul>
                    <li>Only upload files when requested by the order workflow (proof, documents, CSV, etc.).</li>
                    <li>Use clear filenames and supported formats.</li>
                    <li>Very large files may fail; split files if needed.</li>
                    <li>Do not upload sensitive secrets in plain text.</li>
                </ul>
            </section>

            <section class="card-soft p-4 mb-4" id="api-notes">
                <h2 class="h5 section-title">API Usage Notes (Simple)</h2>
                <p>If your team uses API integrations, these basics are important:</p>
                <ul>
                    <li>Send authorization header (example: <code>Authorization: Bearer &lt;token&gt;</code>).</li>
                    <li>Common responses: <code>200</code> success, <code>401</code> unauthorized, <code>403</code> forbidden, <code>422</code> validation error, <code>429</code> too many requests.</li>
                    <li>If rate limits are enabled, retry after waiting.</li>
                </ul>
            </section>

            <section class="card-soft p-4 mb-4" id="mistakes">
                <h2 class="h5 section-title">Common Mistakes</h2>
                <ul>
                    <li>Choosing wrong provider or plan before submitting.</li>
                    <li>Ignoring order status and reopening duplicate requests.</li>
                    <li>Using old contact details and missing important updates.</li>
                    <li>Submitting cancellation without reason/details.</li>
                </ul>
            </section>

            <section class="card-soft p-4 mb-4" id="troubleshooting">
                <h2 class="h5 section-title">Troubleshooting</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-light"><tr><th>Issue</th><th>What to do</th></tr></thead>
                        <tbody>
                        <tr><td>Order stuck in Pending</td><td>Wait standard processing window, then contact admin/support with order ID.</td></tr>
                        <tr><td>No notification received</td><td>Check spam/junk, verify profile email/phone, ask support to resend.</td></tr>
                        <tr><td>Cancellation not completed</td><td>Check if status is “Cancellation in Process”; provider side may still be updating.</td></tr>
                        <tr><td>Permission denied</td><td>You are likely signed in with the wrong role/account.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card-soft p-4 mb-4" id="faq">
                <h2 class="h5 section-title">FAQs</h2>
                <p><strong>Q: Can I edit an order after submission?</strong><br>Usually no. Request support/admin help immediately if change is urgent.</p>
                <p><strong>Q: Why do I see “In Progress” for long?</strong><br>Some providers require longer processing or confirmation cycles.</p>
                <p><strong>Q: Who can cancel an order?</strong><br>Users can request cancellation; admins finalize based on policy and status.</p>
            </section>

            <section class="card-soft p-4" id="glossary">
                <h2 class="h5 section-title">Glossary (Simple)</h2>
                <ul class="mb-0">
                    <li><strong>Order:</strong> Your request submitted in the system.</li>
                    <li><strong>Provider:</strong> External/internal service source used to fulfill an order.</li>
                    <li><strong>Status:</strong> Current stage of the order lifecycle.</li>
                    <li><strong>RBAC:</strong> Role-Based Access Control; what each user role is allowed to do.</li>
                    <li><strong>Webhook:</strong> Automatic message sent from one system to another.</li>
                    <li><strong>Rate limit:</strong> Maximum number of API requests allowed in a time window.</li>
                </ul>
            </section>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
