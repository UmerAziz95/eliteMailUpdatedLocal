# Order Automation & Mailbox Creation Analysis

## Table of Contents
1. [Order Automation Table Monitoring](#order-automation-table-monitoring)
2. [Mailbox Creation Flow - Domain Already Exists](#mailbox-creation-flow---domain-already-exists)
3. [Mailbox Creation Flow - Domain First Transferred](#mailbox-creation-flow---domain-first-transferred)
4. [Timing Analysis](#timing-analysis)
5. [Log Analysis Summary](#log-analysis-summary)

---

## Order Automation Table Monitoring

### Where `order_automations` Records Are Created/Updated

#### 1. **Initial Creation - When Mailboxes Are Created**
**Location:** `app/Jobs/MailinAi/CreateMailboxesOnOrderJob.php::saveMailboxesForDomains()` (Line 1761-1772)

**Code:**
```php
OrderAutomation::updateOrCreate(
    [
        'order_id' => $this->orderId,
        'action_type' => 'mailbox',
    ],
    [
        'provider_type' => $this->providerType,
        'job_uuid' => $jobUuid,
        'status' => $mailboxesAlreadyExist ? 'completed' : 'pending',
        'response_data' => $result['response'] ?? null,
    ]
);
```

**When It Happens:**
- After `MailinAiService::createMailboxes()` is called successfully
- Before mailbox IDs are fetched
- Status is set to:
  - `'completed'` if mailboxes already exist on Mailin.ai
  - `'pending'` if new mailboxes are being created (async job)

**Fields Stored:**
- `order_id`: Order ID
- `action_type`: Always `'mailbox'`
- `provider_type`: From order (usually `'Private SMTP'`)
- `job_uuid`: UUID from Mailin.ai API response (null if mailboxes already exist)
- `status`: `'pending'` or `'completed'`
- `response_data`: Full API response from Mailin.ai

---

#### 2. **Update on Job Failure**
**Location:** `app/Jobs/MailinAi/CreateMailboxesOnOrderJob.php::handle()` (Line 332-343)

**Code:**
```php
OrderAutomation::updateOrCreate(
    [
        'order_id' => $this->orderId,
        'action_type' => 'mailbox',
    ],
    [
        'provider_type' => $this->providerType,
        'job_uuid' => null, // No UUID for failed jobs
        'status' => 'failed',
        'error_message' => $e->getMessage(),
    ]
);
```

**When It Happens:**
- When the mailbox creation job fails with an exception
- Catches all exceptions in the `handle()` method

**Fields Stored:**
- `status`: `'failed'`
- `job_uuid`: `null`
- `error_message`: Exception message

---

#### 3. **Update on Order Completion**
**Location:** `app/Jobs/MailinAi/CreateMailboxesOnOrderJob.php::completeOrderAfterAllMailboxesCreated()` (Line 2133-2137)

**Code:**
```php
OrderAutomation::where('order_id', $this->orderId)
    ->where('action_type', 'mailbox')
    ->update(['status' => 'completed']);
```

**When It Happens:**
- When all mailboxes are created and order is marked as completed
- Updates existing record to `status = 'completed'`

---

### Monitoring Query

To monitor `order_automations` table:

```sql
-- Get all mailbox automation records
SELECT 
    id,
    order_id,
    action_type,
    provider_type,
    job_uuid,
    status,
    error_message,
    created_at,
    updated_at
FROM order_automations
WHERE action_type = 'mailbox'
ORDER BY created_at DESC;

-- Get pending mailbox jobs
SELECT * FROM order_automations
WHERE action_type = 'mailbox' 
  AND status = 'pending'
ORDER BY created_at DESC;

-- Get failed mailbox jobs
SELECT * FROM order_automations
WHERE action_type = 'mailbox' 
  AND status = 'failed'
ORDER BY created_at DESC;

-- Get completed mailbox jobs
SELECT * FROM order_automations
WHERE action_type = 'mailbox' 
  AND status = 'completed'
ORDER BY created_at DESC;
```

---

## Mailbox Creation Flow - Domain Already Exists

### Scenario: Domain is Already Registered and Active in Mailin.ai

**When:** Domain status check returns `status = 'active'` and `name_server_status = 'active'`

### Step-by-Step Process

#### 1. **Domain Status Check** (Immediate)
- **Location:** `CreateMailboxesOnOrderJob::handle()` (Line 225)
- **Action:** `MailinAiService::checkDomainStatus($domain)`
- **Result:** Domain is `'active'`
- **Time:** ~1-2 seconds

#### 2. **Mailbox Creation Request** (Immediate)
- **Location:** `CreateMailboxesOnOrderJob::createMailboxesForProvider()` (Line 1719)
- **Action:** `MailinAiService::createMailboxes($mailboxes)`
- **Request:** POST to `/mailboxes` with mailbox data
- **Response:** 
  ```json
  {
    "uuid": "c4eec37f-984c-4c9f-a61c-d4c64691fd7a",
    "message": "Your request has been received and will be processed as soon as possible."
  }
  ```
- **Time:** ~1 second

#### 3. **OrderAutomation Record Created** (Immediate)
- **Location:** `saveMailboxesForDomains()` (Line 1761)
- **Status:** `'pending'` (because new mailboxes are being created)
- **job_uuid:** UUID from API response
- **Time:** < 1 second

#### 4. **Fetch Mailbox IDs** (After 3-second delay)
- **Location:** `saveMailboxesForDomains()` (Line 1834-1892)
- **Action:** 
  - Wait 3 seconds (`sleep(3)`)
  - Call `MailinAiService::getMailboxesByDomain($domain)` for each domain
  - Fetch mailbox IDs from API response
- **Time:** 
  - Wait: 3 seconds
  - API call: ~1-2 seconds per domain
  - Total: ~4-5 seconds per domain

#### 5. **Save OrderEmail Records** (Immediate)
- **Location:** `saveMailboxesForDomains()` (Line 1970-2064)
- **Action:** Create `OrderEmail` records with:
  - `email`: `{prefix}@{domain}`
  - `password`: Generated password
  - `mailin_mailbox_id`: From API
  - `mailin_domain_id`: From API
  - `provider_slug`: Provider identifier
- **Time:** < 1 second

#### 6. **Order Completion Check** (Immediate)
- **Location:** `handle()` (Line 278-320)
- **Action:** Check if all domains have mailboxes
- **If Complete:** Call `completeOrderAfterAllMailboxesCreated()`
- **Time:** < 1 second

### Total Time for Domain Already Exists

**Breakdown:**
- Domain status check: ~1-2 seconds
- Mailbox creation request: ~1 second
- Wait for mailboxes: 3 seconds
- Fetch mailbox IDs: ~1-2 seconds per domain
- Save records: < 1 second
- Order completion: < 1 second

**Total:** ~6-9 seconds per domain (typically ~7 seconds)

**Example from Logs:**
- Order 1118: Mailbox creation started at 07:30:06, completed at 07:30:13
- **Actual Time:** ~7 seconds

---

## Mailbox Creation Flow - Domain First Transferred

### Scenario: Domain is Not Registered in Mailin.ai

**When:** Domain status check returns `not_found = true` or `status != 'active'`

### Step-by-Step Process

#### Phase 1: Domain Transfer (Initial Job Run)

##### 1. **Domain Status Check** (Immediate)
- **Location:** `CreateMailboxesOnOrderJob::handle()` (Line 225)
- **Action:** `MailinAiService::checkDomainStatus($domain)`
- **Result:** Domain `not_found` or `status != 'active'`
- **Time:** ~1-2 seconds

##### 2. **Domain Transfer Initiation** (Immediate)
- **Location:** `handleDomainTransfer()` (Line 596)
- **Action:** `MailinAiService::transferDomain($domain)`
- **Request:** POST to `/domains/transfer`
- **Response:**
  ```json
  {
    "success": true,
    "name_servers": ["byron.ns.cloudflare.com", "ines.ns.cloudflare.com"]
  }
  ```
- **Time:** ~2-3 seconds

##### 3. **DomainTransfer Record Created** (Immediate)
- **Location:** `handleDomainTransfer()` (Line 700-750)
- **Fields:**
  - `order_id`: Order ID
  - `domain_name`: Domain name
  - `status`: `'pending'`
  - `name_server_status`: `'updated'` or `'failed'`
  - `name_servers`: JSON array of nameservers
- **Time:** < 1 second

##### 4. **Nameserver Update** (Immediate)
- **Location:** `handleDomainTransfer()` (Line 627-866)
- **Action:**
  - **Spaceship:** `SpaceshipService::updateNameservers()`
  - **Namecheap:** `NamecheapService::updateNameservers()`
- **Time:** ~1-2 seconds

##### 5. **Job Exits** (Waiting for Domain Activation)
- **Location:** `handle()` (Line 268-276)
- **Action:** Job returns early, waiting for domain to become active
- **Status:** Order remains `'in-progress'`
- **Time:** < 1 second

**Phase 1 Total Time:** ~5-8 seconds

---

#### Phase 2: Domain Activation Check (Cron Job)

##### 1. **Cron Job Runs** (Every 5 minutes)
- **Location:** `app/Console/Commands/CheckDomainTransferStatus.php`
- **Action:** Checks pending domain transfers
- **Frequency:** Every 5 minutes (configurable)

##### 2. **Domain Status Check** (Per Domain)
- **Location:** `CheckDomainTransferStatus::handle()` (Line 200-300)
- **Action:** `MailinAiService::checkDomainStatus($domain)`
- **Checks:**
  - First check: Domain status = `"0"` (not active yet)
  - Subsequent checks: Domain status = `"active"` (active)
- **Time:** ~1-2 seconds per check

##### 3. **Domain Transfer Marked Complete** (When Active)
- **Location:** `CheckDomainTransferStatus::handle()` (Line 250-280)
- **Action:** Update `DomainTransfer` record:
  - `status = 'completed'`
- **Time:** < 1 second

##### 4. **Mailbox Creation Job Dispatched** (When All Domains Active)
- **Location:** `CheckDomainTransferStatus::processOrderMailboxCreation()` (Line 400-500)
- **Action:** 
  - Check if all domain transfers are completed
  - Dispatch `CreateMailboxesOnOrderJob` if all complete
- **Time:** < 1 second

**Phase 2 Total Time:** Varies based on domain activation time
- **Typical:** 5-15 minutes (depends on DNS propagation)
- **From Logs:** Order 1118 took ~8 minutes 26 seconds

---

#### Phase 3: Mailbox Creation (After Domain Activation)

##### 1. **Job Re-executes** (Triggered by Cron)
- **Location:** `CreateMailboxesOnOrderJob::handle()` (Line 58)
- **Action:** Job runs again with same order_id
- **Time:** < 1 second

##### 2. **Domain Status Check** (Confirms Active)
- **Location:** `handle()` (Line 225)
- **Action:** `MailinAiService::checkDomainStatus($domain)`
- **Result:** Domain is `'active'`
- **Time:** ~1-2 seconds

##### 3. **Mailbox Creation Request** (Immediate)
- **Location:** `createMailboxesForProvider()` (Line 1719)
- **Action:** `MailinAiService::createMailboxes($mailboxes)`
- **Request:** POST to `/mailboxes`
- **Response:** UUID for async job
- **Time:** ~1 second

##### 4. **OrderAutomation Record Created** (Immediate)
- **Location:** `saveMailboxesForDomains()` (Line 1761)
- **Status:** `'pending'`
- **job_uuid:** UUID from API
- **Time:** < 1 second

##### 5. **Fetch Mailbox IDs** (After 3-second delay)
- **Location:** `saveMailboxesForDomains()` (Line 1834-1892)
- **Action:**
  - Wait 3 seconds
  - Call `getMailboxesByDomain()` for each domain
  - Extract mailbox IDs
- **Time:** ~4-5 seconds per domain

##### 6. **Save OrderEmail Records** (Immediate)
- **Location:** `saveMailboxesForDomains()` (Line 1970-2064)
- **Action:** Create `OrderEmail` records
- **Time:** < 1 second

##### 7. **Order Completion** (Immediate)
- **Location:** `completeOrderAfterAllMailboxesCreated()` (Line 2052)
- **Action:**
  - Update order: `status_manage_by_admin = 'completed'`
  - Update `OrderAutomation`: `status = 'completed'`
  - Send notifications
- **Time:** < 1 second

**Phase 3 Total Time:** ~7-9 seconds

---

### Total Time for Domain First Transferred

**Breakdown:**
- **Phase 1 (Transfer Initiation):** ~5-8 seconds
- **Phase 2 (Domain Activation):** 5-15 minutes (typical)
- **Phase 3 (Mailbox Creation):** ~7-9 seconds

**Total:** ~5-16 minutes (typically ~8-10 minutes)

**Example from Logs:**
- Order 1118: Transfer started at 07:21:36, completed at 07:30:13
- **Actual Time:** ~8 minutes 37 seconds
  - Transfer + nameserver update: ~4 seconds (07:21:36 to 07:21:40)
  - Domain activation wait: ~8 minutes 26 seconds (07:21:40 to 07:30:06)
  - Mailbox creation: ~7 seconds (07:30:06 to 07:30:13)

---

## Timing Analysis

### Comparison Table

| Scenario | Phase 1 | Phase 2 | Phase 3 | Total Time |
|----------|---------|---------|---------|------------|
| **Domain Already Exists** | N/A | N/A | ~7 seconds | **~7 seconds** |
| **Domain First Transferred** | ~5-8 sec | 5-15 min | ~7-9 sec | **~5-16 minutes** |

### Key Timing Factors

#### For Domain Already Exists:
1. **No Domain Transfer:** Saves 5-15 minutes
2. **Immediate Mailbox Creation:** No waiting for activation
3. **Fast ID Fetching:** 3-second delay + API call (~4-5 seconds)

#### For Domain First Transferred:
1. **Domain Transfer Time:** ~2-3 seconds (API call)
2. **Nameserver Update Time:** ~1-2 seconds (Spaceship/Namecheap API)
3. **DNS Propagation Time:** 5-15 minutes (varies by registrar/DNS provider)
4. **Mailbox Creation Time:** ~7-9 seconds (same as existing domain)

### Optimization Opportunities

1. **Reduce Wait Time for Active Domains:**
   - Current: 3 seconds
   - Could be reduced to 1-2 seconds for faster processing

2. **Parallel Domain Processing:**
   - Currently processes domains sequentially
   - Could process multiple domains in parallel

3. **Cron Job Frequency:**
   - Current: Every 5 minutes
   - Could be reduced to 2-3 minutes for faster activation detection

---

## Log Analysis Summary

### Order 1118 - Complete Flow Analysis

**Order ID:** 1118  
**Domain:** observeexample.com  
**Prefix Variants:** eee, fff  
**Hosting Platform:** Spaceship

#### Timeline

| Time | Event | Duration | Details |
|------|-------|----------|---------|
| **07:21:36** | Job Started | - | Mailbox creation job dispatched |
| **07:21:36** | Domain Status Check | ~0.5s | Domain not found in Mailin.ai |
| **07:21:36** | Domain Transfer Initiated | ~3s | Transfer request sent to Mailin.ai |
| **07:21:39** | Nameservers Received | - | `["byron.ns.cloudflare.com", "ines.ns.cloudflare.com"]` |
| **07:21:39** | DomainTransfer Record Created | - | Status: `pending`, NameServerStatus: `updated` |
| **07:21:39** | Spaceship Nameserver Update | ~1s | Successfully updated nameservers |
| **07:21:40** | Job Exited (Waiting) | - | Waiting for domain activation |
| **07:25:04** | Cron Check #1 | - | Domain status: `"0"` (not active yet) |
| **07:30:05** | Cron Check #2 | - | Domain status: `"active"` ✅ |
| **07:30:06** | Domain Transfer Completed | - | Status updated to `completed` |
| **07:30:06** | Mailbox Creation Job Dispatched | - | Job re-executed |
| **07:30:06** | Domain Status Check | ~0.5s | Confirmed: `status = "active"` |
| **07:30:06** | Mailbox Creation Request | ~1s | 2 mailboxes: `eee@observeexample.com`, `fff@observeexample.com` |
| **07:30:07** | Job UUID Received | - | `c4eec37f-984c-4c9f-a61c-d4c64691fd7a` |
| **07:30:07** | OrderAutomation Created | - | Status: `pending`, JobUUID: set |
| **07:30:10** | Mailbox IDs Fetched | ~3s | Wait 3s + API call |
| **07:30:11** | OrderEmail Records Created | - | 2 records with Mailin.ai IDs |
| **07:30:13** | Order Completed | - | Status: `completed` |

#### Total Time Breakdown

- **Domain Transfer + Nameserver Update:** ~4 seconds (07:21:36 to 07:21:40)
- **Domain Activation Wait:** ~8 minutes 26 seconds (07:21:40 to 07:30:06)
- **Mailbox Creation:** ~7 seconds (07:30:06 to 07:30:13)
- **Total Time:** **~8 minutes 37 seconds**

#### Key Observations

1. **Domain Transfer was Fast:**
   - Transfer API call: ~3 seconds
   - Nameserver update: ~1 second
   - Total: ~4 seconds

2. **Domain Activation Took Time:**
   - First check (07:25:04): Still not active (`status = "0"`)
   - Second check (07:30:05): Active (`status = "active"`)
   - Wait time: ~8 minutes 26 seconds
   - This is typical for DNS propagation

3. **Mailbox Creation was Fast:**
   - API request: ~1 second
   - Wait for mailboxes: 3 seconds
   - Fetch IDs: ~3 seconds
   - Total: ~7 seconds

4. **OrderAutomation Record:**
   - Created at: 07:30:07
   - Status: `pending` (initially)
   - Updated to: `completed` at 07:30:13

---

## Monitoring Recommendations

### 1. Monitor OrderAutomation Table

**Key Metrics to Track:**
- Pending jobs (status = 'pending')
- Failed jobs (status = 'failed')
- Average completion time
- Jobs without UUID (potential issues)

**SQL Queries:**

```sql
-- Pending jobs older than 10 minutes
SELECT * FROM order_automations
WHERE action_type = 'mailbox'
  AND status = 'pending'
  AND created_at < NOW() - INTERVAL 10 MINUTE
ORDER BY created_at DESC;

-- Failed jobs in last 24 hours
SELECT * FROM order_automations
WHERE action_type = 'mailbox'
  AND status = 'failed'
  AND created_at > NOW() - INTERVAL 24 HOUR
ORDER BY created_at DESC;

-- Average completion time
SELECT 
    AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_seconds,
    MIN(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as min_seconds,
    MAX(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as max_seconds
FROM order_automations
WHERE action_type = 'mailbox'
  AND status = 'completed'
  AND created_at > NOW() - INTERVAL 24 HOUR;
```

### 2. Monitor Domain Transfer Times

**Key Metrics:**
- Average domain activation time
- Domains stuck in pending status
- Failed nameserver updates

### 3. Monitor Mailbox Creation Times

**Key Metrics:**
- Average mailbox creation time
- Failed mailbox creations
- Mailboxes without IDs

---

## Summary

### OrderAutomation Table

- **Created at:** When mailboxes are created (Line 1761)
- **Updated at:** When order completes (Line 2133) or job fails (Line 332)
- **Key Fields:** `order_id`, `action_type`, `job_uuid`, `status`, `error_message`

### Mailbox Creation Times

- **Domain Already Exists:** ~7 seconds
- **Domain First Transferred:** ~5-16 minutes (typically ~8-10 minutes)
  - Transfer: ~5-8 seconds
  - Activation: 5-15 minutes
  - Mailbox Creation: ~7-9 seconds

### Key Takeaways

1. **Domain activation is the bottleneck** for new transfers (5-15 minutes)
2. **Mailbox creation is fast** once domain is active (~7 seconds)
3. **OrderAutomation records** are created immediately when mailboxes are requested
4. **Status tracking** allows monitoring of pending/failed/completed jobs
