# Mail Automation Refactoring Workflow

## Executive Summary

This document outlines a comprehensive workflow for refactoring the mail automation system to support multiple SMTP providers (Mailin, Premiuminboxes, and Mailrun) with improved code maintainability, reusability, and proper architectural patterns.

---

## 1. Current State Analysis

### 1.1 Existing Components

#### Services
- **MailinAiService**: Monolithic service handling all Mailin.ai API operations
- **DomainSplitService**: Basic domain splitting logic based on percentage
- **CreateMailboxesOnOrderJob**: Large job (2600+ lines) handling mailbox creation

#### Models
- **SmtpProviderSplit**: Stores provider configuration (credentials, split percentages, priorities)
- **OrderEmail**: Stores created email addresses with mailbox IDs
- **DomainTransfer**: Tracks domain transfer status
- **OrderAutomation**: Tracks automation job status

#### Current Flow
1. Order created/updated → Dispatches `CreateMailboxesOnOrderJob`
2. Job splits domains across providers using `DomainSplitService`
3. Only Mailin provider is currently implemented (hardcoded)
4. Domain transfer handled inline in job
5. Mailbox creation handled inline in job
6. Order completion logic mixed with mailbox creation

### 1.2 Issues Identified

1. **Tight Coupling**: `MailinAiService` is hardcoded to Mailin API structure
2. **Monolithic Job**: `CreateMailboxesOnOrderJob` handles too many responsibilities
3. **No Provider Abstraction**: Cannot easily add new providers (Premiuminboxes, Mailrun)
4. **Mixed Concerns**: Domain transfer, mailbox creation, and order completion all in one place
5. **Poor Reusability**: Logic cannot be reused for different scenarios
6. **Limited Testability**: Large classes are difficult to unit test
7. **Domain Split Logic**: Basic percentage-based splitting, no advanced strategies

---

## 2. Target Architecture

### 2.1 Architectural Principles

1. **Repository Pattern**: Separate data access from business logic
2. **Service Layer**: Business logic in focused, single-responsibility services
3. **Provider Abstraction**: Interface-based design for SMTP providers
4. **Dependency Injection**: Loose coupling through interfaces
5. **Chunked Services**: Small, focused services that can be composed
6. **Simple Percentage-Based Splitting**: Domain split uses percentage from repository (no strategy pattern complexity)

### 2.2 Directory Structure

```
app/
├── Contracts/
│   └── Providers/
│       ├── SmtpProviderInterface.php
│       └── ProviderCredentialsInterface.php
├── Repositories/
│   ├── SmtpProviderRepository.php
│   ├── OrderRepository.php
│   ├── DomainTransferRepository.php
│   └── OrderEmailRepository.php
├── Services/
│   ├── MailAutomation/
│   │   ├── MailAutomationOrchestrator.php
│   │   ├── DomainSplitService.php (refactored)
│   │   ├── DomainRegistrationService.php
│   │   ├── MailboxCreationService.php
│   │   ├── OrderCompletionService.php
│   │   └── ProviderSelectionService.php
│   └── Providers/
│       ├── MailinProviderService.php          ← Handles Mailin API calls
│       ├── PremiuminboxesProviderService.php  ← Handles Premiuminboxes API calls
│       └── MailrunProviderService.php         ← Handles Mailrun API calls
├── Jobs/
│   └── MailAutomation/
│       ├── CreateMailboxesJob.php             ← Main job that starts the process
│       ├── TransferDomainJob.php              ← Handles domain transfers
│       └── CheckDomainStatusJob.php           ← Checks if domains are ready

Note: We use simple arrays for data transfer instead of DTOs to keep things simple.
Example: ['mailin' => ['domain1.com'], 'premium' => ['domain2.com']] for domain splits.

Domain splitting uses percentage-based logic from SmtpProviderRepository.
No strategy pattern needed - just calculate split based on split_percentage field from database.
```

---

## 3. Detailed Implementation Workflow

### Phase 1: Foundation & Contracts (Week 1)

#### Step 1.1: Create Provider Interface
**File**: `app/Contracts/Providers/SmtpProviderInterface.php`

**Purpose**: Define contract that all SMTP providers must implement

**Methods**:
```php
interface SmtpProviderInterface
{
    public function authenticate(): ?string;
    public function transferDomain(string $domain): array;
    public function checkDomainStatus(string $domain): array;
    public function createMailboxes(array $mailboxes): array;
    public function deleteMailbox(int $mailboxId): array;
    public function getMailboxesByDomain(string $domain): array;
    public function getProviderName(): string;
    public function getProviderSlug(): string;
    public function isAvailable(): bool;
}
```

**Deliverable**: Interface contract defined

---

#### Step 1.2: Create Repository Interfaces
**Files**:
- `app/Repositories/SmtpProviderRepository.php`
- `app/Repositories/OrderRepository.php`
- `app/Repositories/DomainTransferRepository.php`
- `app/Repositories/OrderEmailRepository.php`

**Purpose**: Abstract data access layer

**Key Methods**:
```php
// SmtpProviderRepository
- findActiveProviders(): Collection
- findBySlug(string $slug): ?SmtpProviderSplit
- getProviderCredentials(string $slug): array

// OrderRepository
- findWithRelations(int $orderId): ?Order
- updateStatus(int $orderId, string $status): bool

// DomainTransferRepository
- findPendingTransfers(int $orderId): Collection
- createTransfer(array $data): DomainTransfer
- updateTransferStatus(int $id, string $status): bool

// OrderEmailRepository
- findEmailsByOrder(int $orderId): Collection
- findEmailsByDomain(string $domain, int $orderId): Collection
- createEmail(array $data): OrderEmail
- getDomainsWithMailboxes(int $orderId): array
```

**Deliverable**: Repository layer established

**Note**: We use simple arrays for data transfer instead of DTOs to keep the code simple and easy to understand. Arrays are sufficient for our needs and easier to work with.

---

### Phase 2: Provider Implementation (Week 2)

#### Step 2.1: Refactor Mailin Provider
**File**: `app/Services/Providers/MailinProviderService.php`

**Purpose**: Extract Mailin logic from `MailinAiService` and implement interface

**Tasks**:
1. Implement `SmtpProviderInterface`
2. Extract authentication logic
3. Extract domain transfer logic
4. Extract mailbox creation logic
5. Extract domain status checking logic
6. Handle provider-specific error codes and responses

**Key Methods**:
- `authenticate()`: Reuse existing MailinAiService logic
- `transferDomain()`: Handle Mailin.ai domain transfer API
- `createMailboxes()`: Handle Mailin.ai mailbox creation API
- `checkDomainStatus()`: Check domain registration status

**Deliverable**: Mailin provider as standalone service implementing interface

---

#### Step 2.2: Implement Premiuminboxes Provider
**File**: `app/Services/Providers/PremiuminboxesProviderService.php`

**Purpose**: New provider implementation for Premiuminboxes

