# PremiumInboxes Provider Implementation Plan (Updated with API Documentation)

## Executive Summary

This document provides a detailed implementation plan for integrating **PremiumInboxes** as a second SMTP provider with a 50/50 split alongside Mailin.ai. The plan is based on the actual PremiumInboxes API documentation and adapts it to work within the existing provider abstraction architecture.

**Key Challenge**: PremiumInboxes uses an **order-based flow** (create order → wait → mailboxes created automatically) while Mailin.ai uses a **step-by-step flow** (transfer domain → create mailboxes separately). We need to adapt PremiumInboxes to fit the existing interface contract.

---

## 1. API Analysis: PremiumInboxes vs Mailin.ai

### 1.1 Authentication Differences

| Aspect | Mailin.ai | PremiumInboxes |
|--------|-----------|----------------|
| **Method** | Email + Password → Token | API Key in Header |
| **Endpoint** | `POST /auth/login` | N/A (API key in header) |
| **Token** | JWT token (cached) | API key (no token) |
| **Header** | `Authorization: Bearer {token}` | `X-API-Key: wl_{api_key}` |

**Solution**: PremiumInboxes doesn't need authentication method - API key is always used. We'll return the API key from `authenticate()` method for consistency.

### 1.2 Flow Differences

#### **Mailin.ai Flow** (Current):
```
1. Transfer Domain → Get Nameservers
2. Update Nameservers at Hosting Provider
3. Wait for Domain to be Active
4. Create Mailboxes Separately
5. Order Complete
```

#### **PremiumInboxes Flow** (New):
```
1. Create Order (includes domains, mailboxes, persona) → Get Order ID
2. Order Status: "ns_validation_pending" → Get Nameservers
3. Update Nameservers at Hosting Provider
4. Wait for Webhook: "order.ns_validated"
5. Wait for Webhook: "order.active" (mailboxes created automatically)
6. Order Complete
```

**Key Difference**: PremiumInboxes creates mailboxes **as part of order creation**, not separately. We need to adapt our flow to handle this.

### 1.3 API Endpoint Mapping

| Function | Mailin.ai | PremiumInboxes |
|----------|-----------|----------------|
| **Auth** | `POST /auth/login` | N/A (API key) |
| **Check Domain** | `GET /domains?name={domain}` | `GET /orders/{order_id}` (check status) |
| **Transfer Domain** | `POST /domains/transfer` | Included in `POST /purchase` |
| **Create Mailboxes** | `POST /mailboxes` | Included in `POST /purchase` |
| **Get Mailboxes** | `GET /mailboxes?domain={domain}` | `GET /orders/{order_id}` (email_accounts) |
| **Delete Mailbox** | `DELETE /mailboxes/{id}` | `DELETE /email-accounts/{id}` |

### 1.4 Status Mapping

#### **PremiumInboxes Order Statuses**:
- `ns_validation_pending` → Domain transferred, waiting for NS validation
- `active` → All mailboxes created and active
- `buildout_issue` → Error during mailbox creation

#### **Mapping to Our System**:
- `ns_validation_pending` → `pending` (domain activation)
- `active` → `active` (domain active, mailboxes ready)
- `buildout_issue` → `failed` (error)

---

## 2. Architecture Adaptation Strategy

### 2.1 Interface Contract Adaptation

The existing `SmtpProviderInterface` needs to work for both patterns. We'll adapt PremiumInboxes methods to fit:

```php
// PremiumInboxes will:
// - authenticate() → Returns API key (for consistency)
// - transferDomain() → Creates order, returns nameservers
// - checkDomainStatus() → Gets order status, maps to domain status
// - createMailboxes() → Already done in transferDomain, returns order_id
// - getMailboxesByDomain() → Gets email_accounts from order
// - deleteMailbox() → Deletes email account
```

### 2.2 Flow Adaptation

**For PremiumInboxes, we'll modify the flow**:

```
Current Flow (Mailin.ai):
1. Split domains → order_provider_splits
2. Activate domains (transfer) → DomainActivationService
3. Create mailboxes → MailboxCreationService

PremiumInboxes Flow (Adapted):
1. Split domains → order_provider_splits
2. Create order (includes transfer + mailboxes) → DomainActivationService
3. Store order_id in order_provider_splits
4. Wait for webhook → Update status
5. Get mailboxes from order → MailboxCreationService (just fetches)
```

### 2.3 Database Schema Updates

**Add to `order_provider_splits` table**:
- `external_order_id` (string, nullable) - PremiumInboxes order_id
- `client_order_id` (string, nullable) - Our order reference for PremiumInboxes
- `order_status` (string, nullable) - PremiumInboxes order status
- `webhook_received_at` (timestamp, nullable) - Last webhook timestamp

**Migration needed**: Add these columns to track PremiumInboxes orders.

---

## 3. Detailed Implementation Plan

### Phase 1: PremiumInboxes API Service (Week 1, Days 1-3)

#### Step 1.1: Create PremiumInboxesService
**File**: `app/Services/PremiumInboxesService.php` (new)

**Purpose**: Low-level API client for PremiumInboxes API

**Key Methods**:

