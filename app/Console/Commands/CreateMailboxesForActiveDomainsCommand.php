<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderEmail;
use App\Models\SmtpProviderSplit;
use App\Services\MailinAiService;
use App\Services\SlackNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CreateMailboxesForActiveDomainsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailin:create-mailboxes-for-active-domains 
                            {order_id : The order ID to process}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create mailboxes on Mailin.ai for orders where domains are already active (manually added)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderId = $this->argument('order_id');
        $isDryRun = $this->option('dry-run');

        $this->info("Processing Order #{$orderId}" . ($isDryRun ? ' (DRY RUN)' : ''));

        // Load order with relationships
        $order = Order::with(['reorderInfo', 'plan', 'user'])->find($orderId);

        if (!$order) {
            $this->error("Order #{$orderId} not found");
            return 1;
        }

        $this->info("Order Status: {$order->status_manage_by_admin}");

        // Get reorder info
        $reorderInfo = $order->reorderInfo->first();
        if (!$reorderInfo) {
            $this->error("No reorder info found for this order");
            return 1;
        }

        // Extract domains
        $domains = array_map('trim', array_filter(preg_split('/[\r\n,]+/', $reorderInfo->domains)));
        $this->info("Domains in order: " . count($domains));

        // Extract prefix variants - use prefix_variants field for email prefix
        $prefixVariants = $reorderInfo->prefix_variants ?? [];
        $prefixVariantsDetails = $reorderInfo->prefix_variants_details ?? [];
        $mailboxPrefixVariants = [];
        $inboxesPerDomain = (int) $reorderInfo->inboxes_per_domain;

        for ($i = 1; $i <= $inboxesPerDomain; $i++) {
            $prefixKey = 'prefix_variant_' . $i;
            // Use prefix_variants for email prefix (not prefix_variants_details)
            if (isset($prefixVariants[$prefixKey])) {
                $prefix = trim($prefixVariants[$prefixKey]);
                if (!empty($prefix)) {
                    $mailboxPrefixVariants[$prefixKey] = $prefix;
                }
            }
        }

        $this->info("Prefix variants: " . implode(', ', $mailboxPrefixVariants));
        $this->info("Expected mailboxes per domain: {$inboxesPerDomain}");

        // Check existing mailboxes in database
        $existingEmails = OrderEmail::where('order_id', $orderId)->pluck('email')->toArray();
        $this->info("Existing OrderEmails in DB: " . count($existingEmails));

        // Initialize Mailin service
        $provider = SmtpProviderSplit::getActiveProvider();
        if (!$provider) {
            $this->error("No active SMTP provider found");
            return 1;
        }

        $credentials = $provider->getCredentials();
        $mailinService = new MailinAiService($credentials);

        if (!$mailinService->authenticate()) {
            $this->error("Failed to authenticate with Mailin.ai");
            return 1;
        }

        $this->info("Authenticated with Mailin.ai");
        $this->newLine();

        // Check each domain's status on Mailin.ai
        $activeDomains = [];
        $inactiveDomains = [];
        $domainIds = [];

        $this->info("Checking domain status on Mailin.ai...");
        foreach ($domains as $domain) {
            $maxRetries = 3;
            $retryCount = 0;
            $success = false;

            while (!$success && $retryCount < $maxRetries) {
                try {
                    $status = $mailinService->checkDomainStatus($domain);
                    if ($status['success'] && $status['status'] === 'active') {
                        $activeDomains[] = $domain;
                        $domainIds[$domain] = $status['data']['id'] ?? null;
                        $this->line("  ✓ {$domain} - ACTIVE" . ($domainIds[$domain] ? " (ID: {$domainIds[$domain]})" : ""));
                    } else {
                        $inactiveDomains[] = $domain;
                        $this->line("  ✗ {$domain} - NOT ACTIVE");
                    }
                    $success = true;
                } catch (\Exception $e) {
                    $errorMsg = $e->getMessage();
                    // Check if it's a rate limit error
                    if (stripos($errorMsg, 'rate limit') !== false || stripos($errorMsg, 'Too Many Attempts') !== false) {
                        $retryCount++;
                        if ($retryCount < $maxRetries) {
                            $waitTime = $retryCount * 2; // 2s, 4s, 6s
                            $this->warn("  ⏳ {$domain} - Rate limited, waiting {$waitTime}s (retry {$retryCount}/{$maxRetries})...");
                            sleep($waitTime);
                        } else {
                            $inactiveDomains[] = $domain;
                            $this->line("  ✗ {$domain} - ERROR after {$maxRetries} retries: {$errorMsg}");
                        }
                    } else {
                        $inactiveDomains[] = $domain;
                        $this->line("  ✗ {$domain} - ERROR: {$errorMsg}");
                        $success = true; // Don't retry non-rate-limit errors
                    }
                }
            }

            usleep(200000); // 0.2 sec delay between domains to prevent rate limiting
        }

        $this->newLine();
        $this->info("Active domains: " . count($activeDomains));
        $this->info("Inactive domains: " . count($inactiveDomains));

        if (empty($activeDomains)) {
            $this->warn("No active domains found. Nothing to do.");

            // Send Slack notification about no active domains
            // Commented out to prevent notifications when running command
            // if (!$isDryRun) {
            //     $this->sendSlackNotification($order, 'no_active_domains', [
            //         'total_domains' => count($domains),
            //         'inactive_domains' => $inactiveDomains,
            //     ]);
            // }

            return 0;
        }

        // Backfill missing mailin_mailbox_id and mailin_domain_id for existing emails
        $this->backfillMissingMailinIds($orderId, $activeDomains, $domainIds, $mailinService, $isDryRun);

        // Build list of mailboxes to create (skip existing ones)
        $this->newLine();
        $this->info("Building mailbox list (skipping existing)...");

        $mailboxesToCreate = [];
        $mailboxData = [];
        $skippedCount = 0;
        $mailboxIndex = 0;

        foreach ($activeDomains as $domain) {
            $prefixIndex = 0;
            foreach ($mailboxPrefixVariants as $prefixKey => $prefix) {
                $prefixIndex++;
                $username = $prefix . '@' . $domain;

                // Check if already exists in database
                if (in_array($username, $existingEmails)) {
                    $this->line("  Skipping (exists in DB): {$username}");
                    $skippedCount++;
                    continue;
                }

                // Get proper name from prefix_variants_details
                // $prefixKey is now 'prefix_variant_X' format
                $variantDetails = $prefixVariantsDetails[$prefixKey] ?? null;

                if ($variantDetails && (isset($variantDetails['first_name']) || isset($variantDetails['last_name']))) {
                    $firstName = trim($variantDetails['first_name'] ?? '');
                    $lastName = trim($variantDetails['last_name'] ?? '');
                    $name = trim($firstName . ' ' . $lastName);
                    if (empty($name)) {
                        $name = $prefix;
                    }
                } else {
                    $name = $prefix;
                }

                $password = $this->generatePassword($orderId);

                $mailboxesToCreate[] = [
                    'username' => $username,
                    'name' => $name,
                    'password' => $password,
                ];

                $mailboxData[] = [
                    'order_id' => $orderId,
                    'username' => $username,
                    'name' => $name,
                    'password' => $password,
                    'domain' => $domain,
                    'prefix' => $prefix,
                    'mailin_domain_id' => $domainIds[$domain] ?? null,
                    'first_name' => $variantDetails['first_name'] ?? $prefix,
                    'last_name' => $variantDetails['last_name'] ?? '',
                ];

                $mailboxIndex++;
            }
        }

        $this->newLine();
        $this->info("Mailboxes to create: " . count($mailboxesToCreate));
        $this->info("Mailboxes skipped (already exist): {$skippedCount}");

        if (empty($mailboxesToCreate)) {
            $this->info("All mailboxes already exist in database. Nothing to create.");

            // Check if order should be completed
            $this->checkOrderStatus($order, $domains, $inboxesPerDomain, $activeDomains, $inactiveDomains, $isDryRun);

            return 0;
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info("DRY RUN - Would create " . count($mailboxesToCreate) . " mailboxes:");
            foreach ($mailboxesToCreate as $mb) {
                $this->line("  Would create: {$mb['username']}");
            }
            return 0;
        }

        // Create mailboxes on Mailin.ai
        $this->newLine();
        $this->info("Creating mailboxes on Mailin.ai...");

        $errors = [];

        try {
            $result = $mailinService->createMailboxes($mailboxesToCreate);

            if ($result['success']) {
                $this->info("✓ Mailbox creation request sent to Mailin.ai");

                if (isset($result['uuid'])) {
                    $this->info("  Job UUID: {$result['uuid']}");
                }

                if (isset($result['already_exists']) && $result['already_exists']) {
                    $this->warn("  Note: Some mailboxes already exist on Mailin.ai");
                }

                // Save to database
                $this->info("Saving mailboxes to database...");
                $savedCount = 0;

                foreach ($mailboxData as $data) {
                    try {
                        // Double-check it doesn't exist
                        $existing = OrderEmail::where('order_id', $data['order_id'])
                            ->where('email', $data['username'])
                            ->first();

                        if (!$existing) {
                            $fullName = trim($data['first_name'] . ' ' . $data['last_name']);
                            if (empty($fullName)) {
                                $fullName = $data['prefix'];
                            }
                            OrderEmail::create([
                                'order_id' => $data['order_id'],
                                'user_id' => $order->user_id,
                                'email' => $data['username'],
                                'password' => $data['password'],
                                'name' => $fullName,
                                'first_name' => $data['first_name'],
                                'last_name' => $data['last_name'],
                                'provider_slug' => 'mailin',
                                'mailin_domain_id' => $data['mailin_domain_id'],
                            ]);
                            $savedCount++;
                            $this->line("  Created: {$data['username']}");
                        } else {
                            $this->line("  Skipped (exists): {$data['username']}");
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Failed to save {$data['username']}: {$e->getMessage()}";
                        $this->error("  Error saving {$data['username']}: {$e->getMessage()}");
                    }
                }

                $this->newLine();
                $this->info("✓ Saved {$savedCount} new mailboxes to database");

                // Fetch mailin_mailbox_id from Mailin.ai and update records
                if ($savedCount > 0) {
                    $this->newLine();
                    $this->info("Fetching mailbox IDs from Mailin.ai...");
                    sleep(3); // Wait for Mailin.ai to process

                    $updatedIds = 0;
                    foreach ($activeDomains as $domain) {
                        try {
                            $fetchResult = $mailinService->getMailboxesByDomain($domain);
                            if ($fetchResult['success'] && !empty($fetchResult['mailboxes'])) {
                                // Build lookup map
                                $mailinMailboxes = [];
                                foreach ($fetchResult['mailboxes'] as $mb) {
                                    $username = strtolower($mb['username'] ?? $mb['email'] ?? '');
                                    if ($username && isset($mb['id'])) {
                                        $mailinMailboxes[$username] = $mb['id'];
                                    }
                                }

                                // Update emails for this domain
                                $domainEmails = OrderEmail::where('order_id', $orderId)
                                    ->where('email', 'like', '%@' . $domain)
                                    ->whereNull('mailin_mailbox_id')
                                    ->get();

                                foreach ($domainEmails as $email) {
                                    $emailLower = strtolower($email->email);
                                    if (isset($mailinMailboxes[$emailLower])) {
                                        $email->update(['mailin_mailbox_id' => $mailinMailboxes[$emailLower]]);
                                        $updatedIds++;
                                    }
                                }
                            }
                            usleep(300000); // 0.3 sec delay between domains
                        } catch (\Exception $e) {
                            $this->warn("  Could not fetch IDs for {$domain}: {$e->getMessage()}");
                        }
                    }

                    $this->info("  Updated {$updatedIds} mailboxes with Mailin IDs");
                }

                Log::channel('mailin-ai')->info('Manually created mailboxes for active domains', [
                    'action' => 'create_mailboxes_for_active_domains',
                    'order_id' => $orderId,
                    'active_domains' => count($activeDomains),
                    'mailboxes_created' => $savedCount,
                ]);

            } else {
                $errorMsg = $result['message'] ?? 'Unknown error';
                $this->error("Failed to create mailboxes: {$errorMsg}");
                $errors[] = "Mailin.ai API error: {$errorMsg}";
            }

        } catch (\Exception $e) {
            $this->error("Error creating mailboxes: " . $e->getMessage());
            $errors[] = "Exception: {$e->getMessage()}";

            Log::channel('mailin-ai')->error('Failed to create mailboxes for active domains', [
                'action' => 'create_mailboxes_for_active_domains',
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }

        // Check and complete order if all mailboxes are created
        $this->checkOrderStatus($order, $domains, $inboxesPerDomain, $activeDomains, $inactiveDomains, $isDryRun);

        $this->newLine();
        $this->info("Done!");
        return empty($errors) ? 0 : 1;
    }

    /**
     * Check order status and send Slack notification (does NOT change order status)
     */
    private function checkOrderStatus($order, $domains, $inboxesPerDomain, $activeDomains, $inactiveDomains, $isDryRun)
    {
        $this->newLine();
        $this->info("Checking order status...");

        $totalEmails = OrderEmail::where('order_id', $order->id)->count();
        $expectedTotal = count($domains) * $inboxesPerDomain;

        $this->info("  Mailboxes in DB: {$totalEmails}");
        $this->info("  Expected total: {$expectedTotal}");
        $this->info("  Active domains: " . count($activeDomains) . "/" . count($domains));

        // Build summary data for Slack notification
        $missingDomains = [];
        foreach ($domains as $domain) {
            $domainEmails = OrderEmail::where('order_id', $order->id)
                ->where('email', 'like', '%@' . $domain)
                ->count();
            if ($domainEmails < $inboxesPerDomain) {
                $missingDomains[] = $domain . " ({$domainEmails}/{$inboxesPerDomain})";
            }
        }

        // Determine status - PRIMARY check is mailbox count, not domain status
        // If all mailboxes are created, order is complete even if domain check had rate limit errors
        $allMailboxesCreated = $totalEmails >= $expectedTotal;
        $allDomainsHaveMailboxes = empty($missingDomains);

        if ($allMailboxesCreated && $allDomainsHaveMailboxes) {
            $this->info("✓ All mailboxes created ({$totalEmails}/{$expectedTotal})");
            $statusType = 'all_complete';
        } elseif (!$allMailboxesCreated) {
            $missing = $expectedTotal - $totalEmails;
            $this->warn("  {$missing} mailboxes missing");
            if (!empty($missingDomains)) {
                $this->warn("  Missing domains: " . implode(', ', array_slice($missingDomains, 0, 5)));
            }
            $statusType = 'incomplete_mailboxes';
        } else {
            $statusType = 'all_complete';
        }

        // If all mailboxes created, mark order as completed
        if ($allMailboxesCreated && $allDomainsHaveMailboxes) {
            if (!$isDryRun && $order->status_manage_by_admin !== 'completed') {
                $order->update([
                    'status_manage_by_admin' => 'completed',
                    'completed_at' => now(),
                ]);
                $this->info("✓ Order marked as COMPLETED");

                Log::channel('mailin-ai')->info('Order completed after manual mailbox creation', [
                    'action' => 'create_mailboxes_for_active_domains',
                    'order_id' => $order->id,
                    'mailbox_count' => $totalEmails,
                ]);
            } elseif ($order->status_manage_by_admin === 'completed') {
                $this->info("  Order already completed");
            } else {
                $this->info("  DRY RUN: Would mark order as completed");
            }
        } else {
            $this->info("  Order status NOT changed (not all mailboxes created)");
        }

        // Only send Slack notification if there are missing mailboxes
        // Commented out to prevent notifications when running command
        // if (!$isDryRun && !$allDomainsHaveMailboxes) {
        //     $this->sendSlackNotification($order, 'command_summary', [
        //         'total_emails' => $totalEmails,
        //         'expected_total' => $expectedTotal,
        //         'missing_count' => max(0, $expectedTotal - $totalEmails),
        //         'missing_domains' => $missingDomains,
        //         'active_domains_count' => count($activeDomains),
        //         'inactive_domains_count' => count($inactiveDomains),
        //         'inactive_domains' => $inactiveDomains,
        //         'all_complete' => $allMailboxesCreated && $allDomainsHaveMailboxes,
        //         'status_type' => $statusType,
        //     ]);
        // } elseif ($allDomainsHaveMailboxes) {
        //     $this->info("  All mailboxes exist - Slack notification not sent");
        // }
    }

    /**
     * Generate password using the same logic as CreateMailboxesOnOrderJob::customEncrypt()
     * Uses orderId + index as seed for consistent password generation
     * 
     * @param int $orderId Order ID for seeding
     * @param int $index Index for uniqueness (default: 0)
     * @return string Generated 8-character password
     */
    private function generatePassword($orderId, $index = 0)
    {
        $upperCase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowerCase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*';

        // Use order ID + index as seed for consistent password generation
        mt_srand($orderId + $index);

        // Generate password with requirements
        $password = '';
        $password .= $upperCase[mt_rand(0, strlen($upperCase) - 1)]; // 1 uppercase
        $password .= $lowerCase[mt_rand(0, strlen($lowerCase) - 1)]; // 1 lowercase
        $password .= $numbers[mt_rand(0, strlen($numbers) - 1)];     // 1 number
        $password .= $specialChars[mt_rand(0, strlen($specialChars) - 1)]; // 1 special char

        // Fill remaining 4 characters with mix of all character types
        $allChars = $upperCase . $lowerCase . $numbers . $specialChars;
        for ($i = 4; $i < 8; $i++) {
            $password .= $allChars[mt_rand(0, strlen($allChars) - 1)];
        }

        // Shuffle using seeded random generator
        $passwordArray = str_split($password);
        for ($i = count($passwordArray) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            // Swap characters
            $temp = $passwordArray[$i];
            $passwordArray[$i] = $passwordArray[$j];
            $passwordArray[$j] = $temp;
        }

        return implode('', $passwordArray);
    }

    /**
     * Backfill missing mailin_mailbox_id and mailin_domain_id for existing emails
     */
    private function backfillMissingMailinIds($orderId, $activeDomains, $domainIds, $mailinService, $isDryRun)
    {
        $this->newLine();
        $this->info("Checking for missing Mailin IDs...");

        // Find emails with missing mailin_mailbox_id or mailin_domain_id
        $emailsToBackfill = OrderEmail::where('order_id', $orderId)
            ->where(function ($query) {
                $query->whereNull('mailin_mailbox_id')
                    ->orWhereNull('mailin_domain_id');
            })
            ->get();

        if ($emailsToBackfill->isEmpty()) {
            $this->info("  No emails need backfilling");
            return;
        }

        $this->info("  Found {$emailsToBackfill->count()} emails with missing Mailin IDs");

        // Group emails by domain for efficient API calls
        $emailsByDomain = [];
        foreach ($emailsToBackfill as $email) {
            $domain = substr($email->email, strpos($email->email, '@') + 1);
            if (!isset($emailsByDomain[$domain])) {
                $emailsByDomain[$domain] = [];
            }
            $emailsByDomain[$domain][] = $email;
        }

        $updatedCount = 0;
        $failedCount = 0;
        $createdCount = 0;
        $mailboxesToCreate = []; // Track mailboxes that need to be created on Mailin.ai

        foreach ($emailsByDomain as $domain => $emails) {
            // Skip if domain is not active
            if (!in_array($domain, $activeDomains)) {
                $this->line("  Skipping {$domain} - not active on Mailin.ai");
                continue;
            }

            // Get domain ID
            $domainId = $domainIds[$domain] ?? null;

            // Fetch mailboxes from Mailin.ai for this domain
            try {
                $result = $mailinService->getMailboxesByDomain($domain);

                // Build lookup map by email
                $mailinMailboxes = [];
                if ($result['success'] && !empty($result['mailboxes'])) {
                    foreach ($result['mailboxes'] as $mb) {
                        $username = strtolower($mb['username'] ?? $mb['email'] ?? '');
                        if ($username) {
                            $mailinMailboxes[$username] = [
                                'id' => $mb['id'] ?? null,
                                'domain_id' => $mb['domain_id'] ?? $domainId,
                            ];
                        }
                    }
                }

                // Update each email
                foreach ($emails as $email) {
                    $emailLower = strtolower($email->email);

                    if (isset($mailinMailboxes[$emailLower])) {
                        // Mailbox exists on Mailin.ai - update IDs
                        $mailinData = $mailinMailboxes[$emailLower];
                        $updates = [];

                        if (empty($email->mailin_mailbox_id) && !empty($mailinData['id'])) {
                            $updates['mailin_mailbox_id'] = $mailinData['id'];
                        }
                        if (empty($email->mailin_domain_id) && !empty($mailinData['domain_id'])) {
                            $updates['mailin_domain_id'] = $mailinData['domain_id'];
                        }

                        if (!empty($updates)) {
                            if ($isDryRun) {
                                $this->line("  Would update {$email->email}: " . json_encode($updates));
                            } else {
                                $email->update($updates);
                                $mailboxId = $updates['mailin_mailbox_id'] ?? 'N/A';
                                $domainIdVal = $updates['mailin_domain_id'] ?? 'N/A';
                                $this->line("  Updated {$email->email}: mailbox_id={$mailboxId}, domain_id={$domainIdVal}");
                            }
                            $updatedCount++;
                        }
                    } else {
                        // Mailbox NOT found on Mailin.ai - need to create it
                        $this->warn("  Mailbox not found on Mailin.ai, will create: {$email->email}");
                        $mailboxesToCreate[] = [
                            'email' => $email,
                            'domain' => $domain,
                            'domain_id' => $domainId,
                        ];
                    }
                }

                // Small delay between domains to avoid rate limits
                usleep(500000); // 0.5 seconds

            } catch (\Exception $e) {
                $this->error("  Error fetching mailboxes for {$domain}: {$e->getMessage()}");
                $failedCount += count($emails);
            }
        }

        // Create missing mailboxes on Mailin.ai
        if (!empty($mailboxesToCreate)) {
            $this->newLine();
            $this->info("Creating " . count($mailboxesToCreate) . " missing mailboxes on Mailin.ai...");

            // Prepare mailbox data for API
            $mailboxApiData = [];
            foreach ($mailboxesToCreate as $index => $mbData) {
                $email = $mbData['email'];
                $password = $email->password ?: $this->generatePassword($email->order_id);
                $name = trim(($email->first_name ?? '') . ' ' . ($email->last_name ?? ''));
                if (empty($name)) {
                    $name = explode('@', $email->email)[0];
                }

                $mailboxApiData[] = [
                    'username' => $email->email,
                    'name' => $name,
                    'password' => $password,
                ];
            }

            if ($isDryRun) {
                foreach ($mailboxApiData as $mb) {
                    $this->line("  Would create on Mailin.ai: {$mb['username']}");
                }
                $this->info("  DRY RUN: Would create " . count($mailboxApiData) . " mailboxes");
            } else {
                try {
                    $result = $mailinService->createMailboxes($mailboxApiData);

                    if ($result['success']) {
                        $this->info("  ✓ Mailboxes created on Mailin.ai");
                        if (isset($result['uuid'])) {
                            $this->info("    Job UUID: {$result['uuid']}");
                        }
                        $createdCount = count($mailboxApiData);

                        // Wait a bit for Mailin.ai to process, then try to get IDs
                        $this->info("  Waiting for Mailin.ai to process...");
                        sleep(5);

                        // Try to fetch and update the IDs
                        foreach ($mailboxesToCreate as $mbData) {
                            $email = $mbData['email'];
                            $domain = $mbData['domain'];
                            $domainId = $mbData['domain_id'];

                            try {
                                $fetchResult = $mailinService->getMailboxesByDomain($domain);
                                if ($fetchResult['success'] && !empty($fetchResult['mailboxes'])) {
                                    foreach ($fetchResult['mailboxes'] as $mb) {
                                        $username = strtolower($mb['username'] ?? $mb['email'] ?? '');
                                        if ($username === strtolower($email->email)) {
                                            $updates = [];
                                            if (!empty($mb['id'])) {
                                                $updates['mailin_mailbox_id'] = $mb['id'];
                                            }
                                            if (!empty($mb['domain_id'])) {
                                                $updates['mailin_domain_id'] = $mb['domain_id'];
                                            } elseif ($domainId) {
                                                $updates['mailin_domain_id'] = $domainId;
                                            }

                                            if (!empty($updates)) {
                                                $email->update($updates);
                                                $this->line("  Updated IDs for {$email->email}");
                                                $updatedCount++;
                                            }
                                            break;
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                $this->warn("  Could not fetch ID for {$email->email}: {$e->getMessage()}");
                            }

                            usleep(300000); // 0.3 seconds between lookups
                        }
                    } else {
                        $this->error("  Failed to create mailboxes: " . ($result['message'] ?? 'Unknown error'));
                        $failedCount += count($mailboxApiData);
                    }
                } catch (\Exception $e) {
                    $this->error("  Error creating mailboxes: {$e->getMessage()}");
                    $failedCount += count($mailboxApiData);
                }
            }
        }

        $this->newLine();
        if ($isDryRun) {
            $this->info("  DRY RUN: Would update {$updatedCount} emails, create {$createdCount} mailboxes");
        } else {
            $this->info("  Updated {$updatedCount} emails with Mailin IDs");
            if ($createdCount > 0) {
                $this->info("  Created {$createdCount} mailboxes on Mailin.ai");
            }
        }

        if ($failedCount > 0) {
            $this->warn("  Failed to update {$failedCount} emails");
        }

        Log::channel('mailin-ai')->info('Backfilled missing Mailin IDs', [
            'action' => 'backfill_mailin_ids',
            'order_id' => $orderId,
            'updated_count' => $updatedCount,
            'created_count' => $createdCount,
            'failed_count' => $failedCount,
        ]);
    }

    /**
     * Send Slack notification for issues
     */
    private function sendSlackNotification($order, $type, $data)
    {
        try {
            $customerName = $order->user ? $order->user->name : 'Unknown';
            $customerEmail = $order->user ? $order->user->email : 'Unknown';

            switch ($type) {
                case 'no_active_domains':
                    $message = [
                        'text' => "⚠️ *Order #{$order->id} - No Active Domains*",
                        'blocks' => [
                            [
                                'type' => 'header',
                                'text' => [
                                    'type' => 'plain_text',
                                    'text' => "⚠️ Order #{$order->id} - No Active Domains",
                                    'emoji' => true,
                                ],
                            ],
                            [
                                'type' => 'section',
                                'fields' => [
                                    ['type' => 'mrkdwn', 'text' => "*Customer:*\n{$customerName}"],
                                    ['type' => 'mrkdwn', 'text' => "*Email:*\n{$customerEmail}"],
                                    ['type' => 'mrkdwn', 'text' => "*Total Domains:*\n{$data['total_domains']}"],
                                    ['type' => 'mrkdwn', 'text' => "*Active:*\n0"],
                                ],
                            ],
                            [
                                'type' => 'section',
                                'text' => [
                                    'type' => 'mrkdwn',
                                    'text' => "*Inactive Domains:*\n" . implode(', ', array_slice($data['inactive_domains'], 0, 10)),
                                ],
                            ],
                        ],
                    ];
                    break;

                case 'mailbox_creation_failed':
                    $errorText = implode("\n", array_slice($data['errors'], 0, 5));
                    $message = [
                        'text' => "❌ *Order #{$order->id} - Mailbox Creation Failed*",
                        'blocks' => [
                            [
                                'type' => 'header',
                                'text' => [
                                    'type' => 'plain_text',
                                    'text' => "❌ Order #{$order->id} - Mailbox Creation Failed",
                                    'emoji' => true,
                                ],
                            ],
                            [
                                'type' => 'section',
                                'fields' => [
                                    ['type' => 'mrkdwn', 'text' => "*Customer:*\n{$customerName}"],
                                    ['type' => 'mrkdwn', 'text' => "*Email:*\n{$customerEmail}"],
                                ],
                            ],
                            [
                                'type' => 'section',
                                'text' => [
                                    'type' => 'mrkdwn',
                                    'text' => "*Errors:*\n```{$errorText}```",
                                ],
                            ],
                        ],
                    ];
                    break;

                case 'incomplete_mailboxes':
                    $missingDomainsText = implode("\n", array_slice($data['missing_domains'], 0, 10));
                    $message = [
                        'text' => "⚠️ *Order #{$order->id} - Incomplete Mailboxes*",
                        'blocks' => [
                            [
                                'type' => 'header',
                                'text' => [
                                    'type' => 'plain_text',
                                    'text' => "⚠️ Order #{$order->id} - Incomplete Mailboxes",
                                    'emoji' => true,
                                ],
                            ],
                            [
                                'type' => 'section',
                                'fields' => [
                                    ['type' => 'mrkdwn', 'text' => "*Customer:*\n{$customerName}"],
                                    ['type' => 'mrkdwn', 'text' => "*Email:*\n{$customerEmail}"],
                                    ['type' => 'mrkdwn', 'text' => "*Created:*\n{$data['total_emails']}"],
                                    ['type' => 'mrkdwn', 'text' => "*Expected:*\n{$data['expected_total']}"],
                                ],
                            ],
                            [
                                'type' => 'section',
                                'text' => [
                                    'type' => 'mrkdwn',
                                    'text' => "*Missing Domains:*\n{$missingDomainsText}",
                                ],
                            ],
                        ],
                    ];
                    break;

                case 'command_summary':
                    // Determine emoji and title based on status
                    $allComplete = $data['all_complete'] ?? false;
                    $statusType = $data['status_type'] ?? 'unknown';

                    if ($allComplete) {
                        $emoji = '✅';
                        $title = "Order #{$order->id} - Manual Mailbox Command Complete";
                    } elseif ($statusType === 'domains_not_active') {
                        $emoji = '⚠️';
                        $title = "Order #{$order->id} - Some Domains Not Active";
                    } else {
                        $emoji = '⚠️';
                        $title = "Order #{$order->id} - Mailboxes Incomplete";
                    }

                    $blocks = [
                        [
                            'type' => 'header',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => "{$emoji} {$title}",
                                'emoji' => true,
                            ],
                        ],
                        [
                            'type' => 'section',
                            'fields' => [
                                ['type' => 'mrkdwn', 'text' => "*Customer:*\n{$customerName}"],
                                ['type' => 'mrkdwn', 'text' => "*Email:*\n{$customerEmail}"],
                                ['type' => 'mrkdwn', 'text' => "*Mailboxes:*\n{$data['total_emails']}/{$data['expected_total']}"],
                                ['type' => 'mrkdwn', 'text' => "*Domains:*\n{$data['active_domains_count']} active"],
                            ],
                        ],
                    ];

                    // Add inactive domains section if any
                    if (!empty($data['inactive_domains'])) {
                        $inactiveText = implode(', ', array_slice($data['inactive_domains'], 0, 10));
                        $blocks[] = [
                            'type' => 'section',
                            'text' => [
                                'type' => 'mrkdwn',
                                'text' => "*Inactive Domains ({$data['inactive_domains_count']}):*\n{$inactiveText}",
                            ],
                        ];
                    }

                    // Add missing domains section if any
                    if (!empty($data['missing_domains'])) {
                        $missingText = implode("\n", array_slice($data['missing_domains'], 0, 10));
                        $blocks[] = [
                            'type' => 'section',
                            'text' => [
                                'type' => 'mrkdwn',
                                'text' => "*Missing Mailboxes:*\n{$missingText}",
                            ],
                        ];
                    }

                    // Add note about manual intervention
                    $blocks[] = [
                        'type' => 'context',
                        'elements' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => "📋 _Order status not changed - manual intervention required_",
                            ],
                        ],
                    ];

                    $message = [
                        'text' => "{$emoji} *{$title}*",
                        'blocks' => $blocks,
                    ];
                    break;

                default:
                    return;
            }

            SlackNotificationService::send('inbox-setup', $message);
            $this->info("Slack notification sent to inbox-setup channel");

        } catch (\Exception $e) {
            $this->warn("Failed to send Slack notification: " . $e->getMessage());
            Log::channel('slack_notifications')->error('Failed to send mailbox creation notification', [
                'order_id' => $order->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