**Tasks**:
1. Research Premiuminboxes API documentation
2. Implement `SmtpProviderInterface`
3. Create authentication mechanism
4. Implement domain transfer (if supported)
5. Implement mailbox creation
6. Implement domain status checking
7. Handle provider-specific errors

**Key Considerations**:
- API endpoint structure may differ from Mailin
- Authentication method may differ
- Error response format may differ
- Rate limiting rules may differ

**Deliverable**: Premiuminboxes provider fully implemented

---

#### Step 2.3: Implement Mailrun Provider
**File**: `app/Services/Providers/MailrunProviderService.php`

**Purpose**: New provider implementation for Mailrun

**Tasks**: (Same as Step 2.2 but for Mailrun)

**Deliverable**: Mailrun provider fully implemented

**Note**: Providers are created directly in services using the slug from `SmtpProviderRepository`. No factory pattern is needed - services can instantiate providers directly based on configuration.

---

### Phase 3: Service Layer Refactoring (Week 3)

#### Step 3.1: Refactor Domain Split Service
**File**: `app/Services/MailAutomation/DomainSplitService.php` (refactored)

**Purpose**: Simple domain splitting based on percentage from repository

**How It Works**:
- Get active providers from `SmtpProviderRepository` (ordered by priority)
- Each provider has a `split_percentage` field (must total 100%)
- Split domains proportionally based on percentages
- Handle rounding errors by distributing remaining domains round-robin

**Key Improvements**:
1. Use repository to get providers (clean data access)
2. Validation: Check that percentages total 100%
3. Handle rounding errors gracefully
4. Better logging and error handling
5. Keep it simple - no strategy pattern complexity

**Methods**:
```php
class DomainSplitService
{
    public function __construct(
        private SmtpProviderRepository $providerRepository
    ) {}
    
    // Returns ['mailin' => ['domain1.com'], 'premium' => ['domain2.com']]
    public function splitDomains(array $domains): array;
    
    // Validate that active provider percentages total 100%
    private function validateSplitPercentages(Collection $providers): bool;
    
    // Handle remaining domains after rounding (round-robin by priority)
    private function distributeRemainingDomains(array $remaining, Collection $providers): array;
}
```

**Example Usage**:
```php
// Providers in database:
// - Mailin: split_percentage = 60%, priority = 1
// - Premium: split_percentage = 40%, priority = 2

// Input: ['domain1.com', 'domain2.com', 'domain3.com', 'domain4.com', 'domain5.com']
// Output: [
//   'mailin' => ['domain1.com', 'domain2.com', 'domain3.com'],      // 60% = 3 domains
//   'premium' => ['domain4.com', 'domain5.com']                      // 40% = 2 domains
// ]
```

**Why Not Use Strategy Pattern?**
- Only one splitting method needed (percentage-based)
- Splitting rules come from database configuration
- Adding complexity without benefit
- Current implementation already works well

**Deliverable**: Simple, clean domain splitting service using repository pattern

---

#### Step 3.2: Create Domain Registration Service
**File**: `app/Services/MailAutomation/DomainRegistrationService.php`

**Purpose**: Handle domain transfer/registration logic separately

**Responsibilities**:
1. Check domain registration status across all providers
2. Initiate domain transfers
3. Track transfer status
4. Handle transfer retries
5. Update DomainTransfer records

**Methods**:
```php
class DomainRegistrationService
{
    public function checkDomainStatus(
        string $domain, 
        SmtpProviderInterface $provider
    ): array;
    
    public function transferDomain(
        string $domain, 
        SmtpProviderInterface $provider,
        int $orderId
    ): DomainTransfer;
    
    public function getUnregisteredDomains(
        array $domains,
        SmtpProviderInterface $provider,
        int $orderId
    ): array;
    
    public function waitForDomainActivation(
        array $domains,
        int $orderId,
        int $maxWaitTime = 3600
    ): array;
}
```

**Deliverable**: Domain registration service extracted and tested

---

#### Step 3.3: Create Mailbox Creation Service
**File**: `app/Services/MailAutomation/MailboxCreationService.php`

**Purpose**: Handle mailbox creation logic separately

**Responsibilities**:
1. Generate mailbox data (emails, names, passwords)
2. Batch mailbox creation requests
3. Handle creation failures and retries
4. Update OrderEmail records
5. Handle provider-specific mailbox creation

**Methods**:
```php
class MailboxCreationService
{
    public function createMailboxesForDomain(
        string $domain,
        array $prefixVariants,
        SmtpProviderInterface $provider,
        int $orderId,
        int $userId
    ): array;
    
    public function batchCreateMailboxes(
        array $mailboxRequests,
        SmtpProviderInterface $provider
    ): array;
    
    public function saveMailboxRecords(
        array $mailboxes,
        int $orderId,
        string $providerSlug
    ): void;
    
    private function generateMailboxData(
        string $domain,
        array $prefixVariants
    ): array;
}
```

**Deliverable**: Mailbox creation service extracted and tested

---

#### Step 3.4: Create Provider Selection Service
**File**: `app/Services/MailAutomation/ProviderSelectionService.php`

**Purpose**: Select appropriate provider for domain allocation

**Responsibilities**:
1. Get active providers from repository
2. Select provider based on priority (from repository)
3. Validate provider availability
4. Check provider quotas/limits

**Methods**:
```php
class ProviderSelectionService
{
    public function getAvailableProviders(): Collection;
    
    public function selectProviderForDomain(string $domain): ?SmtpProviderSplit;
    
    public function canHandleDomains(
        SmtpProviderSplit $provider,
        int $domainCount
    ): bool;
}
```

**Deliverable**: Provider selection service created

---

#### Step 3.5: Create Order Completion Service
**File**: `app/Services/MailAutomation/OrderCompletionService.php`

**Purpose**: Handle order completion logic separately

**Responsibilities**:
1. Verify all domains have mailboxes
2. Check all providers processed
3. Update order status
4. Trigger completion notifications
5. Log completion metrics

**Methods**:
```php
class OrderCompletionService
{
    public function canCompleteOrder(int $orderId): bool;
    
    public function completeOrder(int $orderId): void;
    
    public function verifyAllDomainsHaveMailboxes(
        int $orderId,
        array $domains
    ): bool;
    
    private function checkAllProvidersProcessed(int $orderId): bool;
}
```

**Deliverable**: Order completion service extracted

---

#### Step 3.6: Create Orchestrator Service
**File**: `app/Services/MailAutomation/MailAutomationOrchestrator.php`

**Purpose**: Orchestrate the entire mailbox creation flow

**Responsibilities**:
1. Coordinate all services
2. Handle workflow state
3. Manage error recovery
4. Provide progress tracking

**Flow**:
```
1. Load order and validate
2. Split domains across providers (DomainSplitService)
   → Example: [domain1, domain2] → Mailin, [domain3] → Premiuminboxes
3. For each provider:
   a. Get provider from repository and create service instance
   b. Check domain registration (DomainRegistrationService)
   c. Transfer unregistered domains (DomainRegistrationService)
   d. Create mailboxes for registered domains (MailboxCreationService)
4. Wait for domain activations if needed
5. Create mailboxes for newly activated domains
6. Verify completion (OrderCompletionService)
   → Check: All domains have mailboxes? All providers processed?
7. Complete order if all done (OrderCompletionService)
```

