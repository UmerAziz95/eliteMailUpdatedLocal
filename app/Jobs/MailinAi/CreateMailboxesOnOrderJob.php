<?php

namespace App\Jobs\MailinAi;

use App\Models\Order;
use App\Models\OrderAutomation;
use App\Models\OrderEmail;
use App\Models\Notification;
use App\Models\DomainTransfer;
use App\Services\MailinAiService;
use App\Services\SpaceshipService;
use App\Services\NamecheapService;
use App\Services\ActivityLogService;
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

            Log::channel('mailin-ai')->info('Extracted data for Mailin.ai mailbox creation', [
                'action' => 'create_mailboxes_on_order',
                'order_id' => $this->orderId,
                'domains' => $this->domains,
                'domain_count' => count($this->domains),
                'prefix_variants' => $this->prefixVariants,
                'prefix_count' => count($this->prefixVariants),
            ]);

            // Initialize Mailin.ai service
            $mailinService = new MailinAiService();

            // Authenticate (token is cached internally)
            $token = $mailinService->authenticate();
            if (!$token) {
                Log::channel('mailin-ai')->error('Failed to authenticate with Mailin.ai for mailbox creation', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                ]);
                throw new \Exception('Failed to authenticate with Mailin.ai. Please try again later.');
            }

            // First, check which domains already have mailboxes created (to avoid duplicates)
            $domainsWithMailboxes = OrderEmail::where('order_id', $this->orderId)
                ->whereNotNull('mailin_mailbox_id')
                ->get()
                ->map(function ($email) {
                    // Extract domain from email (e.g., "prefix@domain.com" -> "domain.com")
                    $parts = explode('@', $email->email);
                    return count($parts) === 2 ? $parts[1] : null;
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();
            
            Log::channel('mailin-ai')->info('Checking for existing mailboxes', [
                'action' => 'create_mailboxes_on_order',
                'order_id' => $this->orderId,
                'domains_with_existing_mailboxes' => $domainsWithMailboxes,
            ]);
            
            // Filter out domains that already have mailboxes
            $domainsToProcess = array_filter($this->domains, function ($domain) use ($domainsWithMailboxes) {
                return !in_array($domain, $domainsWithMailboxes);
            });
            
            if (empty($domainsToProcess)) {
                Log::channel('mailin-ai')->info('All domains already have mailboxes created, skipping mailbox creation', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                    'all_domains' => $this->domains,
                ]);
                return; // All mailboxes already created
            }
            
            // First, check which domains are already registered/active in Mailin.ai
            $registeredDomains = [];
            $unregisteredDomains = [];
            
            Log::channel('mailin-ai')->info('Checking domain registration status before mailbox creation', [
                'action' => 'create_mailboxes_on_order',
                'order_id' => $this->orderId,
                'domains_to_check' => $domainsToProcess,
                'domains_with_existing_mailboxes' => $domainsWithMailboxes,
            ]);
            
            foreach ($domainsToProcess as $domain) {
                try {
                    $statusResult = $mailinService->checkDomainStatus($domain);
                    if ($statusResult['success'] && isset($statusResult['status']) && $statusResult['status'] === 'active') {
                        $registeredDomains[] = $domain;
                        Log::channel('mailin-ai')->info('Domain is registered and active', [
                            'action' => 'create_mailboxes_on_order',
                            'order_id' => $this->orderId,
                            'domain' => $domain,
                        ]);
                    } else {
                        $unregisteredDomains[] = $domain;
                        Log::channel('mailin-ai')->info('Domain is not registered or not active', [
                            'action' => 'create_mailboxes_on_order',
                            'order_id' => $this->orderId,
                            'domain' => $domain,
                            'status' => $statusResult['status'] ?? 'not_found',
                        ]);
                    }
                } catch (\Exception $e) {
                    // If check fails, assume domain needs transfer
                    $unregisteredDomains[] = $domain;
                    Log::channel('mailin-ai')->warning('Failed to check domain status, will require transfer', [
                        'action' => 'create_mailboxes_on_order',
                        'order_id' => $this->orderId,
                        'domain' => $domain,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            Log::channel('mailin-ai')->info('Domain registration check completed', [
                'action' => 'create_mailboxes_on_order',
                'order_id' => $this->orderId,
                'registered_domains' => $registeredDomains,
                'unregistered_domains' => $unregisteredDomains,
            ]);
            
            // If there are unregistered domains, initiate transfer for them
            if (!empty($unregisteredDomains)) {
                Log::channel('mailin-ai')->info('Unregistered domains found, initiating transfer process', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                    'unregistered_domains' => $unregisteredDomains,
                    'registered_domains' => $registeredDomains,
                ]);
                
                // Ensure order status is in-progress (not completed)
                $oldStatus = $order->status_manage_by_admin;
                if ($oldStatus !== 'in-progress') {
                    $order->update([
                        'status_manage_by_admin' => 'in-progress',
                    ]);
                    
                    // Log the status change
                    try {
                        ActivityLogService::log(
                            'mailin_ai_order_status_in_progress',
                            "Order #{$order->id} status set to in-progress - domain transfer required",
                            $order,
                            [
                                'order_id' => $order->id,
                                'old_status' => $oldStatus,
                                'new_status' => 'in-progress',
                                'provider_type' => $this->providerType,
                                'reason' => 'Domain transfer required before mailbox creation',
                            ]
                        );
                    } catch (\Exception $e) {
                        Log::channel('mailin-ai')->warning('Failed to create activity log for status change', [
                            'action' => 'create_mailboxes_on_order',
                            'order_id' => $this->orderId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                
                // Transfer unregistered domains
                $this->handleDomainTransfer($order, $mailinService, $unregisteredDomains);
            }
            
            // Create mailboxes for registered domains immediately (if any)
            if (!empty($registeredDomains)) {
                Log::channel('mailin-ai')->info('Creating mailboxes for registered domains', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                    'registered_domains' => $registeredDomains,
                ]);
                
                // Generate mailboxes only for registered domains
                $mailboxes = [];
                $mailboxData = []; // Store mailbox data for OrderEmail creation
                $mailboxIndex = 0;

                foreach ($registeredDomains as $domain) {
                    foreach ($this->prefixVariants as $prefix) {
                        $username = $prefix . '@' . $domain;
                        $name = $prefix; // Use prefix as name
                        
                        // Generate password
                        $password = $this->generatePassword($this->userId, $mailboxIndex);

                        $mailboxes[] = [
                            'username' => $username,
                            'name' => $name,
                            'password' => $password,
                        ];
                        
                        // Store for OrderEmail creation with domain information
                        $mailboxData[] = [
                            'order_id' => $this->orderId,
                            'username' => $username,
                            'name' => $name,
                            'password' => $password,
                            'domain' => $domain,
                            'prefix' => $prefix,
                        ];

                        $mailboxIndex++;
                    }
                }
                
                if (!empty($mailboxes)) {
                    // Call Mailin.ai API to create mailboxes for registered domains
                    try {
                        $result = $mailinService->createMailboxes($mailboxes);
                        
                        // If successful, save mailboxes
                        // Complete order only if there are no unregistered domains
                        if ($result['success'] && isset($result['uuid'])) {
                            $shouldCompleteOrder = empty($unregisteredDomains);
                            $this->saveMailboxesForDomains($order, $result, $mailboxData, $mailboxes, $shouldCompleteOrder);
                        }
                    } catch (\Exception $e) {
                        Log::channel('mailin-ai')->error('Failed to create mailboxes for registered domains', [
                            'action' => 'create_mailboxes_on_order',
                            'order_id' => $this->orderId,
                            'registered_domains' => $registeredDomains,
                            'error' => $e->getMessage(),
                        ]);
                        // Don't throw - we've already initiated transfer for unregistered domains
                        // Registered domain mailboxes can be retried later
                    }
                }
            }
            
            // If we have unregistered domains, return early (transfer is in progress)
            if (!empty($unregisteredDomains)) {
                Log::channel('mailin-ai')->info('Domain transfer initiated, waiting for domains to become active', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                    'unregistered_domains' => $unregisteredDomains,
                ]);
                return; // Exit early - will retry when domains become active
            }
            
            // If all domains are registered and mailboxes were already created above, we're done
            if (!empty($registeredDomains)) {
                Log::channel('mailin-ai')->info('All mailboxes created for registered domains, order processing complete', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                ]);
                return; // Exit - mailboxes already created in the block above
            }
            
            // Fallback: If no registered domains were found but we reach here, proceed with normal mailbox creation
            // (This should rarely happen, but handles edge cases)
            // Generate mailboxes: for each domain × each prefix variant
            $mailboxes = [];
            $mailboxData = []; // Store mailbox data for OrderEmail creation
            $mailboxIndex = 0;

            foreach ($this->domains as $domain) {
                foreach ($this->prefixVariants as $prefix) {
                    $username = $prefix . '@' . $domain;
                    $name = $prefix; // Use prefix as name
                    
                    // Generate password
                    $password = $this->generatePassword($this->userId, $mailboxIndex);

                    $mailboxes[] = [
                        'username' => $username,
                        'name' => $name,
                        'password' => $password,
                    ];
                    
                    // Store for OrderEmail creation with domain information
                    $mailboxData[] = [
                        'order_id' => $this->orderId,
                        'username' => $username,
                        'name' => $name,
                        'password' => $password,
                        'domain' => $domain,
                        'prefix' => $prefix,
                    ];

                    $mailboxIndex++;
                }
            }

            if (empty($mailboxes)) {
                Log::channel('mailin-ai')->warning('No mailboxes generated', [
                    'action' => 'create_mailboxes_on_order',
                    'order_id' => $this->orderId,
                ]);
                return;
            }

            Log::channel('mailin-ai')->info('Generated mailbox list for all domains', [
                'action' => 'create_mailboxes_on_order',
                'order_id' => $this->orderId,
                'mailbox_count' => count($mailboxes),
            ]);

            // Call Mailin.ai API to create mailboxes
            $result = $mailinService->createMailboxes($mailboxes);

            if ($result['success'] && isset($result['uuid'])) {
                // Save mailboxes and complete order (all domains are registered)
                $this->saveMailboxesForDomains($order, $result, $mailboxData, $mailboxes, true); // true = complete order
            } else {
                throw new \Exception('Mailbox creation failed: ' . ($result['message'] ?? 'Unknown error'));
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
     * @return void
     */
    private function handleDomainTransfer($order, $mailinService, $domainsToTransfer = null)
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
            
            // Transfer each domain
            foreach ($domains as $index => $domain) {
                try {
                    Log::channel('mailin-ai')->info('Initiating domain transfer', [
                        'action' => 'handle_domain_transfer',
                        'order_id' => $this->orderId,
                        'domain_index' => $index + 1,
                        'total_domains' => count($domains),
                        'domain' => $domain,
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
                    Log::channel('mailin-ai')->error('Failed to transfer domain', [
                        'action' => 'handle_domain_transfer',
                        'order_id' => $this->orderId,
                        'domain' => $domain,
                        'error' => $transferException->getMessage(),
                    ]);
                    
                    // Save failed transfer record
                    try {
                        DomainTransfer::create([
                            'order_id' => $this->orderId,
                            'domain_name' => $domain,
                            'status' => 'failed',
                            'error_message' => $transferException->getMessage(),
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
            
            // Log transfer summary
            $successCount = DomainTransfer::where('order_id', $this->orderId)
                ->where('status', 'pending')
                ->whereIn('domain_name', $domains)
                ->count();
            $failedCount = DomainTransfer::where('order_id', $this->orderId)
                ->where('status', 'failed')
                ->whereIn('domain_name', $domains)
                ->count();
            
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
    private function saveMailboxesForDomains($order, $result, $mailboxData, $mailboxes, $completeOrder = true)
    {
        $jobUuid = $result['uuid'];
        $mailinService = new MailinAiService();
        
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