```php
class PremiumInboxesService
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct(array $credentials)
    {
        $this->baseUrl = $credentials['base_url'] ?? 'https://api.piwhitelabel.dev/api/v1';
        $this->apiKey = $credentials['api_key'] ?? $credentials['password'] ?? '';
        $this->timeout = 30;
    }

    /**
     * Create order (includes domain transfer + mailbox creation)
     * POST /purchase
     */
    public function createOrder(array $orderData): array
    {
        // Request:
        // {
        //   "client_order_id": "order-123",
        //   "domains": ["domain1.com", "domain2.com"],
        //   "inboxes_per_domain": 3,
        //   "persona": {
        //     "first_name": "John",
        //     "last_name": "Smith",
        //     "variations": ["john", "jsmith"]
        //   },
        //   "email_password": "SecurePass123!",
        //   "sequencer": {
        //     "platform": "instantly",
        //     "email": "instantly@email.com",
        //     "password": "instantly_pass"
        //   }
        // }
        
        // Response:
        // {
        //   "order_id": "uuid",
        //   "status": "ns_validation_pending",
        //   "domains": [{"domain": "...", "nameservers": [...]}]
        // }
    }

    /**
     * Get order status
     * GET /orders/{order_id}
     */
    public function getOrder(string $orderId): array
    {
        // Returns full order with status, email_accounts, etc.
    }

    /**
     * List orders (for debugging)
     * GET /orders
     */
    public function listOrders(array $filters = []): array
    {
        // Optional: for admin/debugging
    }

    /**
     * Cancel order
     * POST /orders/{order_id}/cancel
     */
    public function cancelOrder(string $orderId): array
    {
        // Cancel entire order
    }

    /**
     * Cancel single email account
     * DELETE /email-accounts/{email_account_id}
     */
    public function cancelEmailAccount(string $emailAccountId): array
    {
        // Cancel single mailbox
    }

    /**
     * Get email accounts by domain (from order)
     * Helper method - extracts from getOrder()
     */
    public function getEmailAccountsByDomain(string $orderId, string $domain): array
    {
        $order = $this->getOrder($orderId);
        $accounts = $order['email_accounts'] ?? [];
        
        return array_filter($accounts, function($account) use ($domain) {
            return $account['domain'] === $domain;
        });
    }

    /**
     * Make authenticated API request
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = rtrim($this->baseUrl, '/') . $endpoint;
        
        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->{strtolower($method)}($url, $data);

        // Handle errors
        if ($response->status() === 401) {
            // Invalid API key
        } elseif ($response->status() === 403) {
            // IP not allowed
        } elseif ($response->status() === 429) {
            // Rate limited - retry with backoff
        }

        return [
            'success' => $response->successful(),
            'status_code' => $response->status(),
            'data' => $response->json(),
            'error' => $response->failed() ? ($response->json()['detail'] ?? 'Unknown error') : null,
        ];
    }
}
```

**Implementation Details**:
- Use `X-API-Key` header for all requests
- Handle rate limiting (429) with exponential backoff
- Map PremiumInboxes errors to standard format
- Comprehensive logging

---

#### Step 1.2: Update PremiuminboxesProviderService
**File**: `app/Services/Providers/PremiuminboxesProviderService.php` (update)

**Purpose**: Adapt PremiumInboxes order-based flow to match interface contract

**Key Adaptation Strategy**:

```php
class PremiuminboxesProviderService implements SmtpProviderInterface
{
    private PremiumInboxesService $service;
    private array $credentials;
    private ?string $currentOrderId = null; // Track order for this provider instance

    public function authenticate(): ?string
    {
        // PremiumInboxes doesn't need authentication
        // Return API key for consistency with interface
        return $this->credentials['api_key'] ?? $this->credentials['password'] ?? null;
    }

    public function transferDomain(string $domain): array
    {
        // For PremiumInboxes, we can't transfer a single domain
        // This will be called during order creation
        // Return standard format for compatibility
        return [
            'success' => false,
            'message' => 'PremiumInboxes requires order creation, not single domain transfer',
            'name_servers' => [],
        ];
    }

    /**
     * Create order with domains and mailboxes
     * This replaces both transferDomain() and createMailboxes() for PremiumInboxes
     */
    public function createOrderWithDomains(
        array $domains,
        array $prefixVariants,
        array $persona,
        string $emailPassword,
        string $clientOrderId,
        ?array $sequencer = null
    ): array {
        $orderData = [
            'client_order_id' => $clientOrderId,
            'domains' => $domains,
            'inboxes_per_domain' => count($prefixVariants),
            'persona' => $persona,
            'email_password' => $emailPassword,
        ];

        if ($sequencer) {
            $orderData['sequencer'] = $sequencer;
        }

        $result = $this->service->createOrder($orderData);

        if ($result['success']) {
            $this->currentOrderId = $result['data']['order_id'] ?? null;
            
            // Extract nameservers from response
            $nameServers = [];
            foreach ($result['data']['domains'] ?? [] as $domainData) {
                $nameServers = array_merge($nameServers, $domainData['nameservers'] ?? []);
            }

            return [
                'success' => true,
                'order_id' => $this->currentOrderId,
                'status' => $result['data']['status'] ?? 'ns_validation_pending',
                'name_servers' => array_unique($nameServers),
                'domains' => $result['data']['domains'] ?? [],
            ];
        }

        return [
            'success' => false,
            'message' => $result['error'] ?? 'Failed to create order',
        ];
    }

    public function checkDomainStatus(string $domain): array
    {
        // PremiumInboxes doesn't have direct domain status check
        // We need order_id to check status
        // This will be called with order context
        
        if (!$this->currentOrderId) {
            return [
                'success' => false,
                'status' => 'unknown',
                'message' => 'Order ID not available',
            ];
        }

        $order = $this->service->getOrder($this->currentOrderId);
        
        if (!$order['success']) {
            return [
                'success' => false,
                'status' => 'unknown',
                'message' => $order['error'] ?? 'Failed to get order',
            ];
        }

        // Find domain in order
        $orderData = $order['data'];
        $domainData = collect($orderData['domains'] ?? [])
            ->firstWhere('domain', $domain);

        if (!$domainData) {
            return [
                'success' => false,
                'status' => 'not_found',
                'message' => 'Domain not found in order',
            ];
        }

        // Map PremiumInboxes status to our status
        $orderStatus = $orderData['status'] ?? 'unknown';
        $nsStatus = $domainData['ns_status'] ?? 'pending';

        $status = 'pending';
        if ($orderStatus === 'active' && $nsStatus === 'validated') {
            $status = 'active';
        } elseif ($orderStatus === 'buildout_issue') {
            $status = 'failed';
        }

        return [
            'success' => true,
            'status' => $status,
            'data' => [
                'order_status' => $orderStatus,
                'ns_status' => $nsStatus,
                'domain' => $domain,
            ],
        ];
    }

    public function createMailboxes(array $mailboxes): array
    {
        // For PremiumInboxes, mailboxes are created as part of order creation
        // This method should not be called separately
        // If called, return success with order_id reference
        
        if ($this->currentOrderId) {
            return [
                'success' => true,
                'uuid' => $this->currentOrderId,
                'message' => 'Mailboxes created as part of order',
                'mailboxes' => [],
            ];
        }

        return [
            'success' => false,
            'message' => 'Order must be created first',
        ];
    }

    public function getMailboxesByDomain(string $domain): array
    {
        if (!$this->currentOrderId) {
            return [
                'success' => false,
                'mailboxes' => [],
                'message' => 'Order ID not available',
            ];
        }

        $order = $this->service->getOrder($this->currentOrderId);
        
        if (!$order['success']) {
            return [
                'success' => false,
                'mailboxes' => [],
                'message' => $order['error'] ?? 'Failed to get order',
            ];
        }

        // Filter email_accounts by domain
        $emailAccounts = collect($order['data']['email_accounts'] ?? [])
            ->filter(function($account) use ($domain) {
                return $account['domain'] === $domain;
            })
            ->map(function($account) {
                return [
                    'id' => $account['id'],
                    'email' => $account['email'],
                    'domain' => $account['domain'],
                    'status' => $account['status'],
                    'password' => $account['password'] ?? null,
                ];
            })
            ->values()
            ->toArray();

        return [
            'success' => true,
            'mailboxes' => $emailAccounts,
        ];
    }

    public function deleteMailbox(int $mailboxId): array
    {
        // PremiumInboxes uses email_account_id (string UUID), not int
        // This method signature needs to be flexible
        $result = $this->service->cancelEmailAccount((string)$mailboxId);
        
        return [
            'success' => $result['success'] ?? false,
            'message' => $result['error'] ?? 'Mailbox deleted',
        ];
    }

    public function getProviderName(): string
    {
        return 'PremiumInboxes';
    }

    public function getProviderSlug(): string
    {
        return 'premiuminboxes';
    }

    public function isAvailable(): bool
    {
        return !empty($this->credentials['api_key'] ?? $this->credentials['password'])
            && !empty($this->credentials['base_url'] ?? 'https://api.piwhitelabel.dev/api/v1');
    }

    /**
     * Set current order ID (for context)
     */
    public function setOrderId(string $orderId): void
    {
        $this->currentOrderId = $orderId;
    }

    /**
     * Get current order ID
     */
    public function getOrderId(): ?string
    {
        return $this->currentOrderId;
    }
}
```

**Key Points**:
- `transferDomain()` and `createMailboxes()` are combined into `createOrderWithDomains()`
- `checkDomainStatus()` requires order context (order_id)
- Need to track order_id per provider instance
- Methods adapted to work with order-based flow

---

### Phase 2: Update Domain Activation Service (Week 1, Days 4-5)

#### Step 2.1: Adapt DomainActivationService for PremiumInboxes
**File**: `app/Services/MailAutomation/DomainActivationService.php` (update)

**Changes Needed**:

```php
public function activateDomainsForSplit(Order $order, OrderProviderSplit $split): array
{
    // ... existing code ...

    // Check provider type
    if ($split->provider_slug === 'premiuminboxes') {
        return $this->activateDomainsForPremiumInboxes($order, $split);
    }

    // Existing Mailin.ai logic
    // ...
}

/**
 * Handle PremiumInboxes order creation
 */
private function activateDomainsForPremiumInboxes(Order $order, OrderProviderSplit $split): array
{
    $providerConfig = SmtpProviderSplit::getBySlug('premiuminboxes');
    $credentials = $providerConfig->getCredentials();
    $provider = $this->createProvider('premiuminboxes', $credentials);

    // Prepare persona from order/reorderInfo
    $reorderInfo = $order->reorderInfo()->first();
    $user = $order->user;
    
    $persona = [
        'first_name' => $user->first_name ?? 'User',
        'last_name' => $user->last_name ?? '',
        'variations' => [], // Will be populated from prefix variants
    ];

    // Get prefix variants for this split
    $prefixVariants = []; // Extract from reorderInfo
    $emailPassword = $this->generatePassword($order->id, 1);
    
    // Create client_order_id: "order-{order_id}-{provider_slug}"
    $clientOrderId = "order-{$order->id}-premiuminboxes";

    // Create order with PremiumInboxes
    $result = $provider->createOrderWithDomains(
        $split->domains ?? [],
        $prefixVariants,
        $persona,
        $emailPassword,
        $clientOrderId,
        $this->getSequencerConfig($order) // If needed
    );

    if ($result['success']) {
        // Save order_id to split
        $split->update([
            'external_order_id' => $result['order_id'],
            'client_order_id' => $clientOrderId,
            'order_status' => $result['status'],
        ]);

        // Update domain statuses
        foreach ($split->domains ?? [] as $domain) {
            $split->setDomainStatus($domain, 'pending'); // ns_validation_pending
        }

        // Extract nameservers and update hosting provider
        $nameServers = $result['name_servers'] ?? [];
        if (!empty($nameServers)) {
            $this->updateNameservers($order, $nameServers);
        }

        return [
            'rejected' => false,
            'active' => [],
            'transferred' => $split->domains ?? [],
            'failed' => [],
        ];
    }

    // Order creation failed
    return [
        'rejected' => true,
        'reason' => $result['message'] ?? 'Failed to create PremiumInboxes order',
        'active' => [],
        'transferred' => [],
        'failed' => $split->domains ?? [],
    ];
}
```