**Methods**:
```php
class MailAutomationOrchestrator
{
    public function processOrder(int $orderId, array $domains, array $prefixVariants): array;
    
    public function processProvider(
        string $providerSlug,
        array $domains,
        int $orderId
    ): array;
    
    private function handleUnregisteredDomains(
        array $domains,
        SmtpProviderInterface $provider,
        int $orderId
    ): array;
}
```

**Deliverable**: Main orchestrator service created

---

### Phase 4: Job Refactoring (Week 4)

#### Step 4.1: Simplify Main Job
**File**: `app/Jobs/MailAutomation/CreateMailboxesJob.php` (refactored)

**Purpose**: Thin job that delegates to orchestrator

**Before**: 2600+ lines with all logic
**After**: ~100 lines, delegates to services

**Structure**:
```php
class CreateMailboxesJob implements ShouldQueue
{
    public function __construct(
        private int $orderId,
        private array $domains,
        private array $prefixVariants,
        private int $userId,
        private string $providerType
    ) {}
    
    public function handle(MailAutomationOrchestrator $orchestrator): void
    {
        try {
            $result = $orchestrator->processOrder(
                $this->orderId,
                $this->domains,
                $this->prefixVariants
            );
            
            // Log result
            // Update OrderAutomation record
        } catch (\Exception $e) {
            // Error handling
            // Update OrderAutomation with failed status
        }
    }
}
```

**Deliverable**: Simplified job that delegates to services

---

#### Step 4.2: Create Domain Transfer Job
**File**: `app/Jobs/MailAutomation/TransferDomainJob.php`

**Purpose**: Handle individual domain transfers asynchronously

**Use Case**: When domain transfer is slow, queue it separately

**Structure**:
```php
class TransferDomainJob implements ShouldQueue
{
    public function __construct(
        private int $orderId,
        private string $domain,
        private string $providerSlug
    ) {}
    
    public function handle(
        DomainRegistrationService $service,
        SmtpProviderRepository $providerRepository
    ): void {
        // Get provider config and create service directly
        $providerConfig = $providerRepository->findBySlug($this->providerSlug);
        $credentials = $providerConfig->getCredentials();
        
        // Create provider service based on slug
        $provider = $this->createProvider($this->providerSlug, $credentials);
        $service->transferDomain($this->domain, $provider, $this->orderId);
    }
    
    private function createProvider(string $slug, array $credentials): SmtpProviderInterface
    {
        return match($slug) {
            'mailin' => new MailinProviderService($credentials),
            'premiuminboxes' => new PremiuminboxesProviderService($credentials),
            'mailrun' => new MailrunProviderService($credentials),
            default => throw new InvalidProviderException("Unknown provider: {$slug}")
        };
    }
}
```

**Deliverable**: Separate job for domain transfers

---

#### Step 4.3: Create Domain Status Check Job
**File**: `app/Jobs/MailAutomation/CheckDomainStatusJob.php`

**Purpose**: Periodically check domain activation status

**Use Case**: Scheduled job to check pending domain transfers

**Structure**:
```php
class CheckDomainStatusJob implements ShouldQueue
{
    public function __construct(
        private int $orderId,
        private array $domains,
        private string $providerSlug
    ) {}
    
    public function handle(
        DomainRegistrationService $service,
        MailAutomationOrchestrator $orchestrator
    ): void {
        // Check status
        // If active, trigger mailbox creation
        // If still pending, reschedule
    }
}
```

**Deliverable**: Job for checking domain status

---

### Phase 5: Testing & Validation (Week 5)

#### Step 5.1: Unit Tests
**Files**: `tests/Unit/`

**Coverage**:
- Repository methods
- Service methods
- Provider implementations
- Repository implementations
- Service return types

**Tools**: PHPUnit

**Deliverable**: Comprehensive unit test suite

---

#### Step 5.2: Integration Tests
**Files**: `tests/Integration/`

**Coverage**:
- End-to-end mailbox creation flow
- Provider switching
- Domain splitting
- Error scenarios

**Deliverable**: Integration test suite

---

#### Step 5.3: Provider Mocking
**Files**: `tests/Mocks/`

**Purpose**: Mock provider responses for testing without real API calls

**Deliverable**: Mock implementations of all providers

---

### Phase 6: Migration & Deployment (Week 6)

#### Step 6.1: Database Updates
**Tasks**:
1. Ensure `smtp_provider_splits` table has Premiuminboxes and Mailrun entries
2. Update seeder with new providers
3. Add any missing indexes

**Migration File**: `database/migrations/xxxx_add_premium_and_mailrun_providers.php`

**Deliverable**: Database ready for new providers

---

#### Step 6.2: Configuration Updates
**Files**: 
- `config/mailin_ai.php` (keep for backward compatibility)
- `config/providers.php` (new centralized config)

**Purpose**: Centralized provider configuration

**Deliverable**: Configuration files updated

---

#### Step 6.3: Gradual Migration Strategy
**Phase A**: Deploy new code alongside old code (feature flag)
**Phase B**: Test with small percentage of orders (10%)
**Phase C**: Increase to 50%, then 100%
**Phase D**: Remove old code

**Deliverable**: Safe migration plan

---

#### Step 6.4: Monitoring & Logging
**Updates**:
1. Add metrics for each provider
2. Track domain split distribution
3. Monitor provider performance
4. Alert on provider failures

**Deliverable**: Monitoring in place

---

## 4. Code Chunking Strategy

### 4.1 Service Chunks

| Service | Lines (Est.) | Responsibility |
|---------|--------------|----------------|
| `MailAutomationOrchestrator` | 200-300 | Main flow coordination |
| `DomainSplitService` | 150-200 | Domain distribution logic |
| `DomainRegistrationService` | 200-250 | Domain transfer handling |
| `MailboxCreationService` | 200-250 | Mailbox creation logic |
| `ProviderSelectionService` | 100-150 | Provider selection |
| `OrderCompletionService` | 100-150 | Order completion logic |
| `MailinProviderService` | 400-500 | Mailin API implementation |
| `PremiuminboxesProviderService` | 400-500 | Premiuminboxes API implementation |
| `MailrunProviderService` | 400-500 | Mailrun API implementation |

**Total**: ~2000-2800 lines (vs 2600+ in single job)

**Benefits**:
- Each service < 500 lines
- Single responsibility
- Easy to test
- Easy to maintain

---

### 4.2 Repository Chunks

| Repository | Methods | Responsibility |
|-----------|---------|----------------|
| `SmtpProviderRepository` | 5-8 | Provider data access |
| `OrderRepository` | 5-8 | Order data access |
| `DomainTransferRepository` | 8-10 | Domain transfer data access |
| `OrderEmailRepository` | 8-10 | Order email data access |

**Benefits**:
- Clear data access layer
- Easy to mock for testing
- Centralized query logic

---

## 5. Key Design Patterns - Detailed Explanation

