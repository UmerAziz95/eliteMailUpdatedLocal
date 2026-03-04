# Private SMTP Order Handling Flow - Complete Detailed Guide

## 📋 Table of Contents
1. [Overview](#overview)
2. [Prerequisites & Requirements](#prerequisites--requirements)
3. [System Architecture](#system-architecture)
4. [Complete Step-by-Step Flow](#complete-step-by-step-flow)
5. [Database Schema & Tables](#database-schema--tables)
6. [API Integration Details](#api-integration-details)
7. [Error Handling & Edge Cases](#error-handling--edge-cases)
8. [Status Management](#status-management)
9. [Notifications & Communication](#notifications--communication)
10. [Configuration & Setup](#configuration--setup)
11. [Troubleshooting Guide](#troubleshooting-guide)

---

## Overview

This document provides a **comprehensive, detailed guide** for handling customer orders when **Private SMTP** is set for normal orders (not pool orders). 

### What is Private SMTP?
Private SMTP is a provider type that uses **Mailin.ai automation** instead of the traditional pool order assignment process. This enables:
- ✅ Fully automated mailbox creation
- ✅ Automatic domain transfer management
- ✅ No manual panel assignment required
- ✅ Direct integration with Mailin.ai API
- ✅ Real-time status updates

### Key Benefits
- **Automation**: Eliminates manual intervention
- **Speed**: Faster order processing
- **Reliability**: Automated error handling and retries
- **Transparency**: Real-time status tracking

---

## Prerequisites & Requirements

### 1. System Configuration
- ✅ **Mailin.ai automation must be enabled**: `MAILIN_AI_AUTOMATION_ENABLED = true` in `.env`
- ✅ **Mailin.ai API credentials configured**:
  - `MAILIN_AI_BASE_URL` - API base URL
  - `MAILIN_AI_EMAIL` - Login email
  - `MAILIN_AI_PASSWORD` - Login password
  - `MAILIN_AI_DEVICE_NAME` - Device identifier (optional, defaults to "project inbox")
  - `MAILIN_AI_TIMEOUT` - Request timeout in seconds (optional, defaults to 30)

### 2. Plan Configuration
- ✅ Plan must have `provider_type = 'Private SMTP'` in `plans` table
- ✅ Plan must be active and available for customers

### 3. Hosting Platform Requirements
- ✅ Must be either `'spaceship'` or `'namecheap'`
- ✅ **For Spaceship**:
  - `spaceship_api_key` - Required
  - `spaceship_api_secret_key` - Required
- ✅ **For Namecheap**:
  - `namecheap_api_key` - Required
  - `namecheap_api_user` (platform_login) - Required
  - `namecheap_ip_whitelisted` - Must be confirmed (IP whitelisted in Namecheap)
  - `backup_codes` - Required

### 4. Order Type
- ✅ Must be a **normal order** (not a pool order)
- ✅ Order must be created through customer order form

### 5. Queue System
- ✅ Laravel queue system must be running
- ✅ Queue worker must be active to process background jobs

---

## System Architecture

### Components Involved

1. **OrderController** (`app/Http/Controllers/Customer/OrderController.php`)
   - Handles order creation and validation
   - Dispatches mailbox creation jobs
   - Manages order status updates

2. **CreateMailboxesOnOrderJob** (`app/Jobs/MailinAi/CreateMailboxesOnOrderJob.php`)
   - Main background job for mailbox creation
   - Handles domain transfer logic
   - Manages mailbox creation via Mailin.ai API

3. **MailinAiService** (`app/Services/MailinAiService.php`)
   - API client for Mailin.ai
   - Handles authentication
   - Domain transfer operations
   - Mailbox creation operations
   - Status checking

4. **SpaceshipService** (`app/Services/SpaceshipService.php`)
   - Updates nameservers in Spaceship
   - Handles Spaceship API interactions

5. **NamecheapService** (`app/Services/NamecheapService.php`)
   - Updates nameservers in Namecheap
   - Handles Namecheap API interactions

### Data Flow Diagram
```
Customer Form → OrderController → Order Created
                                      ↓
                              CreateMailboxesOnOrderJob (Queued)
                                      ↓
                              MailinAiService (Authenticate)
                                      ↓
                              Check Domain Status
                    ┌─────────────────┴─────────────────┐
                    ↓                                   ↓
        Domains Registered?                    Domains Unregistered?
                    ↓                                   ↓
        Create Mailboxes Directly              Transfer Domains
                    ↓                                   ↓
        Save to order_emails                   Update Nameservers
                    ↓                                   ↓
        Complete Order                         Wait for Transfer
                                                    ↓
                                            Check Status (Cron)
                                                    ↓
                                            Create Mailboxes
                                                    ↓
                                            Complete Order
```

---

## Complete Step-by-Step Flow

### **STEP 1: Order Creation/Submission**
**Location:** `app/Http/Controllers/Customer/OrderController.php` (store method, Line 544+)

#### 1.1: Customer Form Submission
Customer submits order form with the following data:

**Required Fields:**
- `user_id` - Customer user ID
- `plan_id` - Selected plan (must have `provider_type = 'Private SMTP'`)
- `domains` - Domain names (comma or newline separated)
  - Example: `"example.com, test.com"` or `"example.com\ntest.com"`
- `inboxes_per_domain` - Number of inboxes per domain (1-3)
- `prefix_variants` - Array of prefix variants
  - Format: `["prefix_variant_1" => "john", "prefix_variant_2" => "jane"]`
- `prefix_variants_details` - Details for each prefix
  - Format: `["prefix_variant_1" => ["first_name" => "John", "last_name" => "Doe"]]`
- `hosting_platform` - Must be `'spaceship'` or `'namecheap'`

**Conditional Fields (Based on Platform):**

**For Spaceship:**
- `spaceship_api_key` - Required
- `spaceship_api_secret_key` - Required

**For Namecheap:**
- `namecheap_api_key` - Required
- `platform_login` - Used as `api_user` and `UserName`
- `namecheap_ip_whitelisted` - Must be confirmed (checkbox)
- `backup_codes` - Required

**Optional Fields:**
- `forwarding_url` - URL for email forwarding
- `profile_picture_link` - Profile picture URL
- `email_persona_picture_link` - Email persona picture URL
- `master_inbox_email` - Master inbox email
- `master_inbox_confirmation` - Boolean
- `additional_info` - Additional notes
- `coupon_code` - Discount coupon

#### 1.2: Validation Process
The system performs comprehensive validation:

**Basic Validation:**
```php
// Line 548-625 in OrderController.php
- user_id: required, exists in users table
- plan_id: required, exists in plans table
- domains: required, string
- hosting_platform: required, string, max 50
- inboxes_per_domain: required, integer, min 1, max 3
```

**Platform-Specific Validation:**
- **Spaceship**: Validates `spaceship_api_key` and `spaceship_api_secret_key` are present
- **Namecheap**: Validates `namecheap_api_key`, `platform_login`, `backup_codes`, and `namecheap_ip_whitelisted`

**Prefix Variant Validation:**
- `prefix_variant_1` is always required
- Each prefix variant must match pattern: `^[a-zA-Z0-9._-]+$`
- For each prefix variant, `first_name` and `last_name` are required
- Maximum 3 prefix variants allowed (based on `inboxes_per_domain`)

**Private SMTP Specific Checks:**
```php
// Line 632-665 in OrderController.php
1. Load plan: $plan = Plan::findOrFail($request->plan_id)
2. Check automation enabled: config('mailin_ai.automation_enabled') === true
3. Check provider type: $plan->provider_type === 'Private SMTP'
4. Check hosting platform: $request->hosting_platform == 'spaceship' || 'namecheap'
```

#### 1.3: Data Extraction for Mailbox Job
If all validations pass, system extracts data for mailbox creation:

```php
// Extract domains
$mailboxDomainNames = array_map(
    'trim',
    array_filter(preg_split('/[\r\n,]+/', $request->domains))
);

// Extract prefix variants
$mailboxPrefixVariants = [];
$inboxesPerDomainForJob = (int) $request->inboxes_per_domain;
for ($i = 1; $i <= $inboxesPerDomainForJob; $i++) {
    $prefixKey = "prefix_variant_{$i}";
    if (!empty($request->prefix_variants[$prefixKey])) {
        $mailboxPrefixVariants[] = trim($request->prefix_variants[$prefixKey]);
    }
}
```

#### 1.4: Order Creation
**Database Operations:**
1. Create `Order` record:
   - `user_id` - Customer ID
   - `plan_id` - Selected plan ID
   - `status` - Set to Chargebee invoice status (if applicable)
   - `status_manage_by_admin` - Set to `'in-progress'` (NOT `'pending'`)
   - `provider_type` - Set to `'Private SMTP'` (from plan)
   - `amount`, `currency`, `paid_at` - Payment details

2. Create `ReorderInfo` record:
   - `order_id` - Link to order
   - `domains` - Raw domain string (comma/newline separated)
   - `inboxes_per_domain` - Number of inboxes
   - `prefix_variants` - JSON encoded array
   - `prefix_variants_details` - JSON encoded array
   - `hosting_platform` - 'spaceship' or 'namecheap'
   - `forwarding_url`, `profile_picture_link`, etc. - Other details

3. Create `PlatformCredential` record (if Spaceship/Namecheap):
   - **For Spaceship:**
     - `platform_type` = 'spaceship'
     - `credentials` = JSON: `{"api_key": "...", "api_secret_key": "..."}`
   - **For Namecheap:**
     - `platform_type` = 'namecheap'
     - `credentials` = JSON: `{"api_key": "...", "api_user": "...", "backup_codes": "..."}`

**Key Difference from Normal Orders:**
- Normal orders: `status_manage_by_admin = 'pending'` → Wait for panel assignment
- Private SMTP orders: `status_manage_by_admin = 'in-progress'` → Skip to automation

**Code Reference:**
```php
// Line 638-664 in OrderController.php
if (config('mailin_ai.automation_enabled') === true && 
    $plan->provider_type === 'Private SMTP' && 
    ($request->hosting_platform == 'spaceship' || $request->hosting_platform == 'namecheap')) {
    
    // Extract domains and prefix variants
    // Prepare mailbox job data
    $status = 'in-progress'; // Set immediately to in-progress
}
```

---

### **STEP 2: Mailbox Creation Job Dispatch**
**Location:** `app/Http/Controllers/Customer/OrderController.php` (after order creation)

1. System dispatches `CreateMailboxesOnOrderJob` queue job with:
   - Order ID
   - Array of domain names
   - Array of prefix variants
   - User ID
   - Provider type (`'Private SMTP'`)

2. Job is queued for background processing

**Key Code Reference:**
```php
// Line 1016-1041 in OrderController.php
if ($shouldDispatchMailboxJob && isset($order)) {
    CreateMailboxesOnOrderJob::dispatch(
        $order->id,
        $mailboxJobData['domains'],
        $mailboxJobData['prefix_variants'],
        $mailboxJobData['user_id'],
        $mailboxJobData['provider_type']
    );
}
```

---

### **STEP 3: Skip Panel Assignment**
**Location:** `app/Http/Controllers/Customer/OrderController.php` (pannelCreationAndOrderSplitOnPannels method)

1. System checks if order uses Mailin.ai automation
2. If `provider_type === 'Private SMTP'`:
   - **SKIP** panel creation and assignment
   - **SKIP** order splitting on panels
   - Log that panel assignment is skipped

**Key Code Reference:**
```php
// Line 1149-1157 in OrderController.php
if (config('mailin_ai.automation_enabled') === true && 
    $order->provider_type === 'Private SMTP') {
    Log::info("Skipping panel assignment for automated order #{$order->id}");
    return; // Exit early
}
```

---

### **STEP 4: Mailbox Creation Job Execution**
**Location:** `app/Jobs/MailinAi/CreateMailboxesOnOrderJob.php` (handle method)

#### **4.1: Initial Validation**
1. Load order with relationships (plan, reorderInfo, platformCredentials)
2. Validate domains array is not empty
3. Validate prefix variants array is not empty
4. Authenticate with Mailin.ai service

#### **4.2: Check Existing Mailboxes**
1. Query `order_emails` table for existing mailboxes with `mailin_mailbox_id`
2. Extract domains that already have mailboxes created
3. Filter out domains that already have mailboxes (avoid duplicates)

#### **4.3: Domain Registration Check**
For each domain:
1. Call `MailinAiService::checkDomainStatus($domain)`
2. Categorize domains:
   - **Registered & Active**: Domain is already registered in Mailin.ai and active
   - **Unregistered**: Domain is not registered or not active

**Key Code Reference:**
```php
// Line 159-188 in CreateMailboxesOnOrderJob.php
foreach ($domainsToProcess as $domain) {
    $statusResult = $mailinService->checkDomainStatus($domain);
    if ($statusResult['status'] === 'active') {
        $registeredDomains[] = $domain;
    } else {
        $unregisteredDomains[] = $domain;
    }
}
```

---

### **STEP 5: Domain Transfer (If Needed)**
**Location:** `app/Jobs/MailinAi/CreateMailboxesOnOrderJob.php` (handleDomainTransfer method)

**Only executed if unregistered domains exist**

#### **5.1: Set Order Status**
1. Ensure order status is `'in-progress'` (not completed)
2. Log status change to activity log

#### **5.2: Initiate Domain Transfer**
For each unregistered domain:
1. Call `MailinAiService::transferDomain($domain)`
2. Receive nameservers from Mailin.ai response
3. Create `DomainTransfer` record with:
   - `order_id`
   - `domain_name`
   - `name_servers` (JSON array)
   - `status = 'pending'`

#### **5.3: Update Nameservers at Hosting Platform**
**For Spaceship:**
1. Get Spaceship API credentials from order
2. Call `SpaceshipService::updateNameservers($domain, $nameServers, $apiKey, $apiSecretKey)`
3. Update `DomainTransfer` record:
   - `name_server_status = 'updated'` (if successful)
   - `name_server_status = 'failed'` (if failed)

**For Namecheap:**
1. Get Namecheap API credentials from order
2. Call `NamecheapService::updateNameservers($domain, $nameServers, $apiUser, $apiKey)`
3. Update `DomainTransfer` record:
   - `name_server_status = 'updated'` (if successful)
   - `name_server_status = 'failed'` (if failed)

#### **5.4: Handle Nameserver Update Failures**
If nameserver updates fail:
1. Set order status to `'draft'`
2. Send email notification to customer with error details
3. Log activity
4. Customer must fix issues (API credentials, IP whitelisting) and resubmit

**Key Code Reference:**
```php
// Line 426-1040 in CreateMailboxesOnOrderJob.php
private function handleDomainTransfer($order, $mailinService, $domainsToTransfer)
{
    // Transfer domains
    // Update nameservers
    // Handle failures
}
```

---

### **STEP 6: Create Mailboxes for Registered Domains**
**Location:** `app/Jobs/MailinAi/CreateMailboxesOnOrderJob.php` (handle method)

**Only executed if registered domains exist**

#### **6.1: Generate Mailbox List**
For each registered domain × each prefix variant:
1. Generate email: `{prefix}@{domain}`
2. Use prefix as name
3. Generate password using `generatePassword($userId, $index)`
4. Create mailbox data structure

#### **6.2: Call Mailin.ai API**
1. Call `MailinAiService::createMailboxes($mailboxes)`
2. Receive job UUID from Mailin.ai
3. Save `OrderAutomation` record with:
   - `order_id`
   - `action_type = 'mailbox'`
   - `job_uuid`
   - `status = 'pending'`

#### **6.3: Wait for Mailbox Creation**
1. Poll `MailinAiService::getMailboxJobStatus($jobUuid)` every 10 seconds
2. Maximum 30 attempts (5 minutes total)
3. Wait until status is `'completed'` or `'failed'`

#### **6.4: Save Mailboxes to Database**
1. Extract mailbox IDs and domain IDs from API response
2. Create `OrderEmail` records for each mailbox:
   - `order_id`
   - `user_id`
   - `email` (username)
   - `password`
   - `mailin_mailbox_id`
   - `mailin_domain_id`
3. Create notification for customer: "New Email Accounts Created"

#### **6.5: Complete Order (If All Domains Registered)**
If there are **no unregistered domains**:
1. Update order:
   - `status_manage_by_admin = 'completed'`
   - `completed_at = now()`
   - `provider_type = 'Private SMTP'`
2. Update `OrderAutomation` status to `'completed'`
3. Create activity log entry
4. Create notification: "Order Completed"
5. Send email to customer: Order completion notification

**Key Code Reference:**
```php
// Line 240-303 in CreateMailboxesOnOrderJob.php
if (!empty($registeredDomains)) {
    // Generate mailboxes
    // Call Mailin.ai API
    // Save mailboxes
    // Complete order if no unregistered domains
}
```

---

### **STEP 7: Wait for Domain Transfers (If Applicable)**
**Location:** Scheduled job checks domain transfer status

**Only if unregistered domains exist**

1. Scheduled job (cron) periodically checks `DomainTransfer` records with `status = 'pending'`
2. For each pending transfer:
   - Check domain status via `MailinAiService::checkDomainStatus($domain)`
   - If domain becomes `'active'`:
     - Update `DomainTransfer` status to `'completed'`
     - Trigger mailbox creation for that domain
3. Once all domains are active:
   - Create mailboxes for remaining domains
   - Complete the order

---

### **STEP 8: Order Completion**
**Location:** `app/Jobs/MailinAi/CreateMailboxesOnOrderJob.php` (saveMailboxesForDomains method)

When all mailboxes are created:

1. **Order Status Update:**
   - `status_manage_by_admin = 'completed'`
   - `completed_at = now()`

2. **OrderAutomation Update:**
   - `status = 'completed'`

3. **Activity Log:**
   - Log order completion with details

4. **Customer Notification:**
   - Create in-app notification
   - Send email notification

5. **OrderEmail Records:**
   - All mailboxes saved with Mailin.ai IDs

**Key Code Reference:**
```php
// Line 1354-1448 in CreateMailboxesOnOrderJob.php
if ($completeOrder) {
    $order->update([
        'status_manage_by_admin' => 'completed',
        'completed_at' => now(),
    ]);
    // Send notifications
    // Log activity
}
```

---

## Key Differences from Pool Orders

| Aspect | Normal Order (Private SMTP) | Pool Order |
|--------|----------------------------|------------|
| **Assignment** | Uses Mailin.ai automation | Uses pool assignment service |
| **Panel Creation** | **SKIPPED** | Created and assigned |
| **Order Splitting** | **SKIPPED** | Split across panels |
| **Domain Transfer** | Automated via Mailin.ai | Manual or different process |
| **Mailbox Creation** | Via Mailin.ai API | Via pool assignment |
| **Status Flow** | `draft` → `in-progress` → `completed` | `pending` → `in-progress` → `completed` |
| **Initial Status** | `in-progress` (if automation enabled) | `pending` |

---

## Error Handling

### **Nameserver Update Failures**
- Order set to `'draft'`
- Customer notified via email
- Customer must fix issues and resubmit

### **Domain Transfer Failures**
- `DomainTransfer` record created with `status = 'failed'`
- Error message stored
- Order remains `'in-progress'`
- Admin can manually retry

### **Mailbox Creation Failures**
- `OrderAutomation` record created with `status = 'failed'`
- Error message logged
- Order remains `'in-progress'`
- Can be retried manually

---

## Database Tables Involved

1. **`orders`** - Main order record
2. **`reorder_infos`** - Order details (domains, prefix variants, hosting platform)
3. **`platform_credentials`** - Spaceship/Namecheap API credentials
4. **`order_emails`** - Created mailboxes with Mailin.ai IDs
5. **`order_automations`** - Track Mailin.ai job status
6. **`domain_transfers`** - Track domain transfer status
7. **`notifications`** - Customer notifications
8. **`activity_logs`** - System activity tracking

---

## Configuration Requirements

1. **`.env` file:**
   ```env
   MAILIN_AI_AUTOMATION_ENABLED=true
   MAILIN_AI_API_URL=https://api.mailin.ai
   MAILIN_AI_API_KEY=your_api_key
   ```

2. **Plan Configuration:**
   - Plan must have `provider_type = 'Private SMTP'`

3. **Hosting Platform:**
   - Must be `spaceship` or `namecheap`
   - Valid API credentials must be provided

---

## Customer Experience Flow

1. **Order Submission:**
   - Customer fills form with domains, prefixes, and credentials
   - Order is immediately set to `in-progress`

2. **Domain Transfer (if needed):**
   - System automatically transfers domains to Mailin.ai
   - Nameservers updated at hosting provider
   - Customer may receive email if nameserver update fails

3. **Mailbox Creation:**
   - System creates mailboxes via Mailin.ai API
   - Customer receives notification when mailboxes are created

4. **Order Completion:**
   - Order automatically completes when all mailboxes are created
   - Customer receives completion email
   - All email accounts are available in `order_emails` table

---

## Monitoring & Logging

All actions are logged to:
- **Channel:** `mailin-ai`
- **Location:** `storage/logs/mailin-ai.log`

Key log events:
- Order creation with Private SMTP
- Domain transfer initiation
- Nameserver update attempts
- Mailbox creation job dispatch
- Mailbox creation completion
- Order completion

---

---

## API Integration Details

### Mailin.ai API Authentication

**Service:** `MailinAiService::authenticate()`

**Process:**
1. **Check Cache**: First checks for cached token (`mailin_ai_token` cache key)
2. **Validate Config**: Ensures `MAILIN_AI_BASE_URL`, `MAILIN_AI_EMAIL`, `MAILIN_AI_PASSWORD` are set
3. **API Request**: POST to `/auth/login` with:
   ```json
   {
     "email": "your_email@example.com",
     "password": "your_password",
     "device_name": "project inbox"
   }
   ```
4. **Token Caching**: Token cached for `expires_in - 60` seconds (default 3540 seconds = 59 minutes)
5. **Auto Retry**: If 401 error, automatically clears cache and re-authenticates

**Response Format:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "expires_in": 3600
}
```

### Domain Status Check

**Endpoint:** `GET /domains?name={domain_name}`

**Purpose:** Check if domain is registered and active in Mailin.ai

**Response Scenarios:**

1. **Domain Found & Active:**
```json
{
  "success": true,
  "status": "active",
  "name_server_status": "active",
  "name_servers": ["ns1.mailin.ai", "ns2.mailin.ai"],
  "domain_name": "example.com"
}
```

2. **Domain Not Found (404):**
```json
{
  "success": false,
  "status": null,
  "not_found": true,
  "message": "Domain not found in Mailin.ai system yet"
}
```

3. **Network Error:**
```json
{
  "success": false,
  "network_error": true,
  "message": "Network error - will retry later"
}
```

### Domain Transfer

**Endpoint:** `POST /domains/transfer`

**Request:**
```json
{
  "domain_name": "example.com"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Domain transfer process started",
  "name_servers": ["ns1.mailin.ai", "ns2.mailin.ai"],
  "response": {...}
}
```

**What Happens:**
1. Mailin.ai initiates domain transfer
2. Returns nameservers that need to be updated at hosting provider
3. System updates nameservers via Spaceship/Namecheap API
4. Domain transfer status tracked in `domain_transfers` table

### Mailbox Creation

**Endpoint:** `POST /mailboxes`

**Request:**
```json
{
  "mailboxes": [
    {
      "username": "john@example.com",
      "name": "john",
      "password": "SecurePass123!"
    },
    {
      "username": "jane@example.com",
      "name": "jane",
      "password": "SecurePass456!"
    }
  ]
}
```

**Response (Async):**
```json
{
  "success": true,
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "message": "Mailbox creation job started"
}
```

**Status Check:**
- **Endpoint:** `GET /mailboxes/status/{job_uuid}`
- **Polling:** Every 10 seconds, max 30 attempts (5 minutes)
- **Status Values:** `pending`, `processing`, `completed`, `failed`

**Completed Response:**
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
      },
      {
        "id": 12346,
        "username": "jane@example.com",
        "domain_id": 67890,
        "name": "jane"
      }
    ]
  }
}
```

### Spaceship API Integration

**Service:** `SpaceshipService::updateNameservers()`

**Purpose:** Update nameservers for domain in Spaceship

**Required Credentials:**
- `api_key` - Spaceship API key
- `api_secret_key` - Spaceship API secret key

**Process:**
1. Authenticate with Spaceship API
2. Update domain nameservers
3. Return success/failure status

### Namecheap API Integration

**Service:** `NamecheapService::updateNameservers()`

**Purpose:** Update nameservers for domain in Namecheap

**Required Credentials:**
- `api_user` - Namecheap API username (same as `platform_login`)
- `api_key` - Namecheap API key
- IP must be whitelisted in Namecheap account

**Process:**
1. Authenticate with Namecheap API
2. Update domain nameservers
3. Return success/failure status

---

## Status Management

### Order Status Flow

**Private SMTP Order Statuses:**

1. **`draft`** - Initial state (if `is_draft = 1`) or set when nameserver update fails
   - Customer can edit order
   - No processing started

2. **`in-progress`** - Order is being processed
   - Set immediately when Private SMTP order is created
   - Domain transfer in progress
   - Mailbox creation in progress
   - Cannot be edited by customer

3. **`completed`** - Order fully processed
   - All mailboxes created
   - All domains transferred (if needed)
   - Customer can access email accounts

4. **`reject`** - Order rejected (rare for Private SMTP)
   - Customer can fix and resubmit

5. **`cancelled`** - Order cancelled
   - No further processing

**Status Transitions:**
```
draft → in-progress → completed
  ↓         ↓
reject   draft (if nameserver update fails)
```

### Domain Transfer Status

**Status Values in `domain_transfers` table:**

- **`pending`** - Transfer initiated, waiting for domain to become active
- **`completed`** - Domain is active in Mailin.ai
- **`failed`** - Transfer failed

**Name Server Status:**
- **`updated`** - Nameservers successfully updated at hosting provider
- **`failed`** - Nameserver update failed (will retry)

### OrderAutomation Status

**Status Values in `order_automations` table:**

- **`pending`** - Job submitted to Mailin.ai, waiting for completion
- **`completed`** - Mailboxes successfully created
- **`failed`** - Mailbox creation failed

---

## Error Handling & Edge Cases

### 1. Nameserver Update Failures

**Scenario:** Nameserver update fails at hosting provider

**Handling:**
1. Order status set to `'draft'`
2. `DomainTransfer` record updated:
   - `name_server_status = 'failed'`
   - `error_message` = Detailed error
   - `status = 'pending'` (kept for retry)
3. Email sent to customer with:
   - Error details
   - Instructions to fix (check API credentials, IP whitelisting)
   - Request to resubmit order
4. Activity log created
5. Customer can fix and resubmit order

**Common Causes:**
- Invalid API credentials
- IP not whitelisted (Namecheap)
- API rate limiting
- Network connectivity issues

**Retry Mechanism:**
- Scheduled job (`CheckDomainTransferStatus`) retries failed nameserver updates
- Customer can also resubmit order after fixing issues

### 2. Domain Transfer Failures

**Scenario:** Domain transfer fails at Mailin.ai

**Handling:**
1. `DomainTransfer` record created with `status = 'failed'`
2. `error_message` stored
3. Order remains `'in-progress'`
4. Admin can manually retry or investigate

**Common Causes:**
- Domain already transferred
- Invalid domain name
- Domain locked at registrar
- Mailin.ai API error

### 3. Mailbox Creation Failures

**Scenario:** Mailbox creation fails at Mailin.ai

**Handling:**
1. `OrderAutomation` record created with `status = 'failed'`
2. `error_message` stored
3. Order remains `'in-progress'`
4. Can be retried manually

**Common Causes:**
- Domain not registered (should trigger transfer first)
- Invalid mailbox data
- Mailin.ai API error
- Rate limiting

### 4. Authentication Failures

**Scenario:** Mailin.ai authentication fails

**Handling:**
1. Job fails with exception
2. Error logged to `mailin-ai` channel
3. Order remains `'in-progress'`
4. Job can be retried (will re-authenticate)

**Common Causes:**
- Invalid credentials in `.env`
- Network connectivity issues
- Mailin.ai service down

### 5. Partial Domain Registration

**Scenario:** Some domains registered, others not

**Handling:**
1. Registered domains: Mailboxes created immediately
2. Unregistered domains: Transfer initiated
3. Order remains `'in-progress'` until all domains active
4. Once all domains active, remaining mailboxes created
5. Order completed when all mailboxes created

### 6. Duplicate Mailbox Prevention

**Scenario:** Job runs multiple times or order edited

**Handling:**
1. System checks `order_emails` table for existing mailboxes
2. Domains with existing mailboxes skipped
3. Only new domains processed
4. Prevents duplicate mailbox creation

### 7. Network/Timeout Errors

**Scenario:** API request times out or network error

**Handling:**
1. Error logged with full details
2. For domain status checks: Returns `network_error = true`, will retry later
3. For mailbox creation: Job fails, can be retried
4. For domain transfer: `DomainTransfer` record created with `status = 'failed'`

---

## Notifications & Communication

### Customer Notifications

**1. Order Created Notification**
- **When:** Order successfully created
- **Type:** In-app notification
- **Content:** "Your order #123 has been created and is being processed"

**2. Email Accounts Created Notification**
- **When:** Mailboxes created for registered domains
- **Type:** In-app notification
- **Content:** "New email accounts have been automatically created for your order #123"
- **Data:** Includes email count

**3. Order Completed Notification**
- **When:** All mailboxes created, order completed
- **Type:** In-app notification + Email
- **Content:** "Your order #123 has been automatically completed - mailbox creation"
- **Email:** `OrderStatusChangeMail` sent to customer

**4. Nameserver Update Failure Notification**
- **When:** Nameserver update fails
- **Type:** Email only
- **Content:** Detailed error message with instructions to fix
- **Action Required:** Customer must fix issues and resubmit

### Activity Logs

**All actions logged to `activity_logs` table:**

1. **Order Status Changes:**
   - `mailin_ai_order_status_in_progress` - Order set to in-progress
   - `mailin_ai_order_completed` - Order completed
   - `mailin_ai_order_set_to_draft` - Order set to draft (nameserver failure)

2. **Domain Transfer:**
   - Domain transfer initiated
   - Nameserver update success/failure
   - Domain transfer completed

3. **Mailbox Creation:**
   - Mailbox creation job dispatched
   - Mailbox creation completed
   - Mailbox creation failed

---

## Database Schema & Tables

### orders Table
```sql
- id (primary key)
- user_id (foreign key → users.id)
- plan_id (foreign key → plans.id)
- status (Chargebee status)
- status_manage_by_admin (draft|pending|in-progress|completed|reject|cancelled)
- provider_type (Google|Microsoft 365|Private SMTP)
- amount, currency, paid_at
- completed_at (timestamp when completed)
- created_at, updated_at
```

### reorder_infos Table
```sql
- id (primary key)
- order_id (foreign key → orders.id)
- domains (text - comma/newline separated)
- inboxes_per_domain (integer)
- prefix_variants (json)
- prefix_variants_details (json)
- hosting_platform (spaceship|namecheap|other)
- forwarding_url, profile_picture_link, etc.
- created_at, updated_at
```

### platform_credentials Table
```sql
- id (primary key)
- order_id (foreign key → orders.id)
- platform_type (spaceship|namecheap)
- credentials (json - encrypted API keys)
- created_at, updated_at
```

### order_emails Table
```sql
- id (primary key)
- order_id (foreign key → orders.id)
- user_id (foreign key → users.id)
- email (varchar - full email address)
- password (encrypted)
- name (varchar - prefix name)
- mailin_mailbox_id (integer - Mailin.ai mailbox ID)
- mailin_domain_id (integer - Mailin.ai domain ID)
- created_at, updated_at
```

### order_automations Table
```sql
- id (primary key)
- order_id (foreign key → orders.id)
- provider_type (Private SMTP)
- action_type (mailbox|domain)
- job_uuid (varchar - Mailin.ai job UUID)
- status (pending|completed|failed)
- response_data (json - API response)
- error_message (text - if failed)
- created_at, updated_at
```

### domain_transfers Table
```sql
- id (primary key)
- order_id (foreign key → orders.id)
- domain_name (varchar)
- name_servers (json array)
- status (pending|completed|failed)
- name_server_status (updated|failed|null)
- response_data (json - API response)
- error_message (text - if failed)
- created_at, updated_at
```

### notifications Table
```sql
- id (primary key)
- user_id (foreign key → users.id)
- type (order_status_change|email_created)
- title (varchar)
- message (text)
- data (json - additional data)
- read_at (timestamp)
- created_at, updated_at
```

### activity_logs Table
```sql
- id (primary key)
- action (varchar - action identifier)
- description (text)
- subject_type, subject_id (polymorphic - Order model)
- properties (json - action details)
- created_at, updated_at
```

---

## Configuration & Setup

### Environment Variables (.env)

```env
# Mailin.ai Configuration
MAILIN_AI_AUTOMATION_ENABLED=true
MAILIN_AI_BASE_URL=https://api.mailin.ai
MAILIN_AI_EMAIL=your_email@example.com
MAILIN_AI_PASSWORD=your_password
MAILIN_AI_DEVICE_NAME=project inbox
MAILIN_AI_TIMEOUT=30
```

### Config File (config/mailin_ai.php)

```php
return [
    'automation_enabled' => env('MAILIN_AI_AUTOMATION_ENABLED', false),
    'base_url' => env('MAILIN_AI_BASE_URL'),
    'email' => env('MAILIN_AI_EMAIL'),
    'password' => env('MAILIN_AI_PASSWORD'),
    'device_name' => env('MAILIN_AI_DEVICE_NAME', 'project inbox'),
    'timeout' => env('MAILIN_AI_TIMEOUT', 30),
];
```

### Queue Configuration

**Required Queue Connection:**
- Default queue connection must be configured
- Queue worker must be running: `php artisan queue:work`

**Job Configuration:**
- Jobs use `ShouldQueue` interface
- Jobs are queued for background processing
- Failed jobs can be retried

### Plan Configuration

**In `plans` table:**
- `provider_type` must be set to `'Private SMTP'`
- Plan must be active
- Plan must be available for customer selection

---

## Troubleshooting Guide

### Issue: Order Stuck in "in-progress"

**Possible Causes:**
1. Queue worker not running
2. Job failed silently
3. Domain transfer pending
4. Mailin.ai API issues

**Solutions:**
1. Check queue worker: `php artisan queue:work`
2. Check failed jobs: `php artisan queue:failed`
3. Check domain transfer status in `domain_transfers` table
4. Check Mailin.ai API status
5. Check logs: `storage/logs/mailin-ai.log`

### Issue: Nameserver Update Fails

**Possible Causes:**
1. Invalid API credentials
2. IP not whitelisted (Namecheap)
3. API rate limiting
4. Network issues

**Solutions:**
1. Verify API credentials in `platform_credentials` table
2. Check IP whitelisting in Namecheap account
3. Wait and retry (rate limiting)
4. Check network connectivity
5. Manually update nameservers if needed

### Issue: Mailbox Creation Fails

**Possible Causes:**
1. Domain not registered
2. Invalid mailbox data
3. Mailin.ai API error
4. Authentication failure

**Solutions:**
1. Check domain status via Mailin.ai API
2. Verify mailbox data format
3. Check Mailin.ai API status
4. Verify authentication credentials
5. Retry job manually

### Issue: Domain Transfer Stuck

**Possible Causes:**
1. Nameservers not updated
2. DNS propagation delay
3. Domain locked at registrar
4. Mailin.ai processing delay

**Solutions:**
1. Verify nameservers updated at hosting provider
2. Wait for DNS propagation (can take 24-48 hours)
3. Check domain lock status at registrar
4. Contact Mailin.ai support if needed

### Issue: Authentication Fails

**Possible Causes:**
1. Invalid credentials in `.env`
2. Mailin.ai service down
3. Network connectivity issues

**Solutions:**
1. Verify `.env` credentials
2. Check Mailin.ai service status
3. Test network connectivity
4. Clear token cache: `Cache::forget('mailin_ai_token')`

---

## Summary

Private SMTP orders for normal customers follow a **fully automated flow**:

1. ✅ **Order Created** → Status immediately set to `in-progress`
2. ✅ **Panel Assignment SKIPPED** → No manual assignment needed
3. ✅ **Domain Transfer Initiated** (if needed) → Automated via Mailin.ai
4. ✅ **Nameservers Updated** → Automated via Spaceship/Namecheap API
5. ✅ **Mailboxes Created** → Automated via Mailin.ai API
6. ✅ **Order Completed** → Automatic when all mailboxes created
7. ✅ **Customer Notified** → At each major step

### Key Advantages

- **Zero Manual Intervention**: Fully automated from order creation to completion
- **Real-time Processing**: Immediate status updates and notifications
- **Error Recovery**: Automatic retry mechanisms for transient failures
- **Transparency**: Complete activity logging and status tracking
- **Customer Experience**: Seamless, automated experience with clear notifications

### When to Use Private SMTP

- ✅ Customer has domains on Spaceship or Namecheap
- ✅ Customer wants automated mailbox creation
- ✅ Customer can provide valid API credentials
- ✅ Customer wants fast order processing

### When NOT to Use Private SMTP

- ❌ Customer uses other hosting providers (not Spaceship/Namecheap)
- ❌ Customer cannot provide API credentials
- ❌ Customer prefers manual control over domain transfer
- ❌ Order requires pool order assignment

This flow eliminates manual intervention for Private SMTP orders and provides a **fully automated, reliable, and transparent experience** for both customers and administrators.

