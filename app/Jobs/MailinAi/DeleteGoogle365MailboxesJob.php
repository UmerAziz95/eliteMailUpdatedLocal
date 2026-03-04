<?php

namespace App\Jobs\MailinAi;

use App\Models\Order;
use App\Services\OrderCancelledService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteGoogle365MailboxesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;
    protected $batchSize;
    protected $offset;

    /**
     * Create a new job instance.
     *
     * @param int $orderId Order ID
     * @param int $batchSize Number of mailboxes to process per batch
     * @param int $offset Starting offset for batch processing
     */
    public function __construct(int $orderId, int $batchSize = 50, int $offset = 0)
    {
        $this->orderId = $orderId;
        $this->batchSize = $batchSize;
        $this->offset = $offset;
    }

    /**
     * Execute the job.
     */
    public function handle(OrderCancelledService $service): void
    {
        Log::channel('mailin-ai')->info('DeleteGoogle365MailboxesJob: Starting batch processing', [
            'action' => 'delete_google365_mailboxes_batch',
            'order_id' => $this->orderId,
            'offset' => $this->offset,
            'batch_size' => $this->batchSize,
        ]);

        try {
            // Call service to process batch
            $result = $service->deleteGoogle365OrderMailboxes(
                $this->orderId,
                $this->batchSize,
                $this->offset
            );

            Log::channel('mailin-ai')->info('DeleteGoogle365MailboxesJob: Batch completed', [
                'action' => 'delete_google365_mailboxes_batch',
                'order_id' => $this->orderId,
                'offset' => $this->offset,
                'processed' => $result['processed'],
                'deleted' => $result['deleted'],
                'failed' => $result['failed'],
                'not_found' => $result['not_found'],
                'lookup_failed' => $result['lookup_failed'],
                'has_more' => $result['has_more'],
                'next_offset' => $result['next_offset'] ?? null,
            ]);

            // Recursively dispatch job if more mailboxes remain
            if ($result['has_more']) {
                $nextOffset = $result['next_offset'];
                
                Log::channel('mailin-ai')->info('DeleteGoogle365MailboxesJob: Dispatching next batch', [
                    'action' => 'delete_google365_mailboxes_batch',
                    'order_id' => $this->orderId,
                    'next_offset' => $nextOffset,
                    'batch_size' => $this->batchSize,
                ]);

                // Dispatch next batch
                self::dispatch($this->orderId, $this->batchSize, $nextOffset);
            } else {
                // All batches completed - update status to 'cancelled'
                $this->updateOrderStatusToCancelled();
                
                Log::channel('mailin-ai')->info('DeleteGoogle365MailboxesJob: All batches completed', [
                    'action' => 'delete_google365_mailboxes_batch',
                    'order_id' => $this->orderId,
                    'total_processed' => $result['processed'],
                    'total_deleted' => $result['deleted'],
                    'total_failed' => $result['failed'],
                ]);
            }

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('DeleteGoogle365MailboxesJob: Batch processing failed', [
                'action' => 'delete_google365_mailboxes_batch',
                'order_id' => $this->orderId,
                'offset' => $this->offset,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Update order status to 'cancelled' when all batches are complete.
     */
    protected function updateOrderStatusToCancelled(): void
    {
        try {
            $order = Order::find($this->orderId);
            
            if ($order && $order->status_manage_by_admin === 'cancellation-in-process') {
                $order->update([
                    'status_manage_by_admin' => 'cancelled',
                ]);
                
                Log::channel('mailin-ai')->info('DeleteGoogle365MailboxesJob: Updated order status to cancelled', [
                    'action' => 'update_order_status',
                    'order_id' => $this->orderId,
                    'previous_status' => 'cancellation-in-process',
                    'new_status' => 'cancelled',
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('DeleteGoogle365MailboxesJob: Failed to update order status', [
                'action' => 'update_order_status_failed',
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