This section provides comprehensive explanations of all design patterns used in this refactoring, with real-world analogies, code examples, and visual representations to ensure easy understanding.

---

### 5.1 Repository Pattern

#### What is it?
The Repository Pattern acts as a **middle layer** between your business logic and database. Think of it like a **library catalog system** - you don't go directly to the storage room to find books; you ask the librarian (repository) who knows exactly where everything is.

#### Real-World Analogy
**Without Repository**: You go directly to the warehouse and manually search through shelves.
```
Business Logic → Directly queries database
```
Problems: Hard to test, logic scattered, tight coupling.

**With Repository**: You ask the librarian who handles all the complex search logic.
```
Business Logic → Repository → Database
```
Benefits: Clean separation, easy to test, centralized queries.

#### How It Works in Our System

**Before (Without Repository)**:
```php
// Bad: Direct database access in service
class MailboxCreationService
{
    public function createMailboxes($orderId)
    {
        // Direct Eloquent query - tight coupling!
        $order = Order::with('reorderInfo', 'plan')->find($orderId);
        $emails = OrderEmail::where('order_id', $orderId)->get();
        
        // What if we want to change how we fetch orders?
        // What if we want to cache orders?
        // What if we want to switch databases?
        // → We have to change EVERY service!
    }
}
```

**After (With Repository)**:
```php
// Good: Repository handles all data access
class OrderRepository
{
    public function findWithRelations(int $orderId): ?Order
    {
        return Order::with(['reorderInfo', 'plan', 'user'])
            ->find($orderId);
    }
    
    public function updateStatus(int $orderId, string $status): bool
    {
        return Order::where('id', $orderId)
            ->update(['status_manage_by_admin' => $status]);
    }
    
    public function findOrdersNeedingProcessing(): Collection
    {
        return Order::where('status_manage_by_admin', 'in-progress')
            ->whereHas('plan', function($q) {
                $q->where('provider_type', 'Private SMTP');
            })
            ->get();
    }
}                                                    

// Service uses repository - clean and testable
class MailboxCreationService
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {}
    
    public function createMailboxes($orderId)
    {
        // Simple call - repository handles complexity
        $order = $this->orderRepository->findWithRelations($orderId);
        
        // If we need to change how orders are fetched:
        // → Only update OrderRepository, not this service!
    }
}                                                           
```

#### Benefits Explained

1. **Easy Testing**: Mock the repository instead of mocking database
```php
// In tests, we can mock the repository easily
$mockRepository = Mockery::mock(OrderRepository::class);
$mockRepository->shouldReceive('findWithRelations')
    ->with(123)
    ->andReturn($mockOrder);

$service = new MailboxCreationService($mockRepository);
// Test service logic without hitting database!
```

2. **Centralized Queries**: All order queries in one place
```php
// If query logic changes (add eager loading, add conditions)
// → Only update OrderRepository::findWithRelations()
// → All services automatically get the improvement
```

3. **Easy to Switch Data Sources**: Change database, add caching, etc.
```php
class CachedOrderRepository implements OrderRepositoryInterface
{
    public function findWithRelations(int $orderId): ?Order
    {
        return Cache::remember("order.{$orderId}", 3600, function() use ($orderId) {
            return Order::with(['reorderInfo', 'plan'])->find($orderId);
        });
    }
}                                         
```

#### Visual Representation
```
┌─────────────────────────────────────────────────────────────┐
│                    Business Logic Layer                      │
│  (MailboxCreationService, DomainSplitService, etc.)          │
└──────────────────────┬──────────────────────────────────────┘
                       │ Uses
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                    Repository Layer                          │
│  ┌──────────────────┐  ┌──────────────────┐                │
│  │ OrderRepository  │  │ DomainRepository │                │
│  └──────────────────┘  └──────────────────┘                │
│  ┌──────────────────┐  ┌──────────────────┐                │
│  │ EmailRepository  │  │ ProviderRepository│                │
│  └──────────────────┘  └──────────────────┘                │
└──────────────────────┬──────────────────────────────────────┘
                       │ Queries
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                    Database Layer                            │
│              (MySQL, PostgreSQL, etc.)                        │
└─────────────────────────────────────────────────────────────┘
```

#### Implementation Structure
```php
// 1. Define Interface (Contract)
interface OrderRepositoryInterface
{
    public function findWithRelations(int $orderId): ?Order;
    public function updateStatus(int $orderId, string $status): bool;
}

// 2. Implement Repository
class OrderRepository implements OrderRepositoryInterface
{
    // Implementation here
}

// 3. Use in Service (Dependency Injection)
class MailboxCreationService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {}
}
```

---

### 5.2 Simple Percentage-Based Domain Splitting (Using Repository)

#### What is it?
We use **simple percentage-based splitting** from the database. No strategy pattern complexity - just get providers from repository and split based on their `split_percentage` field.

#### How It Works

**Simple Approach**: Get providers from repository, split by percentage
```php
class DomainSplitService
{
    public function __construct(
        private SmtpProviderRepository $providerRepository
    ) {}
    
    // Returns ['mailin' => ['domain1.com'], 'premium' => ['domain2.com']]
    public function splitDomains(array $domains): array
    {
        // Get active providers from repository (ordered by priority)
        $providers = $this->providerRepository->findActiveProviders();
        
        // Validate percentages total 100%
        if (!$this->validatePercentages($providers)) {
            throw new Exception('Provider percentages must total 100%');
        }
        
        $totalDomains = count($domains);
        $result = [];
        $index = 0;
        
        // Split domains based on percentage
        foreach ($providers as $provider) {
            $percentage = (float) $provider->split_percentage;
            $domainCount = (int) round($totalDomains * ($percentage / 100));
            
            if ($domainCount > 0) {
                $result[$provider->slug] = array_slice($domains, $index, $domainCount);
                $index += $domainCount;
            }
        }
        
        // Handle remaining domains (due to rounding) - distribute by priority
        if ($index < $totalDomains) {
            $remaining = array_slice($domains, $index);
            $remainingIndex = 0;
            
            foreach ($remaining as $domain) {
                $provider = $providers[$remainingIndex % $providers->count()];
                $result[$provider->slug][] = $domain;
                $remainingIndex++;
            }
        }
        
        return $result;
    }
    
    private function validatePercentages(Collection $providers): bool
    {
        $total = $providers->sum('split_percentage');
        return abs($total - 100.00) < 0.01; // Allow small floating point differences
    }
}
```

#### Why Not Use Strategy Pattern?

**Analysis of Actual Requirements**:
1. ✅ **Only one splitting method needed**: Percentage-based from database
2. ✅ **No need for multiple algorithms**: RoundRobin and LoadBalanced are not used
3. ✅ **Splitting rules come from database**: Configured via `split_percentage` field
4. ✅ **Current implementation works well**: No need to over-engineer

**Database Structure**:
- `SmtpProviderSplit` table has `split_percentage` field (must total 100%)
- `priority` field for ordering providers
- No fields for capacity/load tracking (would be needed for LoadBalanced)
- No fields for round-robin configuration (not needed)