---

### Phase 3: Update Mailbox Creation Service (Week 1, Days 6-7)

#### Step 3.1: Adapt MailboxCreationService for PremiumInboxes
**File**: `app/Services/MailAutomation/MailboxCreationService.php` (update)

**Changes Needed**:

```php
public function createMailboxesForSplit(
    Order $order,
    OrderProviderSplit $split,
    array $prefixVariants,
    array $prefixVariantsDetails
): array {
    // Check if PremiumInboxes
    if ($split->provider_slug === 'premiuminboxes') {
        return $this->fetchMailboxesFromPremiumInboxes($order, $split, $prefixVariants);
    }

    // Existing Mailin.ai logic
    // ...
}

/**
 * For PremiumInboxes, mailboxes are already created
 * We just need to fetch them from the order
 */
private function fetchMailboxesFromPremiumInboxes(
    Order $order,
    OrderProviderSplit $split,
    array $prefixVariants
): array {
    $results = [
        'created' => [],
        'failed' => [],
    ];

    if (!$split->external_order_id) {
        Log::channel('mailin-ai')->error('PremiumInboxes order ID not found', [
            'order_id' => $order->id,
            'split_id' => $split->id,
        ]);
        return $results;
    }

    $providerConfig = SmtpProviderSplit::getBySlug('premiuminboxes');
    $credentials = $providerConfig->getCredentials();
    $provider = $this->createProvider('premiuminboxes', $credentials);
    $provider->setOrderId($split->external_order_id);

    // Check order status
    $orderStatus = $this->checkPremiumInboxesOrderStatus($provider, $split->external_order_id);
    
    if ($orderStatus['status'] !== 'active') {
        Log::channel('mailin-ai')->warning('PremiumInboxes order not active yet', [
            'order_id' => $order->id,
            'premiuminboxes_order_id' => $split->external_order_id,
            'status' => $orderStatus['status'],
        ]);
        return $results;
    }

    // Fetch mailboxes for each domain
    foreach ($split->domains ?? [] as $domain) {
        $mailboxesResult = $provider->getMailboxesByDomain($domain);
        
        if ($mailboxesResult['success']) {
            foreach ($mailboxesResult['mailboxes'] as $mailbox) {
                // Map to our format
                $mailboxData = [
                    'id' => $mailbox['id'],
                    'email' => $mailbox['email'],
                    'name' => $mailbox['email'], // Extract name from email
                    'password' => $mailbox['password'] ?? null,
                    'status' => $mailbox['status'] ?? 'active',
                ];

                // Store in split
                $prefixKey = $this->extractPrefixKey($mailbox['email'], $prefixVariants);
                $split->addMailbox($domain, $prefixKey, $mailboxData);

                $results['created'][] = $mailboxData;
            }
        } else {
            $results['failed'][] = [
                'domain' => $domain,
                'error' => $mailboxesResult['message'] ?? 'Failed to fetch mailboxes',
            ];
        }
    }

    return $results;
}
```

---

### Phase 4: Webhook Integration (Week 2, Days 1-2)

#### Step 4.1: Create Webhook Controller
**File**: `app/Http/Controllers/Webhook/PremiumInboxesWebhookController.php` (new)

**Purpose**: Handle PremiumInboxes webhook events

**Events to Handle**:
- `order.ns_validated` - Nameservers validated, domains active
- `order.active` - Order active, mailboxes created
- `order.buildout_issue` - Error during mailbox creation

