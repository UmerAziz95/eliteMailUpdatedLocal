<?php

namespace App\Services;

use App\Models\PoolOrder;
use App\Models\Pool;
use App\Models\User;
use App\Models\Configuration;
use App\Services\ActivityLogService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use ChargeBee\ChargeBee\Environment;
use ChargeBee\ChargeBee\Models\Subscription;

class PoolOrderCancelledService
{
    /**
     * Safely get value from array-like data
     * 
     * @param mixed $data The data to extract from
     * @param string $key The key to get
     * @param mixed $default Default value if not found
     * @return mixed
     */
    private function safeGet($data, $key, $default = null)
    {
        if (!is_array($data)) {
            return $default;
        }

        return $data[$key] ?? $default;
    }

    /**
     * Safely check if data is a valid array with content
     * 
     * @param mixed $data
     * @return bool
     */
    private function isValidArray($data)
    {
        return is_array($data) && !empty($data);
    }

    /**
     * Cancel a pool order subscription
     * 
     * @param string $poolOrderId The pool order ID
     * @param int $userId The user ID
     * @param string|null $reason Cancellation reason
     * @return array Response array with success status and message
     */
    public function cancelSubscription($poolOrderId, $userId, $reason = null)
    {
        Log::info("=== POOL ORDER CANCELLATION START ===", [
            'pool_order_id' => $poolOrderId,
            'user_id' => $userId,
            'reason' => $reason
        ]);

        // Step 1: Validate pool order
        try {
            $poolOrder = PoolOrder::where('id', $poolOrderId)
                ->where('user_id', $userId)
                ->firstOrFail();

            Log::info("Pool order found", [
                'id' => $poolOrder->id,
                'status' => $poolOrder->status,
                'status_manage_by_admin' => $poolOrder->status_manage_by_admin,
                'chargebee_subscription_id' => $poolOrder->chargebee_subscription_id
            ]);

            // Check if already cancelled
            if ($poolOrder->status === 'cancelled' || $poolOrder->status_manage_by_admin === 'cancelled') {
                Log::warning("Pool order already cancelled", ['pool_order_id' => $poolOrderId]);
                return [
                    'success' => false,
                    'message' => 'This subscription is already cancelled.'
                ];
            }

            // Validate if subscription ID exists
            if (!$poolOrder->chargebee_subscription_id) {
                Log::warning("No ChargeBee subscription ID found", ['pool_order_id' => $poolOrderId]);
                return [
                    'success' => false,
                    'message' => 'No ChargeBee subscription found for this order.'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Pool order validation failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'pool_order_id' => $poolOrderId
            ]);

            return [
                'success' => false,
                'message' => 'Failed to find pool order: ' . $e->getMessage()
            ];
        }

        // Step 2: Cancel subscription in ChargeBee FIRST
        try {
            Log::info("Starting ChargeBee cancellation", [
                'subscription_id' => $poolOrder->chargebee_subscription_id,
                'chargebee_site' => config('services.chargebee.site'),
                'api_key_configured' => !empty(config('services.chargebee.api_key'))
            ]);

            // Configure ChargeBee environment
            Environment::configure(
                config('services.chargebee.site'),
                config('services.chargebee.api_key')
            );

            // First check if already cancelled in ChargeBee
            Log::info("Retrieving subscription from ChargeBee", [
                'subscription_id' => $poolOrder->chargebee_subscription_id
            ]);

            $chargebeeSubscription = Subscription::retrieve($poolOrder->chargebee_subscription_id);
            $currentStatus = $chargebeeSubscription->subscription()->status;
            $isAlreadyCancelled = $currentStatus === 'cancelled';

            // Log detailed subscription information
            $subscriptionData = [
                'subscription_id' => $poolOrder->chargebee_subscription_id,
                'status' => $currentStatus,
                'is_already_cancelled' => $isAlreadyCancelled,
                'has_subscription_items' => !empty($chargebeeSubscription->subscription()->subscriptionItems),
                'customer_id' => $chargebeeSubscription->subscription()->customerId ?? 'N/A',
                'currency_code' => $chargebeeSubscription->subscription()->currencyCode ?? 'N/A',
                'billing_period' => $chargebeeSubscription->subscription()->billingPeriod ?? 'N/A',
                'billing_period_unit' => $chargebeeSubscription->subscription()->billingPeriodUnit ?? 'N/A'
            ];

            if (!empty($chargebeeSubscription->subscription()->subscriptionItems)) {
                $subscriptionData['subscription_items_count'] = count($chargebeeSubscription->subscription()->subscriptionItems);
                $subscriptionData['first_item_id'] = $chargebeeSubscription->subscription()->subscriptionItems[0]->itemPriceId ?? 'N/A';
            }

            Log::info("ChargeBee subscription retrieved successfully", $subscriptionData);

            // Only call cancel API if not already cancelled
            if (!$isAlreadyCancelled) {
                Log::info("Preparing to cancel subscription - Product Catalog 2.0");

                // Build cancellation parameters
                $cancelParams = [
                    "end_of_term" => false,
                    "unbilled_charges_option" => "delete"
                ];

                // Only add credit option if subscription has a current term
                if (!empty($chargebeeSubscription->subscription()->currentTermEnd)) {
                    $cancelParams["credit_option_for_current_term_charges"] = "none";
                }

                Log::info("Cancellation parameters prepared", [
                    'params' => $cancelParams,
                    'subscription_has_current_term' => !empty($chargebeeSubscription->subscription()->currentTermEnd)
                ]);

                try {
                    Log::info("Calling ChargeBee cancelForItems API");

                    $result = Subscription::cancelForItems(
                        $poolOrder->chargebee_subscription_id,
                        $cancelParams
                    );

                    Log::info('ChargeBee subscription cancelled successfully', [
                        'subscription_id' => $poolOrder->chargebee_subscription_id,
                        'new_status' => $result->subscription()->status,
                        'cancelled_at' => $result->subscription()->cancelledAt ?? 'not set',
                        'current_term_end' => $result->subscription()->currentTermEnd ?? 'N/A'
                    ]);

                } catch (\ChargeBee\ChargeBee\Exceptions\APIError $apiError) {
                    // Detailed API error logging
                    Log::error('ChargeBee API Error during cancel call', [
                        'error_message' => $apiError->getMessage(),
                        'api_error_code' => $apiError->getApiErrorCode() ?? 'unknown',
                        'http_status_code' => $apiError->getHttpStatusCode() ?? 'unknown',
                        'subscription_id' => $poolOrder->chargebee_subscription_id,
                        'params_sent' => $cancelParams
                    ]);

                    // If internal error, try simpler parameters
                    if ($apiError->getApiErrorCode() === 'internal_error') {
                        Log::warning("Internal error detected, trying simplified cancellation");

                        $simplifiedParams = [
                            "end_of_term" => false
                        ];

                        Log::info("Retrying with simplified parameters", ['params' => $simplifiedParams]);

                        $result = Subscription::cancelForItems(
                            $poolOrder->chargebee_subscription_id,
                            $simplifiedParams
                        );

                        Log::info('Subscription cancelled with simplified parameters', [
                            'new_status' => $result->subscription()->status
                        ]);
                    } else {
                        throw $apiError;
                    }
                } catch (\Exception $cancelException) {
                    Log::error('Unexpected error during cancellation', [
                        'error' => $cancelException->getMessage(),
                        'error_class' => get_class($cancelException),
                        'trace' => $cancelException->getTraceAsString()
                    ]);
                    throw $cancelException;
                }
            } else {
                $result = $chargebeeSubscription;
                Log::info('Subscription already cancelled in ChargeBee, using existing status');
            }

            $subscription = $result->subscription();

            // Verify cancellation status
            if ($subscription->status !== 'cancelled') {
                throw new \Exception('ChargeBee subscription status is not cancelled: ' . $subscription->status);
            }

        } catch (\ChargeBee\ChargeBee\Exceptions\APIError $e) {
            // ChargeBee specific API error
            Log::error('ChargeBee API Error during cancellation', [
                'error' => $e->getMessage(),
                'api_error_code' => $e->getApiErrorCode() ?? 'unknown',
                'http_status_code' => $e->getHttpStatusCode() ?? 'unknown',
                'subscription_id' => $poolOrder->chargebee_subscription_id,
                'pool_order_id' => $poolOrderId,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            $errorMessage = $e->getMessage();
            if ($e->getApiErrorCode()) {
                $errorMessage .= ' (Code: ' . $e->getApiErrorCode() . ')';
            }

            return [
                'success' => false,
                'message' => 'Failed to cancel subscription in ChargeBee: ' . $errorMessage
            ];
        } catch (\Exception $e) {
            Log::error('ChargeBee cancellation failed', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'subscription_id' => $poolOrder->chargebee_subscription_id,
                'pool_order_id' => $poolOrderId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to cancel subscription in ChargeBee: ' . $e->getMessage()
            ];
        }

        // Step 3: Update local system ONLY after ChargeBee cancellation succeeds
        try {
            Log::info("Updating local pool order status");

            $user = User::find($userId);

            // Update pool order status in database
            $poolOrder->status = 'cancelled';
            $poolOrder->status_manage_by_admin = 'cancelled';
            $poolOrder->cancelled_at = now();
            $poolOrder->reason = $reason ?? 'Customer requested cancellation';

            // Update meta data
            $meta = is_array($poolOrder->meta) ? $poolOrder->meta : [];
            $meta['cancellation'] = [
                'cancelled_at' => now()->toDateTimeString(),
                'cancelled_by' => $userId,
                'cancelled_by_name' => $user ? $user->name : 'Unknown',
                'reason' => $poolOrder->reason,
                'chargebee_status' => $subscription->status ?? 'cancelled'
            ];
            $poolOrder->meta = $meta;

            $poolOrder->save();

            Log::info('Pool order status updated in database', [
                'pool_order_id' => $poolOrder->id,
                'status' => $poolOrder->status,
                'status_manage_by_admin' => $poolOrder->status_manage_by_admin,
                'cancelled_at' => $poolOrder->cancelled_at
            ]);

            // Log activity
            try {
                ActivityLogService::log(
                    'customer-pool-subscription-cancelled',
                    'Pool subscription cancelled successfully: ' . $poolOrder->id,
                    $poolOrder,
                    [
                        'user_id' => $userId,
                        'pool_order_id' => $poolOrder->id,
                        'status' => $poolOrder->status,
                        'chargebee_subscription_id' => $poolOrder->chargebee_subscription_id,
                    ]
                );
                Log::info('Activity logged successfully');
            } catch (\Exception $e) {
                Log::warning('Failed to log activity (non-critical)', ['error' => $e->getMessage()]);
            }

        } catch (\Exception $e) {
            Log::error('Local system update failed after ChargeBee cancellation', [
                'error' => $e->getMessage(),
                'pool_order_id' => $poolOrderId,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return [
                'success' => false,
                'message' => 'Subscription cancelled in ChargeBee but failed to update local system: ' . $e->getMessage()
            ];
        }

        // Step 4: Free domains (handled after cancellation migration task completion)
        Log::info('Domain freeing deferred to cancellation migration task completion', [
            'pool_order_id' => $poolOrder->id
        ]);

        Log::info("=== POOL ORDER CANCELLATION COMPLETED SUCCESSFULLY ===", [
            'pool_order_id' => $poolOrder->id
        ]);

        return [
            'success' => true,
            'message' => 'Pool subscription cancelled successfully.',
            'data' => [
                'pool_order_id' => $poolOrder->id,
                'status' => $poolOrder->status,
                'cancelled_at' => $poolOrder->cancelled_at->format('Y-m-d H:i:s')
            ]
        ];
    }

    /**
     * Free domains when subscription is cancelled
     * 
     * @param array $domains Array of domain data from pool order
     * @return array Stats about freed domains
     */
    private function freeDomains($domains)
    {
        $stats = [
            'freed' => 0,
            'skipped' => 0,
            'total' => 0
        ];

        // Validate input
        if (!$this->isValidArray($domains)) {
            Log::warning('freeDomains: Invalid domains parameter', [
                'type' => gettype($domains),
                'is_array' => is_array($domains)
            ]);
            return $stats;
        }

        $stats['total'] = count($domains);
        Log::info("freeDomains: Processing {$stats['total']} domain(s)");

        // Determine warming period for cancellations (in days)
        $cancellationWarmingDays = (int) Configuration::get(
            'CANCELLATION_POOL_WARMING_PERIOD',
            Configuration::get('POOL_WARMING_PERIOD', 21)
        );
        if ($cancellationWarmingDays < 0) {
            $cancellationWarmingDays = 0;
        }

        $warmingStartDate = Carbon::now();
        $warmingDates = [
            'start_date' => $warmingStartDate->format('Y-m-d'),
            'end_date' => $warmingStartDate->copy()->addDays($cancellationWarmingDays)->format('Y-m-d')
        ];

        foreach ($domains as $index => $domain) {
            try {
                // Validate domain entry
                if (!is_array($domain)) {
                    $stats['skipped']++;
                    Log::warning('freeDomains: Domain entry is not array', [
                        'index' => $index,
                        'type' => gettype($domain)
                    ]);
                    continue;
                }

                // Extract required fields safely
                $poolId = $this->safeGet($domain, 'pool_id');
                $domainId = $this->safeGet($domain, 'domain_id');
                $domainName = $this->safeGet($domain, 'domain_name', 'unknown');
                $selectedPrefixes = $this->safeGet($domain, 'selected_prefixes', []);

                if (!$poolId || !$domainId) {
                    $stats['skipped']++;
                    Log::warning('freeDomains: Missing required fields', [
                        'index' => $index,
                        'pool_id' => $poolId,
                        'domain_id' => $domainId
                    ]);
                    continue;
                }

                Log::debug("freeDomains: Processing domain", [
                    'index' => $index,
                    'pool_id' => $poolId,
                    'domain_id' => $domainId,
                    'domain_name' => $domainName
                ]);

                // Find the pool
                $pool = Pool::find($poolId);
                if (!$pool) {
                    $stats['skipped']++;
                    Log::warning('freeDomains: Pool not found', ['pool_id' => $poolId]);
                    continue;
                }

                // Check if pool has domains
                if (!$pool->domains) {
                    $stats['skipped']++;
                    Log::warning('freeDomains: Pool has no domains', ['pool_id' => $poolId]);
                    continue;
                }

                // Decode pool domains safely
                $poolDomains = $this->decodePoolDomains($pool->domains, $poolId);
                if (!$this->isValidArray($poolDomains)) {
                    $stats['skipped']++;
                    continue; // Error already logged in decodePoolDomains
                }

                // Process pool domains - only update prefixes that were selected in the order
                $result = $this->updatePoolDomainStatus($poolDomains, $domainId, $poolId, $domainName, $warmingDates, $selectedPrefixes);

                if ($result['changed']) {
                    // Save updated domains
                    if ($this->savePoolDomains($pool, $result['domains'])) {
                        $stats['freed']++;
                        Log::info('freeDomains: Domain freed successfully', [
                            'pool_id' => $poolId,
                            'domain_id' => $domainId,
                            'domain_name' => $domainName
                        ]);
                    } else {
                        $stats['skipped']++;
                    }
                } else {
                    $stats['skipped']++;
                    Log::debug('freeDomains: Domain not found in pool', [
                        'pool_id' => $poolId,
                        'domain_id' => $domainId
                    ]);
                }

            } catch (\Exception $e) {
                $stats['skipped']++;
                Log::error('freeDomains: Exception processing domain', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
        }

        Log::info("freeDomains: Process completed", $stats);
        return $stats;
    }

    /**
     * Public helper to free domains for a given pool order
     */
    public function freeDomainsFromPoolOrder(\App\Models\PoolOrder $poolOrder): array
    {
        $poolOrder->refresh();
        $domains = $poolOrder->domains;

        if (is_string($domains)) {
            try {
                $decoded = json_decode($domains, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $domains = $decoded;
                }
            } catch (\Exception $e) {
                Log::warning('freeDomainsFromPoolOrder: Failed to decode domains JSON', [
                    'pool_order_id' => $poolOrder->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (!$this->isValidArray($domains)) {
            Log::warning('freeDomainsFromPoolOrder: No valid domains to free', [
                'pool_order_id' => $poolOrder->id
            ]);
            return ['freed' => 0, 'skipped' => 0, 'total' => 0];
        }

        $freed = $this->freeDomains($domains);

        Log::info('freeDomainsFromPoolOrder: Domain freeing completed', [
            'pool_order_id' => $poolOrder->id,
            'freed_count' => $freed['freed'] ?? 0,
            'skipped_count' => $freed['skipped'] ?? 0,
            'total' => $freed['total'] ?? 0
        ]);

        return $freed;
    }

    /**
     * Decode pool domains from JSON or array
     */
    private function decodePoolDomains($domains, $poolId)
    {
        if (is_array($domains)) {
            return $domains;
        }

        if (is_string($domains)) {
            try {
                $decoded = json_decode($domains, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('freeDomains: JSON decode error', [
                        'pool_id' => $poolId,
                        'error' => json_last_error_msg()
                    ]);
                    return null;
                }

                return $decoded;
            } catch (\Exception $e) {
                Log::error('freeDomains: Exception decoding JSON', [
                    'pool_id' => $poolId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }

        Log::error('freeDomains: Invalid domains type', [
            'pool_id' => $poolId,
            'type' => gettype($domains)
        ]);
        return null;
    }

    /**
     * Update domain status in pool domains array
     * Only updates prefix_statuses that match the selected_prefixes from the pool order
     * 
     * @param array $poolDomains All domains from the pool
     * @param mixed $domainId The domain ID to update
     * @param mixed $poolId The pool ID
     * @param string $domainName The domain name for logging
     * @param array|null $warmingDates Warming period dates
     * @param array $selectedPrefixes The prefixes that were selected in the pool order (keys are prefix variant names)
     */
    private function updatePoolDomainStatus($poolDomains, $domainId, $poolId, $domainName, array $warmingDates = null, array $selectedPrefixes = [])
    {
        $updatedDomains = [];
        $hasChanges = false;

        // Get the prefix keys that were actually selected in the order
        $selectedPrefixKeys = is_array($selectedPrefixes) ? array_keys($selectedPrefixes) : [];

        foreach ($poolDomains as $poolDomain) {
            // Preserve non-array entries as-is
            if (!is_array($poolDomain)) {
                $updatedDomains[] = $poolDomain;
                continue;
            }

            // Get pool domain ID safely
            $poolDomainId = $this->safeGet($poolDomain, 'id') ?? $this->safeGet($poolDomain, 'domain_id');

            // Check if this is the domain we're looking for
            if ($poolDomainId && $poolDomainId == $domainId) {
                $previousStatus = $this->safeGet($poolDomain, 'status', 'unknown');
                $previousIsUsed = $this->safeGet($poolDomain, 'is_used', false);
                $updatedPrefixCount = 0;

                // Handle prefix_statuses if present - only update selected prefixes
                if (isset($poolDomain['prefix_statuses']) && is_array($poolDomain['prefix_statuses'])) {
                    foreach ($poolDomain['prefix_statuses'] as $key => $statusData) {
                        if (is_array($statusData)) {
                            // Only update if this prefix was selected in the order
                            // If selectedPrefixKeys is empty, update all (legacy behavior fallback)
                            if (empty($selectedPrefixKeys) || in_array($key, $selectedPrefixKeys)) {
                                $poolDomain['prefix_statuses'][$key]['status'] = 'warming';
                                if ($warmingDates) {
                                    $poolDomain['prefix_statuses'][$key]['start_date'] = $warmingDates['start_date'];
                                    $poolDomain['prefix_statuses'][$key]['end_date'] = $warmingDates['end_date'];
                                }
                                $updatedPrefixCount++;
                            }
                        }
                    }

                    Log::info('freeDomains: Updated prefix_statuses for domain', [
                        'domain_id' => $domainId,
                        'total_prefixes' => count($poolDomain['prefix_statuses']),
                        'selected_prefixes' => $selectedPrefixKeys,
                        'updated_count' => $updatedPrefixCount
                    ]);
                }

                // Only update domain-level status if we actually updated some prefixes
                // or if there are no prefix_statuses (legacy format)
                if ($updatedPrefixCount > 0 || !isset($poolDomain['prefix_statuses'])) {
                    // For legacy format without prefix_statuses
                    if (!isset($poolDomain['prefix_statuses'])) {
                        $poolDomain['status'] = 'warming';
                        if ($warmingDates) {
                            $poolDomain['start_date'] = $warmingDates['start_date'];
                            $poolDomain['end_date'] = $warmingDates['end_date'];
                        }
                    }

                    $hasChanges = true;

                    Log::info('freeDomains: Domain status updated', [
                        'domain_id' => $domainId,
                        'pool_id' => $poolId,
                        'domain_name' => $this->safeGet($poolDomain, 'name', $domainName),
                        'previous_status' => $previousStatus,
                        'new_status' => 'warming',
                        'previous_is_used' => $previousIsUsed,
                        'selected_prefixes' => $selectedPrefixKeys,
                        'updated_prefix_count' => $updatedPrefixCount
                    ]);
                }
            }

            $updatedDomains[] = $poolDomain;
        }

        return [
            'domains' => $updatedDomains,
            'changed' => $hasChanges
        ];
    }

    /**
     * Save updated domains to pool
     */
    private function savePoolDomains($pool, $domains)
    {
        try {
            DB::table('pools')
                ->where('id', $pool->id)
                ->update([
                    'domains' => json_encode($domains),
                    'updated_at' => now()
                ]);

            Log::debug('freeDomains: Pool updated in database', [
                'pool_id' => $pool->id,
                'domains_count' => count($domains)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('freeDomains: Database update failed', [
                'pool_id' => $pool->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }
}