#### Example Usage

```php
// Database Configuration:
// - Mailin: split_percentage = 60%, priority = 1
// - Premium: split_percentage = 40%, priority = 2

$service = new DomainSplitService($providerRepository);

// Input: 10 domains
$domains = ['domain1.com', 'domain2.com', ..., 'domain10.com'];
$result = $service->splitDomains($domains);

// Output:
// [
//   'mailin' => ['domain1.com', 'domain2.com', 'domain3.com', 'domain4.com', 'domain5.com', 'domain6.com'], // 60% = 6
//   'premium' => ['domain7.com', 'domain8.com', 'domain9.com', 'domain10.com'] // 40% = 4
// ]
```

#### Visual Representation
```
┌─────────────────────────────────────────────────────────────┐
│              DomainSplitService                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │  splitDomains($domains)                            │     │
│  │                                                     │     │
│  │  1. Get providers from repository                  │     │
│  │  2. Split by percentage from database              │     │
│  │  3. Handle rounding errors (round-robin remaining) │     │
│  │  4. Return array                                    │     │
│  └────────────────────────────────────────────────────┘     │
└──────────────────────┬──────────────────────────────────────┘
                       │ Uses
                       ▼
┌─────────────────────────────────────────────────────────────┐
│            SmtpProviderRepository                            │
│  ┌────────────────────────────────────────────────────┐     │
│  │  findActiveProviders()                             │     │
│  │  Returns: Collection with split_percentage         │     │
│  └────────────────────────────────────────────────────┘     │
└──────────────────────┬──────────────────────────────────────┘
                       │ Queries
                       ▼
┌─────────────────────────────────────────────────────────────┐
│          smtp_provider_splits table                          │
│  - slug: 'mailin'                                           │
│  - split_percentage: 60.00                                  │
│  - priority: 1                                              │
│  - is_active: true                                          │
└─────────────────────────────────────────────────────────────┘
```

#### Benefits of Simple Approach

1. **Easy to Understand**: Clear, straightforward logic
2. **Easy to Test**: Simple method with clear inputs/outputs
3. **Easy to Maintain**: No complex strategy classes to manage
4. **Configuration-Driven**: Split percentages come from database (admin can change)
5. **No Over-Engineering**: Only implements what's actually needed

---

### 5.3 Provider Creation (Simple Direct Instantiation)

#### What is it?
Instead of using a Factory Pattern, we create provider services **directly** based on the provider slug from the repository. This is simpler and easier to understand - when you know which provider you need, you just create it directly.

#### How It Works - Simple Direct Creation

**Simple Approach**: Create providers directly in a helper method
```php
// Simple: Create provider directly based on slug
class MailboxCreationService
{
    public function __construct(
        private SmtpProviderRepository $providerRepository
    ) {}
    
    public function createMailboxes(string $providerSlug, array $domains)
    {
        // Get credentials from repository
        $providerConfig = $this->providerRepository->findBySlug($providerSlug);
        $credentials = $providerConfig->getCredentials();
        
        // Create provider directly - simple and clear!
        $provider = $this->createProvider($providerSlug, $credentials);
        $provider->createMailboxes($domains);
    }
    
    // Helper method - keeps creation logic in one place
    private function createProvider(string $slug, array $credentials): SmtpProviderInterface
    {
        return match($slug) {
            'mailin' => new MailinProviderService($credentials),
            'premiuminboxes' => new PremiuminboxesProviderService($credentials),
            'mailrun' => new MailrunProviderService($credentials),
            default => throw new InvalidProviderException("Unknown provider: {$slug}")
        };
    }
}
```

#### How It Works - Direct Provider Creation

**Simple Direct Creation**: Each service creates providers using a helper method
```php
// Simple: Each service has a helper method to create providers
class MailboxCreationService
{
    public function __construct(
        private SmtpProviderRepository $providerRepository
    ) {}
    
    public function createMailboxes(string $providerSlug, array $domains)
    {
        // Get credentials from repository
        $providerConfig = $this->providerRepository->findBySlug($providerSlug);
        $credentials = $providerConfig->getCredentials();
        
        // Create provider using helper method - simple!
        $provider = $this->createProvider($providerSlug, $credentials);
        $provider->createMailboxes($domains);
    }
    
    // Helper method - keeps creation logic organized
    private function createProvider(string $slug, array $credentials): SmtpProviderInterface
    {
        return match($slug) {
            'mailin' => new MailinProviderService($credentials),
            'premiuminboxes' => new PremiuminboxesProviderService($credentials),
            'mailrun' => new MailrunProviderService($credentials),
            default => throw new InvalidProviderException("Unknown provider: {$slug}")
        };
    }
}

// Same pattern in other services - simple and clear!
class DomainRegistrationService
{
    public function __construct(
        private SmtpProviderRepository $providerRepository
    ) {}
    
    public function transferDomain(string $providerSlug, string $domain)
    {
        $providerConfig = $this->providerRepository->findBySlug($providerSlug);
        $credentials = $providerConfig->getCredentials();
        
        // Same helper method pattern
        $provider = $this->createProvider($providerSlug, $credentials);
        $provider->transferDomain($domain);
    }
    
    private function createProvider(string $slug, array $credentials): SmtpProviderInterface
    {
        return match($slug) {
            'mailin' => new MailinProviderService($credentials),
            'premiuminboxes' => new PremiuminboxesProviderService($credentials),
            'mailrun' => new MailrunProviderService($credentials),
            default => throw new InvalidProviderException("Unknown provider: {$slug}")
        };
    }
}
```

**Note**: If creation logic becomes complex, you can extract it to a shared trait:
```php
// Shared trait for provider creation
trait CreatesProviders
{
    protected function createProvider(string $slug, array $credentials): SmtpProviderInterface
    {
        return match($slug) {
            'mailin' => new MailinProviderService($credentials),
            'premiuminboxes' => new PremiuminboxesProviderService($credentials),
            'mailrun' => new MailrunProviderService($credentials),
            default => throw new InvalidProviderException("Unknown provider: {$slug}")
        };
    }
}

// Services use the trait
class MailboxCreationService
{
    use CreatesProviders;
    
    public function createMailboxes(string $providerSlug, array $domains)
    {
        $providerConfig = $this->providerRepository->findBySlug($providerSlug);
        $credentials = $providerConfig->getCredentials();
        
        // Use trait method
        $provider = $this->createProvider($providerSlug, $credentials);
        $provider->createMailboxes($domains);
    }
}
```

#### Benefits Explained

1. **Simple and Clear**: Direct creation is easier to understand
```php
// To add a new provider:
// 1. Create ProviderService class
// 2. Add to match() statement in createProvider() method
// → Done! Simple and straightforward!
```

2. **Easy to Add New Providers**: Just update the match statement
```php
// New provider: "SuperMail"
private function createProvider(string $slug, array $credentials): SmtpProviderInterface
{
    return match($slug) {
        'mailin' => new MailinProviderService($credentials),
        'premiuminboxes' => new PremiuminboxesProviderService($credentials),
        'mailrun' => new MailrunProviderService($credentials),
        'supermail' => new SuperMailProviderService($credentials), // Just add this!
        default => throw new InvalidProviderException("Unknown provider: {$slug}")
    };
}
```

