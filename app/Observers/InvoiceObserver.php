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
                // Choose appropriate notification method based on event type
                if ($eventType === 'updated') {
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
                
                Log::channel('slack_notifications')->info('InvoiceObserver: Slack notification sent', [
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
