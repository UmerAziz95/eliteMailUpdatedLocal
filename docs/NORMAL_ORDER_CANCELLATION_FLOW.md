# Normal Order Cancellation Flow

This document explains the complete flow when a **normal order** is cancelled. (Pool orders are handled separately and are not covered in this document)

## Overview

When a normal order is cancelled, the system performs a series of steps to:
1. Cancel the subscription in ChargeBee (payment gateway)
2. Update all related database records
3. Handle email accounts/mailboxes appropriately
4. Schedule domain removal if needed
5. Send notifications to relevant parties
6. Log all activities

---

## Entry Points (Where Cancellation Can Be Triggered)

### 1. Admin Panel
- **Location**: `app/Http/Controllers/Admin/OrderController.php`
- **Method**: `updateOrderStatus()`
- **Trigger**: Admin changes order status to `'cancelled'` or `'cancelled_force'`
- **Lines**: 2087-2098

### 2. Contractor Panel
- **Location**: `app/Http/Controllers/Contractor/OrderController.php`
- **Method**: `updateOrderStatus()`
- **Trigger**: Contractor changes order status to `'cancelled'` or `'cancelled_force'`
- **Lines**: 1579-1591

### 3. Customer Portal
- **Location**: `app/Http/Controllers/Customer/PlanController.php`
- **Method**: `cancelSubscriptionProcess()`
- **Trigger**: Customer directly cancels their subscription
- **Lines**: 1061-1070

---

## Main Cancellation Service

**Service**: `app/Services/OrderCancelledService.php`  
**Method**: `cancelSubscription()`

### Method Signature
```php
public function cancelSubscription(
    $chargebee_subscription_id, 
    $user_id, 
    $reason, 
    $remove_accounts = false, 
    $force_cancel = false
)
```

### Parameters
- `$chargebee_subscription_id`: The ChargeBee subscription ID
- `$user_id`: The user ID who owns the subscription
- `$reason`: Cancellation reason (required)
- `$remove_accounts`: Whether to remove accounts immediately (legacy parameter, not actively used)
- `$force_cancel`: 
  - `false` = End of Billing Cycle (EOBC) cancellation - services continue until end date
  - `true` = Immediate cancellation - services stop immediately

---

## Detailed Flow Steps

### Step 1: Validation
**Location**: Lines 26-36

- Checks if subscription exists in database (`UserSubscription` model)
- Verifies subscription status is `'active'`
- Returns error if subscription not found or already cancelled

### Step 2: ChargeBee Cancellation
**Location**: Lines 39-57

- First checks if subscription is already cancelled in ChargeBee (to avoid duplicate API calls)
- If not cancelled, calls ChargeBee API:
  ```php
  \ChargeBee\ChargeBee\Models\Subscription::cancelForItems($chargebee_subscription_id, [
      "end_of_term" => false,  // Immediate cancellation
      "credit_option" => "none",
      "unbilled_charges_option" => "delete",
      "account_receivables_handling" => "no_action"
  ])
  ```
- Verifies cancellation was successful

### Step 3: Calculate End Date
**Location**: Lines 62-84

The end date determines when services actually stop:

**For Force Cancel (`$force_cancel = true`)**:
- End date = Current date/time (`now()`)
- Services stop immediately

**For EOBC Cancel (`$force_cancel = false`)**:
- Calculates based on billing cycle:
  - Uses `next_billing_date` if available: `next_billing_date - 1 day`
  - Falls back to `last_billing_date + 1 month` if no next billing date
  - Services continue until calculated end date

### Step 4: Update Subscription Record
**Location**: Lines 86-95

Updates `UserSubscription` model:
```php
[
    'status' => 'cancelled',
    'cancellation_at' => now(),
    'reason' => $reason,
    'end_date' => $endDate,
    'next_billing_date' => null,
    'is_cancelled_force' => $force_cancel,
]
```

### Step 5: Update User Record
**Location**: Lines 96-102

Updates `User` model:
```php
[
    'subscription_status' => 'cancelled',
    'subscription_id' => null,
    'plan_id' => null
]
```

### Step 6: Update Order Record
**Location**: Lines 105-123