3. **Type Safety**: Method ensures correct type is returned
```php
// Method guarantees it returns SmtpProviderInterface
$provider = $this->createProvider('mailin', $credentials);
// IDE knows $provider has all interface methods
$provider->createMailboxes(...); // ✅ IDE autocomplete works
```

#### Visual Representation (Simple Direct Creation)
```
┌─────────────────────────────────────────────────────────────┐
│              Services (MailboxCreation, DomainRegistration)  │
│  ┌────────────────────────────────────────────────────┐     │
│  │  createProvider($slug, $credentials)               │     │
│  │                                                     │     │
│  │  match($slug) {                                    │     │
│  │    'mailin' → new MailinProviderService()          │     │
│  │    'premium' → new PremiumProviderService()        │     │
│  │    'mailrun' → new MailrunProviderService()        │     │
│  │  }                                                 │     │
│  └────────────────────────────────────────────────────┘     │
└──────────────────────┬──────────────────────────────────────┘
                       │ Returns
                       ▼
┌─────────────────────────────────────────────────────────────┐
│           SmtpProviderInterface (Contract)                   │
│  ┌──────────────────┐  ┌──────────────────┐                │
│  │  Mailin Provider │  │ Premium Provider │                │
│  └──────────────────┘  └──────────────────┘                │
│  ┌──────────────────┐                                       │
│  │ Mailrun Provider │                                       │
│  └──────────────────┘                                       │
└─────────────────────────────────────────────────────────────┘
```

**Why This Approach?**
- ✅ **Simpler**: No need for separate factory class
- ✅ **Clearer**: Creation logic is visible in the service
- ✅ **Easier**: Less code to maintain
- ✅ **Flexible**: Can extract to trait if needed for reusability

---

### 5.4 Interface Segregation Principle (ISP)

#### What is it?
Interface Segregation states: **"Clients should not be forced to depend on methods they don't use"**. Instead of one fat interface, create **smaller, focused interfaces**. It's like ordering from a restaurant menu - you don't want a "do everything" waiter; you want specialists (waiter for orders, bartender for drinks, chef for cooking).

#### Real-World Analogy
**Bad Interface (Fat Interface)**:
```php
interface WorkerInterface
{
    public function work();
    public function eat();
    public function sleep();
    public function code();
    public function design();
    public function manage();
}

// Programmer must implement ALL methods, even ones not needed!
class Programmer implements WorkerInterface
{
    public function code() { /* ✓ Makes sense */ }
    public function design() { /* ✗ Programmer doesn't design! */ }
    public function manage() { /* ✗ Programmer doesn't manage! */ }
    public function work() { /* ? */ }
    public function eat() { /* ? */ }
    public function sleep() { /* ? */ }
}
```

**Good Interface (Segregated Interfaces)**:
```php
interface CoderInterface { public function code(); }
interface DesignerInterface { public function design(); }
interface ManagerInterface { public function manage(); }

// Programmer only implements what it needs!
class Programmer implements CoderInterface
{
    public function code() { /* ✓ Only what's needed! */ }
}
```

#### How It Works in Our System

**Bad Approach (Fat Provider Interface)**:
```php
// Bad: One interface that tries to do everything
interface SmtpProviderInterface
{
    // All providers might not support all operations!
    public function createMailboxes(array $mailboxes): array;
    public function deleteMailbox(int $id): bool;
    public function transferDomain(string $domain): array;
    public function checkDomainStatus(string $domain): array;
    public function getMailboxesByDomain(string $domain): array;
    public function updateMailbox(int $id, array $data): bool; // Not all support this!
    public function getProviderAnalytics(): array; // Not all support this!
    public function configureDns(string $domain): array; // Not all support this!
}

// Problem: Mailrun might not support some methods
class MailrunProviderService implements SmtpProviderInterface
{
    public function createMailboxes(array $mailboxes): array
    {
        // ✓ Mailrun supports this
    }
    
    public function updateMailbox(int $id, array $data): bool
    {
        // ✗ Mailrun doesn't support updating mailboxes!
        // Must throw exception or return false - violates interface!
        throw new NotSupportedException("Mailrun doesn't support mailbox updates");
    }
    
    public function configureDns(string $domain): array
    {
        // ✗ Mailrun doesn't support DNS configuration!
        throw new NotSupportedException("Mailrun doesn't support DNS config");
    }
}
```

**Good Approach (Segregated Interfaces)**:
```php
// Step 1: Core Interface (what all providers MUST support)
interface SmtpProviderInterface
{
    public function getProviderName(): string;
    public function getProviderSlug(): string;
    public function authenticate(): ?string;
    public function isAvailable(): bool;
}

// Step 2: Segregated Feature Interfaces
interface MailboxManagementInterface
{
    public function createMailboxes(array $mailboxes): array;
    public function deleteMailbox(int $id): bool;
    public function getMailboxesByDomain(string $domain): array;
}

interface MailboxUpdateInterface  // Optional interface
{
    public function updateMailbox(int $id, array $data): bool;
}

interface DomainTransferInterface  // Optional interface
{
    public function transferDomain(string $domain): array;
    public function checkDomainStatus(string $domain): array;
}

interface DnsConfigurationInterface  // Optional interface
{
    public function configureDns(string $domain): array;
}

interface AnalyticsInterface  // Optional interface
{
    public function getProviderAnalytics(): array;
}

// Step 3: Providers implement only what they support
class MailinProviderService implements 
    SmtpProviderInterface, 
    MailboxManagementInterface,
    MailboxUpdateInterface,
    DomainTransferInterface,
    DnsConfigurationInterface,
    AnalyticsInterface
{
    // Implements ALL interfaces - Mailin is full-featured
}

class MailrunProviderService implements 
    SmtpProviderInterface,
    MailboxManagementInterface,
    DomainTransferInterface
{
    // Only implements what Mailrun actually supports
    // No need to implement update/analytics/DNS - clean!
}

class PremiuminboxesProviderService implements 
    SmtpProviderInterface,
    MailboxManagementInterface,
    MailboxUpdateInterface
{
    // Implements core + mailbox management + updates
    // No domain transfer or DNS - that's fine!
}
```

**Usage with Interface Segregation**:
```php
// Service only depends on what it needs
class MailboxCreationService
{
    public function __construct(
        private SmtpProviderRepository $providerRepository
    ) {}
    
    public function createMailboxes(string $providerSlug, array $mailboxes)
    {
        $providerConfig = $this->providerRepository->findBySlug($providerSlug);
        $credentials = $providerConfig->getCredentials();
        $provider = $this->createProvider($providerSlug, $credentials);
        
        // Type hint only what we need
        if ($provider instanceof MailboxManagementInterface) {
            $provider->createMailboxes($mailboxes);
        } else {
            throw new Exception("Provider doesn't support mailbox management");
        }
    }
}

// Different service uses different interface
class DnsConfigurationService
{
    public function configureDns(string $providerSlug, string $domain)
    {
        $provider = $this->factory->create($providerSlug, $credentials);
        
        // Only providers that support DNS configuration
        if ($provider instanceof DnsConfigurationInterface) {
            $provider->configureDns($domain);
        } else {
            // Gracefully handle - not an error, just not supported
            Log::info("Provider {$providerSlug} doesn't support DNS configuration");
        }
    }
}
```