```php
<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderProviderSplit;
use App\Services\MailAutomation\MailboxCreationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PremiumInboxesWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify webhook signature
        if (!$this->verifySignature($request)) {
            Log::channel('mailin-ai')->warning('PremiumInboxes webhook signature verification failed');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $request->input('event');
        $orderId = $request->input('order_id');
        $clientOrderId = $request->input('client_order_id');
        $data = $request->input('data', []);

        Log::channel('mailin-ai')->info('PremiumInboxes webhook received', [
            'event' => $event,
            'order_id' => $orderId,
            'client_order_id' => $clientOrderId,
        ]);

        // Extract our order_id from client_order_id
        // Format: "order-{order_id}-premiuminboxes"
        if (preg_match('/^order-(\d+)-premiuminboxes$/', $clientOrderId, $matches)) {
            $ourOrderId = $matches[1];
            $order = Order::find($ourOrderId);

            if (!$order) {
                Log::channel('mailin-ai')->error('Order not found for PremiumInboxes webhook', [
                    'client_order_id' => $clientOrderId,
                    'our_order_id' => $ourOrderId,
                ]);
                return response()->json(['error' => 'Order not found'], 404);
            }

            // Find the provider split
            $split = OrderProviderSplit::where('order_id', $ourOrderId)
                ->where('provider_slug', 'premiuminboxes')
                ->where('external_order_id', $orderId)
                ->first();

            if (!$split) {
                Log::channel('mailin-ai')->error('OrderProviderSplit not found', [
                    'order_id' => $ourOrderId,
                    'premiuminboxes_order_id' => $orderId,
                ]);
                return response()->json(['error' => 'Split not found'], 404);
            }

            // Handle event
            switch ($event) {
                case 'order.ns_validated':
                    $this->handleNsValidated($order, $split, $data);
                    break;

                case 'order.active':
                    $this->handleOrderActive($order, $split, $data);
                    break;

                case 'order.buildout_issue':
                    $this->handleBuildoutIssue($order, $split, $data);
                    break;

                default:
                    Log::channel('mailin-ai')->warning('Unknown PremiumInboxes webhook event', [
                        'event' => $event,
                    ]);
            }

            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid client_order_id format'], 400);
    }

    private function handleNsValidated(Order $order, OrderProviderSplit $split, array $data): void
    {
        // Update domain statuses to active
        foreach ($data['domains'] ?? [] as $domainData) {
            if ($domainData['ns_status'] === 'validated') {
                $split->setDomainStatus($domainData['domain'], 'active');
            }
        }

        // Check if all domains are active
        $split->checkAndUpdateAllDomainsActive();

        Log::channel('mailin-ai')->info('PremiumInboxes nameservers validated', [
            'order_id' => $order->id,
            'domains' => $data['domains'] ?? [],
        ]);
    }

    private function handleOrderActive(Order $order, OrderProviderSplit $split, array $data): void
    {
        // Update split status
        $split->update([
            'order_status' => 'active',
            'webhook_received_at' => now(),
        ]);

        // Fetch and store mailboxes
        $mailboxService = new MailboxCreationService();
        $reorderInfo = $order->reorderInfo()->first();
        
        // Extract prefix variants
        $prefixVariants = []; // Extract from reorderInfo
        $prefixVariantsDetails = []; // Extract from reorderInfo

        $result = $mailboxService->createMailboxesForSplit(
            $order,
            $split,
            $prefixVariants,
            $prefixVariantsDetails
        );

        Log::channel('mailin-ai')->info('PremiumInboxes order active, mailboxes fetched', [
            'order_id' => $order->id,
            'mailboxes_created' => count($result['created'] ?? []),
        ]);

        // Check if all splits are complete
        $this->checkOrderCompletion($order);
    }

    private function handleBuildoutIssue(Order $order, OrderProviderSplit $split, array $data): void
    {
        // Mark domains as failed
        foreach ($split->domains ?? [] as $domain) {
            $split->setDomainStatus($domain, 'failed');
        }

        $split->update([
            'order_status' => 'buildout_issue',
            'webhook_received_at' => now(),
        ]);

        // Reject order or mark for manual review
        $order->update([
            'status_manage_by_admin' => 'reject',
            'reason' => 'PremiumInboxes buildout issue: ' . ($data['reason'] ?? 'Unknown error'),
        ]);

        Log::channel('mailin-ai')->error('PremiumInboxes buildout issue', [
            'order_id' => $order->id,
            'reason' => $data['reason'] ?? 'Unknown',
        ]);
    }

    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Webhook-Signature');
        $payload = $request->getContent();
        $secret = config('premiuminboxes.webhook_secret');

        if (!$signature || !$secret) {
            return false;
        }

        // Extract sha256 hash from signature
        if (!preg_match('/sha256=(.+)/', $signature, $matches)) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals("sha256={$expected}", $signature);
    }

    private function checkOrderCompletion(Order $order): void
    {
        // Check if all provider splits have mailboxes created
        $splits = OrderProviderSplit::where('order_id', $order->id)->get();
        
        $allComplete = true;
        foreach ($splits as $split) {
            if ($split->provider_slug === 'premiuminboxes') {
                // Check if order is active
                if ($split->order_status !== 'active') {
                    $allComplete = false;
                    break;
                }
            } else {
                // Mailin.ai - check if mailboxes exist
                $mailboxes = $split->getAllMailboxes();
                if (empty($mailboxes)) {
                    $allComplete = false;
                    break;
                }
            }
        }

        if ($allComplete) {
            $order->update([
                'status_manage_by_admin' => 'completed',
                'completed_at' => now(),
            ]);

            Log::channel('mailin-ai')->info('Order completed after all providers finished', [
                'order_id' => $order->id,
            ]);
        }
    }
}
```

#### Step 4.2: Add Webhook Route
**File**: `routes/webhook.php` or `routes/web.php`

```php
Route::post('/webhook/premiuminboxes', [PremiumInboxesWebhookController::class, 'handle'])
    ->name('webhook.premiuminboxes');
```

**Note**: Webhook routes should be excluded from CSRF verification.

---

### Phase 5: Database Migration (Week 2, Day 3)

