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
        $successfullyDeletedIds = []; // Track IDs that were successfully deleted from Mailin.ai

        // Group emails by domain for efficient lookups
        $emailsByDomain = [];
        foreach ($orderEmails as $orderEmail) {
            $domain = substr($orderEmail->email, strpos($orderEmail->email, '@') + 1);
            if (!isset($emailsByDomain[$domain])) {
                $emailsByDomain[$domain] = [];
            }
            $emailsByDomain[$domain][] = $orderEmail;
        }

        // Authenticate with Mailin.ai
        if (!$mailinService->authenticate()) {
            $this->error("Failed to authenticate with Mailin.ai");
            return 1;
        }
        $this->info("Authenticated with Mailin.ai");

        // Process each domain
        foreach ($emailsByDomain as $domain => $emails) {
            $this->newLine();
            $this->info("Processing domain: {$domain}");

            // Fetch all mailboxes for this domain from Mailin.ai
            try {
                $lookupResult = $mailinService->getMailboxesByDomain($domain);
                
                // Build lookup map by email
                $mailinMailboxes = [];
                if ($lookupResult['success'] && !empty($lookupResult['mailboxes'])) {
                    foreach ($lookupResult['mailboxes'] as $mb) {
                        $username = strtolower($mb['username'] ?? $mb['email'] ?? '');
                        if ($username && isset($mb['id'])) {
                            $mailinMailboxes[$username] = $mb['id'];
                        }
                    }
                    $this->line("  Found " . count($mailinMailboxes) . " mailboxes on Mailin.ai");
                } else {
                    $this->warn("  Could not fetch mailboxes for domain: {$domain}");
                }
            } catch (\Exception $e) {
                $this->error("  Error fetching mailboxes for {$domain}: {$e->getMessage()}");
                $mailinMailboxes = [];
            }

            // Delete each email
            foreach ($emails as $orderEmail) {
                try {
                    $mailboxIdToDelete = $orderEmail->mailin_mailbox_id;

                    // If mailin_mailbox_id is null, look up in the fetched mailboxes
                    if (!$mailboxIdToDelete && $orderEmail->email) {
                        $emailLower = strtolower($orderEmail->email);
                        if (isset($mailinMailboxes[$emailLower])) {
                            $mailboxIdToDelete = $mailinMailboxes[$emailLower];
                            $this->line("  Found ID via domain lookup: {$orderEmail->email} => {$mailboxIdToDelete}");
                        }
                    }

                    if ($mailboxIdToDelete) {
                        $this->line("  Deleting: {$orderEmail->email} (ID: {$mailboxIdToDelete})");
                        $result = $mailinService->deleteMailbox($mailboxIdToDelete);
                        
                        // Successfully deleted from Mailin.ai - track for DB deletion
                        $deletedCount++;
                        $successfullyDeletedIds[] = $orderEmail->id;
                        $this->info("    ✓ Deleted from Mailin.ai");
                        
                        Log::channel('mailin-ai')->info('DeleteOrderMailboxesCommand: Deleted mailbox from Mailin.ai', [
                            'action' => 'delete_order_mailboxes_cmd',
                            'order_id' => $orderId,
                            'email' => $orderEmail->email,
                            'mailin_mailbox_id' => $mailboxIdToDelete,
                        ]);
                    } else {
                        // No mailbox ID found - safe to delete from DB (doesn't exist on Mailin.ai)
                        $successfullyDeletedIds[] = $orderEmail->id;
                        $this->warn("  ⚠ No mailbox ID for: {$orderEmail->email} (will remove from DB)");
                    }
                    
                    usleep(500000); // 0.5 sec delay to avoid rate limits
                    
                } catch (\Exception $deleteException) {
                    $failedCount++;
                    // Do NOT add to successfullyDeletedIds - keep in DB
                    $this->error("  ✗ Failed to delete {$orderEmail->email}: {$deleteException->getMessage()}");
                    $this->warn("    → Keeping in database (retry later)");
                    
                    Log::channel('mailin-ai')->error('DeleteOrderMailboxesCommand: Failed to delete mailbox from Mailin.ai', [
                        'action' => 'delete_order_mailboxes_cmd',
                        'order_id' => $orderId,
                        'email' => $orderEmail->email,
                        'error' => $deleteException->getMessage(),
                    ]);
                }
            }
        }

        // Delete ONLY successfully deleted OrderEmail records from database
        $deletedFromDb = 0;
        if (!empty($successfullyDeletedIds)) {
            $deletedFromDb = OrderEmail::whereIn('id', $successfullyDeletedIds)->delete();
        }

        $this->newLine();
        $this->info("=== Deletion Summary ===");
        $this->info("Deleted from Mailin.ai: {$deletedCount}");
        $this->info("Failed deletions: {$failedCount}");
        $this->info("Deleted from database: {$deletedFromDb}");
        
        if ($failedCount > 0) {
            $this->warn("Note: {$failedCount} mailbox(es) kept in database. Run command again to retry.");
        }

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
