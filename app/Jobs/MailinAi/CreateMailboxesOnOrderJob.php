<?php

namespace App\Jobs\MailinAi;

use App\Models\Order;
use App\Models\OrderAutomation;
use App\Models\OrderEmail;
use App\Models\Notification;
use App\Models\DomainTransfer;
use App\Services\MailinAiService;
use App\Services\SpaceshipService;
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

            // First, check which domains are already registered/active in Mailin.ai
            $registeredDomains = [];
            $unregisteredDomains = [];
            
            Log::channel('mailin-ai')->info('Checking domain registration status before mailbox creation', [
                'action' => 'create_mailboxes_on_order',
                'order_id' => $this->orderId,
                'domains_to_check' => $this->domains,
            ]);
            
            foreach ($this->domains as $domain) {
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
                        
                        // If successful, save mailboxes but don't complete order yet if unregistered domains exist
                        if ($result['success'] && isset($result['uuid'])) {
                            $this->saveMailboxesForDomains($order, $result, $mailboxData, $mailboxes, false); // false = don't complete order yet
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
            
            // If all domains are registered, proceed with normal mailbox creation for all domains
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
            
            // Get hosting platform from order
            $hostingPlatform = null;
            $spaceshipCredential = null;
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
                                // Log error but don't fail the transfer
                                Log::channel('mailin-ai')->error('Failed to update Spaceship nameservers', [
                                    'action' => 'handle_domain_transfer',
                                    'order_id' => $this->orderId,
                                    'domain' => $domain,
                                    'error' => $spaceshipException->getMessage(),
                                    'trace' => $spaceshipException->getTraceAsString(),
                                ]);
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
            
            Log::channel('mailin-ai')->info('Domain transfer process completed', [
                'action' => 'handle_domain_transfer',
                'order_id' => $this->orderId,
                'total_domains' => count($domains),
                'successful_transfers' => $successCount,
                'failed_transfers' => $failedCount,
            ]);
            
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
        // Save OrderAutomation record
        OrderAutomation::create([
            'order_id' => $this->orderId,
            'provider_type' => $this->providerType,
            'action_type' => 'mailbox',
            'job_uuid' => $result['uuid'],
            'status' => 'pending',
            'response_data' => $result['response'] ?? null,
        ]);

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
                        ]);
                        $savedCount++;
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

