<?php

namespace App\Jobs\MailinAi;

use App\Models\Order;
use App\Models\OrderAutomation;
use App\Models\SmtpProviderSplit;
use App\Services\MailinAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateMailboxesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;

    /**
     * Create a new job instance.
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::channel('mailin-ai')->info('Starting mailbox creation job', [
                'action' => 'create_mailboxes_job',
                'order_id' => $this->orderId,
            ]);

            // Load order with reorderInfo
            $order = Order::with('reorderInfo', 'plan')->find($this->orderId);

            if (!$order) {
                Log::channel('mailin-ai')->error('Order not found for mailbox creation', [
                    'action' => 'create_mailboxes_job',
                    'order_id' => $this->orderId,
                ]);
                return;
            }

            // Only process Private SMTP orders
            if ($order->plan->provider_type !== 'Private SMTP') {
                Log::channel('mailin-ai')->info('Skipping mailbox creation - not Private SMTP order', [
                    'action' => 'create_mailboxes_job',
                    'order_id' => $this->orderId,
                    'provider_type' => $order->plan->provider_type ?? 'unknown',
                ]);
                return;
            }

            $reorderInfo = $order->reorderInfo->first();

            if (!$reorderInfo) {
                Log::channel('mailin-ai')->error('ReorderInfo not found for order', [
                    'action' => 'create_mailboxes_job',
                    'order_id' => $this->orderId,
                ]);
                return;
            }

            // Extract domains
            $domains = [];
            if ($reorderInfo->domains) {
                $domains = array_filter(
                    preg_split('/[\r\n,]+/', $reorderInfo->domains),
                    function($domain) {
                        return !empty(trim($domain));
                    }
                );
                $domains = array_map('trim', $domains);
            }

            if (empty($domains)) {
                Log::channel('mailin-ai')->warning('No domains found for mailbox creation', [
                    'action' => 'create_mailboxes_job',
                    'order_id' => $this->orderId,
                ]);
                return;
            }

            // Extract prefix variants with details for name extraction
            $prefixVariants = [];
            $prefixVariantsRaw = [];
            if ($reorderInfo->prefix_variants) {
                if (is_string($reorderInfo->prefix_variants)) {
                    $decoded = json_decode($reorderInfo->prefix_variants, true);
                    if (is_array($decoded)) {
                        // Keep the associative array format like {"prefix_variant_1": "user.dev", "prefix_variant_2": "user.test"}
                        $prefixVariantsRaw = $decoded;
                    } else {
                        // Comma-separated string - convert to associative format
                        $parts = array_map('trim', explode(',', $reorderInfo->prefix_variants));
                        foreach ($parts as $index => $prefix) {
                            $prefixVariantsRaw['prefix_variant_' . ($index + 1)] = $prefix;
                        }
                    }
                } elseif (is_array($reorderInfo->prefix_variants)) {
                    $prefixVariantsRaw = $reorderInfo->prefix_variants;
                }
            }

            // Extract prefix_variants_details for first_name/last_name
            $prefixVariantsDetails = [];
            if ($reorderInfo->prefix_variants_details) {
                if (is_string($reorderInfo->prefix_variants_details)) {
                    $decoded = json_decode($reorderInfo->prefix_variants_details, true);
                    if (is_array($decoded)) {
                        $prefixVariantsDetails = $decoded;
                    }
                } elseif (is_array($reorderInfo->prefix_variants_details)) {
                    $prefixVariantsDetails = $reorderInfo->prefix_variants_details;
                }
            }

            if (empty($prefixVariantsRaw)) {
                Log::channel('mailin-ai')->warning('No prefix variants found for mailbox creation', [
                    'action' => 'create_mailboxes_job',
                    'order_id' => $this->orderId,
                ]);
                return;
            }

            // Generate mailbox list: for each domain × each prefix
            $mailboxes = [];
            $mailboxIndex = 0;

            foreach ($domains as $domain) {
                foreach ($prefixVariantsRaw as $prefixKey => $prefix) {
                    $username = $prefix . '@' . $domain;
                    
                    // Get proper name from prefix_variants_details (matching CreateMailboxesForActiveDomainsCommand logic)
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
                    
                    // Generate password using same logic as CSV export
                    // Use orderId + index to ensure uniqueness
                    $password = $this->generatePassword($this->orderId, $mailboxIndex);

                    $mailboxes[] = [
                        'username' => $username,
                        'name' => $name,
                        'password' => $password,
                    ];

                    $mailboxIndex++;
                }
            }

            if (empty($mailboxes)) {
                Log::channel('mailin-ai')->warning('No mailboxes generated', [
                    'action' => 'create_mailboxes_job',
                    'order_id' => $this->orderId,
                ]);
                return;
            }

            Log::channel('mailin-ai')->info('Generated mailbox list', [
                'action' => 'create_mailboxes_job',
                'order_id' => $this->orderId,
                'mailbox_count' => count($mailboxes),
                'domain_count' => count($domains),
                'prefix_count' => count($prefixVariants),
            ]);

            // Get active provider from split table (Mailin for now)
            $activeProvider = SmtpProviderSplit::getActiveProvider();
            $credentials = null;
            
            if ($activeProvider) {
                $credentials = $activeProvider->getCredentials();
                Log::channel('mailin-ai')->info('Using provider split credentials', [
                    'action' => 'create_mailboxes_job',
                    'order_id' => $this->orderId,
                    'provider' => $activeProvider->slug,
                    'has_credentials' => !empty($credentials),
                ]);
            } else {
                Log::channel('mailin-ai')->info('No active provider found in split table, using config fallback', [
                    'action' => 'create_mailboxes_job',
                    'order_id' => $this->orderId,
                ]);
            }

            // Call MailinAiService to create mailboxes with credentials from split table (or fallback to config)
            $mailinService = new MailinAiService($credentials);
            $result = $mailinService->createMailboxes($mailboxes);

            if ($result['success'] && isset($result['uuid'])) {
                // Save mailbox job UUID to order_automations
                OrderAutomation::create([
                    'order_id' => $this->orderId,
                    'provider_type' => $order->plan->provider_type,
                    'action_type' => 'mailbox',
                    'job_uuid' => $result['uuid'],
                    'status' => 'pending',
                    'response_data' => $result['response'] ?? null,
                ]);

                Log::channel('mailin-ai')->info('Mailbox creation job dispatched successfully', [
                    'action' => 'create_mailboxes_job',
                    'order_id' => $this->orderId,
                    'job_uuid' => $result['uuid'],
                    'mailbox_count' => count($mailboxes),
                ]);
            } else {
                throw new \Exception('Mailbox creation failed: ' . ($result['message'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mailbox creation job failed', [
                'action' => 'create_mailboxes_job',
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
                        'provider_type' => 'Private SMTP',
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]
                );
            } catch (\Exception $saveException) {
                Log::channel('mailin-ai')->error('Failed to save mailbox job error status', [
                    'action' => 'create_mailboxes_job',
                    'order_id' => $this->orderId,
                    'error' => $saveException->getMessage(),
                ]);
            }

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Generate password using same logic as CSV export
     * Based on EmailExportService::customEncrypt but with index for uniqueness
     * 
     * @param int $orderId
     * @param int $index
     * @return string
     */
    private function generatePassword($orderId, $index = 0)
    {
        $upperCase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowerCase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*';
        
        // Use orderId + index as seed for unique passwords
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
}
