<?php

namespace App\Observers;

use App\Jobs\MailinAi\CreateMailboxesJob;
use App\Models\OrderAutomation;
use Illuminate\Support\Facades\Log;

class OrderAutomationObserver
{
    /**
     * Handle the OrderAutomation "created" event.
     */
    public function created(OrderAutomation $orderAutomation): void
    {
        $this->triggerMailboxCreation($orderAutomation, 'created');
    }

    /**
     * Handle the OrderAutomation "updated" event.
     */
    public function updated(OrderAutomation $orderAutomation): void
    {
        $this->triggerMailboxCreation($orderAutomation, 'updated');
    }

    /**
     * Trigger mailbox creation if conditions are met
     */
    private function triggerMailboxCreation(OrderAutomation $orderAutomation, string $event): void
    {
        // Check if this is a domain purchase
        // TODO: Status check commented out for now - will be used in future when status updates are implemented
        // if ($orderAutomation->action_type === 'domain' && 
        //     $orderAutomation->status === 'completed' &&
        //     $orderAutomation->wasChanged('status')) {
        //     
        //     $previousStatus = $orderAutomation->getOriginal('status');
        //     
        //     // Only trigger if status changed from something other than 'completed' to 'completed'
        //     if ($previousStatus !== 'completed') {
        
        if ($orderAutomation->action_type === 'domain') {
            Log::channel('mailin-ai')->info('Domain purchase automation ' . $event . ', triggering mailbox creation', [
                'action' => 'domain_purchase_' . $event,
                'order_id' => $orderAutomation->order_id,
                'job_uuid' => $orderAutomation->job_uuid,
                'status' => $orderAutomation->status,
                'event' => $event,
            ]);

            // Check if mailbox creation job already exists for this order
            $existingMailboxJob = OrderAutomation::where('order_id', $orderAutomation->order_id)
                ->where('action_type', 'mailbox')
                ->first();

            if ($existingMailboxJob) {
                Log::channel('mailin-ai')->info('Mailbox creation job already exists for order', [
                    'action' => 'domain_purchase_' . $event,
                    'order_id' => $orderAutomation->order_id,
                    'existing_mailbox_job_uuid' => $existingMailboxJob->job_uuid,
                ]);
                return;
            }

            // Dispatch mailbox creation job
            try {
                CreateMailboxesJob::dispatch($orderAutomation->order_id);
                
                Log::channel('mailin-ai')->info('Mailbox creation job dispatched', [
                    'action' => 'domain_purchase_' . $event,
                    'order_id' => $orderAutomation->order_id,
                ]);
            } catch (\Exception $e) {
                Log::channel('mailin-ai')->error('Failed to dispatch mailbox creation job', [
                    'action' => 'domain_purchase_' . $event,
                    'order_id' => $orderAutomation->order_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
        // }
    }
}
