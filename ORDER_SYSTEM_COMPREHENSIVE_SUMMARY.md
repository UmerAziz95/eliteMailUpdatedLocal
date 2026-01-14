# Comprehensive Order System Summary

## Table of Contents
1. [Order Creation Flow](#order-creation-flow)
2. [Automation System](#automation-system)
3. [API Integrations](#api-integrations)
4. [Events & Listeners](#events--listeners)
5. [Notifications](#notifications)
6. [Use Cases & Error Handling](#use-cases--error-handling)

---

## Order Creation Flow

### 1. Customer Order Submission

**Location:** `app/Http/Controllers/Customer/OrderController.php::store()`

**Process:**
1. **Form Validation** (Lines 548-625)
   - Validates user_id, plan_id, domains, hosting_platform, sending_platform
   - Validates prefix variants and details
   - Validates domain format (no duplicates, valid format)
   - Validates inboxes_per_domain (1-3 max)
   - Validates platform-specific credentials:
     - **Spaceship**: `spaceship_api_key`, `spaceship_api_secret_key`
     - **Namecheap**: `namecheap_api_key`, `namecheap_ip_whitelisted`, `backup_codes`

2. **Order Type Detection** (Lines 638-665)
   - Checks if automation is enabled: `config('mailin_ai.automation_enabled') === true`
   - Checks plan provider type: `$plan->provider_type === 'Private SMTP'`
   - Checks hosting platform: `spaceship` or `namecheap`
   - If all conditions met:
     - Extracts domains and prefix variants
     - Prepares mailbox job data
     - Sets status to `'in-progress'` (skips panel assignment)
   - Otherwise: Sets status to `'draft'` or `'pending'`

3. **Order Creation** (Database)
   - Creates `Order` record:
     - `user_id`, `plan_id`, `status_manage_by_admin`
     - `provider_type` (from plan)
     - Payment details (if applicable)
   - Creates `ReorderInfo` record:
     - `domains` (comma/newline separated)
     - `inboxes_per_domain`, `prefix_variants`, `prefix_variants_details`
     - `hosting_platform`, `forwarding_url`, etc.
   - Creates `PlatformCredential` record (if Spaceship/Namecheap):
     - **Spaceship**: `api_key`, `api_secret_key`
     - **Namecheap**: `api_user`, `api_key`

4. **Mailbox Job Dispatch** (Lines 1016-1041)
   - If Private SMTP order:
     - Dispatches `CreateMailboxesOnOrderJob` queue job
     - Job contains: order_id, domains, prefix_variants, user_id, provider_type

5. **Panel Assignment** (Lines 1145-1157)
   - **SKIPPED** for Private SMTP orders
   - Normal orders: Creates/splits panels based on capacity

### 2. Order Editing

**Location:** `app/Http/Controllers/Customer/OrderController.php::store()` (Lines 739-1013)

**Process:**
- Only allowed for orders with status `'draft'` or `'reject'`
- Updates `ReorderInfo` with new data
- Updates `PlatformCredential` if credentials changed
- Sends `OrderEditedMail` to:
  - Customer
  - Assigned contractor (if exists)
- Creates activity log entry
- Updates order status based on changes

---

## Automation System

### Mailin.ai Automation Flow

**Main Job:** `app/Jobs/MailinAi/CreateMailboxesOnOrderJob.php`

### Step-by-Step Automation Process

#### 1. Job Initialization
- Loads order with relationships (plan, reorderInfo, platformCredentials)
- Validates domains and prefix variants
- Splits domains across active SMTP providers using `DomainSplitService`

#### 2. Domain Status Check
For each provider:
- Checks domain registration status via `MailinAiService::checkDomainStatus()`
- Categorizes domains:
  - **Registered**: Already active in Mailin.ai
  - **Unregistered**: Need transfer

#### 3. Domain Transfer (if needed)
**Location:** `handleDomainTransfer()` method

**Process:**
1. **Retry Failed Nameserver Updates**
   - Checks for existing failed nameserver updates
   - Retries them first before new transfers

2. **Transfer Unregistered Domains**
   - Calls `MailinAiService::transferDomain()` for each domain
   - Creates `DomainTransfer` record with status `'pending'`
   - Receives nameservers from Mailin.ai

3. **Update Nameservers at Hosting Provider**
   - **Spaceship**: Calls `SpaceshipService::updateNameservers()`
   - **Namecheap**: Calls `NamecheapService::updateNameservers()`
   - Updates `DomainTransfer` record:
     - Success: `name_server_status = 'updated'`
     - Failure: `name_server_status = 'failed'`, stores error message

4. **Error Handling**
   - If nameserver update fails:
     - Order status set to `'draft'`
     - Email sent to customer with error details
     - Activity log created
     - Customer can fix and resubmit

#### 4. Mailbox Creation
**Location:** `createMailboxesForProvider()` method

**Process:**
1. **Generate Mailbox Data**
   - For each domain × prefix variant combination:
     - Username: `{prefix}@{domain}`
     - Name: prefix variant
     - Password: Generated based on user_id

2. **Create Mailboxes via API**
   - Calls `MailinAiService::createMailboxes()`
   - Receives job UUID for async processing

3. **Poll Job Status**
   - Polls `MailinAiService::getMailboxJobStatus()` every 10 seconds
   - Max 30 attempts (5 minutes)
   - Status values: `pending`, `processing`, `completed`, `failed`

4. **Save Mailbox Records**
   - Creates `OrderEmail` records for each mailbox
   - Stores `mailin_mailbox_id`, `email`, `password`, `provider_slug`

5. **Create Notification**
   - In-app notification: "New email accounts created"
   - Includes email count

#### 5. Order Completion
**Location:** `completeOrderAfterAllMailboxesCreated()` method

**Trigger:** All domains have at least one mailbox created

**Process:**
1. Updates order:
   - `status_manage_by_admin = 'completed'`
   - `completed_at = now()`
   - `provider_type = 'Private SMTP'`

2. Updates `OrderAutomation`:
   - `status = 'completed'`

3. Creates activity log:
   - Type: `mailin_ai_order_completed`

4. Creates notification:
   - Type: `order_status_change`
   - Title: "Order Completed"

5. Sends email:
   - `OrderStatusChangeMail` to customer

---

## API Integrations

### 1. Mailin.ai API

**Service:** `app/Services/MailinAiService.php`

#### Authentication
- **Endpoint:** `POST /auth/login`
- **Request:**
  ```json
  {
    "email": "your_email@example.com",
    "password": "your_password",
    "device_name": "project inbox"
  }
  ```
- **Response:**
  ```json
  {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 3600
  }
  ```
- **Caching:** Token cached for `expires_in - 60` seconds
- **Auto Retry:** On 401, clears cache and re-authenticates

#### Domain Status Check
- **Endpoint:** `GET /domains?name={domain_name}`
- **Method:** `checkDomainStatus(string $domainName)`
- **Response Scenarios:**
  - **Domain Found & Active:**
    ```json
    {
      "success": true,
      "status": "active",
      "name_server_status": "active",
      "name_servers": ["ns1.mailin.ai", "ns2.mailin.ai"],
      "domain_name": "example.com"
    }
    ```
  - **Domain Not Found (404):**
    ```json
    {
      "success": false,
      "not_found": true,
      "message": "Domain not found in Mailin.ai system yet"
    }
    ```
  - **Network Error:**
    ```json
    {
      "success": false,
      "network_error": true,
      "message": "Network error - will retry later"
    }
    ```

#### Domain Transfer
- **Endpoint:** `POST /domains/transfer`
- **Method:** `transferDomain(string $domainName, $maxRetries = 3)`
- **Request:**
  ```json
  {
    "domain_name": "example.com"
  }
  ```
- **Response:**
  ```json
  {
    "success": true,
    "message": "Domain transfer process started",
    "name_servers": ["ns1.mailin.ai", "ns2.mailin.ai"]
  }
  ```
- **Rate Limiting:** Handles 429 errors with exponential backoff

#### Mailbox Creation
- **Endpoint:** `POST /mailboxes`
- **Method:** `createMailboxes(array $mailboxes)`
- **Request:**
  ```json
  {
    "mailboxes": [
      {
        "username": "john@example.com",
        "name": "john",
        "password": "SecurePass123!"
      }
    ]
  }
  ```
- **Response (Async):**
  ```json
  {
    "success": true,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "message": "Mailbox creation job started"
  }
  ```
- **Special Handling:**
  - If mailboxes already exist: Returns success with `already_exists: true`
  - If domain not registered: Throws exception with domain list

#### Mailbox Job Status
- **Endpoint:** `GET /mailboxes/status/{job_uuid}`
- **Method:** `getMailboxJobStatus(string $jobId)`
- **Response:**
  ```json
  {
    "success": true,
    "status": "completed",
    "data": {
      "status": "completed",
      "data": [
        {
          "id": 12345,
          "username": "john@example.com",
          "domain_id": 67890,
          "name": "john"
        }
      ]
    }
  }
  ```

#### Delete Mailbox
- **Endpoint:** `DELETE /mailboxes/{mailbox_id}`
- **Method:** `deleteMailbox(int $mailboxId)`

#### Get Mailboxes by Domain
- **Endpoint:** `GET /mailboxes?name={domain_name}`
- **Method:** `getMailboxesByDomain(string $domainName)`

#### Get Mailboxes by Email/Name
- **Endpoint:** `GET /mailboxes?name={email}&per_page=10`
- **Method:** `getMailboxesByName(string $email, int $perPage = 10)`

#### Rate Limiting
- Handles 429 (Too Many Requests) with exponential backoff
- Base delay: 2 seconds
- Max delay: 60 seconds
- Max retries: 3 (configurable)

---

### 2. Spaceship API

**Service:** `app/Services/SpaceshipService.php`

#### Update Nameservers
- **Endpoint:** `PUT https://spaceship.dev/api/v1/domains/{domain}/nameservers`
- **Method:** `updateNameservers(string $domain, array $nameServers, string $apiKey, string $apiSecretKey)`
- **Headers:**
  - `X-API-Key`: API Key
  - `X-API-Secret`: API Secret Key
  - `Content-Type`: application/json
- **Request:**
  ```json
  {
    "provider": "custom",
    "hosts": ["ns1.mailin.ai", "ns2.mailin.ai"]
  }
  ```
- **Response (Success):**
  ```json
  {
    "success": true,
    "message": "Nameservers updated successfully"
  }
  ```
- **Error Handling:**
  - **401/403**: Invalid API credentials
  - **404**: Domain not found in Spaceship account
  - Provides specific error messages for each scenario

---

### 3. Namecheap API

**Service:** `app/Services/NamecheapService.php`

#### Update Nameservers
- **Endpoint:** `GET https://api.namecheap.com/xml.response`
- **Method:** `updateNameservers(string $domain, array $nameServers, string $apiUser, string $apiKey)`
- **Query Parameters:**
  - `ApiUser`: API username
  - `ApiKey`: API key
  - `UserName`: Same as ApiUser (username)
  - `ClientIp`: **144.172.95.185** (MUST match server's outbound IP)
  - `Command`: `namecheap.domains.dns.setCustom`
  - `SLD`: Second-level domain (e.g., "example" from "example.com")
  - `TLD`: Top-level domain (e.g., "com" from "example.com")
  - `NameServers`: Comma-separated nameservers
- **Response (XML):**
  ```xml
  <ApiResponse Status="OK">
    <CommandResponse>
      <DomainDNSSetCustomResult Updated="true" />
    </CommandResponse>
  </ApiResponse>
  ```
- **Error Handling:**
  - **Invalid IP**: Server's outbound IP must match ClientIp parameter
  - **Invalid Credentials**: API key or username incorrect
  - **Domain Not Found**: Domain doesn't exist in Namecheap account
- **Important:** IP whitelisting required in Namecheap account

---

## Events & Listeners

### Events

#### 1. OrderCreated
- **Location:** `app/Events/OrderCreated.php`
- **Triggered:** When new order is created
- **Used in:** OrderObserver

#### 2. OrderUpdated
- **Location:** `app/Events/OrderUpdated.php`
- **Triggered:** When order is updated
- **Used in:** OrderObserver

#### 3. OrderStatusUpdated
- **Location:** `app/Events/OrderStatusUpdated.php`
- **Triggered:** When order status changes
- **Used in:** OrderObserver

#### 4. TaskStarted
- **Location:** `app/Events/TaskStarted.php`
- **Triggered:** When task is started

### Listeners

**Note:** No dedicated listeners found in `app/Listeners/` directory. Events are handled directly in Observers.

### Observers

#### OrderObserver
- **Location:** `app/Observers/OrderObserver.php`
- **Handles:**
  - Order creation notifications
  - Order status change notifications
  - Order assignment notifications

---

## Notifications

### In-App Notifications

#### 1. Order Created
- **Type:** `order_created`
- **When:** Order successfully created
- **Recipient:** Customer
- **Message:** "Your order #123 has been created and is being processed"

#### 2. Email Accounts Created
- **Type:** `email_created`
- **When:** Mailboxes created for registered domains
- **Recipient:** Customer
- **Message:** "New email accounts have been automatically created for your order #123"
- **Data:** Includes email count
- **Location:** `CreateMailboxesOnOrderJob::createMailboxesForProvider()` (Line 2016)

#### 3. Order Completed
- **Type:** `order_status_change`
- **When:** All mailboxes created, order completed
- **Recipient:** Customer
- **Message:** "Your order #123 has been automatically completed - mailbox creation"
- **Data:** Includes old_status, new_status, mailbox_count
- **Location:** `CreateMailboxesOnOrderJob::completeOrderAfterAllMailboxesCreated()` (Line 2110)

#### 4. Order Panel Status Changed
- **Type:** `order_panel_status_change`
- **When:** Order panel status changes (e.g., rejected → pending)
- **Recipient:** Customer, Contractor
- **Message:** "Order #123 status changed to pending"
- **Location:** `OrderController::updateFixedDomains()` (Lines 1979, 1995)

#### 5. Order Edited
- **Type:** `order_edited` (via email only, no in-app notification)
- **When:** Customer edits order
- **Recipient:** Customer, Contractor (if assigned)

### Email Notifications

#### 1. OrderStatusChangeMail
- **Class:** `app/Mail/OrderStatusChangeMail.php`
- **Sent When:**
  - Order status changes to `completed` (automation)
  - Order status changes (admin/contractor actions)
- **Recipients:**
  - Customer
  - Admin (in some cases)
- **Content:**
  - Order details
  - Old status → New status
  - Reason for change
- **Locations:**
  - `CreateMailboxesOnOrderJob::completeOrderAfterAllMailboxesCreated()` (Line 2136)
  - `CreateMailboxesOnOrderJob::handleDomainTransfer()` (Lines 1151, 1269)
  - Admin/Contractor controllers

#### 2. OrderEditedMail
- **Class:** `app/Mail/OrderEditedMail.php`
- **Sent When:**
  - Customer edits order
  - Admin edits order
- **Recipients:**
  - Customer
  - Contractor (if assigned)
  - Admin (commented out for customer edits)
- **Content:**
  - Order details
  - Changes made
- **Location:** `OrderController::store()` (Lines 948, 986)

#### 3. Nameserver Update Failure Email
- **Type:** `OrderStatusChangeMail`
- **Sent When:** Nameserver update fails
- **Recipient:** Customer
- **Content:**
  - Detailed error message
  - Instructions to fix (check API credentials, IP whitelisting)
  - Request to resubmit order
- **Location:** `CreateMailboxesOnOrderJob::handleDomainTransfer()` (Lines 1151, 1269)

---

## Use Cases & Error Handling

### Use Case 1: New Order with Registered Domains

**Scenario:** Customer creates order with domains already registered in Mailin.ai

**Flow:**
1. Order created with status `'in-progress'`
2. `CreateMailboxesOnOrderJob` dispatched
3. Domain status check: All domains are `'active'`
4. Mailboxes created immediately (no transfer needed)
5. Order completed when all mailboxes created

**Notifications:**
- Email accounts created notification
- Order completed notification + email

---

### Use Case 2: New Order with Unregistered Domains

**Scenario:** Customer creates order with domains not yet in Mailin.ai

**Flow:**
1. Order created with status `'in-progress'`
2. `CreateMailboxesOnOrderJob` dispatched
3. Domain status check: Domains are `'not_found'`
4. Domain transfer initiated:
   - `MailinAiService::transferDomain()` called
   - Nameservers received from Mailin.ai
   - Nameservers updated at hosting provider (Spaceship/Namecheap)
5. Order remains `'in-progress'` (waiting for domain activation)
6. Cron job (`CheckDomainTransferStatus`) periodically checks domain status
7. When domains become active, mailboxes created
8. Order completed when all mailboxes created

**Notifications:**
- Email accounts created notification (when mailboxes created)
- Order completed notification + email

---

### Use Case 3: Nameserver Update Failure

**Scenario:** Nameserver update fails at hosting provider

**Flow:**
1. Domain transfer initiated
2. Nameserver update fails (invalid credentials, IP not whitelisted, etc.)
3. `DomainTransfer` record updated:
   - `name_server_status = 'failed'`
   - `error_message` stored
4. Order status set to `'draft'`
5. Email sent to customer with error details
6. Activity log created: `mailin_ai_order_set_to_draft`
7. Customer fixes issues and resubmits order

**Error Types Handled:**
- **Spaceship:**
  - Invalid API credentials (401/403)
  - Domain not found (404)
  - Network errors
- **Namecheap:**
  - Invalid request IP (IP mismatch)
  - Invalid API credentials
  - Domain not found

**Notifications:**
- Email: Nameserver update failure with instructions

---

### Use Case 4: Partial Domain Registration

**Scenario:** Some domains registered, others not

**Flow:**
1. Domain status check categorizes domains
2. Registered domains: Mailboxes created immediately
3. Unregistered domains: Transfer initiated
4. Order remains `'in-progress'` until all domains active
5. Once all domains active, remaining mailboxes created
6. Order completed when all mailboxes created

**Notifications:**
- Email accounts created notification (for registered domains)
- Order completed notification + email (when all done)

---

### Use Case 5: Mailbox Creation Failure

**Scenario:** Mailbox creation fails at Mailin.ai

**Flow:**
1. `MailinAiService::createMailboxes()` called
2. API returns error (domain not registered, invalid data, etc.)
3. `OrderAutomation` record created with `status = 'failed'`
4. Error message stored
5. Order remains `'in-progress'`
6. Can be retried manually

**Error Types Handled:**
- Domain not registered (triggers transfer)
- Mailboxes already exist (treated as success)
- Invalid mailbox data
- Rate limiting (with retry)
- Network errors

---

### Use Case 6: Authentication Failure

**Scenario:** Mailin.ai authentication fails

**Flow:**
1. `MailinAiService::authenticate()` called
2. Authentication fails (invalid credentials, network error)
3. Job fails with exception
4. Error logged to `mailin-ai` channel
5. Order remains `'in-progress'`
6. Job can be retried (will re-authenticate)

**Error Types Handled:**
- Invalid credentials
- Network connectivity issues
- Mailin.ai service down
- Token expiration (auto-retry with re-auth)

---

### Use Case 7: Rate Limiting

**Scenario:** API rate limit exceeded

**Flow:**
1. API request returns 429 (Too Many Requests)
2. Exponential backoff applied:
   - Delay: `2^retryCount * 2` seconds
   - Max delay: 60 seconds
   - Max retries: 3
3. Request retried after delay
4. If max retries reached, exception thrown

**Applied To:**
- Mailin.ai domain transfer
- Mailin.ai mailbox creation
- Mailin.ai status checks

---

### Use Case 8: Duplicate Mailbox Prevention

**Scenario:** Job runs multiple times or order edited

**Flow:**
1. System checks `order_emails` table for existing mailboxes
2. Domains with existing mailboxes skipped
3. Only new domains processed
4. Prevents duplicate mailbox creation

**Location:** `CreateMailboxesOnOrderJob::handle()` (Lines 192-217)

---

### Use Case 9: Network/Timeout Errors

**Scenario:** API request times out or network error

**Flow:**
1. Error logged with full details
2. **Domain Status Checks:**
   - Returns `network_error = true`
   - Will retry later (via cron)
3. **Mailbox Creation:**
   - Job fails, can be retried
4. **Domain Transfer:**
   - `DomainTransfer` record created with `status = 'failed'`
   - Can be retried

---

### Use Case 10: Order Editing

**Scenario:** Customer edits draft/rejected order

**Flow:**
1. Validation: Only `'draft'` or `'reject'` status allowed
2. Updates `ReorderInfo` with new data
3. Updates `PlatformCredential` if credentials changed
4. Sends `OrderEditedMail` to:
   - Customer
   - Assigned contractor (if exists)
5. Creates activity log entry
6. Updates order status:
   - If Private SMTP: Sets to `'in-progress'` (if not draft)
   - Otherwise: Based on changes

**Restrictions:**
- Cannot edit if status is `'in-progress'`, `'completed'`, `'cancelled'`
- Cannot exceed original `total_inboxes` limit

---

### Use Case 11: Domain Transfer Retry

**Scenario:** Failed nameserver update retried

**Flow:**
1. Job checks for failed nameserver updates
2. Retries nameserver update for failed domains
3. If successful:
   - `DomainTransfer` updated: `name_server_status = 'updated'`
   - Domain transfer continues
4. If still fails:
   - Error message updated
   - Order remains `'draft'`

**Location:** `CreateMailboxesOnOrderJob::retryFailedNameserverUpdates()` (Lines 1400-1520)

---

### Use Case 12: Multi-Provider Domain Split

**Scenario:** Order with domains split across multiple SMTP providers

**Flow:**
1. `DomainSplitService` splits domains across active providers
2. Each provider processed separately:
   - Domain status checked per provider
   - Domain transfer per provider (if needed)
   - Mailbox creation per provider
3. Order completed only when ALL providers have all mailboxes created

**Location:** `CreateMailboxesOnOrderJob::handle()` (Lines 104-266)

---

## Activity Logs

All actions logged to `activity_logs` table via `ActivityLogService`:

### Order Status Changes
- `mailin_ai_order_status_in_progress` - Order set to in-progress
- `mailin_ai_order_completed` - Order completed
- `mailin_ai_order_set_to_draft` - Order set to draft (nameserver failure)

### Domain Transfer
- Domain transfer initiated
- Nameserver update success/failure
- Domain transfer completed

### Mailbox Creation
- Mailbox creation job dispatched
- Mailbox creation completed
- Mailbox creation failed

---

## Database Tables

### Key Tables

1. **orders**
   - Order records with status, provider_type, etc.

2. **reorder_infos**
   - Order details: domains, prefix_variants, hosting_platform, etc.

3. **platform_credentials**
   - API credentials for Spaceship/Namecheap (encrypted)

4. **order_emails**
   - Created mailboxes with email, password, mailin_mailbox_id

5. **order_automations**
   - Tracks Mailin.ai job status (pending, completed, failed)

6. **domain_transfers**
   - Tracks domain transfer status and nameserver updates

7. **notifications**
   - In-app notifications for users

8. **activity_logs**
   - System activity tracking

9. **order_provider_splits**
   - Tracks which providers are used for each order

---

## Configuration

### Environment Variables

```env
# Mailin.ai Configuration
MAILIN_AI_AUTOMATION_ENABLED=true
MAILIN_AI_BASE_URL=https://api.mailin.ai
MAILIN_AI_EMAIL=your_email@example.com
MAILIN_AI_PASSWORD=your_password
MAILIN_AI_DEVICE_NAME=project inbox
MAILIN_AI_TIMEOUT=30

# Domain Transfer Configuration
MAILIN_AI_DOMAIN_TRANSFER_DELAY=2  # seconds between transfers
MAILIN_AI_DOMAIN_TRANSFER_BATCH_SIZE=10  # domains per batch
MAILIN_AI_DOMAIN_TRANSFER_BATCH_DELAY=10  # seconds between batches
```

### Plan Configuration
- Plan must have `provider_type = 'Private SMTP'` in `plans` table

### Hosting Platform Requirements
- Must be `'spaceship'` or `'namecheap'`
- Valid API credentials must be provided

---

## Logging

All Mailin.ai operations logged to:
- **Channel:** `mailin-ai`
- **Location:** `storage/logs/mailin-ai.log`

Key log events:
- Order creation with Private SMTP
- Domain transfer initiation
- Nameserver update attempts
- Mailbox creation job dispatch
- Mailbox creation completion
- Order completion
- All errors and exceptions

---

## Summary

This system provides a fully automated order processing flow for Private SMTP orders:

1. **Order Creation**: Validates and creates order, dispatches automation job
2. **Domain Transfer**: Automatically transfers domains to Mailin.ai and updates nameservers
3. **Mailbox Creation**: Creates mailboxes via Mailin.ai API with status polling
4. **Order Completion**: Automatically completes order when all mailboxes created
5. **Error Handling**: Comprehensive error handling with retries, notifications, and status management
6. **Notifications**: In-app and email notifications for all key events
7. **Multi-Provider Support**: Handles domain splitting across multiple SMTP providers

The system integrates with three APIs:
- **Mailin.ai**: Domain transfer, mailbox creation, status checking
- **Spaceship**: Nameserver updates
- **Namecheap**: Nameserver updates

All operations are logged, tracked, and provide notifications to keep users informed throughout the process.
