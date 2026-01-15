<?php

namespace App\Jobs\MailinAi;

use App\Models\Order;
use App\Models\OrderAutomation;
use App\Models\OrderEmail;
use App\Models\OrderProviderSplit;
use App\Models\Notification;
use App\Models\DomainTransfer;
use App\Models\SmtpProviderSplit;
use App\Services\MailinAiService;
use App\Services\SpaceshipService;
use App\Services\NamecheapService;
use App\Services\ActivityLogService;
use App\Services\DomainSplitService;
use App\Mail\OrderStatusChangeMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CreateMailboxesOnOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;
    protected $domains;
    protected $prefixVariants;
    protected $userId;
    protected $providerType;

    /**
     * Create a new job instance.
     * 
     * @param int $orderId Order ID
     * @param array $domains Array of domain names
     * @param array $prefixVariants Array of prefix variants
     * @param int $userId User ID for password generation
     * @param string $providerType Provider type (should be 'Private SMTP')
     */
    public function __construct($orderId, $domains, $prefixVariants, $userId, $providerType)
    {
        $this->orderId = $orderId;
        $this->domains = $domains;
        $this->prefixVariants = $prefixVariants;
        $this->userId = $userId;
        $this->providerType = $providerType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::channel('mailin-ai')->info('Starting mailbox creation job for order', [
                'action' => 'create_mailboxes_on_order',
                'order_id' => $this->orderId,
            ]);

            // Load order with required relationships
            $order = Order::with(['plan', 'reorderInfo', 'platformCredentials'])->find($this->orderId);

            if (!$order) {
                Log::channel('mailin-ai')->error('Order not found for mailbox creation', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                ]);
                return;
            }

            // Validate domains
            if (empty($this->domains)) {
                Log::channel('mailin-ai')->warning('No domains provided for mailbox creation', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                ]);
                return;
            }

            // Validate prefix variants
            if (empty($this->prefixVariants)) {
                Log::channel('mailin-ai')->warning('No prefix variants provided for mailbox creation', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                ]);
                return;
            }

            Log::channel('mailin-ai')->info('Extracted data for mailbox creation', [
                'action' => 'create_mailboxes_on_order',
                'order_id' => $this->orderId,
                'domains' => $this->domains,
                'domain_count' => count($this->domains),
                'prefix_variants' => $this->prefixVariants,
                'prefix_count' => count($this->prefixVariants),
            ]);

            // Split domains across active providers
            $domainSplitService = new DomainSplitService();
            $domainSplit = $domainSplitService->splitDomains($this->domains);

            if (empty($domainSplit)) {
                Log::channel('mailin-ai')->error('Failed to split domains across providers', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                    'domains' => $this->domains,
                ]);
                throw new \Exception('Failed to split domains across providers. Please check provider configuration.');
            }

            Log::channel('mailin-ai')->info('Domain split completed', [
                'action' => 'create_mailboxes_on_order',
                'order_id' => $this->orderId,
                'domain_split' => array_map(function ($domains) {
                    return count($domains);
                }, $domainSplit),
            ]);

            // Save order provider splits to track which providers are used
            $this->saveOrderProviderSplits($order, $domainSplit);

            // Process domains for each provider
            $allUnregisteredDomains = [];
            $allRegisteredDomains = [];
            $hasUnregisteredDomains = false;

            foreach ($domainSplit as $providerSlug => $providerDomains) {
                if (empty($providerDomains)) {
                    continue; // Skip providers with no domains
                }

                Log::channel('mailin-ai')->info('Processing domains for provider', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                    'provider' => $providerSlug,
                    'domain_count' => count($providerDomains),
                    'domains' => $providerDomains,
                ]);

                // Get provider configuration
                $provider = SmtpProviderSplit::getBySlug($providerSlug);
                if (!$provider) {
                    Log::channel('mailin-ai')->error('Provider not found in split table', [
                        'action' => 'create_mailboxes_on_order',
                        'order_id' => $this->orderId,
                        'provider_slug' => $providerSlug,
                    ]);
                    continue;
                }

                // Get provider credentials
                $credentials = $provider->getCredentials();
                if (!$credentials) {
                    Log::channel('mailin-ai')->error('Provider credentials not configured', [
                        'action' => 'create_mailboxes_on_order',
                        'order_id' => $this->orderId,
                        'provider_slug' => $providerSlug,
                    ]);
                    continue;
                }

                // Initialize provider service (for now, only Mailin is supported)
                if ($providerSlug !== 'mailin') {
                    Log::channel('mailin-ai')->warning('Provider not yet implemented, skipping', [
                        'action' => 'create_mailboxes_on_order',
                        'order_id' => $this->orderId,
                        'provider_slug' => $providerSlug,
                    ]);
                    continue;
                }

                $mailinService = new MailinAiService($credentials);

                // Authenticate
                $token = $mailinService->authenticate();
                if (!$token) {
                    Log::channel('mailin-ai')->error('Failed to authenticate with provider', [
                        'action' => 'create_mailboxes_on_order',
                        'order_id' => $this->orderId,
                        'provider' => $providerSlug,
                    ]);
                    continue;
                }

                // Check which domains already have mailboxes for this provider
                $domainsWithMailboxes = OrderEmail::where('order_id', $this->orderId)
                    ->where('provider_slug', $providerSlug)
                    ->whereNotNull('mailin_mailbox_id')
                    ->get()
                    ->map(function ($email) {
                        $parts = explode('@', $email->email);
                        return count($parts) === 2 ? $parts[1] : null;
                    })
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                // Filter out domains that already have mailboxes
                $domainsToProcess = array_filter($providerDomains, function ($domain) use ($domainsWithMailboxes) {
                    return !in_array($domain, $domainsWithMailboxes);
                });

                if (empty($domainsToProcess)) {
                    Log::channel('mailin-ai')->info('All domains already have mailboxes for provider', [
                        'action' => 'create_mailboxes_on_order',
                        'order_id' => $this->orderId,
                        'provider' => $providerSlug,
                    ]);
                    continue;
                }

                // Check domain registration status
                $registeredDomains = [];
                $unregisteredDomains = [];

                foreach ($domainsToProcess as $domain) {
                    try {
                        $statusResult = $mailinService->checkDomainStatus($domain);
                        if ($statusResult['success'] && isset($statusResult['status']) && $statusResult['status'] === 'active') {
                            $registeredDomains[] = $domain;
                        } else {
                            $unregisteredDomains[] = $domain;
                        }
                    } catch (\Exception $e) {
                        $unregisteredDomains[] = $domain;
                    }
                }

                $allRegisteredDomains = array_merge($allRegisteredDomains, $registeredDomains);
                $allUnregisteredDomains = array_merge($allUnregisteredDomains, $unregisteredDomains);

                if (!empty($unregisteredDomains)) {
                    $hasUnregisteredDomains = true;
                    Log::channel('mailin-ai')->info('Unregistered domains found for provider', [
                        'action' => 'create_mailboxes_on_order',
                        'order_id' => $this->orderId,
                        'provider' => $providerSlug,
                        'unregistered_domains' => $unregisteredDomains,
                    ]);
                }

                // Handle domain transfer if needed
                if (!empty($unregisteredDomains)) {
                    // Ensure order status is in-progress
                    $oldStatus = $order->status_manage_by_admin;
                    if ($oldStatus !== 'in-progress') {
                        $order->update(['status_manage_by_admin' => 'in-progress']);
                    }

                    // Transfer unregistered domains with provider_slug
                    $this->handleDomainTransfer($order, $mailinService, $unregisteredDomains, $providerSlug);
                    
                    // Check if order was rejected during domain transfer - stop processing
                    $order->refresh();
                    if ($order->status_manage_by_admin === 'reject') {
                        Log::channel('mailin-ai')->info('Order was rejected during domain transfer, stopping mailbox creation', [
                            'action' => 'create_mailboxes_on_order',
                            'order_id' => $this->orderId,
                        ]);
                        return;
                    }
                }

                // Create mailboxes for registered domains (already active - skip status checking)
                // DO NOT complete order here - wait until ALL providers are processed
                if (!empty($registeredDomains)) {
                    // Double-check order is not rejected before creating mailboxes
                    $order->refresh();
                    if ($order->status_manage_by_admin === 'reject') {
                        Log::channel('mailin-ai')->info('Order is rejected, skipping mailbox creation for registered domains', [
                            'action' => 'create_mailboxes_on_order',
                            'order_id' => $this->orderId,
                            'registered_domains' => $registeredDomains,
                        ]);
                        return;
                    }
                    $this->createMailboxesForProvider($order, $mailinService, $providerSlug, $registeredDomains, false, true);
                }
            }

            // If we have unregistered domains, return early (transfer in progress)
            if ($hasUnregisteredDomains) {
                Log::channel('mailin-ai')->info('Domain transfer initiated, waiting for domains to become active', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                    'unregistered_domains' => $allUnregisteredDomains,
                ]);
                return;
            }

            // After processing ALL providers, check if ALL domains have mailboxes before completing
            // Use $this->domains as the source of truth for all domains in the order
            $allDomainsInOrder = $this->domains;

            // Check if all domains have mailboxes created
            // A domain is considered to have mailboxes if at least one mailbox exists for that domain
            $domainsWithMailboxes = OrderEmail::where('order_id', $this->orderId)
                ->get()
                ->map(function ($email) {
                    $parts = explode('@', $email->email);
                    return count($parts) === 2 ? $parts[1] : null;
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            // Check if all domains have at least one mailbox
            $missingDomains = array_diff($allDomainsInOrder, $domainsWithMailboxes);
            $allDomainsHaveMailboxes = count($allDomainsInOrder) > 0 && 
                                       count($missingDomains) === 0;

            Log::channel('mailin-ai')->info('Checking if all domains have mailboxes before completing order', [
                'action' => 'create_mailboxes_on_order',
                'order_id' => $this->orderId,
                'total_domains' => count($allDomainsInOrder),
                'domains_with_mailboxes' => count($domainsWithMailboxes),
                'all_domains_have_mailboxes' => $allDomainsHaveMailboxes,
                'missing_domains' => $missingDomains,
                'missing_domains_count' => count($missingDomains),
            ]);

            // Only complete order if ALL domains have mailboxes
            if ($allDomainsHaveMailboxes) {
                $this->completeOrderAfterAllMailboxesCreated($order);
            } else {
                Log::channel('mailin-ai')->info('Not all domains have mailboxes yet, keeping order in-progress', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                    'missing_domains_count' => count($missingDomains),
                    'missing_domains' => $missingDomains,
                ]);
            }

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mailbox creation job failed', [
                'action' => 'create_mailboxes_on_order',
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update or create order_automation record with failed status
            try {
                OrderAutomation::updateOrCreate(
                    [
                        'order_id' => $this->orderId,
                        'action_type' => 'mailbox',
                    ],
                    [
                        'provider_type' => $this->providerType,
                        'job_uuid' => null, // No UUID for failed jobs
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]
                );
            } catch (\Exception $saveException) {
                Log::channel('mailin-ai')->error('Failed to save mailbox job error status', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                    'error' => $saveException->getMessage(),
                ]);
            }

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle domain transfer when domain is not registered
     * 
     * @param Order $order
     * @param MailinAiService $mailinService
     * @param array $domainsToTransfer Array of domain names to transfer
     * @param string|null $providerSlug Provider slug for tracking
     * @return void
     */
    private function handleDomainTransfer($order, $mailinService, $domainsToTransfer = null, $providerSlug = null)
    {
        try {
            // Use provided domains or fallback to all domains
            $domains = $domainsToTransfer ?? $this->domains;

            // Clean up old domain transfers that are no longer in the current order
            // This handles cases where an order was rejected and resubmitted with different domains
            $staleDomainTransfers = DomainTransfer::where('order_id', $this->orderId)
                ->whereNotIn('domain_name', $domains)
                ->where('status', 'pending') // Only clean up pending, not already failed
                ->get();

            if ($staleDomainTransfers->count() > 0) {
                $staleDomainNames = $staleDomainTransfers->pluck('domain_name')->toArray();
                
                DomainTransfer::where('order_id', $this->orderId)
                    ->whereNotIn('domain_name', $domains)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'failed',
                        'error_message' => 'Domain removed from order - superseded by new submission',
                    ]);

                Log::channel('mailin-ai')->info('Marked stale domain transfers as failed from previous submission', [
                    'action' => 'handle_domain_transfer',
                    'order_id' => $this->orderId,
                    'superseded_domains' => $staleDomainNames,
                    'current_domains' => $domains,
                ]);
            }

            // Check for existing failed nameserver updates and retry them first
            $failedTransfers = DomainTransfer::where('order_id', $this->orderId)
                ->whereIn('domain_name', $domains)
                ->where('status', 'pending')
                ->where('name_server_status', 'failed')
                ->whereNotNull('name_servers')
                ->get();

            if ($failedTransfers->count() > 0) {
                Log::channel('mailin-ai')->info('Found failed nameserver updates to retry', [
                    'action' => 'handle_domain_transfer',
                    'order_id' => $this->orderId,
                    'failed_transfers_count' => $failedTransfers->count(),
                    'domains' => $failedTransfers->pluck('domain_name')->toArray(),
                ]);

                // Retry failed nameserver updates and capture any errors
                $retryNameserverErrors = $this->retryFailedNameserverUpdates($order, $failedTransfers);
            } else {
                $retryNameserverErrors = [];
            }

            // Get hosting platform from order
            $hostingPlatform = null;
            $spaceshipCredential = null;
            $namecheapCredential = null;
            if ($order->reorderInfo && $order->reorderInfo->count() > 0) {
                $hostingPlatform = $order->reorderInfo->first()->hosting_platform;
                // Get Spaceship credentials if platform is Spaceship
                if ($hostingPlatform === 'spaceship') {
                    $spaceshipCredential = $order->getPlatformCredential('spaceship');
                    Log::channel('mailin-ai')->info('Spaceship platform detected, checking credentials', [
                        'action' => 'handle_domain_transfer',
                        'order_id' => $this->orderId,
                        'hosting_platform' => $hostingPlatform,
                        'has_credentials' => $spaceshipCredential ? true : false,
                    ]);
                }
                // Get Namecheap credentials if platform is Namecheap
                if ($hostingPlatform === 'namecheap') {
                    $namecheapCredential = $order->getPlatformCredential('namecheap');
                    Log::channel('mailin-ai')->info('Namecheap platform detected, checking credentials', [
                        'action' => 'handle_domain_transfer',
                        'order_id' => $this->orderId,
                        'hosting_platform' => $hostingPlatform,
                        'has_credentials' => $namecheapCredential ? true : false,
                    ]);
                }
            }

            // Log total domains to transfer
            Log::channel('mailin-ai')->info('Starting domain transfer process', [
                'action' => 'handle_domain_transfer',
                'order_id' => $this->orderId,
                'total_domains' => count($domains),
                'domains' => $domains,
                'hosting_platform' => $hostingPlatform,
                'has_reorder_info' => $order->reorderInfo && $order->reorderInfo->count() > 0,
            ]);

            // Initialize Spaceship service if needed
            $spaceshipService = null;
            if ($hostingPlatform === 'spaceship' && $spaceshipCredential) {
                $spaceshipService = new SpaceshipService();
                Log::channel('mailin-ai')->info('SpaceshipService initialized for nameserver updates', [
                    'action' => 'handle_domain_transfer',
                    'order_id' => $this->orderId,
                ]);
            } else {
                $reason = ($hostingPlatform !== 'spaceship') ? 'Platform is not Spaceship' : 'Credentials not found';
                Log::channel('mailin-ai')->info('SpaceshipService not initialized', [
                    'action' => 'handle_domain_transfer',
                    'order_id' => $this->orderId,
                    'hosting_platform' => $hostingPlatform,
                    'has_credential' => $spaceshipCredential ? true : false,
                    'reason' => $reason,
                ]);
            }

            // Initialize Namecheap service if needed
            $namecheapService = null;
            if ($hostingPlatform === 'namecheap' && $namecheapCredential) {
                $namecheapService = new NamecheapService();
                Log::channel('mailin-ai')->info('NamecheapService initialized for nameserver updates', [
                    'action' => 'handle_domain_transfer',
                    'order_id' => $this->orderId,
                ]);
            } else {
                $reason = ($hostingPlatform !== 'namecheap') ? 'Platform is not Namecheap' : 'Credentials not found';
                Log::channel('mailin-ai')->info('NamecheapService not initialized', [
                    'action' => 'handle_domain_transfer',
                    'order_id' => $this->orderId,
                    'hosting_platform' => $hostingPlatform,
                    'has_credential' => $namecheapCredential ? true : false,
                    'reason' => $reason,
                ]);
            }

            // Track if any nameserver updates fail (to set order to draft)
            $hasNameserverUpdateFailures = false;
            $nameserverUpdateErrors = [];

            // Merge retry errors with nameserver update errors
            if (!empty($retryNameserverErrors)) {
                $nameserverUpdateErrors = array_merge($nameserverUpdateErrors, $retryNameserverErrors);
                $hasNameserverUpdateFailures = true;
                
                Log::channel('mailin-ai')->info('Retry errors added to nameserver update errors', [
                    'action' => 'handle_domain_transfer',
                    'order_id' => $this->orderId,
                    'retry_errors_count' => count($retryNameserverErrors),
                ]);
            }

            // Configuration for rate limit handling
            $delayBetweenTransfers = config('mailin_ai.domain_transfer_delay', 2); // seconds between transfers
            $batchSize = config('mailin_ai.domain_transfer_batch_size', 10); // domains per batch
            $batchDelay = config('mailin_ai.domain_transfer_batch_delay', 10); // seconds between batches

            // Track domains that hit rate limits for retry
            $rateLimitedDomains = [];
            
            // Track domains with permanent errors (invalid format, etc.) that should trigger rejection
            $invalidFormatDomains = [];

            // Transfer each domain with delays and rate limit handling
            foreach ($domains as $index => $domain) {
                try {
                    // Add delay between transfers (except for the first one)
                    if ($index > 0) {
                        // Longer delay between batches
                        if ($index % $batchSize === 0) {
                            Log::channel('mailin-ai')->info('Batch delay before processing next batch', [
                                'action' => 'handle_domain_transfer',
                                'order_id' => $this->orderId,
                                'batch_number' => ($index / $batchSize) + 1,
                                'delay_seconds' => $batchDelay,
                            ]);
                            sleep($batchDelay);
                        } else {
                            // Regular delay between individual transfers
                            sleep($delayBetweenTransfers);
                        }
                    }

                    Log::channel('mailin-ai')->info('Initiating domain transfer', [
                        'action' => 'handle_domain_transfer',
                        'order_id' => $this->orderId,
                        'domain_index' => $index + 1,
                        'total_domains' => count($domains),
                        'domain' => $domain,
                        'batch_info' => [
                            'batch_number' => floor($index / $batchSize) + 1,
                            'position_in_batch' => ($index % $batchSize) + 1,
                        ],
                    ]);

                    $transferResult = $mailinService->transferDomain($domain);

                    if ($transferResult['success']) {
                        $nameServers = $transferResult['name_servers'] ?? [];

                        // Log the raw name_servers value to debug
                        Log::channel('mailin-ai')->info('Raw name_servers from transfer result', [
                            'action' => 'handle_domain_transfer',
                            'order_id' => $this->orderId,
                            'domain' => $domain,
                            'name_servers_raw' => $nameServers,
                            'name_servers_type' => gettype($nameServers),
                            'is_array' => is_array($nameServers),
                        ]);

                        // Ensure nameServers is an array for JSON storage
                        if (!is_array($nameServers)) {
                            // If it's a string, convert to array
                            if (is_string($nameServers)) {
                                $nameServers = array_filter(array_map('trim', explode(',', $nameServers)));
                                $nameServers = array_values($nameServers); // Re-index array
                            } else {
                                $nameServers = [];
                            }
                        }

                        // Ensure it's a proper array (not associative with numeric keys)
                        $nameServers = array_values($nameServers);

                        // Final validation: ensure name_servers is a proper array
                        $nameServersForDb = is_array($nameServers) ? array_values($nameServers) : [];
                        // Filter out any empty values
                        $nameServersForDb = array_values(array_filter($nameServersForDb, function ($ns) {
                            return !empty($ns) && is_string($ns);
                        }));

                        Log::channel('mailin-ai')->info('Processed name_servers for database', [
                            'action' => 'handle_domain_transfer',
                            'order_id' => $this->orderId,
                            'domain' => $domain,
                            'name_servers' => $nameServersForDb,
                            'name_servers_type' => gettype($nameServersForDb),
                            'is_array' => is_array($nameServersForDb),
                            'json_preview' => json_encode($nameServersForDb),
                        ]);

                        // Save domain transfer record with domain name and nameservers
                        // Use updateOrCreate to prevent duplicate records for the same domain
                        try {
                            $domainTransfer = DomainTransfer::updateOrCreate(
                                [
                                    'order_id' => $this->orderId,
                                    'domain_name' => $domain,
                                ],
                                [
                                    'provider_slug' => $providerSlug,
                                    'name_servers' => $nameServersForDb, // Store as array (will be cast to JSON)
                                    'status' => 'pending',
                                    'response_data' => $transferResult['response'] ?? null,
                                    'error_message' => null, // Clear any previous error
                                ]
                            );

                            Log::channel('mailin-ai')->info('Domain transfer record created successfully', [
                                'action' => 'handle_domain_transfer',
                                'order_id' => $this->orderId,
                                'domain' => $domain,
                                'domain_transfer_id' => $domainTransfer->id,
                            ]);
                        } catch (\Exception $dbException) {
                            Log::channel('mailin-ai')->error('Failed to create domain transfer record', [
                                'action' => 'handle_domain_transfer',
                                'order_id' => $this->orderId,
                                'domain' => $domain,
                                'name_servers' => $nameServers,
                                'name_servers_type' => gettype($nameServers),
                                'error' => $dbException->getMessage(),
                                'sql' => $dbException->getTrace()[0]['args'][0] ?? 'N/A',
                            ]);
                            throw $dbException;
                        }

                        Log::channel('mailin-ai')->info('Domain transfer initiated successfully', [
                            'action' => 'handle_domain_transfer',
                            'order_id' => $this->orderId,
                            'domain' => $domain,
                            'name_servers' => $nameServers,
                        ]);

                        // Update nameservers in Spaceship if hosting platform is Spaceship
                        Log::channel('mailin-ai')->info('Checking if Spaceship nameserver update is needed', [
                            'action' => 'handle_domain_transfer',
                            'order_id' => $this->orderId,
                            'domain' => $domain,
                            'hosting_platform' => $hostingPlatform,
                            'has_spaceship_service' => $spaceshipService ? true : false,
                            'has_credential' => $spaceshipCredential ? true : false,
                            'has_nameservers' => !empty($nameServers),
                            'nameservers' => $nameServers,
                        ]);

                        if ($hostingPlatform === 'spaceship' && $spaceshipService && $spaceshipCredential && !empty($nameServers)) {
                            try {
                                $apiKey = $spaceshipCredential->getCredential('api_key');
                                $apiSecretKey = $spaceshipCredential->getCredential('api_secret_key');

                                Log::channel('mailin-ai')->info('Attempting to update Spaceship nameservers', [
                                    'action' => 'handle_domain_transfer',
                                    'order_id' => $this->orderId,
                                    'domain' => $domain,
                                    'has_api_key' => !empty($apiKey),
                                    'has_api_secret' => !empty($apiSecretKey),
                                ]);

                                if ($apiKey && $apiSecretKey) {
                                    // Ensure nameServers is an array
                                    $nameServersArray = is_array($nameServers) ? $nameServers : explode(',', $nameServers);
                                    $nameServersArray = array_map('trim', $nameServersArray);

                                    Log::channel('mailin-ai')->info('Calling SpaceshipService.updateNameservers', [
                                        'action' => 'handle_domain_transfer',
                                        'order_id' => $this->orderId,
                                        'domain' => $domain,
                                        'name_servers' => $nameServersArray,
                                    ]);

                                    $spaceshipResult = $spaceshipService->updateNameservers(
                                        $domain,
                                        $nameServersArray,
                                        $apiKey,
                                        $apiSecretKey
                                    );

                                    if ($spaceshipResult['success']) {
                                        Log::channel('mailin-ai')->info('Spaceship nameservers updated successfully', [
                                            'action' => 'handle_domain_transfer',
                                            'order_id' => $this->orderId,
                                            'domain' => $domain,
                                            'name_servers' => $nameServersArray,
                                        ]);

                                        // Update domain transfer status to indicate nameserver update completed
                                        // Note: Domain transfer status will be set to 'completed' when domain becomes active
                                        // This is just to track that nameserver update was successful
                                        try {
                                            $domainTransfer->update([
                                                'name_server_status' => 'updated',
                                            ]);
                                        } catch (\Exception $updateException) {
                                            Log::channel('mailin-ai')->warning('Failed to update domain transfer name_server_status', [
                                                'action' => 'handle_domain_transfer',
                                                'order_id' => $this->orderId,
                                                'domain' => $domain,
                                                'error' => $updateException->getMessage(),
                                            ]);
                                        }
                                    }
                                } else {
                                    Log::channel('mailin-ai')->warning('Spaceship API credentials missing, skipping nameserver update', [
                                        'action' => 'handle_domain_transfer',
                                        'order_id' => $this->orderId,
                                        'domain' => $domain,
                                        'has_api_key' => !empty($apiKey),
                                        'has_api_secret' => !empty($apiSecretKey),
                                    ]);
                                }
                            } catch (\Exception $spaceshipException) {
                                // Check if error is due to invalid credentials or domain not found
                                $errorMsg = $spaceshipException->getMessage();
                                $errorMsgLower = strtolower($errorMsg);

                                // Detect invalid credentials - check for various credential-related error patterns
                                $isInvalidCredentials =
                                    str_contains($errorMsgLower, 'invalid api credentials') ||
                                    str_contains($errorMsgLower, 'please verify your api credentials') ||
                                    str_contains($errorMsgLower, 'verify your api credentials') ||
                                    (str_contains($errorMsgLower, 'invalid') && (str_contains($errorMsgLower, 'api') || str_contains($errorMsgLower, 'credentials'))) ||
                                    str_contains($errorMsgLower, 'unauthorized') ||
                                    str_contains($errorMsgLower, 'forbidden') ||
                                    str_contains($errorMsgLower, 'authentication') ||
                                    str_contains($errorMsgLower, 'api key') && (str_contains($errorMsgLower, 'invalid') || str_contains($errorMsgLower, 'incorrect') || str_contains($errorMsgLower, 'wrong'));

                                // Detect domain not found errors - check exception code and message patterns
                                $isDomainNotFound =
                                    $spaceshipException->getCode() === 404 || // 404 status code indicates domain not found
                                    str_contains($errorMsgLower, "hasn't been found") ||
                                    str_contains($errorMsgLower, 'has not been found') ||
                                    str_contains($errorMsgLower, 'zone file') && str_contains($errorMsgLower, 'not found') ||
                                    str_contains($errorMsgLower, 'domain name not found') ||
                                    str_contains($errorMsgLower, 'domain not found') ||
                                    str_contains($errorMsgLower, 'domain not exist') ||
                                    str_contains($errorMsgLower, 'domain does not exist') ||
                                    str_contains($errorMsgLower, 'domain is not registered') ||
                                    (str_contains($errorMsgLower, 'domain') && str_contains($errorMsgLower, 'not exist'));

                                // Mark that we have a nameserver update failure
                                $hasNameserverUpdateFailures = true;

                                // Extract the actual error message (remove "Failed to update nameservers via Spaceship API: " prefix if present)
                                $actualErrorMsg = $errorMsg;
                                $apiPrefix = 'Failed to update nameservers via Spaceship API: ';
                                if (strpos($actualErrorMsg, $apiPrefix) === 0) {
                                    $actualErrorMsg = substr($actualErrorMsg, strlen($apiPrefix));
                                }

                                $errorMessage = 'Spaceship nameserver update failed: ' . $actualErrorMsg;
                                $nameserverUpdateErrors[] = [
                                    'domain' => $domain,
                                    'platform' => 'Spaceship',
                                    'error' => $actualErrorMsg, // Store the actual error without the prefix
                                    'is_invalid_credentials' => $isInvalidCredentials,
                                    'is_domain_not_found' => $isDomainNotFound,
                                ];

                                // Log error
                                Log::channel('mailin-ai')->error('Failed to update Spaceship nameservers', [
                                    'action' => 'handle_domain_transfer',
                                    'order_id' => $this->orderId,
                                    'domain' => $domain,
                                    'error' => $errorMsg,
                                    'is_invalid_credentials' => $isInvalidCredentials,
                                    'is_domain_not_found' => $isDomainNotFound,
                                    'trace' => $spaceshipException->getTraceAsString(),
                                ]);

                                // Store detailed error message in domain transfer
                                try {
                                    $domainTransfer->update([
                                        'error_message' => $errorMessage,
                                        'name_server_status' => 'failed',
                                        'status' => 'pending', // Keep as pending for retry
                                    ]);
                                } catch (\Exception $updateException) {
                                    Log::channel('mailin-ai')->warning('Failed to update domain transfer error_message', [
                                        'action' => 'handle_domain_transfer',
                                        'order_id' => $this->orderId,
                                        'domain' => $domain,
                                        'error' => $updateException->getMessage(),
                                    ]);
                                }
                            }
                        } else {
                            // Determine reason for skipping
                            $skipReason = 'Unknown';
                            if ($hostingPlatform !== 'spaceship') {
                                $skipReason = 'Platform is not Spaceship';
                            } elseif (!$spaceshipService) {
                                $skipReason = 'SpaceshipService not initialized';
                            } elseif (!$spaceshipCredential) {
                                $skipReason = 'Credentials not found';
                            } elseif (empty($nameServers)) {
                                $skipReason = 'Nameservers are empty';
                            }

                            Log::channel('mailin-ai')->info('Skipping Spaceship nameserver update', [
                                'action' => 'handle_domain_transfer',
                                'order_id' => $this->orderId,
                                'domain' => $domain,
                                'reason' => $skipReason,
                            ]);
                        }

                        // Update nameservers in Namecheap if hosting platform is Namecheap
                        Log::channel('mailin-ai')->info('Checking if Namecheap nameserver update is needed', [
                            'action' => 'handle_domain_transfer',
                            'order_id' => $this->orderId,
                            'domain' => $domain,
                            'hosting_platform' => $hostingPlatform,
                            'has_namecheap_service' => $namecheapService ? true : false,
                            'has_credential' => $namecheapCredential ? true : false,
                            'has_nameservers' => !empty($nameServers),
                            'nameservers' => $nameServers,
                        ]);

                        if ($hostingPlatform === 'namecheap' && $namecheapService && $namecheapCredential && !empty($nameServers)) {
                            try {
                                // api_user is the username (platform_login) used for both ApiUser and UserName
                                $apiUser = $namecheapCredential->getCredential('api_user');
                                $apiKey = $namecheapCredential->getCredential('api_key');

                                Log::channel('mailin-ai')->info('Attempting to update Namecheap nameservers', [
                                    'action' => 'handle_domain_transfer',
                                    'order_id' => $this->orderId,
                                    'domain' => $domain,
                                    'has_api_user' => !empty($apiUser),
                                    'has_api_key' => !empty($apiKey),
                                ]);

                                if ($apiUser && $apiKey) {
                                    // Ensure nameServers is an array
                                    $nameServersArray = is_array($nameServers) ? $nameServers : explode(',', $nameServers);
                                    $nameServersArray = array_map('trim', $nameServersArray);

                                    Log::channel('mailin-ai')->info('Calling NamecheapService.updateNameservers', [
                                        'action' => 'handle_domain_transfer',
                                        'order_id' => $this->orderId,
                                        'domain' => $domain,
                                        'name_servers' => $nameServersArray,
                                    ]);

                                    $namecheapResult = $namecheapService->updateNameservers(
                                        $domain,
                                        $nameServersArray,
                                        $apiUser,
                                        $apiKey
                                    );

                                    if ($namecheapResult['success']) {
                                        Log::channel('mailin-ai')->info('Namecheap nameservers updated successfully', [
                                            'action' => 'handle_domain_transfer',
                                            'order_id' => $this->orderId,
                                            'domain' => $domain,
                                            'name_servers' => $nameServersArray,
                                        ]);

                                        // Update domain transfer status to indicate nameserver update completed
                                        // Note: Domain transfer status will be set to 'completed' when domain becomes active
                                        // This is just to track that nameserver update was successful
                                        try {
                                            $domainTransfer->update([
                                                'name_server_status' => 'updated',
                                            ]);
                                        } catch (\Exception $updateException) {
                                            Log::channel('mailin-ai')->warning('Failed to update domain transfer name_server_status', [
                                                'action' => 'handle_domain_transfer',
                                                'order_id' => $this->orderId,
                                                'domain' => $domain,
                                                'error' => $updateException->getMessage(),
                                            ]);
                                        }
                                    }
                                } else {
                                    Log::channel('mailin-ai')->warning('Namecheap API credentials missing, skipping nameserver update', [
                                        'action' => 'handle_domain_transfer',
                                        'order_id' => $this->orderId,
                                        'domain' => $domain,
                                        'has_api_user' => !empty($apiUser),
                                        'has_api_key' => !empty($apiKey),
                                    ]);
                                }
                            } catch (\Exception $namecheapException) {
                                // Check if error is due to invalid credentials or domain not found
                                $errorMsg = $namecheapException->getMessage();
                                $errorMsgLower = strtolower($errorMsg);

                                // Detect invalid credentials - check for various credential-related error patterns
                                $isInvalidCredentials =
                                    str_contains($errorMsgLower, 'api key is invalid') ||
                                    str_contains($errorMsgLower, 'api access has not been enabled') ||
                                    str_contains($errorMsgLower, 'invalid request ip') ||
                                    str_contains($errorMsgLower, 'api key') && (str_contains($errorMsgLower, 'invalid') || str_contains($errorMsgLower, 'incorrect')) ||
                                    str_contains($errorMsgLower, 'unauthorized') ||
                                    str_contains($errorMsgLower, 'forbidden') ||
                                    str_contains($errorMsgLower, 'authentication') ||
                                    (str_contains($errorMsgLower, 'invalid') && (str_contains($errorMsgLower, 'api') || str_contains($errorMsgLower, 'credentials'))) ||
                                    str_contains($errorMsgLower, 'whitelist') && str_contains($errorMsgLower, 'invalid');

                                // Detect domain not found errors
                                $isDomainNotFound =
                                    str_contains($errorMsgLower, 'domain name not found') ||
                                    str_contains($errorMsgLower, 'domain not found') ||
                                    str_contains($errorMsgLower, 'domain not exist') ||
                                    str_contains($errorMsgLower, 'domain does not exist') ||
                                    str_contains($errorMsgLower, 'domain is not registered');

                                // Mark that we have a nameserver update failure
                                $hasNameserverUpdateFailures = true;

                                // Extract the actual error message (remove "Failed to update nameservers via Namecheap API: " prefix if present)
                                $actualErrorMsg = $errorMsg;
                                $apiPrefix = 'Failed to update nameservers via Namecheap API: ';
                                if (strpos($actualErrorMsg, $apiPrefix) === 0) {
                                    $actualErrorMsg = substr($actualErrorMsg, strlen($apiPrefix));
                                }

                                $errorMessage = 'Namecheap nameserver update failed: ' . $actualErrorMsg;
                                $nameserverUpdateErrors[] = [
                                    'domain' => $domain,
                                    'platform' => 'Namecheap',
                                    'error' => $actualErrorMsg, // Store the actual error without the prefix
                                    'is_invalid_credentials' => $isInvalidCredentials,
                                    'is_domain_not_found' => $isDomainNotFound,
                                ];

                                // Log error
                                Log::channel('mailin-ai')->error('Failed to update Namecheap nameservers', [
                                    'action' => 'handle_domain_transfer',
                                    'order_id' => $this->orderId,
                                    'domain' => $domain,
                                    'error' => $errorMsg,
                                    'is_invalid_credentials' => $isInvalidCredentials,
                                    'is_domain_not_found' => $isDomainNotFound,
                                    'trace' => $namecheapException->getTraceAsString(),
                                ]);

                                // Store detailed error message in domain transfer
                                try {
                                    $domainTransfer->update([
                                        'error_message' => $errorMessage,
                                        'name_server_status' => 'failed',
                                        'status' => 'pending', // Keep as pending for retry
                                    ]);
                                } catch (\Exception $updateException) {
                                    Log::channel('mailin-ai')->warning('Failed to update domain transfer error_message', [
                                        'action' => 'handle_domain_transfer',
                                        'order_id' => $this->orderId,
                                        'domain' => $domain,
                                        'error' => $updateException->getMessage(),
                                    ]);
                                }
                            }
                        } else {
                            // Determine reason for skipping
                            $skipReason = 'Unknown';
                            if ($hostingPlatform !== 'namecheap') {
                                $skipReason = 'Platform is not Namecheap';
                            } elseif (!$namecheapService) {
                                $skipReason = 'NamecheapService not initialized';
                            } elseif (!$namecheapCredential) {
                                $skipReason = 'Credentials not found';
                            } elseif (empty($nameServers)) {
                                $skipReason = 'Nameservers are empty';
                            }

                            Log::channel('mailin-ai')->info('Skipping Namecheap nameserver update', [
                                'action' => 'handle_domain_transfer',
                                'order_id' => $this->orderId,
                                'domain' => $domain,
                                'reason' => $skipReason,
                            ]);
                        }
                    }
                } catch (\Exception $transferException) {
                    $errorMessage = $transferException->getMessage();
                    $isRateLimitError = $transferException->getCode() === 429
                        || str_contains($errorMessage, 'rate limit')
                        || str_contains($errorMessage, 'Too Many Attempts')
                        || str_contains($errorMessage, '429');

                    if ($isRateLimitError) {
                        // Rate limit error - mark for retry instead of failing
                        $rateLimitedDomains[] = $domain;

                        Log::channel('mailin-ai')->warning('Domain transfer hit rate limit, will retry later', [
                            'action' => 'handle_domain_transfer',
                            'order_id' => $this->orderId,
                            'domain' => $domain,
                            'error' => $errorMessage,
                            'retry_strategy' => 'scheduled_retry',
                        ]);

                        // Save as pending (not failed) so it can be retried
                        // Use updateOrCreate to prevent duplicate records
                        try {
                            DomainTransfer::updateOrCreate(
                                [
                                    'order_id' => $this->orderId,
                                    'domain_name' => $domain,
                                ],
                                [
                                    'status' => 'pending',
                                    'error_message' => 'Rate limit exceeded. Will retry automatically: ' . $errorMessage,
                                ]
                            );
                        } catch (\Exception $e) {
                            Log::channel('mailin-ai')->error('Failed to save rate-limited domain transfer record', [
                                'action' => 'handle_domain_transfer',
                                'order_id' => $this->orderId,
                                'domain' => $domain,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        // If we hit rate limit, add extra delay before continuing
                        // This helps prevent hitting rate limits on subsequent requests
                        $rateLimitDelay = config('mailin_ai.rate_limit_delay', 30); // seconds
                        Log::channel('mailin-ai')->info('Adding delay after rate limit to prevent further rate limiting', [
                            'action' => 'handle_domain_transfer',
                            'order_id' => $this->orderId,
                            'delay_seconds' => $rateLimitDelay,
                        ]);
                        sleep($rateLimitDelay);

                    } else {
                        // Check if this is a permanent error (invalid domain format)
                        $isInvalidFormatError = str_contains(strtolower($errorMessage), 'domain name format is invalid')
                            || str_contains(strtolower($errorMessage), 'format is invalid')
                            || str_contains(strtolower($errorMessage), 'invalid domain');
                        
                        if ($isInvalidFormatError) {
                            $invalidFormatDomains[] = $domain;
                        }
                        
                        // Other errors - log and save as failed
                        Log::channel('mailin-ai')->error('Failed to transfer domain', [
                            'action' => 'handle_domain_transfer',
                            'order_id' => $this->orderId,
                            'domain' => $domain,
                            'error' => $errorMessage,
                            'is_rate_limit' => false,
                            'is_invalid_format' => $isInvalidFormatError,
                        ]);

                        // Save failed transfer record
                        // Use updateOrCreate to prevent duplicate records
                        try {
                            DomainTransfer::updateOrCreate(
                                [
                                    'order_id' => $this->orderId,
                                    'domain_name' => $domain,
                                ],
                                [
                                    'status' => 'failed',
                                    'error_message' => $errorMessage,
                                ]
                            );
                        } catch (\Exception $e) {
                            Log::channel('mailin-ai')->error('Failed to save failed domain transfer record', [
                                'action' => 'handle_domain_transfer',
                                'order_id' => $this->orderId,
                                'domain' => $domain,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }

            // Log transfer summary
            $successCount = DomainTransfer::where('order_id', $this->orderId)
                ->where('status', 'pending')
                ->whereIn('domain_name', $domains)
                ->where(function ($query) {
                    $query->whereNull('error_message')
                        ->orWhere('error_message', 'not like', '%Rate limit%');
                })
                ->count();
            $failedCount = DomainTransfer::where('order_id', $this->orderId)
                ->where('status', 'failed')
                ->whereIn('domain_name', $domains)
                ->count();
            $rateLimitedCount = count($rateLimitedDomains);

            // Log summary including rate-limited domains
            Log::channel('mailin-ai')->info('Domain transfer batch completed', [
                'action' => 'handle_domain_transfer',
                'order_id' => $this->orderId,
                'total_domains' => count($domains),
                'successful_transfers' => $successCount,
                'failed_transfers' => $failedCount,
                'rate_limited_domains' => $rateLimitedCount,
                'rate_limited_domain_list' => $rateLimitedDomains,
            ]);

            // If we have rate-limited domains, log a warning
            if ($rateLimitedCount > 0) {
                Log::channel('mailin-ai')->warning('Some domains hit rate limits and will be retried automatically', [
                    'action' => 'handle_domain_transfer',
                    'order_id' => $this->orderId,
                    'rate_limited_count' => $rateLimitedCount,
                    'rate_limited_domains' => $rateLimitedDomains,
                    'retry_info' => 'These domains will be retried by the scheduled domain transfer status check command',
                ]);
            }

            // Check for nameserver update failures (status pending but name_server_status failed)
            $nameserverFailureCount = DomainTransfer::where('order_id', $this->orderId)
                ->whereIn('domain_name', $domains)
                ->where('status', 'pending')
                ->where('name_server_status', 'failed')
                ->count();

            $hasNameserverUpdateFailures = $hasNameserverUpdateFailures || $nameserverFailureCount > 0;

            // Check if there are domain transfer failures (status = failed)
            $hasDomainTransferFailures = $failedCount > 0;

            // Check if there are any rate limit errors (from domain transfers)
            $hasRateLimitErrors = $rateLimitedCount > 0;

            Log::channel('mailin-ai')->info('Domain transfer process completed', [
                'action' => 'handle_domain_transfer',
                'order_id' => $this->orderId,
                'total_domains' => count($domains),
                'successful_transfers' => $successCount,
                'failed_transfers' => $failedCount,
                'nameserver_update_failures' => $nameserverFailureCount,
                'has_nameserver_failures' => $hasNameserverUpdateFailures,
                'has_domain_transfer_failures' => $hasDomainTransferFailures,
                'rate_limited_count' => $rateLimitedCount,
                'has_rate_limit_errors' => $hasRateLimitErrors,
            ]);

            // Check if there are domains with invalid format errors - these are permanent errors that should trigger rejection
            if (!empty($invalidFormatDomains)) {
                Log::channel('mailin-ai')->warning('Order has domains with invalid format - rejecting', [
                    'action' => 'handle_domain_transfer',
                    'order_id' => $this->orderId,
                    'invalid_format_domains' => $invalidFormatDomains,
                ]);
                
                // Get failed domain transfer records for the invalid format domains
                $invalidDomainTransfers = DomainTransfer::where('order_id', $this->orderId)
                    ->whereIn('domain_name', $invalidFormatDomains)
                    ->get();
                
                // Format rejection reason
                $rejectionReason = "Order rejected: The following domains have an invalid format and cannot be processed by Mailin.ai:\n\n";
                foreach ($invalidDomainTransfers as $transfer) {
                    $rejectionReason .= "• **{$transfer->domain_name}**: {$transfer->error_message}\n";
                }
                $rejectionReason .= "\nPlease verify these domains are valid and properly registered, then resubmit the order with corrected domains.";
                
                // Delete any mailboxes created for this order before rejecting
                $this->deleteOrderMailboxesFromMailin($mailinService);
                
                $order->update([
                    'status_manage_by_admin' => 'reject',
                    'reason' => $rejectionReason,
                ]);
                
                Log::channel('mailin-ai')->warning('Order rejected due to invalid domain format', [
                    'action' => 'handle_domain_transfer',
                    'order_id' => $this->orderId,
                    'invalid_domains_count' => count($invalidFormatDomains),
                    'invalid_domains' => $invalidFormatDomains,
                    'new_status' => 'reject',
                ]);
                
                // Send email notification
                try {
                    $order->refresh();
                    $user = $order->user;
                    if ($user && $user->email) {
                        Mail::to($user->email)
                            ->queue(new OrderStatusChangeMail(
                                $order,
                                $user,
                                'in-progress',
                                'reject',
                                $rejectionReason,
                                false
                            ));
                        Log::channel('mailin-ai')->info('Email notification sent for rejected order (invalid domain format)', [
                            'action' => 'handle_domain_transfer',
                            'order_id' => $this->orderId,
                            'user_email' => $user->email,
                        ]);
                    }
                } catch (\Exception $emailException) {
                    Log::channel('email-failures')->error('Failed to send order rejection email', [
                        'exception' => $emailException->getMessage(),
                        'order_id' => $this->orderId,
                    ]);
                }
                
                return;
            }

            // If ALL domains failed to transfer (no successful transfers and no rate-limited), reject the order
            if ($hasDomainTransferFailures && $successCount === 0 && $rateLimitedCount === 0) {
                // Get the failed domain transfer errors
                $failedDomainTransfers = DomainTransfer::where('order_id', $this->orderId)
                    ->whereIn('domain_name', $domains)
                    ->where('status', 'failed')
                    ->get();

                $domainTransferErrors = [];
                foreach ($failedDomainTransfers as $failedTransfer) {
                    $domainTransferErrors[] = [
                        'domain' => $failedTransfer->domain_name,
                        'platform' => 'Mailin.ai',
                        'error' => $failedTransfer->error_message ?? 'Domain transfer failed',
                    ];
                }

                // Format rejection reason
                $rejectionReason = "Order rejected due to domain transfer failures during automation. Please review the errors below:\n\n";
                foreach ($domainTransferErrors as $error) {
                    $rejectionReason .= "• **{$error['domain']}** ({$error['platform']}): {$error['error']}\n";
                }
                $rejectionReason .= "\nAfter fixing the issues, please resubmit the order.";

                // Delete any mailboxes created for this order before rejecting
                $this->deleteOrderMailboxesFromMailin($mailinService);

                $order->update([
                    'status_manage_by_admin' => 'reject',
                    'reason' => $rejectionReason,
                ]);

                Log::channel('mailin-ai')->warning('Order rejected due to domain transfer failures', [
                    'action' => 'handle_domain_transfer',
                    'order_id' => $this->orderId,
                    'failed_count' => $failedCount,
                    'domain_transfer_errors' => $domainTransferErrors,
                    'new_status' => 'reject',
                ]);

                // Send email notification
                try {
                    $order->refresh();
                    $user = $order->user;
                    if ($user && $user->email) {
                        Mail::to($user->email)
                            ->queue(new OrderStatusChangeMail(
                                $order,
                                $user,
                                'in-progress',
                                'reject',
                                $rejectionReason,
                                false
                            ));
                        Log::channel('mailin-ai')->info('Email notification sent for rejected order', [
                            'action' => 'handle_domain_transfer',
                            'order_id' => $this->orderId,
                            'user_email' => $user->email,
                        ]);
                    }
                } catch (\Exception $emailException) {
                    Log::channel('email-failures')->error('Failed to send order rejection email', [
                        'exception' => $emailException->getMessage(),
                        'order_id' => $this->orderId,
                    ]);
                }

                return;
            }

            // If there are ONLY rate limit errors (no nameserver failures, no domain transfer failures), keep order in-progress
            // Rate limit errors are automatically retried by scheduled command
            if ($hasRateLimitErrors && !$hasNameserverUpdateFailures && !$hasDomainTransferFailures) {
                Log::channel('mailin-ai')->info('Order kept in-progress due to rate limit errors only (will retry automatically)', [
                    'action' => 'handle_domain_transfer',
                    'order_id' => $this->orderId,
                    'rate_limited_count' => $rateLimitedCount,
                    'rate_limited_domains' => $rateLimitedDomains,
                ]);
                // Don't change status - order stays in-progress and will retry automatically
                return;
            }

            // If nameserver updates failed, check if we should reject or keep in-progress
            if ($hasNameserverUpdateFailures) {
                // Check if any errors are due to invalid credentials or domain not found
                $hasInvalidCredentials = false;
                $hasDomainNotFound = false;

                foreach ($nameserverUpdateErrors as $error) {
                    if (isset($error['is_invalid_credentials']) && $error['is_invalid_credentials']) {
                        $hasInvalidCredentials = true;
                    }
                    if (isset($error['is_domain_not_found']) && $error['is_domain_not_found']) {
                        $hasDomainNotFound = true;
                    }
                }

                // If invalid credentials or domain not found, reject the order
                // If only rate limit errors exist (no credential/domain errors), keep in-progress
                // Otherwise, set to draft for retry (only if no rate limit errors)
                $shouldReject = $hasInvalidCredentials || $hasDomainNotFound;

                // If there are rate limit errors and no credential/domain errors, keep in-progress
                // Rate limit errors are automatically retried by scheduled command
                $isRateLimitOnly = $hasRateLimitErrors && !$shouldReject;
                if ($isRateLimitOnly) {
                    $newStatus = 'in-progress'; // Keep in-progress, will retry automatically
                    Log::channel('mailin-ai')->info('Order kept in-progress due to rate limit errors (will retry automatically)', [
                        'action' => 'handle_domain_transfer',
                        'order_id' => $this->orderId,
                        'rate_limited_count' => $rateLimitedCount,
                        'has_credential_errors' => $shouldReject,
                    ]);

                    // Don't change status or send notifications for rate limit errors
                    // The order will remain in-progress and retry automatically
                    return;
                } else {
                    $newStatus = $shouldReject ? 'reject' : 'draft';
                }

                try {
                    $oldStatus = $order->status_manage_by_admin;

                    // Format error message for rejection reason
                    $rejectionReason = "Order rejected due to nameserver update failures during automation. Please review the errors below:\n\n";
                    foreach ($nameserverUpdateErrors as $error) {
                        // Use the actual error message (already cleaned of prefixes)
                        $actualError = $error['error'];
                        $rejectionReason .= "• **{$error['domain']}** ({$error['platform']}): {$actualError}\n";
                    }
                    $rejectionReason .= "\nAfter fixing the issues, please resubmit the order. The system will retry the nameserver updates and continue processing.";

                    // Delete any mailboxes created for this order before rejecting
                    if ($shouldReject) {
                        $this->deleteOrderMailboxesFromMailin($mailinService);
                    }

                    $order->update([
                        'status_manage_by_admin' => $newStatus,
                        'reason' => $shouldReject ? $rejectionReason : null,
                    ]);

                    Log::channel('mailin-ai')->warning("Order set to {$newStatus} due to nameserver update failures", [
                        'action' => 'handle_domain_transfer',
                        'order_id' => $this->orderId,
                        'nameserver_errors' => $nameserverUpdateErrors,
                        'nameserver_failure_count' => $nameserverFailureCount,
                        'has_invalid_credentials' => $hasInvalidCredentials,
                        'has_domain_not_found' => $hasDomainNotFound,
                        'should_reject' => $shouldReject,
                        'new_status' => $newStatus,
                    ]);

                    // Format error message for email
                    $emailErrorMessage = $shouldReject
                        ? $rejectionReason
                        : "Your order could not be processed due to nameserver update failures. Please review the errors below and resubmit:\n\n" .
                        implode("\n", array_map(function ($error) {
                            return "• {$error['domain']}: {$error['error']}";
                        }, $nameserverUpdateErrors)) .
                        "\n\nAfter fixing the issues (e.g., updating API credentials, whitelisting IP address), please resubmit the order. The system will retry the nameserver updates and continue processing.";

                    // Send email notification to customer
                    try {
                        $user = $order->user;
                        if ($user && $user->email) {
                            Mail::to($user->email)
                                ->queue(new OrderStatusChangeMail(
                                    $order,
                                    $user,
                                    $oldStatus,
                                    $newStatus,
                                    $emailErrorMessage,
                                    false
                                ));

                            Log::channel('mailin-ai')->info("Email notification sent for {$newStatus} order", [
                                'action' => 'handle_domain_transfer',
                                'order_id' => $this->orderId,
                                'user_email' => $user->email,
                                'status' => $newStatus,
                            ]);
                        }
                    } catch (\Exception $emailException) {
                        Log::channel('email-failures')->error("Failed to send {$newStatus} order email notification", [
                            'action' => 'handle_domain_transfer',
                            'order_id' => $this->orderId,
                            'user_id' => $order->user_id,
                            'status' => $newStatus,
                            'exception' => $emailException->getMessage(),
                            'stack_trace' => $emailException->getTraceAsString(),
                            'timestamp' => now()->toDateTimeString(),
                        ]);
                    }

                    // Log activity
                    try {
                        $logAction = $shouldReject ? 'mailin_ai_order_rejected' : 'mailin_ai_order_set_to_draft';
                        $logMessage = $shouldReject
                            ? "Order #{$order->id} rejected - nameserver update failed due to invalid credentials or domain not found."
                            : "Order #{$order->id} set to draft - nameserver update failed. Please check and resubmit.";

                        ActivityLogService::log(
                            $logAction,
                            $logMessage,
                            $order,
                            [
                                'order_id' => $order->id,
                                'reason' => $shouldReject ? 'Invalid credentials or domain not found' : 'Nameserver update failed',
                                'nameserver_errors' => $nameserverUpdateErrors,
                                'has_invalid_credentials' => $hasInvalidCredentials,
                                'has_domain_not_found' => $hasDomainNotFound,
                            ]
                        );
                    } catch (\Exception $e) {
                        Log::channel('mailin-ai')->warning('Failed to create activity log', [
                            'action' => 'handle_domain_transfer',
                            'order_id' => $this->orderId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::channel('mailin-ai')->error("Failed to set order to {$newStatus}", [
                        'action' => 'handle_domain_transfer',
                        'order_id' => $this->orderId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // No admin notifications - domain status will be checked by cron job
            // Once status is active, mailboxes will be created automatically

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Error in handleDomainTransfer', [
                'action' => 'handle_domain_transfer',
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Retry failed nameserver updates for domain transfers
     * 
     * This method deduplicates domains before retrying to avoid hitting rate limits.
     * Spaceship API limit: 5 requests per domain within 300 seconds.
     * 
     * @param Order $order
     * @param Collection $failedTransfers
     * @return array Array of errors with 'domain', 'platform', 'error', 'is_invalid_credentials', 'is_domain_not_found'
     */
    private function retryFailedNameserverUpdates(Order $order, $failedTransfers): array
    {
        $retryErrors = [];
        
        $hostingPlatform = null;
        if ($order->reorderInfo && $order->reorderInfo->count() > 0) {
            $hostingPlatform = $order->reorderInfo->first()->hosting_platform;
        }

        if (!$hostingPlatform || !in_array($hostingPlatform, ['spaceship', 'namecheap'])) {
            return $retryErrors;
        }

        // Deduplicate domain transfers by domain name - only retry once per unique domain
        $uniqueDomainTransfers = [];
        foreach ($failedTransfers as $domainTransfer) {
            $domain = $domainTransfer->domain_name;
            if (!isset($uniqueDomainTransfers[$domain])) {
                $uniqueDomainTransfers[$domain] = $domainTransfer;
            }
        }

        Log::channel('mailin-ai')->info('Deduplicating domain transfers for retry', [
            'action' => 'retry_failed_nameserver_updates',
            'order_id' => $this->orderId,
            'original_count' => $failedTransfers->count(),
            'unique_domains' => count($uniqueDomainTransfers),
            'domains' => array_keys($uniqueDomainTransfers),
        ]);

        $spaceshipService = null;
        $namecheapService = null;
        $spaceshipCredential = null;
        $namecheapCredential = null;

        if ($hostingPlatform === 'spaceship') {
            $spaceshipService = new SpaceshipService();
            $spaceshipCredential = $order->getPlatformCredential('spaceship');
        } elseif ($hostingPlatform === 'namecheap') {
            $namecheapService = new NamecheapService();
            $namecheapCredential = $order->getPlatformCredential('namecheap');
        }

        $processedDomains = [];
        $rateLimitHit = false;

        foreach ($uniqueDomainTransfers as $domain => $domainTransfer) {
            // Skip if we've already hit rate limit - don't waste more API calls
            if ($rateLimitHit) {
                Log::channel('mailin-ai')->info('Skipping domain due to rate limit hit', [
                    'action' => 'retry_failed_nameserver_updates',
                    'order_id' => $this->orderId,
                    'domain' => $domain,
                ]);
                continue;
            }

            $nameServers = $domainTransfer->name_servers;

            if (empty($nameServers)) {
                continue;
            }

            $nameServersArray = is_array($nameServers) ? $nameServers : explode(',', $nameServers);
            $nameServersArray = array_map('trim', $nameServersArray);
            $nameServersArray = array_values(array_filter($nameServersArray));

            if (empty($nameServersArray)) {
                continue;
            }

            // Add delay between API calls to avoid rate limiting
            // Spaceship allows 5 requests per domain per 300 seconds
            // For safety, we wait 65 seconds between requests (300/5 = 60, plus buffer)
            if (!empty($processedDomains) && $hostingPlatform === 'spaceship') {
                $delaySeconds = 65;
                Log::channel('mailin-ai')->info('Adding delay before Spaceship API call to avoid rate limit', [
                    'action' => 'retry_failed_nameserver_updates',
                    'order_id' => $this->orderId,
                    'domain' => $domain,
                    'delay_seconds' => $delaySeconds,
                ]);
                sleep($delaySeconds);
            }

            try {
                if ($hostingPlatform === 'spaceship' && $spaceshipService && $spaceshipCredential) {
                    $apiKey = $spaceshipCredential->getCredential('api_key');
                    $apiSecretKey = $spaceshipCredential->getCredential('api_secret_key');

                    if ($apiKey && $apiSecretKey) {
                        $result = $spaceshipService->updateNameservers(
                            $domain,
                            $nameServersArray,
                            $apiKey,
                            $apiSecretKey
                        );

                        if ($result['success']) {
                            // Update ALL domain transfer records for this domain (not just the first one)
                            DomainTransfer::where('order_id', $this->orderId)
                                ->where('domain_name', $domain)
                                ->where('name_server_status', 'failed')
                                ->update([
                                    'name_server_status' => 'updated',
                                    'error_message' => null,
                                ]);

                            $processedDomains[] = $domain;

                            Log::channel('mailin-ai')->info('Retried Spaceship nameserver update successful', [
                                'action' => 'retry_failed_nameserver_updates',
                                'order_id' => $this->orderId,
                                'domain' => $domain,
                            ]);
                        }
                    }
                } elseif ($hostingPlatform === 'namecheap' && $namecheapService && $namecheapCredential) {
                    $apiUser = $namecheapCredential->getCredential('api_user');
                    $apiKey = $namecheapCredential->getCredential('api_key');

                    if ($apiUser && $apiKey) {
                        $result = $namecheapService->updateNameservers(
                            $domain,
                            $nameServersArray,
                            $apiUser,
                            $apiKey
                        );

                        if ($result['success']) {
                            // Update ALL domain transfer records for this domain
                            DomainTransfer::where('order_id', $this->orderId)
                                ->where('domain_name', $domain)
                                ->where('name_server_status', 'failed')
                                ->update([
                                    'name_server_status' => 'updated',
                                    'error_message' => null,
                                ]);

                            $processedDomains[] = $domain;

                            Log::channel('mailin-ai')->info('Retried Namecheap nameserver update successful', [
                                'action' => 'retry_failed_nameserver_updates',
                                'order_id' => $this->orderId,
                                'domain' => $domain,
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                $errorMsgLower = strtolower($errorMessage);
                
                $isRateLimitError = str_contains($errorMessage, 'rate limit')
                    || str_contains($errorMessage, 'Rate limit')
                    || str_contains($errorMessage, 'Too Many Attempts')
                    || str_contains($errorMessage, '429');

                // Detect invalid credentials
                $isInvalidCredentials =
                    str_contains($errorMsgLower, 'invalid api credentials') ||
                    str_contains($errorMsgLower, 'please verify your api credentials') ||
                    str_contains($errorMsgLower, 'verify your api credentials') ||
                    (str_contains($errorMsgLower, 'invalid') && (str_contains($errorMsgLower, 'api') || str_contains($errorMsgLower, 'credentials'))) ||
                    str_contains($errorMsgLower, 'unauthorized') ||
                    str_contains($errorMsgLower, 'forbidden') ||
                    str_contains($errorMsgLower, 'authentication');

                // Detect domain not found
                $isDomainNotFound =
                    $e->getCode() === 404 ||
                    str_contains($errorMsgLower, "hasn't been found") ||
                    str_contains($errorMsgLower, 'has not been found') ||
                    str_contains($errorMsgLower, 'domain not found') ||
                    str_contains($errorMsgLower, 'domain does not exist') ||
                    str_contains($errorMsgLower, 'zone file') && str_contains($errorMsgLower, 'not found') ||
                    (str_contains($errorMsgLower, 'domain') && str_contains($errorMsgLower, 'not exist'));

                if ($isRateLimitError) {
                    $rateLimitHit = true;
                    Log::channel('mailin-ai')->warning('Rate limit hit during retry, stopping further retries', [
                        'action' => 'retry_failed_nameserver_updates',
                        'order_id' => $this->orderId,
                        'domain' => $domain,
                        'platform' => $hostingPlatform,
                        'error' => $errorMessage,
                        'remaining_domains' => array_diff(array_keys($uniqueDomainTransfers), $processedDomains),
                    ]);
                } else {
                    // Extract the actual error message (remove prefix if present)
                    $actualErrorMsg = $errorMessage;
                    $apiPrefix = 'Failed to update nameservers via Spaceship API: ';
                    if (strpos($actualErrorMsg, $apiPrefix) === 0) {
                        $actualErrorMsg = substr($actualErrorMsg, strlen($apiPrefix));
                    }
                    $apiPrefix2 = 'Failed to update nameservers via Namecheap API: ';
                    if (strpos($actualErrorMsg, $apiPrefix2) === 0) {
                        $actualErrorMsg = substr($actualErrorMsg, strlen($apiPrefix2));
                    }

                    // Add to retry errors for rejection handling
                    $retryErrors[] = [
                        'domain' => $domain,
                        'platform' => ucfirst($hostingPlatform),
                        'error' => $actualErrorMsg,
                        'is_invalid_credentials' => $isInvalidCredentials,
                        'is_domain_not_found' => $isDomainNotFound,
                    ];

                    Log::channel('mailin-ai')->error('Retry nameserver update failed', [
                        'action' => 'retry_failed_nameserver_updates',
                        'order_id' => $this->orderId,
                        'domain' => $domain,
                        'platform' => $hostingPlatform,
                        'error' => $errorMessage,
                        'is_invalid_credentials' => $isInvalidCredentials,
                        'is_domain_not_found' => $isDomainNotFound,
                    ]);
                }
                // Keep error message in domain_transfer for display
            }
        }

        Log::channel('mailin-ai')->info('Retry nameserver updates completed', [
            'action' => 'retry_failed_nameserver_updates',
            'order_id' => $this->orderId,
            'processed_domains' => $processedDomains,
            'rate_limit_hit' => $rateLimitHit,
            'retry_errors_count' => count($retryErrors),
        ]);

        return $retryErrors;
    }

    /**
     * Save mailboxes to database and optionally complete the order
     * 
     * @param Order $order
     * @param array $result API result from createMailboxes
     * @param array $mailboxData Array of mailbox data for OrderEmail creation
     * @param array $mailboxes Array of mailboxes sent to API
     * @param bool $completeOrder Whether to mark order as completed
     * @return void
     */
    /**
     * Create mailboxes for a specific provider's domains
     * 
     * @param Order $order
     * @param MailinAiService $mailinService
     * @param string $providerSlug
     * @param array $domains
     * @param bool $shouldCompleteOrder
     * @return void
     */
    private function createMailboxesForProvider($order, $mailinService, $providerSlug, $domains, $shouldCompleteOrder = true, $domainsAreActive = false)
    {
        Log::channel('mailin-ai')->info('Creating mailboxes for provider domains', [
            'action' => 'create_mailboxes_for_provider',
            'order_id' => $this->orderId,
            'provider' => $providerSlug,
            'domains' => $domains,
        ]);

        // Get prefix_variants_details from reorderInfo for proper mailbox names
        $prefixVariantsDetails = [];
        if ($order->reorderInfo && $order->reorderInfo->count() > 0) {
            $reorderInfo = $order->reorderInfo->first();
            $details = $reorderInfo->prefix_variants_details;
            if ($details) {
                $prefixVariantsDetails = is_string($details) 
                    ? json_decode($details, true) ?? []
                    : (is_array($details) ? $details : []);
            }
        }

        // Generate mailboxes for these domains
        $mailboxes = [];
        $mailboxData = [];
        $mailboxIndex = 0;
        $prefixIndex = 0;

        foreach ($domains as $domain) {
            $prefixIndex = 0;
            foreach ($this->prefixVariants as $prefixKey => $prefix) {
                $prefixIndex++;
                $username = $prefix . '@' . $domain;
                
                // Get proper name from prefix_variants_details
                // Try with the original key first, then try prefix_variant_N format
                $variantKey = is_numeric($prefixKey) ? 'prefix_variant_' . ($prefixKey + 1) : $prefixKey;
                $variantDetails = $prefixVariantsDetails[$variantKey] ?? $prefixVariantsDetails['prefix_variant_' . $prefixIndex] ?? null;
                
                if ($variantDetails && (isset($variantDetails['first_name']) || isset($variantDetails['last_name']))) {
                    $firstName = trim($variantDetails['first_name'] ?? '');
                    $lastName = trim($variantDetails['last_name'] ?? '');
                    $name = trim($firstName . ' ' . $lastName);
                    // Fallback to prefix if name is empty after trimming
                    if (empty($name)) {
                        $name = $prefix;
                    }
                } else {
                    // Fallback to email prefix if no details found
                    $name = $prefix;
                }
                
                $password = $this->customEncrypt($this->orderId);

                $mailboxes[] = [
                    'username' => $username,
                    'name' => $name,
                    'password' => $password,
                ];

                $mailboxData[] = [
                    'order_id' => $this->orderId,
                    'username' => $username,
                    'name' => $name,
                    'password' => $password,
                    'domain' => $domain,
                    'prefix' => $prefix,
                    'provider_slug' => $providerSlug,
                ];

                $mailboxIndex++;
            }
        }

        if (empty($mailboxes)) {
            Log::channel('mailin-ai')->warning('No mailboxes generated for provider', [
                'action' => 'create_mailboxes_for_provider',
                'order_id' => $this->orderId,
                'provider' => $providerSlug,
            ]);
            return;
        }

        try {
            $result = $mailinService->createMailboxes($mailboxes);

            // Check if mailboxes already exist on Mailin.ai - reject the order
            if ($result['success'] && isset($result['already_exists']) && $result['already_exists']) {
                // Get the specific mailbox emails that already exist from the API response
                $existingMailboxes = $result['existing_mailbox_emails'] ?? [];
                
                // If no specific emails extracted, use the request mailboxes as fallback
                if (empty($existingMailboxes)) {
                    $existingMailboxes = array_map(function($mb) {
                        return $mb['username'] ?? 'unknown';
                    }, $mailboxes);
                }
                
                // Delete any mailboxes that were created for this order before rejecting
                $this->deleteOrderMailboxesFromMailin($mailinService);
                
                $rejectionReason = "Order rejected: The following mailboxes already exist on Mailin.ai:\n\n";
                foreach ($existingMailboxes as $email) {
                    $rejectionReason .= "• {$email}\n";
                }
                $rejectionReason .= "\nPlease use different email prefixes or domains, or contact support if you believe this is an error.";
                
                $order->update([
                    'status_manage_by_admin' => 'reject',
                    'reason' => $rejectionReason,
                ]);
                
                Log::channel('mailin-ai')->warning('Order rejected - mailboxes already exist on Mailin.ai', [
                    'action' => 'create_mailboxes_for_provider',
                    'order_id' => $this->orderId,
                    'provider' => $providerSlug,
                    'existing_mailboxes' => $existingMailboxes,
                    'new_status' => 'reject',
                ]);
                
                // Send email notification
                try {
                    $order->refresh();
                    $user = $order->user;
                    if ($user && $user->email) {
                        Mail::to($user->email)
                            ->queue(new OrderStatusChangeMail(
                                $order,
                                $user,
                                'in-progress',
                                'reject',
                                $rejectionReason,
                                false
                            ));
                    }
                } catch (\Exception $emailException) {
                      Log::channel('email-failures')->error('Failed to send order rejection email', [
                        'exception' => $emailException->getMessage(),
                        'order_id' => $this->orderId,
                    ]);
                }
                
                return; // Stop processing this provider's domains
            }

            // Handle new mailboxes (with UUID)
            if ($result['success'] && isset($result['uuid'])) {
                $this->saveMailboxesForDomains($order, $result, $mailboxData, $mailboxes, $shouldCompleteOrder, $providerSlug, $domainsAreActive);
            } else {
                throw new \Exception('Mailbox creation failed: ' . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Failed to create mailboxes for provider', [
                'action' => 'create_mailboxes_for_provider',
                'order_id' => $this->orderId,
                'provider' => $providerSlug,
                'domains' => $domains,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function saveMailboxesForDomains($order, $result, $mailboxData, $mailboxes, $completeOrder = true, $providerSlug = null, $domainsAreActive = false)
    {
        $jobUuid = $result['uuid'] ?? null;
        $mailboxesAlreadyExist = isset($result['already_exists']) && $result['already_exists'];

        // Get provider credentials based on provider_slug (or fallback to active provider)
        $provider = null;
        if ($providerSlug) {
            $provider = SmtpProviderSplit::getBySlug($providerSlug);
        }

        if (!$provider) {
            $provider = SmtpProviderSplit::getActiveProvider();
        }

        $credentials = $provider ? $provider->getCredentials() : null;
        $mailinService = new MailinAiService($credentials);

        // Use provider slug from parameter or provider, or fallback to 'mailin'
        $finalProviderSlug = $providerSlug ?: ($provider ? $provider->slug : 'mailin');

        // Save or update OrderAutomation record
        OrderAutomation::updateOrCreate(
            [
                'order_id' => $this->orderId,
                'action_type' => 'mailbox',
            ],
            [
                'provider_type' => $this->providerType,
                'job_uuid' => $jobUuid,
                'status' => $mailboxesAlreadyExist ? 'completed' : 'pending',
                'response_data' => $result['response'] ?? null,
            ]
        );

        // Initialize mailbox map to store mailbox IDs from API
        $mailboxMap = [];
        
        // Check job status and wait for completion to get mailbox IDs
        // Skip status checking if:
        // 1. Domains are already active - mailboxes will be created immediately
        // 2. Mailboxes already exist on Mailin.ai - fetch IDs from API
        $mailboxStatusData = null;
        
        if ($mailboxesAlreadyExist) {
            // Mailboxes already exist on Mailin.ai, fetch their IDs from API
            Log::channel('mailin-ai')->info('Mailboxes already exist on Mailin.ai, fetching mailbox IDs from API', [
                'action' => 'save_mailboxes_for_domains',
                'order_id' => $this->orderId,
                'reason' => 'mailboxes_already_exist',
            ]);
            
            // Get unique domains from mailboxData
            $uniqueDomains = array_unique(array_column($mailboxData, 'domain'));
            
            // Fetch mailboxes for each domain to get their IDs
            foreach ($uniqueDomains as $domain) {
                try {
                    $mailboxesResult = $mailinService->getMailboxesByDomain($domain);
                    
                    if ($mailboxesResult['success'] && !empty($mailboxesResult['mailboxes'])) {
                        // Add mailboxes to mailboxMap (use lowercase for case-insensitive matching)
                        foreach ($mailboxesResult['mailboxes'] as $apiMailbox) {
                            $username = $apiMailbox['username'] ?? $apiMailbox['email'] ?? null;
                            if ($username) {
                                $mailboxMap[strtolower($username)] = [
                                    'mailbox_id' => $apiMailbox['id'] ?? null,
                                    'domain_id' => $apiMailbox['domain_id'] ?? null,
                                ];
                            }
                        }
                        
                        Log::channel('mailin-ai')->info('Fetched mailbox IDs for domain', [
                            'action' => 'save_mailboxes_for_domains',
                            'order_id' => $this->orderId,
                            'domain' => $domain,
                            'mailbox_count' => count($mailboxesResult['mailboxes']),
                        ]);
                    } else {
                        Log::channel('mailin-ai')->warning('Could not fetch mailboxes for domain', [
                            'action' => 'save_mailboxes_for_domains',
                            'order_id' => $this->orderId,
                            'domain' => $domain,
                            'message' => $mailboxesResult['message'] ?? 'Unknown error',
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::channel('mailin-ai')->warning('Error fetching mailboxes for domain, will continue without IDs', [
                        'action' => 'save_mailboxes_for_domains',
                        'order_id' => $this->orderId,
                        'domain' => $domain,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } elseif ($domainsAreActive) {
            // For active domains, mailboxes are created immediately
            // Try to fetch mailbox IDs from API after a short delay (mailboxes might be created instantly)
            Log::channel('mailin-ai')->info('Domains are already active, attempting to fetch mailbox IDs from API', [
                'action' => 'save_mailboxes_for_domains',
                'order_id' => $this->orderId,
                'job_uuid' => $jobUuid,
                'reason' => 'domains_already_active',
            ]);
            
            // Wait a few seconds for mailboxes to be created on Mailin.ai
            sleep(3);
            
            // Get unique domains from mailboxData
            $uniqueDomains = array_unique(array_column($mailboxData, 'domain'));
            
            // Try to fetch mailbox IDs for each domain
            foreach ($uniqueDomains as $domain) {
                try {
                    $mailboxesResult = $mailinService->getMailboxesByDomain($domain);
                    
                    if ($mailboxesResult['success'] && !empty($mailboxesResult['mailboxes'])) {
                        // Add mailboxes to mailboxMap
                        foreach ($mailboxesResult['mailboxes'] as $apiMailbox) {
                            $username = $apiMailbox['username'] ?? $apiMailbox['email'] ?? null;
                            if ($username) {
                                $mailboxMap[strtolower($username)] = [
                                    'mailbox_id' => $apiMailbox['id'] ?? null,
                                    'domain_id' => $apiMailbox['domain_id'] ?? null,
                                ];
                            }
                        }
                        
                        Log::channel('mailin-ai')->info('Fetched mailbox IDs for active domain', [
                            'action' => 'save_mailboxes_for_domains',
                            'order_id' => $this->orderId,
                            'domain' => $domain,
                            'mailbox_count' => count($mailboxesResult['mailboxes']),
                        ]);
                    } else {
                        Log::channel('mailin-ai')->warning('Could not fetch mailboxes for active domain (may need to wait longer)', [
                            'action' => 'save_mailboxes_for_domains',
                            'order_id' => $this->orderId,
                            'domain' => $domain,
                            'message' => $mailboxesResult['message'] ?? 'Unknown error',
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::channel('mailin-ai')->warning('Error fetching mailboxes for active domain, will continue without IDs', [
                        'action' => 'save_mailboxes_for_domains',
                        'order_id' => $this->orderId,
                        'domain' => $domain,
                        'error' => $e->getMessage(),
                    ]);
                }
                
                // Small delay between domain fetches
                usleep(500000); // 0.5 seconds
            }
        } else {
            // For unregistered domains (transferred), check job status to get mailbox IDs
            $maxAttempts = 30; // Maximum 30 attempts (5 minutes if polling every 10 seconds)
            $attempt = 0;

            Log::channel('mailin-ai')->info('Checking mailbox job status to get mailbox IDs', [
                'action' => 'save_mailboxes_for_domains',
                'order_id' => $this->orderId,
                'job_uuid' => $jobUuid,
                'reason' => 'domains_were_transferred',
            ]);

            while ($attempt < $maxAttempts) {
                try {
                    $statusResult = $mailinService->getMailboxJobStatus($jobUuid);

                    if ($statusResult['success']) {
                        $status = $statusResult['data']['status'] ?? 'unknown';

                        Log::channel('mailin-ai')->info('Mailbox job status checked', [
                            'action' => 'save_mailboxes_for_domains',
                            'order_id' => $this->orderId,
                            'job_uuid' => $jobUuid,
                            'status' => $status,
                            'attempt' => $attempt + 1,
                        ]);

                        if ($status === 'completed') {
                            $mailboxStatusData = $statusResult['data'];
                            Log::channel('mailin-ai')->info('Mailbox job completed, extracting mailbox data', [
                                'action' => 'save_mailboxes_for_domains',
                                'order_id' => $this->orderId,
                                'job_uuid' => $jobUuid,
                                'mailbox_count' => count($mailboxStatusData['data'] ?? []),
                            ]);
                            break;
                        } elseif ($status === 'failed') {
                            Log::channel('mailin-ai')->error('Mailbox job failed', [
                                'action' => 'save_mailboxes_for_domains',
                                'order_id' => $this->orderId,
                                'job_uuid' => $jobUuid,
                                'report' => $statusResult['data']['report'] ?? 'Unknown error',
                            ]);
                            break;
                        }

                        // If still pending/processing, wait before next check
                        if ($status === 'pending' || $status === 'processing' || $status === 'created') {
                            sleep(10); // Wait 10 seconds before next check
                        }
                    }
                } catch (\Exception $e) {
                    Log::channel('mailin-ai')->warning('Error checking mailbox job status, will continue without mailbox IDs', [
                        'action' => 'save_mailboxes_for_domains',
                        'order_id' => $this->orderId,
                        'job_uuid' => $jobUuid,
                        'error' => $e->getMessage(),
                    ]);
                    break; // Continue without mailbox IDs if status check fails
                }

                $attempt++;
            }
        }

        // Populate mailbox map from job status data (if not already populated from existing mailboxes)
        if ($mailboxStatusData && isset($mailboxStatusData['data']) && is_array($mailboxStatusData['data'])) {
            foreach ($mailboxStatusData['data'] as $apiMailbox) {
                $username = $apiMailbox['username'] ?? $apiMailbox['email'] ?? null;
                if ($username) {
                    $usernameLower = strtolower($username);
                    if (!isset($mailboxMap[$usernameLower])) {
                        $mailboxMap[$usernameLower] = [
                            'mailbox_id' => $apiMailbox['id'] ?? null,
                            'domain_id' => $apiMailbox['domain_id'] ?? null,
                        ];
                    }
                }
            }
        }

        // Create OrderEmail records - don't delete existing ones (may be adding for additional domains)
        try {
            if ($mailboxesAlreadyExist) {
                Log::channel('mailin-ai')->info('Saving mailboxes that already exist on Mailin.ai to database', [
                    'action' => 'save_mailboxes_for_domains',
                    'order_id' => $this->orderId,
                    'mailbox_count' => count($mailboxData),
                ]);
            }
            
            $savedCount = 0;
            foreach ($mailboxData as $mailbox) {
                try {
                    // Check if mailbox already exists (by email)
                    $existing = OrderEmail::where('order_id', $this->orderId)
                        ->where('email', $mailbox['username'])
                        ->first();

                    if (!$existing) {
                        // Get mailbox ID and domain ID from API response if available
                        $mailinMailboxId = isset($mailboxMap[$mailbox['username']]) 
                            ? ($mailboxMap[$mailbox['username']]['mailbox_id'] ?? null) 
                            : null;
                        $mailinDomainId = isset($mailboxMap[$mailbox['username']]) 
                            ? ($mailboxMap[$mailbox['username']]['domain_id'] ?? null) 
                            : null;

                        // Get provider_slug from mailboxData or use finalProviderSlug
                        $mailboxProviderSlug = $mailbox['provider_slug'] ?? $finalProviderSlug;

                        OrderEmail::create([
                            'order_id' => $this->orderId,
                            'user_id' => $order->user_id,
                            'order_split_id' => null,
                            'contractor_id' => null,
                            'name' => $mailbox['name'],
                            'last_name' => null,
                            'email' => $mailbox['username'],
                            'password' => $mailbox['password'],
                            'profile_picture' => null,
                            'mailin_mailbox_id' => $mailinMailboxId,
                            'mailin_domain_id' => $mailinDomainId,
                            'domain' => $mailbox['domain'] ?? null,
                            'provider_slug' => $mailboxProviderSlug,
                        ]);
                        $savedCount++;

                        if ($mailinMailboxId) {
                            Log::channel('mailin-ai')->debug('Saved mailbox with Mailin.ai IDs', [
                                'action' => 'save_mailboxes_for_domains',
                                'order_id' => $this->orderId,
                                'email' => $mailbox['username'],
                                'mailin_mailbox_id' => $mailinMailboxId,
                                'mailin_domain_id' => $mailinDomainId,
                            ]);
                        }
                    } else {
                        // Update existing mailbox with Mailin.ai IDs if not already set
                        // Use case-insensitive lookup for username matching
                        $usernameLower = strtolower($mailbox['username']);
                        $mailinMailboxId = isset($mailboxMap[$usernameLower]) 
                            ? ($mailboxMap[$usernameLower]['mailbox_id'] ?? null) 
                            : null;
                        $mailinDomainId = isset($mailboxMap[$usernameLower]) 
                            ? ($mailboxMap[$usernameLower]['domain_id'] ?? null) 
                            : null;

                        if ($mailinMailboxId && !$existing->mailin_mailbox_id) {
                            $existing->update([
                                'mailin_mailbox_id' => $mailinMailboxId,
                                'mailin_domain_id' => $mailinDomainId,
                            ]);

                            Log::channel('mailin-ai')->debug('Updated existing mailbox with Mailin.ai IDs', [
                                'action' => 'save_mailboxes_for_domains',
                                'order_id' => $this->orderId,
                                'email' => $mailbox['username'],
                                'mailin_mailbox_id' => $mailinMailboxId,
                                'mailin_domain_id' => $mailinDomainId,
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::channel('mailin-ai')->error('Failed to save individual mailbox to database', [
                        'action' => 'create_mailboxes_on_order',
                        'order_id' => $this->orderId,
                        'mailbox' => $mailbox['username'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::channel('mailin-ai')->info('OrderEmail records created and saved to database', [
                'action' => 'create_mailboxes_on_order',
                'order_id' => $this->orderId,
                'total_mailboxes' => count($mailboxData),
                'saved_count' => $savedCount,
            ]);

            // Create notification
            try {
                Notification::create([
                    'user_id' => $order->user_id,
                    'type' => 'email_created',
                    'title' => 'New Email Accounts Created',
                    'message' => 'New email accounts have been automatically created for your order #' . $this->orderId,
                    'data' => [
                        'order_id' => $this->orderId,
                        'email_count' => $savedCount,
                        'created_by' => 'automation',
                    ]
                ]);
            } catch (\Exception $e) {
                Log::channel('mailin-ai')->warning('Failed to create email_created notification', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                    'error' => $e->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->warning('Failed to create OrderEmail records', [
                'action' => 'create_mailboxes_on_order',
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
            ]);
        }

        // DO NOT complete order here - completion is handled after ALL providers are processed
        // The $completeOrder parameter is now ignored to prevent premature completion
    }

    /**
     * Complete order after ALL domains have mailboxes created
     * 
     * @param Order $order
     * @return void
     */
    private function completeOrderAfterAllMailboxesCreated($order)
    {
        $oldStatus = $order->status_manage_by_admin;
        
        // Get total mailbox count
        $mailboxCount = OrderEmail::where('order_id', $this->orderId)->count();
        
        // Get the latest job UUID from OrderAutomation
        $latestAutomation = OrderAutomation::where('order_id', $this->orderId)
            ->where('action_type', 'mailbox')
            ->orderBy('created_at', 'desc')
            ->first();
        $jobUuid = $latestAutomation ? $latestAutomation->job_uuid : null;

        DB::transaction(function () use ($order, $oldStatus, $mailboxCount, $jobUuid) {
            $order->update([
                'status_manage_by_admin' => 'completed',
                'completed_at' => now(),
                'provider_type' => $this->providerType,
            ]);

            try {
                OrderAutomation::where('order_id', $this->orderId)
                    ->where('action_type', 'mailbox')
                    ->update(['status' => 'completed']);
            } catch (\Exception $e) {
                Log::channel('mailin-ai')->warning('Failed to update order_automations status', [
                    'action' => 'complete_order_after_all_mailboxes',
                    'order_id' => $this->orderId,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                ActivityLogService::log(
                    'mailin_ai_order_completed',
                    "Order #{$order->id} automatically completed mailbox creation",
                    $order,
                    [
                        'order_id' => $order->id,
                        'old_status' => $oldStatus,
                        'new_status' => 'completed',
                        'provider_type' => $this->providerType,
                        'job_uuid' => $jobUuid,
                        'mailbox_count' => $mailboxCount,
                        'completed_by' => 'system',
                        'completed_by_type' => 'automation'
                    ]
                );
            } catch (\Exception $e) {
                Log::channel('mailin-ai')->warning('Failed to create activity log', [
                    'action' => 'complete_order_after_all_mailboxes',
                    'order_id' => $this->orderId,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                Notification::create([
                    'user_id' => $order->user_id,
                    'type' => 'order_status_change',
                    'title' => 'Order Completed',
                    'message' => "Your order #{$order->id} has been automatically completed - mailbox creation",
                    'data' => [
                        'order_id' => $order->id,
                        'old_status' => $oldStatus,
                        'new_status' => 'completed',
                        'provider_type' => $this->providerType,
                        'completed_by' => 'automation',
                        'mailbox_count' => $mailboxCount,
                    ]
                ]);
            } catch (\Exception $e) {
                Log::channel('mailin-ai')->warning('Failed to create notification', [
                    'action' => 'complete_order_after_all_mailboxes',
                    'order_id' => $this->orderId,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $order->refresh();
                $user = $order->user;
                if ($user && $user->email) {
                    Mail::to($user->email)
                        ->queue(new OrderStatusChangeMail(
                            $order,
                            $user,
                            $oldStatus,
                            'completed',
                            'Automatically completed mailbox creation',
                            false
                        ));
                }
            } catch (\Exception $e) {
                Log::channel('email-failures')->error('Failed to send order completion email', [
                    'exception' => $e->getMessage(),
                    'order_id' => $order->id,
                    'timestamp' => now()->toDateTimeString(),
                    'context' => 'CreateMailboxesOnOrderJob'
                ]);
            }

            Log::channel('mailin-ai')->info('Order completed after all mailboxes created', [
                'action' => 'complete_order_after_all_mailboxes',
                'order_id' => $this->orderId,
                'job_uuid' => $jobUuid,
                'mailbox_count' => $mailboxCount,
                'order_status' => 'completed',
            ]);
        });
    }

    /**
     * Generate password for mailbox
     * 
     * @param int $userId User ID for seeding
     * @param int $index Index for uniqueness
     * @return string Generated password
     */
    /**
     * Save order provider splits to track which providers are used for this order
     * 
     * @param Order $order
     * @param array $domainSplit Array with provider slug as key and domains array as value
     * @return void
     */
    private function saveOrderProviderSplits($order, $domainSplit)
    {
        try {
            // Get total domains for percentage calculation
            $totalDomains = count($this->domains);

            if ($totalDomains === 0) {
                return;
            }

            // Delete existing splits for this order (in case of resubmission)
            OrderProviderSplit::where('order_id', $this->orderId)->delete();

            foreach ($domainSplit as $providerSlug => $providerDomains) {
                if (empty($providerDomains)) {
                    continue;
                }

                // Get provider configuration
                $provider = SmtpProviderSplit::getBySlug($providerSlug);
                if (!$provider) {
                    Log::channel('mailin-ai')->warning('Provider not found when saving order provider split', [
                        'action' => 'save_order_provider_splits',
                        'order_id' => $this->orderId,
                        'provider_slug' => $providerSlug,
                    ]);
                    continue;
                }

                $domainCount = count($providerDomains);
                $percentage = ($domainCount / $totalDomains) * 100;

                OrderProviderSplit::create([
                    'order_id' => $this->orderId,
                    'provider_slug' => $providerSlug,
                    'provider_name' => $provider->name,
                    'split_percentage' => round($percentage, 2),
                    'domain_count' => $domainCount,
                    'domains' => $providerDomains,
                    'priority' => $provider->priority,
                ]);

                Log::channel('mailin-ai')->info('Saved order provider split', [
                    'action' => 'save_order_provider_splits',
                    'order_id' => $this->orderId,
                    'provider_slug' => $providerSlug,
                    'domain_count' => $domainCount,
                    'percentage' => round($percentage, 2),
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Failed to save order provider splits', [
                'action' => 'save_order_provider_splits',
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - this is tracking data, not critical for order processing
        }
    }

    /**
     * Generate password using the same logic as CSV export
     * Uses orderId + index as seed for consistent password generation
     * 
     * @param int $orderId Order ID for seeding
     * @param int $index Index for uniqueness (default: 0)
     * @return string Generated 8-character password
     */
    private function customEncrypt($orderId, $index = 0)
    {
        $upperCase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowerCase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*';
        
        // Use order ID as seed for consistent password generation
        mt_srand($orderId);

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
     * Delete all mailboxes for an order from Mailin.ai
     * Called when an order is rejected to clean up any created mailboxes
     * 
     * @param MailinAiService|null $mailinService
     * @return void
     */
    private function deleteOrderMailboxesFromMailin($mailinService = null)
    {
        // Get all order emails that have mailin_mailbox_id or need lookup
        $orderEmails = OrderEmail::where('order_id', $this->orderId)->get();
        
        if ($orderEmails->count() === 0) {
            return;
        }
        
        Log::channel('mailin-ai')->info('Deleting mailboxes from Mailin.ai on order rejection', [
            'action' => 'delete_order_mailboxes',
            'order_id' => $this->orderId,
            'email_count' => $orderEmails->count(),
        ]);
        
        // Get mailin service if not provided
        if (!$mailinService) {
            $provider = SmtpProviderSplit::getActiveProvider();
            $credentials = $provider ? $provider->getCredentials() : null;
            $mailinService = new MailinAiService($credentials);
        }
        
        $deletedCount = 0;
        $failedCount = 0;
        
        foreach ($orderEmails as $orderEmail) {
            try {
                $mailboxIdToDelete = $orderEmail->mailin_mailbox_id;
                
                // If mailin_mailbox_id is null, look up by email
                if (!$mailboxIdToDelete && $orderEmail->email) {
                    try {
                        $lookupResult = $mailinService->getMailboxesByName($orderEmail->email);
                        if (!empty($lookupResult) && isset($lookupResult[0]['id'])) {
                            $mailboxIdToDelete = $lookupResult[0]['id'];
                        }
                    } catch (\Exception $lookupException) {
                        Log::channel('mailin-ai')->warning('Failed to lookup mailbox by email', [
                            'action' => 'delete_order_mailboxes',
                            'order_id' => $this->orderId,
                            'email' => $orderEmail->email,
                            'error' => $lookupException->getMessage(),
                        ]);
                    }
                }
                
                if ($mailboxIdToDelete) {
                    $mailinService->deleteMailbox($mailboxIdToDelete);
                    $deletedCount++;
                    Log::channel('mailin-ai')->info('Deleted mailbox from Mailin.ai', [
                        'action' => 'delete_order_mailboxes',
                        'order_id' => $this->orderId,
                        'email' => $orderEmail->email,
                        'mailin_mailbox_id' => $mailboxIdToDelete,
                    ]);
                }
            } catch (\Exception $deleteException) {
                $failedCount++;
                Log::channel('mailin-ai')->error('Failed to delete mailbox from Mailin.ai', [
                    'action' => 'delete_order_mailboxes',
                    'order_id' => $this->orderId,
                    'email' => $orderEmail->email,
                    'error' => $deleteException->getMessage(),
                ]);
            }
        }
        
        // Delete OrderEmail records from database
        OrderEmail::where('order_id', $this->orderId)->delete();
        
        Log::channel('mailin-ai')->info('Completed mailbox cleanup on order rejection', [
            'action' => 'delete_order_mailboxes',
            'order_id' => $this->orderId,
            'deleted_from_mailin' => $deletedCount,
            'failed_deletions' => $failedCount,
            'deleted_from_database' => $orderEmails->count(),
        ]);
    }
}

