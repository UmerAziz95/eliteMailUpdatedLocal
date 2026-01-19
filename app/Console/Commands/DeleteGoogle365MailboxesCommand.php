<?php

namespace App\Console\Commands;

use App\Services\OrderCancelledService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DeleteGoogle365MailboxesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailboxes:delete-google/Microsoft365 
                            {order_id : The order ID to delete mailboxes for}
                            {--batch-size=50 : Number of mailboxes to process per batch}
                            {--offset=0 : Starting offset for batch processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete mailboxes for Google/365 orders in batches (recursive)';

    /**
     * Execute the console command.
     */
    public function handle(OrderCancelledService $service): int
    {
        $orderId = (int) $this->argument('order_id');
        $batchSize = (int) $this->option('batch-size');
        $offset = (int) $this->option('offset');

        $this->info("Processing batch for order #{$orderId} (offset: {$offset}, batch size: {$batchSize})");

        Log::channel('mailin-ai')->info('DeleteGoogle365MailboxesCommand: Starting batch processing', [
            'action' => 'delete_google365_mailboxes_batch',
            'order_id' => $orderId,
            'offset' => $offset,
            'batch_size' => $batchSize,
        ]);

        try {
            // Call service to process batch (pass order ID and batch parameters)
            $result = $service->deleteGoogle365OrderMailboxes(
                $orderId,
                $batchSize,
                $offset
            );

            $this->info("Batch completed: {$result['deleted']} deleted, {$result['failed']} failed, {$result['not_found']} not found");

            Log::channel('mailin-ai')->info('DeleteGoogle365MailboxesCommand: Batch completed', [
                'action' => 'delete_google365_mailboxes_batch',
                'order_id' => $orderId,
                'offset' => $offset,
                'processed' => $result['processed'],
                'deleted' => $result['deleted'],
                'failed' => $result['failed'],
                'not_found' => $result['not_found'],
                'lookup_failed' => $result['lookup_failed'],
                'has_more' => $result['has_more'],
                'next_offset' => $result['next_offset'] ?? null,
            ]);

            // Recursively call command if more mailboxes remain
            if ($result['has_more']) {
                $nextOffset = $result['next_offset'];
                $this->info("More mailboxes to process. Continuing with offset {$nextOffset}...");

                Log::channel('mailin-ai')->info('DeleteGoogle365MailboxesCommand: Recursively calling command for next batch', [
                    'action' => 'delete_google365_mailboxes_batch',
                    'order_id' => $orderId,
                    'next_offset' => $nextOffset,
                    'batch_size' => $batchSize,
                ]);

                Artisan::call('mailboxes:delete-google/Microsoft365', [
                    'order_id' => $orderId,
                    '--batch-size' => $batchSize,
                    '--offset' => $nextOffset,
                ]);

                // Output the recursive call result
                $this->line(Artisan::output());
            } else {
                $this->info("All mailboxes processed for order #{$orderId}");
                
                Log::channel('mailin-ai')->info('DeleteGoogle365MailboxesCommand: All batches completed', [
                    'action' => 'delete_google365_mailboxes_batch',
                    'order_id' => $orderId,
                    'total_processed' => $result['processed'],
                    'total_deleted' => $result['deleted'],
                    'total_failed' => $result['failed'],
                ]);
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Error processing batch: {$e->getMessage()}");
            
            Log::channel('mailin-ai')->error('DeleteGoogle365MailboxesCommand: Batch processing failed', [
                'action' => 'delete_google365_mailboxes_batch',
                'order_id' => $orderId,
                'offset' => $offset,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