#### Step 5.1: Add Columns to order_provider_splits
**File**: `database/migrations/YYYY_MM_DD_HHMMSS_add_premiuminboxes_fields_to_order_provider_splits.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_provider_splits', function (Blueprint $table) {
            $table->string('external_order_id')->nullable()->after('provider_slug');
            $table->string('client_order_id')->nullable()->after('external_order_id');
            $table->string('order_status')->nullable()->after('client_order_id');
            $table->timestamp('webhook_received_at')->nullable()->after('order_status');
            
            $table->index('external_order_id');
            $table->index('client_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_provider_splits', function (Blueprint $table) {
            $table->dropIndex(['external_order_id']);
            $table->dropIndex(['client_order_id']);
            $table->dropColumn([
                'external_order_id',
                'client_order_id',
                'order_status',
                'webhook_received_at',
            ]);
        });
    }
};
```

---

### Phase 6: Update ProcessMailAutomationJob (Week 2, Day 4)

#### Step 6.1: Handle PremiumInboxes in Job
**File**: `app/Jobs/MailAutomation/ProcessMailAutomationJob.php` (update)

**Changes**:

```php
private function createMailboxes(Order $order): void
{
    // Check if order has PremiumInboxes splits
    $premiumInboxesSplits = OrderProviderSplit::where('order_id', $order->id)
        ->where('provider_slug', 'premiuminboxes')
        ->get();

    // For PremiumInboxes, mailboxes are created via order creation
    // We just need to wait for webhook or poll status
    // For now, we'll check status here (webhook is preferred)
    
    $allPremiumInboxesActive = true;
    foreach ($premiumInboxesSplits as $split) {
        if ($split->order_status !== 'active') {
            $allPremiumInboxesActive = false;
            break;
        }
    }

    // Only proceed if PremiumInboxes orders are active (or no PremiumInboxes splits)
    if ($allPremiumInboxesActive || $premiumInboxesSplits->isEmpty()) {
        $mailboxService = new MailboxCreationService();
        // ... existing mailbox creation logic
    } else {
        Log::channel('mailin-ai')->info('Waiting for PremiumInboxes order activation', [
            'order_id' => $order->id,
        ]);
    }
}
```

---

### Phase 7: Configuration & Testing (Week 2, Days 5-7)

#### Step 7.1: Environment Configuration
**File**: `.env`

```env
# PremiumInboxes Configuration
PREMIUMINBOXES_API_URL=https://api.piwhitelabel.dev/api/v1
PREMIUMINBOXES_API_KEY=wl_your_api_key_here
PREMIUMINBOXES_WEBHOOK_SECRET=your_webhook_secret_here
```

#### Step 7.2: Config File
**File**: `config/premiuminboxes.php` (new)

```php
<?php

return [
    'api_url' => env('PREMIUMINBOXES_API_URL', 'https://api.piwhitelabel.dev/api/v1'),
    'api_key' => env('PREMIUMINBOXES_API_KEY', ''),
    'webhook_secret' => env('PREMIUMINBOXES_WEBHOOK_SECRET', ''),
    'timeout' => env('PREMIUMINBOXES_TIMEOUT', 30),
];
```

#### Step 7.3: Database Seeder
**File**: `database/seeders/SmtpProviderSplitSeeder.php` (update or create)

```php
// Update Mailin.ai to 50%
SmtpProviderSplit::updateOrCreate(
    ['slug' => 'mailin'],
    ['split_percentage' => 50.00]
);

// Add PremiumInboxes with 50%
SmtpProviderSplit::updateOrCreate(
    ['slug' => 'premiuminboxes'],
    [
        'name' => 'PremiumInboxes',
        'api_endpoint' => config('premiuminboxes.api_url'),
        'email' => '', // Not used for PremiumInboxes
        'password' => config('premiuminboxes.api_key'), // Store API key in password field
        'split_percentage' => 50.00,
        'priority' => 2,
        'is_active' => true,
        'additional_config' => [
            'webhook_secret' => config('premiuminboxes.webhook_secret'),
        ],
    ]
);
```

---

## 4. Key Implementation Challenges & Solutions

### Challenge 1: Different Flow Patterns

**Problem**: PremiumInboxes uses order-based flow, Mailin.ai uses step-by-step.

**Solution**: 
- Create `createOrderWithDomains()` method for PremiumInboxes
- Adapt `checkDomainStatus()` to work with order context
- Store `external_order_id` in `order_provider_splits` for tracking

### Challenge 2: Mailbox Creation Timing

**Problem**: PremiumInboxes creates mailboxes automatically, Mailin.ai creates them separately.

**Solution**:
- For PremiumInboxes: Mailboxes created during order creation
- `createMailboxesForSplit()` for PremiumInboxes just fetches existing mailboxes
- Use webhook to trigger mailbox fetching when order becomes active

### Challenge 3: Domain Status Checking

**Problem**: PremiumInboxes doesn't have direct domain status endpoint.

**Solution**:
- Use `GET /orders/{order_id}` to get order status
- Map order status to domain status
- Check `ns_status` for each domain in order

### Challenge 4: Authentication

**Problem**: PremiumInboxes uses API key, not token-based auth.

**Solution**:
- `authenticate()` returns API key (for interface consistency)
- All requests use `X-API-Key` header
- No token caching needed

### Challenge 5: Webhook Integration

**Problem**: PremiumInboxes uses webhooks, Mailin.ai uses polling.

**Solution**:
- Create webhook controller
- Verify webhook signature
- Update order status based on webhook events
- Fallback to polling if webhook not received

