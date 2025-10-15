<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\User;
use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\Log;

class InvoiceObserver
{
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        Log::info('InvoiceObserver: Invoice created event triggered', [
            'invoice_id' => $invoice->id,
            'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
            'status' => $invoice->status
        ]);

        // Set attempt_number to 1 for new invoices if not already set
        if (is_null($invoice->attempt_number)) {
            $invoice->attempt_number = 1;
            $invoice->saveQuietly(); // Save without triggering observers again
        }

        // Check invoice status and send Slack notification for invoice created/generated
        if (strtolower($invoice->status) === 'failed') {
            $this->sendInvoiceSlackNotification($invoice, true);
        } else {
            $this->sendInvoiceSlackNotification($invoice, false);
        }
        // Log invoice creation
        Log::info('InvoiceObserver: T1 Invoice created with status', [
            'invoice_id' => $invoice->id,
            'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
            'status' => $invoice->status
        ]);
        // Record payment failure if status is not paid
        if (strtolower($invoice->status) !== 'paid') {
            try {
                \DB::table('payment_failures')->updateOrInsert(
                    [
                        'chargebee_subscription_id' => $invoice->chargebee_subscription_id,
                        'chargebee_customer_id' => $invoice->chargebee_customer_id,
                    ],
                    [
                        'type' => 'invoice',
                        'status' => $invoice->status ?? 'unknown',
                        'user_id' => $invoice->user_id ?? null,
                        'plan_id' => $invoice->plan_id ?? null,
                        'failed_at' => now('UTC'),
                        'invoice_data' => json_encode($invoice->toArray()) ?? null,
                        'updated_at' => now('UTC'),
                        'created_at' => now('UTC'),
                    ]
                );

                Log::info('Payment failure recorded successfully', [
                    'invoice_id' => $invoice->id,
                    'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
                    'status' => $invoice->status,
                ]);
            } catch (\Exception $ex) {
                Log::error('Failed to record payment failure: ' . $ex->getMessage());
            }
        }
    }

    /**
     * Handle the Invoice "updated" event.
     */
    
    public function updated(Invoice $invoice): void
    {
        $oldStatus = $invoice->getOriginal('status');
        $newStatus = $invoice->status;
        $originalAttemptNumber = $invoice->getOriginal('attempt_number') ?? 1;
        $currentAttemptNumber = $invoice->attempt_number ?? 1;

        Log::info('InvoiceObserver: Invoice updated event triggered', [
            'invoice_id' => $invoice->id,
            'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
            'status' => $newStatus,
            'old_status' => $oldStatus,
            'attempt_number' => $currentAttemptNumber,
            'original_attempt_number' => $originalAttemptNumber
        ]);

        // Check if we need to increment attempt_number
        $needsAttemptIncrement = false;
        
        if ($invoice->isDirty('status') && strtolower($newStatus) === 'failed') {
            // Status changed to 'failed' from any other status
            if (strtolower($oldStatus) !== 'failed') {
                // First time failing - set to 1 if not already set and persist
                if ($currentAttemptNumber <= 1) {
                    $invoice->attempt_number = 1;
                    // Persist the initial attempt_number without firing observers
                    $invoice->saveQuietly();
                }
            } else {
                // Status remained 'failed' but was updated (retry) - increment
                $needsAttemptIncrement = true;
            }
        } elseif (!$invoice->isDirty('status') && 
                  strtolower($newStatus) === 'failed' && 
                  strtolower($oldStatus) === 'failed') {
            // Both old and new status are 'failed' and status didn't change,
            // but invoice was updated (likely a retry attempt from external system)
            $needsAttemptIncrement = true;
        }

        if ($needsAttemptIncrement && !$invoice->isDirty('attempt_number')) {
            // Only increment if attempt_number wasn't manually changed
            $invoice->attempt_number = $currentAttemptNumber + 1;
            // Persist the increment without firing observers
            $invoice->saveQuietly();
            
            Log::info('InvoiceObserver: Auto-incremented attempt number', [
                'invoice_id' => $invoice->id,
                'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
                'old_attempt_number' => $currentAttemptNumber,
                'new_attempt_number' => $invoice->attempt_number
            ]);
        }

        // Send Slack notification if status has changed OR both old and new status are 'failed'
        if ($invoice->isDirty('status')) {
            Log::info('InvoiceObserver: Invoice status changed', [
                'invoice_id' => $invoice->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'attempt_number' => $invoice->attempt_number ?? 1
            ]);

            // Send Slack notification based on new status
            if (strtolower($newStatus) === 'failed') {
                $this->sendInvoiceSlackNotification($invoice, true, 'updated');
            } elseif (in_array(strtolower($newStatus), ['paid', 'pending'])) {
                $this->sendInvoiceSlackNotification($invoice, false, 'updated');
            }
        }
        // Also send notification if both old and new status are 'failed' (retry attempts)
        elseif (strtolower($newStatus) === 'failed' && 
                 strtolower($oldStatus) === 'failed') {
            Log::info('InvoiceObserver: Both old and new status are failed, sending notification for retry attempt', [
                'invoice_id' => $invoice->id,
                'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
                'attempt_number' => $invoice->attempt_number ?? 1
            ]);
            
            $this->sendInvoiceSlackNotification($invoice, true, 'updated');
        }
    }

    /**
     * Send invoice Slack notification
     */
    private function sendInvoiceSlackNotification(Invoice $invoice, bool $isPaymentFailed, string $eventType = 'created'): void
    {
        try {
            $user = User::find($invoice->user_id);
            if ($user) {
                Log::channel('slack_notifications')->info('InvoiceObserver: Invoice status changed, preparing to send Slack notification', [
                    'invoice_id' => $invoice->id,
                    'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
                    'user_id' => $user->id,
                    'is_payment_failed' => $isPaymentFailed,
                    'event_type' => $eventType,
                    'attempt_number' => $invoice->attempt_number ?? 1
                ]);
                // Choose appropriate notification method based on event type
                if ($eventType === 'updated') {
                    Log::channel('slack_notifications')->info('InvoiceObserver: Sending invoice updated notification', [
                        'invoice_id' => $invoice->id,
                        'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
                        'user_id' => $user->id,
                        'is_payment_failed' => $isPaymentFailed,
                        'event_type' => $eventType,
                        'attempt_number' => $invoice->attempt_number ?? 1
                    ]);
                    SlackNotificationService::sendInvoiceUpdatedNotification(
                        $invoice, 
                        $user, 
                        $isPaymentFailed,
                        $invoice->attempt_number ?? 1
                    );
                } else {
                    SlackNotificationService::sendInvoiceGeneratedNotification(
                        $invoice, 
                        $user, 
                        $isPaymentFailed,
                        $invoice->attempt_number ?? 1
                    );
                }
                Log::channel('slack_notifications')->info('InvoiceObserver: Slack notification sent for invoice updated', [
                    'invoice_id' => $invoice->id,
                    'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
                    'user_id' => $user->id,
                    'is_payment_failed' => $isPaymentFailed,
                    'event_type' => $eventType,
                    'attempt_number' => $invoice->attempt_number ?? 1
                ]);
            } else {
                Log::warning('InvoiceObserver: User not found for invoice', [
                    'invoice_id' => $invoice->id,
                    'user_id' => $invoice->user_id
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('slack_notifications')->error('InvoiceObserver: Failed to send Slack notification', [
                'invoice_id' => $invoice->id,
                'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
                'error' => $e->getMessage(),
                'event_type' => $eventType,
                'attempt_number' => $invoice->attempt_number ?? 1
            ]);
        }
    }
}
