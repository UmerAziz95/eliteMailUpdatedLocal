# PremiumInboxes Integration - Key Adaptations Summary

## Quick Reference: API Differences

### Authentication
| Provider | Method | Header | Caching |
|----------|--------|--------|---------|
| **Mailin.ai** | Email + Password → Token | `Authorization: Bearer {token}` | Yes (59 min) |
| **PremiumInboxes** | API Key | `X-API-Key: wl_{key}` | No (always use key) |

### Flow Pattern
| Provider | Pattern | Steps |
|----------|---------|-------|
| **Mailin.ai** | Step-by-Step | 1. Transfer Domain<br>2. Wait for Active<br>3. Create Mailboxes |
| **PremiumInboxes** | Order-Based | 1. Create Order (includes domains + mailboxes)<br>2. Wait for Webhooks<br>3. Fetch Mailboxes |

### API Endpoints Mapping

#### Mailin.ai Endpoints:
```
POST /auth/login                    → Get token
GET  /domains?name={domain}          → Check domain
POST /domains/transfer              → Transfer domain
POST /mailboxes                     → Create mailboxes
GET  /mailboxes?domain={domain}      → Get mailboxes
DELETE /mailboxes/{id}              → Delete mailbox
```

#### PremiumInboxes Endpoints:
```
POST /purchase                       → Create order (domains + mailboxes)
GET  /orders/{order_id}              → Get order status + mailboxes
POST /orders/{order_id}/cancel      → Cancel order
DELETE /email-accounts/{id}         → Cancel mailbox
Webhook: order.ns_validated         → Nameservers validated
Webhook: order.active               → Order active, mailboxes ready
Webhook: order.buildout_issue       → Error occurred
```

---

## Critical Adaptation Points

### 1. Interface Method Mapping

The `SmtpProviderInterface` methods need to be adapted for PremiumInboxes:

| Interface Method | Mailin.ai Implementation | PremiumInboxes Adaptation |
|-----------------|-------------------------|---------------------------|
| `authenticate()` | Login, cache token | Return API key (for consistency) |
| `transferDomain()` | `POST /domains/transfer` | Part of `createOrderWithDomains()` |
| `checkDomainStatus()` | `GET /domains?name={}` | `GET /orders/{id}` → extract domain status |
| `createMailboxes()` | `POST /mailboxes` | Already done in order creation, just fetch |
| `getMailboxesByDomain()` | `GET /mailboxes?domain={}` | Extract from `order.email_accounts` |
| `deleteMailbox()` | `DELETE /mailboxes/{id}` | `DELETE /email-accounts/{id}` |

### 2. New Method Required

**`createOrderWithDomains()`** - PremiumInboxes-specific method that combines domain transfer + mailbox creation:

```php
public function createOrderWithDomains(
    array $domains,
    array $prefixVariants,
    array $persona,
    string $emailPassword,
    string $clientOrderId,
    ?array $sequencer = null
): array
```

This replaces the separate `transferDomain()` + `createMailboxes()` calls.

### 3. Order Context Tracking

PremiumInboxes requires `order_id` for most operations. We need to:

1. **Store `external_order_id`** in `order_provider_splits` table
2. **Store `client_order_id`** (our reference: `"order-{id}-premiuminboxes"`)
3. **Track `order_status`** (ns_validation_pending, active, buildout_issue)
4. **Set order context** in provider instance: `$provider->setOrderId($orderId)`

### 4. Webhook Integration

PremiumInboxes uses webhooks instead of polling:

| Event | Action |
|-------|--------|
| `order.ns_validated` | Update domain statuses to `active` |
| `order.active` | Fetch mailboxes, mark order complete |
| `order.buildout_issue` | Mark order as failed, reject |

**Webhook Security**: Verify signature using HMAC-SHA256:
```
X-Webhook-Signature: sha256={hash}
hash = HMAC-SHA256(payload, webhook_secret)
```

### 5. Status Mapping

| PremiumInboxes Status | Our System Status | Meaning |
|----------------------|-------------------|---------|
| `ns_validation_pending` | `pending` | Waiting for nameserver validation |
| `active` | `active` | Order complete, mailboxes ready |
| `buildout_issue` | `failed` | Error during mailbox creation |

---

## Implementation Flow Comparison