- Finds the related `Order` record by `chargebee_subscription_id`
- Updates order status: `status_manage_by_admin = 'cancelled'`

**Mailbox Handling** (Lines 112-123):
- **Force Cancel**: Immediately deletes mailboxes from Mailin.ai via `deleteOrderMailboxes()`
- **EOBC Cancel**: Skips immediate deletion - mailboxes remain active until subscription end date
  - Deletion happens later via scheduled job `DeleteExpiredMailboxes` command

### Step 7: Activity Logging
**Location**: Lines 126-135

Creates activity log entry:
- Action: `'customer-subscription-cancelled'`
- Logs subscription ID, user ID, and status change

### Step 8: Domain Removal Task Creation
**Location**: Lines 137-176

**Condition Checks**:
1. Order must have splits (`OrderPanel` records exist)
2. Mailin.ai automation must be disabled OR provider type must NOT be "Private SMTP"

If conditions are met:
- Creates `DomainRemovalTask` entry
- **Queue Start Date**:
  - Force Cancel: Starts immediately (`now()`)
  - EOBC Cancel: Starts 72 hours after subscription end date (`end_date + 72 hours`)

**Skipped If**:
- Order has no splits (no domains to remove)
- Mailin.ai automation is enabled AND provider type is "Private SMTP" (automation handles domain removal)

### Step 9: Send Email Notifications
**Location**: Lines 178-225

Sends cancellation emails via queue:
1. **To Customer**: `SubscriptionCancellationMail` sent to user's email
2. **To Admin**: `SubscriptionCancellationMail` sent to admin email (configured in `mail.admin_address`)

Both emails are queued (not sent immediately) and include:
- Subscription details
- Cancellation reason
- End date information

Errors are logged to `email-failures` channel but don't fail the cancellation process.

### Step 10: OrderObserver Triggers
**Location**: `app/Observers/OrderObserver.php` (Lines 145-158)

When order status is updated to `'cancelled'`, the `OrderObserver` automatically:
- Sends Slack notification via `SlackNotificationService::sendOrderCancellationNotification()`
- Fires `OrderStatusUpdated` event for real-time updates
- Fires `OrderUpdated` event

### Step 11: Timer Pausing
**Location**: `app/Observers/OrderObserver.php` (Lines 520-522)

When order status changes to `'cancelled'`:
- Sets `timer_paused_at = now()` to stop order processing timer

---

## Return Response

### Success Response
```php
[
    'success' => true,
    'message' => 'Subscription cancelled successfully',
    'order_id' => $order->id,
    'cancellation_reason' => $reason,
]
```

### Error Responses
```php
// No active subscription found
[
    'success' => false,
    'message' => 'No active subscription found'
]

// ChargeBee cancellation failed
[
    'success' => false,
    'message' => 'Failed to cancel subscription in payment gateway'
]

// General exception
[
    'success' => false,
    'message' => 'Failed to cancel subscription: ' . $e->getMessage()
]
```

---

## Mailbox Deletion Details

### Method: `deleteOrderMailboxes(Order $order)`
**Location**: Lines 369-491

**Purpose**: Deletes all Mailin.ai mailboxes associated with an order

**Conditions** (must ALL be true):
1. Mailin.ai automation is enabled (`config('mailin_ai.automation_enabled') = true`)
2. Order provider type is `'Private SMTP'`
3. Order has `OrderEmail` records with `mailin_mailbox_id` set

**Process**:
1. Retrieves all `OrderEmail` records for the order with `mailin_mailbox_id`
2. For each email:
   - Calls `MailinAiService::deleteMailbox()` to delete from Mailin.ai
   - If successful: Deletes `OrderEmail` record from database
   - If failed: Logs error but continues with other mailboxes
3. Logs activity for successful deletions

**Called When**:
- Force cancel: Immediately during cancellation
- EOBC cancel: Not called immediately (handled by `DeleteExpiredMailboxes` scheduled job later)

---

## Domain Removal Task Details

### Model: `DomainRemovalTask`
**Purpose**: Queues domains for removal after cancellation

**Fields**:
- `started_queue_date`: When removal process should start
- `user_id`: User who owns the subscription
- `order_id`: Related order ID
- `chargebee_subscription_id`: ChargeBee subscription ID
- `reason`: Cancellation reason
- `assigned_to`: Contractor assigned to handle removal (null initially)
- `status`: Task status (default: `'pending'`)

