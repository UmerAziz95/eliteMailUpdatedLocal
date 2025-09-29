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
        Log::info('InvoiceObserver: Invoice updated event triggered', [
            'invoice_id' => $invoice->id,
            'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
            'status' => $invoice->status,
            'old_status' => $invoice->getOriginal('status')
        ]);

        // Only send Slack notification if status has changed
        if ($invoice->isDirty('status')) {
            $oldStatus = $invoice->getOriginal('status');
            $newStatus = $invoice->status;
            
            Log::info('InvoiceObserver: Invoice status changed', [
                'invoice_id' => $invoice->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);

            // Send Slack notification based on new status
            if (strtolower($newStatus) === 'failed') {
                $this->sendInvoiceSlackNotification($invoice, true, 'updated');
            } elseif (in_array(strtolower($newStatus), ['paid', 'pending'])) {
                $this->sendInvoiceSlackNotification($invoice, false, 'updated');
            }
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
                    'event_type' => $eventType
                ]);
                // Choose appropriate notification method based on event type
                if ($eventType === 'updated') {
                    Log::channel('slack_notifications')->info('InvoiceObserver: Sending invoice updated notification', [
                        'invoice_id' => $invoice->id,
                        'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
                        'user_id' => $user->id,
                        'is_payment_failed' => $isPaymentFailed,
                        'event_type' => $eventType
                    ]);
                    SlackNotificationService::sendInvoiceUpdatedNotification(
                        $invoice, 
                        $user, 
                        $isPaymentFailed
                    );
                } else {
                    SlackNotificationService::sendInvoiceGeneratedNotification(
                        $invoice, 
                        $user, 
                        $isPaymentFailed
                    );
                }
                Log::channel('slack_notifications')->info('InvoiceObserver: Slack notification sent for invoice updated', [
                    'invoice_id' => $invoice->id,
                    'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
                    'user_id' => $user->id,
                    'is_payment_failed' => $isPaymentFailed,
                    'event_type' => $eventType
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
                'event_type' => $eventType
            ]);
        }
    }
}