#### Benefits Explained

1. **Providers Don't Implement Unsupported Features**
```php
// Before: Must implement all methods, throw exceptions for unsupported
class MailrunProviderService implements SmtpProviderInterface
{
    public function configureDns() { 
        throw new Exception("Not supported"); // Ugly!
    }
}

// After: Just don't implement the interface
class MailrunProviderService implements 
    SmtpProviderInterface,
    MailboxManagementInterface
{
    // Clean - no exceptions needed!
}
```

2. **Clear Contract**: Code is self-documenting
```php
// Looking at class definition tells you exactly what it supports
class MailrunProviderService implements 
    SmtpProviderInterface,
    MailboxManagementInterface  // ← Clearly supports mailboxes
{
    // We know it supports mailboxes, not DNS or analytics
}
```

3. **Easier to Test**: Test only what's needed
```php
// Mock only the interfaces you need
$provider = Mockery::mock(MailboxManagementInterface::class);
$provider->shouldReceive('createMailboxes')->once();

// Don't need to mock configureDns() - it's not part of the interface!
```

#### Visual Representation
```
┌─────────────────────────────────────────────────────────────┐
│              Fat Interface (BAD)                             │
│  ┌────────────────────────────────────────────────────┐     │
│  │     SmtpProviderInterface                          │     │
│  │  • createMailboxes()                               │     │
│  │  • deleteMailbox()                                 │     │
│  │  • transferDomain()                                │     │
│  │  • checkDomainStatus()                             │     │
│  │  • updateMailbox()     ← Mailrun doesn't support   │     │
│  │  • configureDns()      ← Mailrun doesn't support   │     │
│  │  • getAnalytics()      ← Mailrun doesn't support   │     │
│  └────────────────────────────────────────────────────┘     │
│                      ▲                                       │
│                      │ Must implement ALL                    │
│         ┌────────────┴────────────┐                          │
│         ▼                         ▼                          │
│  ┌─────────────┐          ┌─────────────┐                   │
│  │   Mailin    │          │   Mailrun   │                   │
│  │ (all methods│          │(throws excep│                   │
│  │  supported) │          │tions for 3!)│                   │
│  └─────────────┘          └─────────────┘                   │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│         Segregated Interfaces (GOOD)                         │
│  ┌──────────────────┐  ┌──────────────────┐                │
│  │SmtpProvider      │  │MailboxManagement │                │
│  │  Interface       │  │   Interface      │                │
│  │(Core - all need) │  │(Core features)   │                │
│  └──────────────────┘  └──────────────────┘                │
│  ┌──────────────────┐  ┌──────────────────┐                │
│  │DomainTransfer    │  │MailboxUpdate     │                │
│  │  Interface       │  │   Interface      │                │
│  │(Optional)        │  │(Optional)        │                │
│  └──────────────────┘  └──────────────────┘                │
│                      ▲                                       │
│                      │ Implement what you support            │
│         ┌────────────┴────────────┐                          │
│         ▼                         ▼                          │
│  ┌─────────────┐          ┌─────────────┐                   │
│  │   Mailin    │          │   Mailrun   │                   │
│  │implements   │          │implements   │                   │
│  │all 4 above  │          │only 2 above │                   │
│  └─────────────┘          └─────────────┘                   │
└─────────────────────────────────────────────────────────────┘
```

---

### 5.5 Additional Patterns Used

#### 5.5.1 Dependency Injection Pattern

**What is it?**
Dependency Injection means **objects receive their dependencies from outside** rather than creating them internally. It's like ordering food delivery - the restaurant doesn't come to you, the delivery service brings it to you!

**Example**:
```php
// Bad: Creates dependencies internally (tight coupling)
class MailboxCreationService
{
    private OrderRepository $orderRepository;
    
    public function __construct()
    {
        // Bad: Creates dependency itself
        $this->orderRepository = new OrderRepository();
    }
}

// Good: Receives dependencies from outside (loose coupling)
class MailboxCreationService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository, // Injected!
        private SmtpProviderRepository $providerRepository, // Injected!
        private DomainSplitService $domainSplitService    // Injected!
    ) {}
}
```

**Benefits**:
- Easy to test (inject mocks)
- Loose coupling
- Flexible (can swap implementations)

---

#### 5.5.2 Command Pattern (Jobs)

**What is it?**
Encapsulate requests as objects, allowing you to queue, log, and undo operations.

**Example**:
```php
// Job is a command - encapsulates the operation
class CreateMailboxesJob implements ShouldQueue
{
    public function __construct(
        private int $orderId,
        private array $domains
    ) {}
    
    public function handle(MailAutomationOrchestrator $orchestrator): void
    {
        // Command execution - can be queued, retried, logged
        $orchestrator->processOrder($this->orderId, $this->domains);
    }
}

// Usage: Commands can be queued
CreateMailboxesJob::dispatch($orderId, $domains);
```

---

#### 5.5.3 Facade Pattern (Orchestrator)

**What is it?**
Provide a simplified interface to a complex subsystem.

**Example**:
```php
// Complex system with many services
class MailAutomationOrchestrator
{
    // Facade - hides complexity
    public function processOrder(int $orderId, array $domains): array
    {
        // Internally uses many services, but provides simple interface
        $splitResult = $this->domainSplitService->splitDomains($domains);
        $registrationResult = $this->domainRegistrationService->register(...);
        $mailboxResult = $this->mailboxCreationService->create(...);
        $completionResult = $this->orderCompletionService->complete(...);
        
        return $this->combineResults(...);
    }
}

// Usage: Simple interface hides complexity
$orchestrator->processOrder($orderId, $domains);
```

---

## 5.6 Pattern Comparison Table

| Pattern | Purpose | When to Use | Complexity |
|---------|---------|-------------|------------|
| **Repository** | Data access abstraction | Need to abstract database queries | Low |
| **Direct Instantiation** | Create objects directly | Simple object creation based on configuration | Low |
| **Interface Segregation** | Smaller, focused interfaces | Interface has too many methods | Low |
| **Dependency Injection** | Provide dependencies externally | Need loose coupling | Low |
| **Command** | Encapsulate operations | Need queuing, logging, undo | Medium |
| **Facade** | Simplify complex subsystem | Many related classes need unified interface | Medium |

---

## 5.7 Pattern Relationships