**Schedule**:
- **Force Cancel**: Queue starts immediately
- **EOBC Cancel**: Queue starts 72 hours after subscription end date

**Not Created If**:
- Order has no splits (no domains to remove)
- Mailin.ai automation enabled AND provider type is "Private SMTP"

---

## Important Distinctions

### Force Cancel vs EOBC Cancel

| Aspect | Force Cancel (`cancelled_force`) | EOBC Cancel (`cancelled`) |
|--------|----------------------------------|---------------------------|
| **End Date** | Current date/time | Calculated from billing cycle |
| **Services Stop** | Immediately | At end of billing period |
| **Mailboxes Deleted** | Immediately | At end date (via scheduled job) |
| **Domain Removal Queue** | Starts immediately | Starts 72 hours after end date |
| **User Experience** | Instant termination | Service continues until paid period ends |

---

## Logging

All cancellation activities are logged with context:

**Log Channels Used**:
- Default: General cancellation flow
- `email-failures`: Email sending failures
- `mailin-ai`: Mailin.ai mailbox operations
- `slack_notifications`: Slack notification attempts

**Key Log Points**:
1. Cancellation initiation
2. Subscription validation
3. ChargeBee API calls
4. Database updates
5. Mailbox operations
6. Email sending
7. Domain removal task creation

---

## Related Files

### Controllers
- `app/Http/Controllers/Admin/OrderController.php` - Admin cancellation
- `app/Http/Controllers/Contractor/OrderController.php` - Contractor cancellation
- `app/Http/Controllers/Customer/PlanController.php` - Customer cancellation

### Services
- `app/Services/OrderCancelledService.php` - Main cancellation logic
- `app/Services/MailinAiService.php` - Mailbox management
- `app/Services/ActivityLogService.php` - Activity logging
- `app/Services/SlackNotificationService.php` - Slack notifications

### Models
- `app/Models/Subscription.php` (UserSubscription) - Subscription records
- `app/Models/Order.php` - Order records
- `app/Models/User.php` - User records
- `app/Models/DomainRemovalTask.php` - Domain removal queue
- `app/Models/OrderEmail.php` - Email account records
- `app/Models/OrderPanel.php` - Order split/panel records

### Observers
- `app/Observers/OrderObserver.php` - Handles order status change events

### Scheduled Jobs
- `app/Console/Commands/DeleteExpiredMailboxes.php` - Deletes mailboxes after EOBC cancellation end date

### Email Templates
- `app/Mail/SubscriptionCancellationMail.php` - Cancellation email template

---

## Flow Diagram Summary

```
[Trigger: Admin/Contractor/Customer]
        ↓
[OrderCancelledService::cancelSubscription()]
        ↓
[Validate: Subscription exists & is active]
        ↓
[Cancel in ChargeBee API]
        ↓
[Calculate End Date (Force or EOBC)]
        ↓
[Update Subscription Record]
        ↓
[Update User Record]
        ↓
[Update Order Record]
        ↓
├──→ [Force Cancel?] → Yes → [Delete Mailboxes Immediately]
│                         └─→ [Queue Domain Removal (immediate)]
│
└──→ [EOBC Cancel?] → Yes → [Skip Mailbox Deletion]
                    └─→ [Queue Domain Removal (72h after end date)]
        ↓
[Create Activity Log]
        ↓
[Send Email Notifications (queued)]
        ↓
[OrderObserver: Send Slack Notification]
        ↓
[Return Success Response]
```

---

## Notes

1. **Pool Orders**: This document does NOT cover pool order cancellations, which use `PoolOrderCancelledService` instead.

2. **Error Handling**: Cancellation process is designed to be resilient - individual step failures (like email sending) don't fail the entire cancellation.

3. **Database Transactions**: Currently, the cancellation is NOT wrapped in a database transaction. Each step commits independently.

4. **Asynchronous Operations**: Email sending and mailbox deletion are queued/async operations for better performance.

5. **ChargeBee Integration**: Cancellation always attempts ChargeBee cancellation first, ensuring billing is stopped at the payment gateway level.