---

## 5. Updated Interface Contract Considerations

### Option A: Keep Interface As-Is (Recommended)

**Pros**: 
- No breaking changes
- PremiumInboxes adapts to interface

**Cons**:
- Some methods don't map perfectly (e.g., `transferDomain()`)

**Implementation**: PremiumInboxes methods adapt the order-based flow to match interface.

### Option B: Extend Interface

**Pros**:
- More accurate representation

**Cons**:
- Breaking changes
- More complex

**Not Recommended**: Keep interface as-is and adapt implementations.

---

## 6. Flow Comparison

### Mailin.ai Flow (Current):
```
Order Created
  ↓
Split Domains (50/50)
  ↓
For Mailin Split:
  - Transfer Domain → Get Nameservers
  - Update Nameservers
  - Wait for Domain Active
  - Create Mailboxes
  ↓
Order Complete
```

### PremiumInboxes Flow (New):
```
Order Created
  ↓
Split Domains (50/50)
  ↓
For PremiumInboxes Split:
  - Create Order (domains + mailboxes) → Get Order ID
  - Get Nameservers from Order
  - Update Nameservers
  - Wait for Webhook: order.ns_validated
  - Wait for Webhook: order.active
  - Fetch Mailboxes from Order
  ↓
Order Complete
```

### Combined Flow (50/50 Split):
```
Order Created
  ↓
Split Domains:
  - 50% → Mailin.ai (step-by-step)
  - 50% → PremiumInboxes (order-based)
  ↓
Both Providers Process in Parallel:
  - Mailin: Transfer → Create Mailboxes
  - PremiumInboxes: Create Order → Wait → Fetch Mailboxes
  ↓
Both Complete → Order Complete
```

---

## 7. Implementation Checklist (Updated)

### Phase 1: API Service (Days 1-3)
- [ ] Create `PremiumInboxesService.php`
- [ ] Implement `createOrder()` method
- [ ] Implement `getOrder()` method
- [ ] Implement `cancelOrder()` method
- [ ] Implement `cancelEmailAccount()` method
- [ ] Add error handling and rate limiting
- [ ] Add comprehensive logging

### Phase 2: Provider Service (Days 4-5)
- [ ] Update `PremiuminboxesProviderService.php`
- [ ] Implement `createOrderWithDomains()` method
- [ ] Adapt `checkDomainStatus()` for order context
- [ ] Adapt `createMailboxes()` (fetch existing)
- [ ] Implement `getMailboxesByDomain()` from order
- [ ] Implement `deleteMailbox()` (cancel email account)
- [ ] Add order_id tracking

### Phase 3: Service Integration (Days 6-7)
- [ ] Update `DomainActivationService` for PremiumInboxes
- [ ] Add `activateDomainsForPremiumInboxes()` method
- [ ] Update `MailboxCreationService` for PremiumInboxes
- [ ] Add `fetchMailboxesFromPremiumInboxes()` method
- [ ] Update `ProcessMailAutomationJob` for mixed providers

### Phase 4: Webhook Integration (Days 8-9)
- [ ] Create `PremiumInboxesWebhookController`
- [ ] Implement signature verification
- [ ] Handle `order.ns_validated` event
- [ ] Handle `order.active` event
- [ ] Handle `order.buildout_issue` event
- [ ] Add webhook route
- [ ] Exclude from CSRF

### Phase 5: Database & Config (Day 10)
- [ ] Create migration for `order_provider_splits` columns
- [ ] Add environment variables
- [ ] Create config file
- [ ] Update seeder with 50/50 split
- [ ] Test database changes

### Phase 6: Testing (Days 11-14)
- [ ] Unit tests for `PremiumInboxesService`
- [ ] Unit tests for `PremiuminboxesProviderService`
- [ ] Integration tests for domain splitting
- [ ] Integration tests for order creation
- [ ] Webhook testing
- [ ] End-to-end testing with 50/50 split
- [ ] Error scenario testing

---

## 8. Data Flow Example

### Example: Order with 10 Domains, 3 Inboxes per Domain

**Step 1: Domain Split**
```
Total: 10 domains
Mailin.ai: 5 domains (50%)
PremiumInboxes: 5 domains (50%)
```

**Step 2: Mailin.ai Processing**
```
For each of 5 domains:
  - Transfer domain → Get nameservers
  - Update nameservers
  - Wait for active
  - Create 3 mailboxes
Result: 15 mailboxes on Mailin.ai
```

**Step 3: PremiumInboxes Processing**
```
Create order with 5 domains:
  - client_order_id: "order-123-premiuminboxes"
  - domains: [domain1, domain2, domain3, domain4, domain5]
  - inboxes_per_domain: 3
  - persona: {...}
Result: Order created, status: "ns_validation_pending"

Update nameservers for all 5 domains

Wait for webhook: order.ns_validated
  → Domains marked as active

Wait for webhook: order.active
  → Fetch 15 mailboxes from order
Result: 15 mailboxes on PremiumInboxes
```

**Step 4: Order Completion**
```
Both providers complete:
  - Mailin.ai: 15 mailboxes ✓
  - PremiumInboxes: 15 mailboxes ✓
Total: 30 mailboxes (10 domains × 3 inboxes)

Order status → "completed"
```

---

## 9. Error Handling Strategy

### PremiumInboxes-Specific Errors

