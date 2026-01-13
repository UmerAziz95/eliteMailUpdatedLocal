<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderEmail;
use App\Models\SmtpProviderSplit;
use App\Services\MailinAiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BackfillMailinMailboxIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailin:backfill-mailbox-ids 
                            {--order-id= : Process specific order ID only}
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill missing mailin_mailbox_id and mailin_domain_id for completed Private SMTP orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Mailin.ai mailbox ID backfill...');

        try {
            $orderId = $this->option('order-id');
            $dryRun = $this->option('dry-run');

            if ($dryRun) {
                $this->warn('DRY RUN MODE - No changes will be made');
            }

            // Build query for orders with missing Mailin.ai IDs
            // First, get order IDs that have order_emails with missing IDs
            $orderIdsWithMissingIds = OrderEmail::where('provider_slug', 'mailin')
                ->where(function ($q) {
                    $q->whereNull('mailin_mailbox_id')
                      ->orWhereNull('mailin_domain_id');
                })
                ->distinct()
                ->pluck('order_id')
                ->toArray();

            if (empty($orderIdsWithMissingIds)) {
                $this->info('No orders found with missing Mailin.ai mailbox IDs.');
                return 0;
            }

            // Build query for orders
            $ordersQuery = Order::where('status_manage_by_admin', 'completed')
                ->whereIn('id', $orderIdsWithMissingIds)
                ->where(function ($query) {
                    $query->where('provider_type', 'Private SMTP')
                        ->orWhereHas('plan', function ($planQuery) {
                            $planQuery->where('provider_type', 'Private SMTP');
                        });
                });

            if ($orderId) {
                $ordersQuery->where('id', $orderId);
            }

            $orders = $ordersQuery->get();

            if ($orders->isEmpty()) {
                $this->info('No orders found with missing Mailin.ai mailbox IDs.');
                return 0;
            }

            $this->info("Found {$orders->count()} order(s) with missing Mailin.ai mailbox IDs.");

            // Get active provider credentials
            $activeProvider = SmtpProviderSplit::getActiveProvider();
            $credentials = $activeProvider ? $activeProvider->getCredentials() : null;
            $mailinService = new MailinAiService($credentials);

            // Authenticate
            $token = $mailinService->authenticate();
            if (!$token) {
                $this->error('Failed to authenticate with Mailin.ai API');
                return 1;
            }

            $totalUpdated = 0;
            $totalSkipped = 0;
            $totalErrors = 0;

            foreach ($orders as $order) {
                $this->info("\nProcessing Order #{$order->id}...");

                // Get order emails that need IDs
                $orderEmails = OrderEmail::where('order_id', $order->id)
                    ->where('provider_slug', 'mailin')
                    ->where(function ($q) {
                        $q->whereNull('mailin_mailbox_id')
                          ->orWhereNull('mailin_domain_id');
                    })
                    ->get();

                if ($orderEmails->isEmpty()) {
                    $this->line("  No order emails found with missing IDs for this order.");
                    continue;
                }

                $this->line("  Found {$orderEmails->count()} order email(s) with missing IDs.");

                // Process each email individually using the name parameter API endpoint
                foreach ($orderEmails as $orderEmail) {
                    try {
                        $email = $orderEmail->email;
                        $this->line("  Fetching mailbox for: {$email}");

                        // Fetch mailbox from Mailin.ai using email/name parameter
                        $mailboxesResult = $mailinService->getMailboxesByName($email);

                        if (!$mailboxesResult['success'] || empty($mailboxesResult['mailboxes'])) {
                            $this->warn("    No mailbox found on Mailin.ai: {$email}");
                            Log::channel('mailin-ai')->warning('Mailbox not found on Mailin.ai during backfill', [
                                'action' => 'backfill_mailbox_ids',
                                'order_id' => $order->id,
                                'order_email_id' => $orderEmail->id,
                                'email' => $email,
                                'message' => $mailboxesResult['message'] ?? 'Unknown error',
                            ]);
                            $totalSkipped++;
                            
                            // Add delay to avoid rate limiting
                            usleep(500000); // 0.5 seconds
                            continue;
                        }

                        // Find matching mailbox by username/email (case-insensitive)
                        $emailLower = strtolower($email);
                        $matchedMailbox = null;
                        
                        foreach ($mailboxesResult['mailboxes'] as $apiMailbox) {
                            $username = $apiMailbox['username'] ?? $apiMailbox['email'] ?? null;
                            if ($username && strtolower($username) === $emailLower) {
                                $matchedMailbox = $apiMailbox;
                                break;
                            }
                        }

                        if (!$matchedMailbox) {
                            $this->warn("    Mailbox not found on Mailin.ai (no exact match): {$email}");
                            Log::channel('mailin-ai')->warning('Mailbox not found on Mailin.ai during backfill (no exact match)', [
                                'action' => 'backfill_mailbox_ids',
                                'order_id' => $order->id,
                                'order_email_id' => $orderEmail->id,
                                'email' => $email,
                            ]);
                            $totalSkipped++;
                            
                            // Add delay to avoid rate limiting
                            usleep(500000); // 0.5 seconds
                            continue;
                        }

                        $mailinMailboxId = $matchedMailbox['id'] ?? null;
                        $mailinDomainId = $matchedMailbox['domain_id'] ?? null;

                        if (!$mailinMailboxId) {
                            $this->warn("    Mailbox ID is null for: {$email}");
                            $totalSkipped++;
                            
                            // Add delay to avoid rate limiting
                            usleep(500000); // 0.5 seconds
                            continue;
                        }

                        // Check if update is needed
                        $needsUpdate = false;
                        $updateData = [];

                        if (!$orderEmail->mailin_mailbox_id && $mailinMailboxId) {
                            $needsUpdate = true;
                            $updateData['mailin_mailbox_id'] = $mailinMailboxId;
                        }

                        if (!$orderEmail->mailin_domain_id && $mailinDomainId) {
                            $needsUpdate = true;
                            $updateData['mailin_domain_id'] = $mailinDomainId;
                        }

                        if (!$needsUpdate) {
                            $this->line("    Already has IDs: {$email}");
                            
                            // Add delay to avoid rate limiting
                            usleep(500000); // 0.5 seconds
                            continue;
                        }

                        if ($dryRun) {
                            $this->info("    [DRY RUN] Would update: {$email}");
                            $this->line("      mailin_mailbox_id: " . ($orderEmail->mailin_mailbox_id ?? 'null') . " -> {$mailinMailboxId}");
                            if ($mailinDomainId) {
                                $this->line("      mailin_domain_id: " . ($orderEmail->mailin_domain_id ?? 'null') . " -> {$mailinDomainId}");
                            }
                            $totalUpdated++;
                        } else {
                            try {
                                $orderEmail->update($updateData);

                                $this->info("    ✓ Updated: {$email}");
                                $this->line("      mailin_mailbox_id: {$mailinMailboxId}");
                                if ($mailinDomainId) {
                                    $this->line("      mailin_domain_id: {$mailinDomainId}");
                                }

                                Log::channel('mailin-ai')->info('Backfilled Mailin.ai mailbox IDs', [
                                    'action' => 'backfill_mailbox_ids',
                                    'order_id' => $order->id,
                                    'order_email_id' => $orderEmail->id,
                                    'email' => $email,
                                    'mailin_mailbox_id' => $mailinMailboxId,
                                    'mailin_domain_id' => $mailinDomainId,
                                ]);

                                $totalUpdated++;
                            } catch (\Exception $e) {
                                $this->error("    ✗ Failed to update: {$email} - {$e->getMessage()}");
                                Log::channel('mailin-ai')->error('Failed to backfill mailbox IDs', [
                                    'action' => 'backfill_mailbox_ids',
                                    'order_id' => $order->id,
                                    'order_email_id' => $orderEmail->id,
                                    'email' => $email,
                                    'error' => $e->getMessage(),
                                ]);
                                $totalErrors++;
                            }
                        }

                        // Add delay to avoid rate limiting
                        usleep(500000); // 0.5 seconds

                    } catch (\Exception $e) {
                        $this->error("  Error processing email {$orderEmail->email}: {$e->getMessage()}");
                        Log::channel('mailin-ai')->error('Error processing email during backfill', [
                            'action' => 'backfill_mailbox_ids',
                            'order_id' => $order->id,
                            'order_email_id' => $orderEmail->id,
                            'email' => $orderEmail->email,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                        $totalErrors++;
                        
                        // Add delay to avoid rate limiting
                        usleep(500000); // 0.5 seconds
                    }
                }
            }

            $this->info("\n" . str_repeat('=', 50));
            $this->info('Backfill Summary:');
            $this->info("  Orders processed: {$orders->count()}");
            $this->info("  Mailboxes updated: {$totalUpdated}");
            $this->info("  Mailboxes skipped: {$totalSkipped}");
            $this->info("  Errors: {$totalErrors}");

            if ($dryRun) {
                $this->warn("\nDRY RUN MODE - No changes were made. Run without --dry-run to apply changes.");
            }

            Log::channel('mailin-ai')->info('Mailin.ai mailbox ID backfill completed', [
                'action' => 'backfill_mailbox_ids',
                'orders_processed' => $orders->count(),
                'mailboxes_updated' => $totalUpdated,
                'mailboxes_skipped' => $totalSkipped,
                'errors' => $totalErrors,
                'dry_run' => $dryRun,
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('Error in mailbox ID backfill: ' . $e->getMessage());
            Log::channel('mailin-ai')->error('Mailbox ID backfill command failed', [
                'action' => 'backfill_mailbox_ids',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
