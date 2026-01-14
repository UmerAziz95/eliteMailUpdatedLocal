<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderEmail;
use App\Services\MailinAiService;
use App\Models\SmtpProviderSplit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeleteOrderMailboxesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:delete-mailboxes {order_id : The order ID to delete mailboxes for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete mailboxes from Mailin.ai for a specific order';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $orderId = $this->argument('order_id');

        $this->info("Starting mailbox deletion for Order #{$orderId}...");

        Log::channel('mailin-ai')->info('DeleteOrderMailboxesCommand: Starting mailbox deletion', [
            'action' => 'delete_order_mailboxes_cmd',
            'order_id' => $orderId,
        ]);

        // Verify order exists
        $order = Order::find($orderId);
        if (!$order) {
            $this->error("Order #{$orderId} not found.");
            return 1;
        }

        // Get all order emails for this order
        $orderEmails = OrderEmail::where('order_id', $orderId)->get();

        if ($orderEmails->count() === 0) {
            $this->info("No mailboxes found for Order #{$orderId}.");
            Log::channel('mailin-ai')->info('DeleteOrderMailboxesCommand: No mailboxes to delete', [
                'action' => 'delete_order_mailboxes_cmd',
                'order_id' => $orderId,
            ]);
            return 0;
        }

        $this->info("Found {$orderEmails->count()} mailbox(es) to delete.");

        // Get Mailin.ai service
        $provider = SmtpProviderSplit::getActiveProvider();
        $credentials = $provider ? $provider->getCredentials() : null;
        $mailinService = new MailinAiService($credentials);

        $deletedCount = 0;
        $failedCount = 0;

        foreach ($orderEmails as $orderEmail) {
            try {
                $mailboxIdToDelete = $orderEmail->mailin_mailbox_id;

                // If mailin_mailbox_id is null, look up by email
                if (!$mailboxIdToDelete && $orderEmail->email) {
                    $this->line("  Looking up mailbox ID for: {$orderEmail->email}");
                    
                    try {
                        $lookupResult = $mailinService->getMailboxesByName($orderEmail->email);
                        if (!empty($lookupResult) && isset($lookupResult[0]['id'])) {
                            $mailboxIdToDelete = $lookupResult[0]['id'];
                            $this->line("    Found ID: {$mailboxIdToDelete}");
                        }
                    } catch (\Exception $lookupException) {
                        $this->warn("    Failed to lookup: {$lookupException->getMessage()}");
                    }
                }

                if ($mailboxIdToDelete) {
                    $this->line("  Deleting: {$orderEmail->email} (ID: {$mailboxIdToDelete})");
                    $mailinService->deleteMailbox($mailboxIdToDelete);
                    $deletedCount++;
                    $this->info("    ✓ Deleted from Mailin.ai");
                    
                    Log::channel('mailin-ai')->info('DeleteOrderMailboxesCommand: Deleted mailbox from Mailin.ai', [
                        'action' => 'delete_order_mailboxes_cmd',
                        'order_id' => $orderId,
                        'email' => $orderEmail->email,
                        'mailin_mailbox_id' => $mailboxIdToDelete,
                    ]);
                } else {
                    $this->warn("  ⚠ Could not find mailbox ID for: {$orderEmail->email}");
                }
            } catch (\Exception $deleteException) {
                $failedCount++;
                $this->error("  ✗ Failed to delete {$orderEmail->email}: {$deleteException->getMessage()}");
                
                Log::channel('mailin-ai')->error('DeleteOrderMailboxesCommand: Failed to delete mailbox from Mailin.ai', [
                    'action' => 'delete_order_mailboxes_cmd',
                    'order_id' => $orderId,
                    'email' => $orderEmail->email,
                    'error' => $deleteException->getMessage(),
                ]);
            }
        }

        // Delete OrderEmail records from database
        $deletedFromDb = OrderEmail::where('order_id', $orderId)->delete();

        $this->newLine();
        $this->info("=== Deletion Summary ===");
        $this->info("Deleted from Mailin.ai: {$deletedCount}");
        $this->info("Failed deletions: {$failedCount}");
        $this->info("Deleted from database: {$deletedFromDb}");

        Log::channel('mailin-ai')->info('DeleteOrderMailboxesCommand: Completed mailbox deletion', [
            'action' => 'delete_order_mailboxes_cmd',
            'order_id' => $orderId,
            'deleted_from_mailin' => $deletedCount,
            'failed_deletions' => $failedCount,
            'deleted_from_database' => $deletedFromDb,
        ]);

        return 0;
    }
}