| Error | HTTP Code | Handling |
|-------|-----------|----------|
| Invalid API key | 401 | Log error, mark provider unavailable |
| IP not allowed | 403 | Alert admin, log IP |
| Duplicate client_order_id | 409 | Generate unique ID, retry |
| Domain already in use | 400 | Reject order, inform customer |
| Rate limited | 429 | Retry with exponential backoff |
| Payment failed | 402 | Alert admin, pause processing |
| Buildout issue | Webhook | Reject order, manual review |

### Fallback Strategy

If PremiumInboxes fails:
1. Log error with full details
2. Mark split as failed
3. Continue with Mailin.ai (if applicable)
4. Alert admin
5. Allow manual retry

---

## 10. Monitoring & Logging

### Key Log Points

1. **Order Creation**:
   ```php
   Log::channel('mailin-ai')->info('PremiumInboxes order created', [
       'our_order_id' => $orderId,
       'premiuminboxes_order_id' => $externalOrderId,
       'client_order_id' => $clientOrderId,
       'domains' => $domains,
   ]);
   ```

2. **Webhook Received**:
   ```php
   Log::channel('mailin-ai')->info('PremiumInboxes webhook received', [
       'event' => $event,
       'order_id' => $orderId,
       'status' => $status,
   ]);
   ```

3. **Mailboxes Fetched**:
   ```php
   Log::channel('mailin-ai')->info('PremiumInboxes mailboxes fetched', [
       'order_id' => $orderId,
       'mailbox_count' => count($mailboxes),
   ]);
   ```

### Metrics to Track

- Order creation success rate
- Webhook delivery rate
- Time from order creation to active
- Mailbox fetch success rate
- Error rates by type

---

## 11. Testing Scenarios

### Scenario 1: 50/50 Split - Even Domains
- **Input**: 10 domains, 3 inboxes/domain
- **Expected**: 5 domains → Mailin, 5 domains → PremiumInboxes
- **Verify**: Both complete successfully

### Scenario 2: 50/50 Split - Odd Domains
- **Input**: 7 domains, 3 inboxes/domain
- **Expected**: 3 domains → Mailin, 4 domains → PremiumInboxes (or vice versa)
- **Verify**: All domains assigned, rounding handled

### Scenario 3: PremiumInboxes Webhook Flow
- **Input**: Order with PremiumInboxes split
- **Expected**: 
  1. Order created → `ns_validation_pending`
  2. Webhook: `order.ns_validated` → Domains active
  3. Webhook: `order.active` → Mailboxes fetched
- **Verify**: Order completes successfully

### Scenario 4: One Provider Fails
- **Input**: Order with both providers, PremiumInboxes fails
- **Expected**: Mailin.ai continues, PremiumInboxes marked failed
- **Verify**: Partial completion handled gracefully

### Scenario 5: Webhook Not Received
- **Input**: Order created, webhook delayed
- **Expected**: Fallback polling checks order status
- **Verify**: Order eventually completes

---

## 12. Rollback Plan

### If PremiumInboxes Integration Fails:

1. **Disable Provider**:
   ```sql
   UPDATE smtp_provider_splits 
   SET is_active = 0 
   WHERE slug = 'premiuminboxes';
   ```

2. **Revert Mailin.ai to 100%**:
   ```sql
   UPDATE smtp_provider_splits 
   SET split_percentage = 100.00 
   WHERE slug = 'mailin';
   ```

3. **System automatically uses Mailin.ai only**

### Code Rollback:
- Keep all PremiumInboxes code
- Simply disable in database
- No code changes needed

---

## 13. Success Criteria

### Functional Requirements
- ✅ Orders split 50/50 between providers
- ✅ PremiumInboxes orders created successfully
- ✅ Nameservers updated correctly
- ✅ Webhooks received and processed
- ✅ Mailboxes fetched from PremiumInboxes
- ✅ Order completes when both providers done
- ✅ Error handling works correctly

### Non-Functional Requirements
- ✅ Code follows existing patterns
- ✅ No breaking changes to Mailin.ai
- ✅ Comprehensive logging
- ✅ Webhook security (signature verification)
- ✅ Performance acceptable

---

## 14. Timeline (Updated)

### Week 1: Core Implementation
- **Days 1-3**: PremiumInboxesService implementation
- **Days 4-5**: PremiuminboxesProviderService implementation
- **Days 6-7**: Service integration (DomainActivation, MailboxCreation)

### Week 2: Integration & Testing
- **Days 1-2**: Webhook implementation
- **Day 3**: Database migration and configuration
- **Days 4-5**: Integration testing
- **Days 6-7**: End-to-end testing and bug fixes

**Total**: 2 weeks

---

## 15. Next Steps

### Immediate Actions:
1. ✅ API documentation reviewed
2. ⏳ Create `PremiumInboxesService.php`
3. ⏳ Update `PremiuminboxesProviderService.php`
4. ⏳ Create webhook controller
5. ⏳ Update automation services
6. ⏳ Database migration
7. ⏳ Testing

### Questions Resolved:
- ✅ API base URL: `https://api.piwhitelabel.dev/api/v1`
- ✅ Authentication: API key in `X-API-Key` header
- ✅ Order creation: `POST /purchase`
- ✅ Order status: `GET /orders/{order_id}`
- ✅ Webhook events: `order.ns_validated`, `order.active`, `order.buildout_issue`
- ✅ Mailbox retrieval: From order `email_accounts` array

---

**Document Version**: 2.0  
**Last Updated**: January 20, 2026  
**Status**: Ready for Implementation  
**Based On**: PremiumInboxes API Documentation
