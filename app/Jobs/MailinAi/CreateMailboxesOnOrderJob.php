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
                'domain_split' => array_map(function($domains) {
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
                }

                // Create mailboxes for registered domains
                if (!empty($registeredDomains)) {
                    $this->createMailboxesForProvider($order, $mailinService, $providerSlug, $registeredDomains, empty($unregisteredDomains));
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
                
                // Retry failed nameserver updates
                $this->retryFailedNameserverUpdates($order, $failedTransfers);
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
            
            // Configuration for rate limit handling
            $delayBetweenTransfers = config('mailin_ai.domain_transfer_delay', 2); // seconds between transfers
            $batchSize = config('mailin_ai.domain_transfer_batch_size', 10); // domains per batch
            $batchDelay = config('mailin_ai.domain_transfer_batch_delay', 10); // seconds between batches
            
            // Track domains that hit rate limits for retry
            $rateLimitedDomains = [];
            
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
                        $nameServersForDb = array_values(array_filter($nameServersForDb, function($ns) {
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
                        try {
                            $domainTransfer = DomainTransfer::create([
                                'order_id' => $this->orderId,
                                'provider_slug' => $providerSlug,
                                'domain_name' => $domain,
                                'name_servers' => $nameServersForDb, // Store as array (will be cast to JSON)
                                'status' => 'pending',
                                'response_data' => $transferResult['response'] ?? null,
                            ]);
                            
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
                                // Mark that we have a nameserver update failure
                                $hasNameserverUpdateFailures = true;
                                $errorMessage = 'Spaceship nameserver update failed: ' . $spaceshipException->getMessage();
                                $nameserverUpdateErrors[] = [
                                    'domain' => $domain,
                                    'platform' => 'Spaceship',
                                    'error' => $errorMessage,
                                ];
                                
                                // Log error but don't fail the transfer - keep status as pending for retry
                                Log::channel('mailin-ai')->error('Failed to update Spaceship nameservers - will retry via scheduled job', [
                                    'action' => 'handle_domain_transfer',
                                    'order_id' => $this->orderId,
                                    'domain' => $domain,
                                    'error' => $spaceshipException->getMessage(),
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
                                // Mark that we have a nameserver update failure
                                $hasNameserverUpdateFailures = true;
                                $errorMessage = 'Namecheap nameserver update failed: ' . $namecheapException->getMessage();
                                $nameserverUpdateErrors[] = [
                                    'domain' => $domain,
                                    'platform' => 'Namecheap',
                                    'error' => $errorMessage,
                                ];
                                
                                // Log error but don't fail the transfer - keep status as pending for retry
                                Log::channel('mailin-ai')->error('Failed to update Namecheap nameservers - will retry via scheduled job', [
                                    'action' => 'handle_domain_transfer',
                                    'order_id' => $this->orderId,
                                    'domain' => $domain,
                                    'error' => $namecheapException->getMessage(),
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
                        try {
                            DomainTransfer::create([
                                'order_id' => $this->orderId,
                                'domain_name' => $domain,
                                'status' => 'pending',
                                'error_message' => 'Rate limit exceeded. Will retry automatically: ' . $errorMessage,
                            ]);
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
                        // Other errors - log and save as failed
                        Log::channel('mailin-ai')->error('Failed to transfer domain', [
                            'action' => 'handle_domain_transfer',
                            'order_id' => $this->orderId,
                            'domain' => $domain,
                            'error' => $errorMessage,
                            'is_rate_limit' => false,
                        ]);
                        
                        // Save failed transfer record
                        try {
                            DomainTransfer::create([
                                'order_id' => $this->orderId,
                                'domain_name' => $domain,
                                'status' => 'failed',
                                'error_message' => $errorMessage,
                            ]);
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
                ->where(function($query) {
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
            
            Log::channel('mailin-ai')->info('Domain transfer process completed', [
                'action' => 'handle_domain_transfer',
                'order_id' => $this->orderId,
                'total_domains' => count($domains),
                'successful_transfers' => $successCount,
                'failed_transfers' => $failedCount,
                'nameserver_update_failures' => $nameserverFailureCount,
                'has_nameserver_failures' => $hasNameserverUpdateFailures,
            ]);
            
            // If nameserver updates failed, set order to draft so user can fix and resubmit
            if ($hasNameserverUpdateFailures) {
                try {
                    $oldStatus = $order->status_manage_by_admin;
                    $order->update([
                        'status_manage_by_admin' => 'draft',
                    ]);
                    
                    Log::channel('mailin-ai')->warning('Order set to draft due to nameserver update failures', [
                        'action' => 'handle_domain_transfer',
                        'order_id' => $this->orderId,
                        'nameserver_errors' => $nameserverUpdateErrors,
                        'nameserver_failure_count' => $nameserverFailureCount,
                    ]);
                    
                    // Format error message for email (same as shown on form)
                    $errorMessage = "Your order could not be processed due to nameserver update failures. Please review the errors below and resubmit:\n\n";
                    foreach ($nameserverUpdateErrors as $error) {
                        $errorMessage .= "• {$error['domain']}: {$error['error']}\n";
                    }
                    $errorMessage .= "\nAfter fixing the issues (e.g., updating API credentials, whitelisting IP address), please resubmit the order. The system will retry the nameserver updates and continue processing.";
                    
                    // Send email notification to customer
                    try {
                        $user = $order->user;
                        if ($user && $user->email) {
                            Mail::to($user->email)
                                ->queue(new OrderStatusChangeMail(
                                    $order,
                                    $user,
                                    $oldStatus,
                                    'draft',
                                    $errorMessage,
                                    false
                                ));
                            
                            Log::channel('mailin-ai')->info('Email notification sent for draft order', [
                                'action' => 'handle_domain_transfer',
                                'order_id' => $this->orderId,
                                'user_email' => $user->email,
                            ]);
                        }
                    } catch (\Exception $emailException) {
                        Log::channel('email-failures')->error('Failed to send draft order email notification', [
                            'action' => 'handle_domain_transfer',
                            'order_id' => $this->orderId,
                            'user_id' => $order->user_id,
                            'exception' => $emailException->getMessage(),
                            'stack_trace' => $emailException->getTraceAsString(),
                            'timestamp' => now()->toDateTimeString(),
                        ]);
                    }
                    
                    // Log activity
                    try {
                        ActivityLogService::log(
                            'mailin_ai_order_set_to_draft',
                            "Order #{$order->id} set to draft - nameserver update failed. Please check and resubmit.",
                            $order,
                            [
                                'order_id' => $order->id,
                                'reason' => 'Nameserver update failed',
                                'nameserver_errors' => $nameserverUpdateErrors,
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
                    Log::channel('mailin-ai')->error('Failed to set order to draft', [
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
     */
    private function retryFailedNameserverUpdates(Order $order, $failedTransfers)
    {
        $hostingPlatform = null;
        if ($order->reorderInfo && $order->reorderInfo->count() > 0) {
            $hostingPlatform = $order->reorderInfo->first()->hosting_platform;
        }
        
        if (!$hostingPlatform || !in_array($hostingPlatform, ['spaceship', 'namecheap'])) {
            return;
        }
        
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
        
        foreach ($failedTransfers as $domainTransfer) {
            $domain = $domainTransfer->domain_name;
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
                            $domainTransfer->update([
                                'name_server_status' => 'updated',
                                'error_message' => null,
                            ]);
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
                            $domainTransfer->update([
                                'name_server_status' => 'updated',
                                'error_message' => null,
                            ]);
                            Log::channel('mailin-ai')->info('Retried Namecheap nameserver update successful', [
                                'action' => 'retry_failed_nameserver_updates',
                                'order_id' => $this->orderId,
                                'domain' => $domain,
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::channel('mailin-ai')->error('Retry nameserver update failed', [
                    'action' => 'retry_failed_nameserver_updates',
                    'order_id' => $this->orderId,
                    'domain' => $domain,
                    'platform' => $hostingPlatform,
                    'error' => $e->getMessage(),
                ]);
                // Keep error message in domain_transfer for display
            }
        }
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
    private function createMailboxesForProvider($order, $mailinService, $providerSlug, $domains, $shouldCompleteOrder = true)
    {
        Log::channel('mailin-ai')->info('Creating mailboxes for provider domains', [
            'action' => 'create_mailboxes_for_provider',
            'order_id' => $this->orderId,
            'provider' => $providerSlug,
            'domains' => $domains,
        ]);

        // Generate mailboxes for these domains
        $mailboxes = [];
        $mailboxData = [];
        $mailboxIndex = 0;

        foreach ($domains as $domain) {
            foreach ($this->prefixVariants as $prefix) {
                $username = $prefix . '@' . $domain;
                $name = $prefix;
                $password = $this->generatePassword($this->userId, $mailboxIndex);

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
            
            if ($result['success'] && isset($result['uuid'])) {
                $this->saveMailboxesForDomains($order, $result, $mailboxData, $mailboxes, $shouldCompleteOrder, $providerSlug);
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

    private function saveMailboxesForDomains($order, $result, $mailboxData, $mailboxes, $completeOrder = true, $providerSlug = null)
    {
        $jobUuid = $result['uuid'];
        
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
        
        // Save OrderAutomation record
        OrderAutomation::create([
            'order_id' => $this->orderId,
            'provider_type' => $this->providerType,
            'action_type' => 'mailbox',
            'job_uuid' => $jobUuid,
            'status' => 'pending',
            'response_data' => $result['response'] ?? null,
        ]);

        // Check job status and wait for completion to get mailbox IDs
        $mailboxStatusData = null;
        $maxAttempts = 30; // Maximum 30 attempts (5 minutes if polling every 10 seconds)
        $attempt = 0;
        
        Log::channel('mailin-ai')->info('Checking mailbox job status to get mailbox IDs', [
            'action' => 'save_mailboxes_for_domains',
            'order_id' => $this->orderId,
            'job_uuid' => $jobUuid,
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
                    if ($status === 'pending' || $status === 'processing') {
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
        
        // Create a map of username -> mailbox data from API response
        $mailboxMap = [];
        if ($mailboxStatusData && isset($mailboxStatusData['data']) && is_array($mailboxStatusData['data'])) {
            foreach ($mailboxStatusData['data'] as $apiMailbox) {
                $username = $apiMailbox['username'] ?? null;
                if ($username) {
                    $mailboxMap[$username] = [
                        'mailbox_id' => $apiMailbox['id'] ?? null,
                        'domain_id' => $apiMailbox['domain_id'] ?? null,
                    ];
                }
            }
        }

        // Create OrderEmail records - don't delete existing ones (may be adding for additional domains)
        try {
            $savedCount = 0;
            foreach ($mailboxData as $mailbox) {
                try {
                    // Check if mailbox already exists (by email)
                    $existing = OrderEmail::where('order_id', $this->orderId)
                        ->where('email', $mailbox['username'])
                        ->first();
                    
                    if (!$existing) {
                        // Get mailbox ID and domain ID from API response if available
                        $mailinMailboxId = $mailboxMap[$mailbox['username']]['mailbox_id'] ?? null;
                        $mailinDomainId = $mailboxMap[$mailbox['username']]['domain_id'] ?? null;
                        
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
                        $mailinMailboxId = $mailboxMap[$mailbox['username']]['mailbox_id'] ?? null;
                        $mailinDomainId = $mailboxMap[$mailbox['username']]['domain_id'] ?? null;
                        
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

        // Only complete order if all domains have mailboxes (or if explicitly requested)
        if ($completeOrder) {
            $oldStatus = $order->status_manage_by_admin;
            $mailboxCount = count($mailboxes);
            $jobUuid = $result['uuid'];
            
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
                        'action' => 'create_mailboxes_on_order',
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
                        'action' => 'create_mailboxes_on_order',
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
                        'action' => 'create_mailboxes_on_order',
                        'order_id' => $this->orderId,
                        'error' => $e->getMessage(),
                    ]);
                }
                
                try {
                    $order->refresh();
                    $user = $order->user;
                    if ($user) {
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
            });
            
            Log::channel('mailin-ai')->info('Mailbox creation successful and order completed', [
                'action' => 'create_mailboxes_on_order',
                'order_id' => $this->orderId,
                'job_uuid' => $result['uuid'],
                'mailbox_count' => count($mailboxes),
                'order_status' => 'completed',
            ]);
        } else {
            Log::channel('mailin-ai')->info('Mailboxes created for registered domains, waiting for domain transfers', [
                'action' => 'create_mailboxes_on_order',
                'order_id' => $this->orderId,
                'mailbox_count' => count($mailboxes),
                'order_status' => 'in-progress',
            ]);
        }
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

    private function generatePassword($userId, $index = 0)
    {
        $upperCase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowerCase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*';
        
        // Use userId + index as seed for unique passwords
        mt_srand($userId + $index);
        
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
}