### Mailin.ai Flow (Current):
```
┌─────────────────┐
│  Order Created  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Split Domains  │
│  (50% Mailin)   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Transfer Domain │ → Get Nameservers
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Update NS at    │
│ Hosting Provider│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Wait for Active │ (Polling)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Create Mailboxes│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Order Complete │
└─────────────────┘
```

### PremiumInboxes Flow (New):
```
┌─────────────────┐
│  Order Created  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Split Domains  │
│  (50% Premium)  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Create Order   │ → Get Order ID + Nameservers
│  (domains +     │
│   mailboxes)    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Update NS at    │
│ Hosting Provider│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Webhook:        │
│ ns_validated    │ → Domains Active
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Webhook:        │
│ order.active    │ → Mailboxes Created
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Fetch Mailboxes │ (from order)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Order Complete │
└─────────────────┘
```

### Combined Flow (50/50 Split):
```
┌─────────────────┐
│  Order Created  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Split Domains  │
│  50% Mailin     │
│  50% Premium    │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
    ▼         ▼
┌────────┐ ┌──────────────┐
│ Mailin │ │ PremiumInbox │
│ Flow   │ │ Flow         │
└───┬────┘ └──────┬───────┘
    │             │
    └─────┬───────┘
          │
          ▼
    ┌─────────────┐
    │ Both Done?  │
    └──────┬──────┘
           │
           ▼
    ┌─────────────┐
    │Order Complete│
    └─────────────┘
```

---

## Database Schema Changes

### New Columns in `order_provider_splits`:

```php
$table->string('external_order_id')->nullable();
// Stores PremiumInboxes order_id (UUID)

$table->string('client_order_id')->nullable();
// Stores our reference: "order-{order_id}-premiuminboxes"

$table->string('order_status')->nullable();
// Stores PremiumInboxes order status: ns_validation_pending, active, buildout_issue

$table->timestamp('webhook_received_at')->nullable();
// Tracks when last webhook was received
```

### Usage Example:

```php
// After creating PremiumInboxes order
$split->update([
    'external_order_id' => '550e8400-e29b-41d4-a716-446655440000',
    'client_order_id' => 'order-123-premiuminboxes',
    'order_status' => 'ns_validation_pending',
]);

// After webhook received
$split->update([
    'order_status' => 'active',
    'webhook_received_at' => now(),
]);
```

---

## Code Structure

### New Files:

1. **`app/Services/PremiumInboxesService.php`**
   - Low-level API client
   - Handles HTTP requests
   - Error handling & rate limiting

2. **`app/Http/Controllers/Webhook/PremiumInboxesWebhookController.php`**
   - Webhook endpoint handler
   - Signature verification
   - Event processing

3. **`config/premiuminboxes.php`**
   - Configuration file
   - API URL, key, webhook secret

### Modified Files:

1. **`app/Services/Providers/PremiuminboxesProviderService.php`**
   - Implement all interface methods
   - Add `createOrderWithDomains()` method
   - Adapt existing methods for order context

2. **`app/Services/MailAutomation/DomainActivationService.php`**
   - Add `activateDomainsForPremiumInboxes()` method
   - Handle order creation instead of domain transfer

3. **`app/Services/MailAutomation/MailboxCreationService.php`**
   - Add `fetchMailboxesFromPremiumInboxes()` method
   - Fetch mailboxes from order instead of creating

4. **`app/Jobs/MailAutomation/ProcessMailAutomationJob.php`**
   - Handle mixed provider scenarios
   - Wait for PremiumInboxes webhooks

---

## Key Implementation Details

### 1. Order Creation Request Format

```php
$orderData = [
    'client_order_id' => 'order-123-premiuminboxes',
    'domains' => ['domain1.com', 'domain2.com'],
    'inboxes_per_domain' => 3,
    'persona' => [
        'first_name' => 'John',
        'last_name' => 'Smith',
        'variations' => ['john', 'jsmith', 'john.smith']
    ],
    'email_password' => 'SecurePassword123!',
    'sequencer' => [
        'platform' => 'instantly',
        'email' => 'instantly@email.com',
        'password' => 'instantly_pass'
    ]
];
```

### 2. Order Response Format

```php
[
    'order_id' => '550e8400-e29b-41d4-a716-446655440000',
    'status' => 'ns_validation_pending',
    'domains' => [
        [
            'domain' => 'domain1.com',
            'nameservers' => ['ns1.cloudflare.com', 'ns2.cloudflare.com']
        ]
    ]
]
```