```
┌─────────────────────────────────────────────────────────────┐
│                    MailAutomationOrchestrator                │
│                      (Facade Pattern)                        │
└──────────────────────┬──────────────────────────────────────┘
                       │ Uses
        ┌──────────────┼──────────────┐
        ▼              ▼              ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│   Domain     │ │   Mailbox    │ │   Domain     │
│ Split Service│ │   Creation   │ │ Registration │
│ (Repository) │ │   Service    │ │   Service    │
└──────┬───────┘ └──────┬───────┘ └──────┬───────┘
       │                │                 │
        │ Uses           │ Uses            │ Uses
       ▼                ▼                 ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│   Creates    │ │   Creates    │ │   Creates    │
│   Providers  │ │   Providers  │ │   Providers  │
│  (Direct)    │ │  (Direct)    │ │  (Direct)    │
└──────┬───────┘ └──────┬───────┘ └──────┬───────┘
       │                │                 │
       │ Creates        │ Creates         │ Creates
       ▼                ▼                 ▼
┌─────────────────────────────────────────────────────────────┐
│            Provider Implementations                          │
│  (Implement SmtpProviderInterface - Interface Segregation)   │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                  │
│  │  Mailin  │  │ Premium  │  │ Mailrun  │                  │
│  └──────────┘  └──────────┘  └──────────┘                  │
└─────────────────────────────────────────────────────────────┘
        │                │                 │
        │ Use            │ Use             │ Use
        ▼                ▼                 ▼
┌─────────────────────────────────────────────────────────────┐
│                  Repository Layer                            │
│  (Repository Pattern - Dependency Injection)                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                  │
│  │  Order   │  │ Domain   │  │  Email   │                  │
│  │Repository│  │Repository│  │Repository│                  │
└─────────────────────────────────────────────────────────────┘
```

---

This comprehensive explanation transforms abstract design patterns into concrete, understandable concepts with real-world analogies, code examples, and visual representations that make the architecture easy to understand and implement.

---

## 6. Domain Split Logic Improvements

### 6.1 Current Issues
- Basic percentage-based splitting
- No validation of split results
- Rounding errors not handled well
- No minimum domain allocation per provider

### 6.2 Proposed Improvements

#### Percentage-Based Splitting (Simple & Effective)
```php
// Get providers from repository
$providers = $repository->findActiveProviders();

// Split based on split_percentage field
foreach ($providers as $provider) {
    $percentage = $provider->split_percentage; // From database
    $domainCount = round($totalDomains * ($percentage / 100));
    // Assign domains...
}

// Handle rounding errors by distributing remaining domains
// Example: 10 domains, 60% Mailin (6), 40% Premium (4) = perfect split
// Example: 11 domains, 60% Mailin (7), 40% Premium (4) = 11 total ✓
```

---

## 7. Error Handling Strategy

### 7.1 Provider-Level Errors
- **Authentication Failures**: Retry with exponential backoff
- **Rate Limiting**: Queue request for later
- **API Errors**: Log and mark domain/provider for manual review

### 7.2 Domain-Level Errors
- **Domain Transfer Failures**: Retry up to N times, then mark as failed
- **Domain Not Registered**: Queue for transfer
- **Domain Already Exists**: Treat as success

### 7.3 Mailbox-Level Errors
- **Mailbox Already Exists**: Treat as success
- **Invalid Mailbox Data**: Log and skip
- **Batch Failures**: Retry individual mailboxes

### 7.4 Order-Level Errors
- **Order Cancelled/Rejected**: Stop processing
- **Order Not Found**: Log and fail job
- **Incomplete Processing**: Mark order for retry

---

## 8. Logging & Monitoring

### 8.1 Logging Levels

**INFO**:
- Order processing started/completed
- Provider selected
- Domains split distribution
- Mailbox creation batches

**WARNING**:
- Provider unavailable
- Domain transfer delays
- Retry attempts

**ERROR**:
- Provider authentication failures
- Domain transfer failures
- Mailbox creation failures
- Order processing failures

### 8.2 Metrics to Track

1. **Provider Performance**:
   - Success rate per provider
   - Average response time
   - Error rate by type

2. **Domain Split Distribution**:
   - Domains per provider
   - Split accuracy (actual vs intended)

3. **Order Processing**:
   - Average processing time
   - Success/failure rate
   - Retry count distribution

---

## 9. Migration Checklist

### Pre-Migration
- [ ] All unit tests passing
- [ ] Integration tests passing
- [ ] Code review completed
- [ ] Documentation updated
- [ ] Feature flag configured

### Migration Steps
- [ ] Deploy new code (feature flag disabled)
- [ ] Enable for 10% of orders (test group)
- [ ] Monitor for 24-48 hours
- [ ] Enable for 50% of orders
- [ ] Monitor for 24-48 hours
- [ ] Enable for 100% of orders
- [ ] Monitor for 1 week
- [ ] Remove old code
- [ ] Remove feature flag

### Post-Migration
- [ ] Verify all orders processing correctly
- [ ] Check provider distribution
- [ ] Review error logs
- [ ] Update documentation
- [ ] Team training/knowledge sharing

---

## 10. Risk Mitigation

### Risk 1: Provider API Changes
**Mitigation**: 
- Interface abstraction allows quick provider updates
- Version API clients
- Monitor provider changelogs

### Risk 2: Domain Split Imbalance
**Mitigation**:
- Log actual vs intended distribution
- Alert on significant deviations
- Manual override capability

### Risk 3: Performance Issues
**Mitigation**:
- Queue long-running operations
- Batch API calls where possible
- Monitor job queue depth

### Risk 4: Data Consistency
**Mitigation**:
- Use database transactions
- Implement idempotent operations
- Add database constraints

---

## 11. Future Enhancements

### Phase 2 (Future)
1. **Dynamic Provider Selection**: Select provider based on real-time metrics
2. **Auto-scaling Providers**: Automatically adjust split percentages
3. **Multi-region Support**: Support providers in different regions
4. **Advanced Analytics**: Detailed provider performance dashboards
5. **A/B Testing**: Test new providers with subset of orders

---

## 12. Success Criteria

### Code Quality
- [ ] All services < 500 lines
- [ ] Test coverage > 80%
- [ ] No code duplication
- [ ] Follows PSR-12 coding standards

### Functionality
- [ ] All three providers working (Mailin, Premiuminboxes, Mailrun)
- [ ] Domain splitting accurate and balanced
- [ ] Error handling robust
- [ ] Order completion reliable

### Performance
- [ ] No degradation in processing time
- [ ] Queue processing efficient
- [ ] API rate limits respected

### Maintainability
- [ ] Easy to add new providers
- [ ] Clear separation of concerns
- [ ] Well-documented code
- [ ] Easy to test

---

## Conclusion

This refactoring will transform the mail automation system from a monolithic, hardcoded implementation to a flexible, maintainable, and extensible architecture. The use of repository patterns, service layers, and provider abstraction will make it easy to add new providers, modify business logic, and maintain the codebase long-term.

The chunked approach ensures that each component has a single responsibility, making it easier to understand, test, and modify. The workflow is designed to be implemented incrementally, reducing risk and allowing for continuous testing and validation.

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-XX  
**Author**: Development Team  
**Review Status**: Pending