### 3. Webhook Payload Format

```php
// order.ns_validated
[
    'event' => 'order.ns_validated',
    'order_id' => '550e8400-e29b-41d4-a716-446655440000',
    'client_order_id' => 'order-123-premiuminboxes',
    'timestamp' => '2026-01-09T12:00:00Z',
    'data' => [
        'domains' => [
            ['domain' => 'domain1.com', 'ns_status' => 'validated']
        ]
    ]
]

// order.active
[
    'event' => 'order.active',
    'order_id' => '550e8400-e29b-41d4-a716-446655440000',
    'client_order_id' => 'order-123-premiuminboxes',
    'timestamp' => '2026-01-09T14:00:00Z',
    'data' => [
        'email_accounts' => [
            ['email' => 'john@domain1.com', 'status' => 'active']
        ]
    ]
]
```

### 4. Webhook Signature Verification

```php
private function verifySignature(Request $request): bool
{
    $signature = $request->header('X-Webhook-Signature');
    $payload = $request->getContent();
    $secret = config('premiuminboxes.webhook_secret');

    // Extract hash: "sha256={hash}"
    preg_match('/sha256=(.+)/', $signature, $matches);
    $receivedHash = $matches[1] ?? '';

    // Calculate expected hash
    $expectedHash = hash_hmac('sha256', $payload, $secret);

    // Compare securely
    return hash_equals($expectedHash, $receivedHash);
}
```

---

## Error Handling

### PremiumInboxes Error Codes:

| Code | Error | Solution |
|------|-------|----------|
| 401 | Invalid API key | Check credentials |
| 403 | IP not allowed | Contact PremiumInboxes support |
| 409 | Duplicate client_order_id | Generate unique ID |
| 400 | Domain already in use | Reject order |
| 429 | Rate limited | Retry with backoff |
| 402 | Payment failed | Alert admin |
| Webhook | buildout_issue | Reject order, manual review |

### Retry Strategy:

```php
// For rate limiting (429)
$maxRetries = 3;
$backoff = [1, 2, 5]; // seconds

for ($i = 0; $i < $maxRetries; $i++) {
    $result = $this->makeRequest(...);
    if ($result['status_code'] !== 429) {
        break;
    }
    sleep($backoff[$i]);
}
```

---

## Testing Checklist

### Unit Tests:
- [ ] PremiumInboxesService::createOrder()
- [ ] PremiumInboxesService::getOrder()
- [ ] PremiuminboxesProviderService::createOrderWithDomains()
- [ ] PremiuminboxesProviderService::checkDomainStatus()
- [ ] PremiuminboxesProviderService::getMailboxesByDomain()
- [ ] Webhook signature verification
- [ ] Webhook event handling

### Integration Tests:
- [ ] 50/50 domain split
- [ ] Order creation with PremiumInboxes
- [ ] Nameserver update
- [ ] Webhook processing
- [ ] Mailbox fetching
- [ ] Order completion

### End-to-End Tests:
- [ ] Full order flow with both providers
- [ ] One provider fails scenario
- [ ] Webhook not received (fallback polling)
- [ ] Error recovery

---

## Configuration

### Environment Variables:

```env
PREMIUMINBOXES_API_URL=https://api.piwhitelabel.dev/api/v1
PREMIUMINBOXES_API_KEY=wl_your_api_key_here
PREMIUMINBOXES_WEBHOOK_SECRET=your_webhook_secret_here
PREMIUMINBOXES_TIMEOUT=30
```

### Database Seeder:

```php
SmtpProviderSplit::updateOrCreate(
    ['slug' => 'premiuminboxes'],
    [
        'name' => 'PremiumInboxes',
        'api_endpoint' => config('premiuminboxes.api_url'),
        'password' => config('premiuminboxes.api_key'), // API key stored here
        'split_percentage' => 50.00,
        'priority' => 2,
        'is_active' => true,
    ]
);
```

---

## Summary

**Key Takeaway**: PremiumInboxes uses an **order-based flow** where domains and mailboxes are created together, while Mailin.ai uses a **step-by-step flow**. We adapt PremiumInboxes to fit the existing interface by:

1. Creating a combined `createOrderWithDomains()` method
2. Storing `external_order_id` for order context
3. Using webhooks instead of polling
4. Fetching mailboxes from order instead of creating separately

This maintains the existing architecture while supporting both provider patterns.
