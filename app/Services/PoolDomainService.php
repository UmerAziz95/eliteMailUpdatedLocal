<?php

namespace App\Services;

use App\Models\Pool;
use App\Models\PoolOrder;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PoolDomainService
{
    private $cacheTime = 300; // 5 minutes
    private $chunkSize = 100; // Process records in chunks

    /**
     * Get aggregated pool domains data - optimized for performance with caching
     */

    public function getPoolDomainsData($useCache = true, $userId = null, $poolId = null, $providerType = null)
    {
        if (!$useCache) {
            return $this->fetchPoolDomainsData($userId, $poolId, $providerType);
        }

        // Use specific cache keys based on filters to reduce cache size
        $cacheKey = $this->buildCacheKey($userId, $poolId, $providerType);

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($userId, $poolId, $providerType) {
            return $this->fetchPoolDomainsData($userId, $poolId, $providerType);
        });
    }

    /**
     * Build cache key based on filters
     */
    private function buildCacheKey($userId = null, $poolId = null, $providerType = null)
    {
        $key = 'pool_domains';
        if ($userId) {
            $key .= "_user_{$userId}";
        }
        if ($poolId) {
            $key .= "_pool_{$poolId}";
        }
        if ($providerType) {
            $key .= "_provider_{$providerType}";
        }
        return $key;
    }

    /**
     * Internal method to fetch pool domains data with proper error handling and chunking
     */
    private function fetchPoolDomainsData($userId = null, $poolId = null, $providerType = null)
    {
        try {
            $results = [];
            $poolOrdersByDomain = [];

            // Build queries with optional filters
            $poolQuery = Pool::with([
                'user' => function ($query) {
                    $query->select('id', 'name', 'email');
                },
                'smtpProvider' => function ($query) {
                    $query->select('id', 'name', 'url');
                }
            ])
                ->select('id', 'user_id', 'domains', 'prefix_variants', 'provider_type', 'smtp_provider_id', 'smtp_provider_url')
                ->whereNotNull('domains')
                ->whereNotNull('purchase_date')
                ->whereRaw('DATE_ADD(purchase_date, INTERVAL 356 DAY) >= CURDATE()');

            $poolOrderQuery = PoolOrder::with([
                'user' => function ($query) {
                    $query->select('id', 'name', 'email');
                }
            ])
                ->select('id', 'user_id', 'domains', 'status', 'status_manage_by_admin')
                // status_manage_by_admin not equal to 'cancelled' to reduce unnecessary data
                ->where('status_manage_by_admin', '!=', 'cancelled')
                ->whereNotNull('domains');

            // Apply filters if provided
            if ($userId) {
                $poolQuery->where('user_id', (int) $userId);
                $poolOrderQuery->where('user_id', (int) $userId);
            }

            if ($poolId) {
                $poolQuery->where('id', (int) $poolId);
            }

            if ($providerType) {
                $poolQuery->where('provider_type', $providerType);
            }

            // First, process pool orders to create lookup map
            $poolOrderQuery->chunk($this->chunkSize, function ($poolOrders) use (&$poolOrdersByDomain, $poolId) {
                foreach ($poolOrders as $poolOrder) {
                    $this->processPoolOrderForLookup($poolOrder, $poolOrdersByDomain, $poolId);
                }
            });

            // Then process pools with chunking to reduce memory usage
            $poolQuery->chunk($this->chunkSize, function ($pools) use (&$results, $poolOrdersByDomain) {
                foreach ($pools as $pool) {
                    $this->processPoolDomains($pool, $poolOrdersByDomain, $results);
                }
            });

            return $results;

        } catch (Exception $e) {
            Log::error('Error fetching pool domains data: ' . $e->getMessage(), [
                'userId' => $userId,
                'poolId' => $poolId,
                'trace' => $e->getTraceAsString()
            ]);

            // Return empty array on error to prevent breaking the UI
            return [];
        }
    }

    /**
     * Process pool order for lookup map creation
     */
    private function processPoolOrderForLookup($poolOrder, &$poolOrdersByDomain, $poolId = null)
    {
        if (!is_array($poolOrder->domains)) {
            return;
        }

        foreach ($poolOrder->domains as $orderDomain) {
            // Normalize domain keys for consistency
            $domainId = $orderDomain['domain_id'] ?? $orderDomain['id'] ?? null;
            $poolIdFromDomain = $orderDomain['pool_id'] ?? null;

            // When filtering by pool, only include matching domains
            if ($poolId && (int) $poolIdFromDomain !== (int) $poolId) {
                continue;
            }

            if ($domainId && $poolIdFromDomain) {
                $orderInfo = [
                    'id' => (int) $poolOrder->id,
                    'user' => $poolOrder->user ?? $this->createDefaultUser(),
                    'per_inbox' => (int) ($orderDomain['per_inbox'] ?? 1),
                    'status' => $poolOrder->status ?? 'unknown',
                    'admin_status' => $poolOrder->status_manage_by_admin ?? 'unknown',
                ];

                $hasGranular = false;

                // If domain has selected_prefixes, map each selected prefix to this order
                if (isset($orderDomain['selected_prefixes']) && is_array($orderDomain['selected_prefixes'])) {
                    foreach (array_keys($orderDomain['selected_prefixes']) as $prefixKey) {
                        $granularKey = $domainId . '_' . $poolIdFromDomain . '_' . $prefixKey;
                        $poolOrdersByDomain[$granularKey] = $orderInfo;
                    }
                    $hasGranular = true;
                } elseif (isset($orderDomain['prefix_statuses']) && is_array($orderDomain['prefix_statuses'])) {
                    // Fallback: if selected_prefixes missing but prefix_statuses exists (legacy/migration edge case)
                    // We map all status keys as a best guess, but this is what caused the bug if multiple orders share domain.
                    // Ideally we prefer selected_prefixes.
                    foreach (array_keys($orderDomain['prefix_statuses']) as $prefixKey) {
                        $granularKey = $domainId . '_' . $poolIdFromDomain . '_' . $prefixKey;
                        // Only set if not already set (priority to first processed? Or maybe last? 
                        // With selected_prefixes it's explicit. Without it, it's ambiguous.
                        // Let's rely on this only if invalid structure.)
                        if (!isset($poolOrdersByDomain[$granularKey])) {
                            $poolOrdersByDomain[$granularKey] = $orderInfo;
                        }
                    }
                    $hasGranular = true;
                }

                // Only set generic key for fallback or legacy handling IF no granular keys were set
                // This prevents ambiguous matches where multiple orders share a domain but have distinct prefixes.
                if (!$hasGranular) {
                    $lookupKey = $domainId . '_' . $poolIdFromDomain;
                    $poolOrdersByDomain[$lookupKey] = $orderInfo;
                }
            }
        }
    }

    /**
     * Process individual pool domains - expands each domain into multiple rows per prefix variant
     */
    private function processPoolDomains($pool, $poolOrdersByDomain, &$results)
    {
        // No need for manual JSON decode due to model casts
        $poolDomains = $pool->domains;
        if (!is_array($poolDomains)) {
            return;
        }

        $poolPrefixes = is_array($pool->prefix_variants) ? $pool->prefix_variants : [];

        foreach ($poolDomains as $domain) {
            // Normalize domain keys for consistency  
            $domainId = $domain['id'] ?? $domain['domain_id'] ?? null;
            if (!$domainId)
                continue;

            $domainName = $domain['name'] ?? $domain['domain_name'] ?? null;
            $prefixStatuses = $domain['prefix_statuses'] ?? null;

            // Base lookup key (generic)
            $baseLookupKey = $domainId . '_' . $pool->id;

            // If domain has prefix_statuses, create a row for each prefix variant
            if ($prefixStatuses && is_array($prefixStatuses) && count($prefixStatuses) > 0) {
                foreach ($prefixStatuses as $prefixKey => $prefixData) {
                    // Try granular lookup first, then fallback to base
                    $granularKey = $baseLookupKey . '_' . $prefixKey;
                    $poolOrderInfo = $poolOrdersByDomain[$granularKey] ?? $poolOrdersByDomain[$baseLookupKey] ?? null;

                    // Set customer and order info
                    if ($poolOrderInfo) {
                        $customer = $poolOrderInfo['user'];
                        $poolOrderId = $poolOrderInfo['id'];
                        $perInbox = $poolOrderInfo['per_inbox'];
                        $poolOrderStatus = $poolOrderInfo['status'];
                        $poolOrderAdminStatus = $poolOrderInfo['admin_status'];
                    } else {
                        $customer = $pool->user ?? $this->createDefaultUser();
                        $poolOrderId = null;
                        $perInbox = (int) ($domain['available_inboxes'] ?? 0);
                        $poolOrderStatus = 'no_order';
                        $poolOrderAdminStatus = 'no_order';
                    }

                    // Extract prefix number from key (e.g., "prefix_variant_1" -> 1)
                    $prefixNumber = (int) preg_replace('/\D/', '', $prefixKey);
                    $prefixValue = $poolPrefixes[$prefixKey] ?? $poolPrefixes["prefix_variant_{$prefixNumber}"] ?? "Prefix {$prefixNumber}";

                    $status = $prefixData['status'] ?? 'unknown';

                    // Fix: If assigned to an order (Pool Order Exists) and status is 'available', it should be 'in-progress'
                    if ($poolOrderInfo && $status === 'available') {
                        $status = 'in-progress';
                    }

                    // Get SMTP provider URL if available
                    $smtpProviderUrl = null;
                    if ($pool->provider_type === 'SMTP' || $pool->provider_type === 'Private SMTP') {
                        if ($pool->smtpProvider && $pool->smtpProvider->url) {
                            $smtpProviderUrl = $pool->smtpProvider->url;
                        } elseif ($pool->smtp_provider_url) {
                            $smtpProviderUrl = $pool->smtp_provider_url;
                        }
                    }

                    // Construct email address (same format as frontend: prefix@domain)
                    $emailAccount = $prefixValue && $domainName ? $prefixValue . '@' . $domainName : '';

                    // Calculate days_remaining when end_date exists (for warming and used status display)
                    $daysRemaining = null;
                    if (isset($prefixData['end_date']) && !empty($prefixData['end_date'])) {
                        try {
                            $endDateStr = $prefixData['end_date'];
                            // Handle different date formats
                            if (is_string($endDateStr)) {
                                // Try Y-m-d format first (most common)
                                try {
                                    $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', $endDateStr);
                                } catch (\Exception $e) {
                                    // Fallback to Carbon parse for other formats
                                    $endDate = \Carbon\Carbon::parse($endDateStr);
                                }
                            } else {
                                $endDate = \Carbon\Carbon::parse($endDateStr);
                            }
                            $today = \Carbon\Carbon::today();
                            $daysRemaining = $today->diffInDays($endDate, false); // false allows negative values
                        } catch (\Exception $e) {
                            \Log::warning('Failed to calculate days_remaining', [
                                'end_date' => $prefixData['end_date'] ?? null,
                                'status' => $status,
                                'pool_id' => $pool->id,
                                'domain_id' => $domainId,
                                'prefix_key' => $prefixKey,
                                'error' => $e->getMessage()
                            ]);
                            $daysRemaining = null;
                        }
                    }

                    $results[] = [
                        'customer_name' => $customer ? $customer->name : 'Unknown',
                        'customer_email' => $customer ? $customer->email : 'unknown@example.com',
                        'domain_id' => $domainId,
                        'pool_id' => (int) $pool->id,
                        // 'pool_order_id' => $poolOrderId,
                        'pool_order_id' => $status !== 'available' ? $poolOrderId : null,
                        'domain_name' => $domainName ?? 'Unknown Domain',
                        'prefix_key' => $prefixKey,
                        'prefix_value' => $prefixValue,
                        'prefix_number' => $prefixNumber,
                        'status' => $status,
                        'start_date' => $prefixData['start_date'] ?? null,
                        'end_date' => $prefixData['end_date'] ?? null,
                        'days_remaining' => $daysRemaining, // Days remaining for warming status
                        'prefixes' => $poolPrefixes,
                        'per_inbox' => $perInbox,
                        'pool_order_status' => $poolOrderStatus,
                        'pool_order_admin_status' => $poolOrderAdminStatus,
                        'is_used' => (bool) ($domain['is_used'] ?? false),
                        'created_by' => $pool->user ? $pool->user->name : 'Unknown',
                        'provider_type' => $pool->provider_type ?? null,
                        'smtp_provider_url' => $smtpProviderUrl,
                        'email_account' => $emailAccount, // Add email_account field for DataTables search
                    ];
                }
            } else {
                // Fallback for domains without prefix_statuses (old format)
                $poolOrderInfo = $poolOrdersByDomain[$baseLookupKey] ?? null;

                if ($poolOrderInfo) {
                    $customer = $poolOrderInfo['user'];
                    $poolOrderId = $poolOrderInfo['id'];
                    $perInbox = $poolOrderInfo['per_inbox'];
                    $poolOrderStatus = $poolOrderInfo['status'];
                    $poolOrderAdminStatus = $poolOrderInfo['admin_status'];
                } else {
                    $customer = $pool->user ?? $this->createDefaultUser();
                    $poolOrderId = null;
                    $perInbox = (int) ($domain['available_inboxes'] ?? 0);
                    $poolOrderStatus = 'no_order';
                    $poolOrderAdminStatus = 'no_order';
                }

                // Get SMTP provider URL if available
                $smtpProviderUrl = null;
                if ($pool->provider_type === 'SMTP' || $pool->provider_type === 'Private SMTP') {
                    if ($pool->smtpProvider && $pool->smtpProvider->url) {
                        $smtpProviderUrl = $pool->smtpProvider->url;
                    } elseif ($pool->smtp_provider_url) {
                        $smtpProviderUrl = $pool->smtp_provider_url;
                    }
                }

                // Construct email address for old format (use first prefix if available)
                $firstPrefix = !empty($poolPrefixes) ? reset($poolPrefixes) : '';
                $emailAccount = $firstPrefix && $domainName ? $firstPrefix . '@' . $domainName : '';

                // Calculate days_remaining when end_date exists (old format, for warming status display)
                $status = $domain['status'] ?? 'unknown';
                $daysRemaining = null;
                if (isset($domain['end_date']) && !empty($domain['end_date'])) {
                    try {
                        $endDateStr = $domain['end_date'];
                        // Handle different date formats
                        if (is_string($endDateStr)) {
                            // Try Y-m-d format first (most common)
                            try {
                                $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', $endDateStr);
                            } catch (\Exception $e) {
                                // Fallback to Carbon parse for other formats
                                $endDate = \Carbon\Carbon::parse($endDateStr);
                            }
                        } else {
                            $endDate = \Carbon\Carbon::parse($endDateStr);
                        }
                        $today = \Carbon\Carbon::today();
                        $daysRemaining = $today->diffInDays($endDate, false); // false allows negative values
                    } catch (\Exception $e) {
                        \Log::warning('Failed to calculate days_remaining (old format)', [
                            'end_date' => $domain['end_date'] ?? null,
                            'status' => $status,
                            'pool_id' => $pool->id,
                            'domain_id' => $domainId,
                            'error' => $e->getMessage()
                        ]);
                        $daysRemaining = null;
                    }
                }

                // Show single row with domain-level status
                $results[] = [
                    'customer_name' => $customer ? $customer->name : 'Unknown',
                    'customer_email' => $customer ? $customer->email : 'unknown@example.com',
                    'domain_id' => $domainId,
                    'pool_id' => (int) $pool->id,
                    'pool_order_id' => $poolOrderId,
                    'domain_name' => $domainName ?? 'Unknown Domain',
                    'prefix_key' => null,
                    'prefix_value' => null,
                    'prefix_number' => null,
                    'status' => $domain['status'] ?? 'unknown',
                    'start_date' => $domain['start_date'] ?? null,
                    'end_date' => $domain['end_date'] ?? null,
                    'days_remaining' => $daysRemaining, // Days remaining for warming status
                    'prefixes' => $poolPrefixes,
                    'per_inbox' => $perInbox,
                    'pool_order_status' => $poolOrderStatus,
                    'pool_order_admin_status' => $poolOrderAdminStatus,
                    'is_used' => (bool) ($domain['is_used'] ?? false),
                    'created_by' => $pool->user ? $pool->user->name : 'Unknown',
                    'provider_type' => $pool->provider_type ?? null,
                    'smtp_provider_url' => $smtpProviderUrl,
                    'email_account' => $emailAccount, // Add email_account field for DataTables search
                ];
            }
        }
    }

    /**
     * Create a default user object to prevent null errors
     */
    private function createDefaultUser()
    {
        return (object) [
            'id' => 0,
            'name' => 'Unknown User',
            'email' => 'unknown@example.com'
        ];
    }



    /**
     * Get formatted prefixes for display
     */
    public function formatPrefixes($prefixes, $domainName = null)
    {
        if (empty($prefixes) || !is_array($prefixes)) {
            return '-';
        }

        $formatted = [];
        foreach ($prefixes as $key => $prefix) {
            if (!empty($prefix)) {
                $formatted[] = $domainName ? $prefix . '@' . $domainName : $prefix;
            }
        }

        return implode(', ', $formatted);
    }

    /**
     * Clear cached pool domains data - handles multiple cache keys
     */
    public function clearCache($userId = null, $poolId = null)
    {
        // Always clear the global cache because it contains all data
        Cache::forget('pool_domains');

        // Clear provider-specific caches (always, since bulk updates affect all providers)
        Cache::forget('pool_domains_provider_Google');
        Cache::forget('pool_domains_provider_Microsoft 365');
        Cache::forget('pool_domains_provider_SMTP');

        // If specific user is involved, clear their cache
        if ($userId) {
            Cache::forget("pool_domains_user_{$userId}");
        }

        // If specific pool is involved, clear its cache
        if ($poolId) {
            Cache::forget("pool_domains_pool_{$poolId}");
        }

        // If both are involved, clear the combination cache
        if ($userId && $poolId) {
            $cacheKey = $this->buildCacheKey($userId, $poolId);
            Cache::forget($cacheKey);
        }

        // Also try to clear using the wildcard method for safety
        if (!$userId && !$poolId) {
            $this->clearAllPoolDomainCaches();
        }
    }

    /**
     * Clear all pool domain caches
     */
    private function clearAllPoolDomainCaches()
    {
        // Clear all known cache keys explicitly
        $cacheKeys = [
            'pool_domains',
            'pool_domains_provider_Google',
            'pool_domains_provider_Microsoft 365',
            'pool_domains_provider_SMTP',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Clear cache when pool or pool order is updated
     * Also clears provider-specific caches to handle provider type changes
     */
    public function clearRelatedCache($poolId = null, $userId = null, $oldProviderType = null, $newProviderType = null)
    {
        // Clear general cache
        Cache::forget('pool_domains');

        // Clear provider-specific caches (always clear all to handle provider type changes)
        // When provider type changes, we need to clear both old and new provider caches
        Cache::forget('pool_domains_provider_Google');
        Cache::forget('pool_domains_provider_Microsoft 365');
        Cache::forget('pool_domains_provider_SMTP');

        // Clear user-specific cache if provided
        if ($userId) {
            Cache::forget("pool_domains_user_{$userId}");
        }

        // Clear pool-specific cache if provided  
        if ($poolId) {
            Cache::forget("pool_domains_pool_{$poolId}");
        }

        // Clear provider-specific combination caches if provider types are provided
        if ($oldProviderType) {
            $oldProviderCacheKey = $this->buildCacheKey($userId, $poolId, $oldProviderType);
            Cache::forget($oldProviderCacheKey);
        }

        if ($newProviderType) {
            $newProviderCacheKey = $this->buildCacheKey($userId, $poolId, $newProviderType);
            Cache::forget($newProviderCacheKey);
        }
    }

    /**
     * Refresh cache with fresh data
     */
    public function refreshCache($userId = null, $poolId = null)
    {
        $this->clearCache($userId, $poolId);
        return $this->getPoolDomainsData(true, $userId, $poolId);
    }

    /**
     * Get pool domains data with pagination for DataTables - optimized with database search
     */
    public function getPoolDomainsForDataTable($request)
    {
        $search = $request->get('search')['value'] ?? '';
        $start = (int) ($request->get('start') ?? 0);
        $length = (int) ($request->get('length') ?? 10);
        $userId = $request->get('user_id');
        $poolId = $request->get('pool_id');
        $providerType = $request->get('provider_type');
        $statusFilter = $request->get('status_filter');

        // Always get all data first, then apply search and pagination
        // This ensures we don't miss any domains
        if (!empty(trim($search))) {
            $data = $this->getFilteredPoolDomainsData($search, 0, 0, $userId, $poolId, $providerType);
        } else {
            // For no search, get all cached data
            $data = $this->getPoolDomainsData(true, $userId, $poolId, $providerType);
        }

        // Apply status filter if provided
        if ($statusFilter) {
            $data = array_filter($data, function ($item) use ($statusFilter) {
                return ($item['status'] ?? '') === $statusFilter;
            });
            $data = array_values($data); // Reset array keys
        }

        // Apply pagination if length is specified and > 0
        if ($length > 0) {
            $data = array_slice($data, $start, $length);
        }

        return array_values($data); // Reset array keys
    }

    /**
     * Get filtered pool domains data using database queries for better performance
     */
    private function getFilteredPoolDomainsData($search, $start = 0, $length = 10, $userId = null, $poolId = null, $providerType = null)
    {
        try {
            $results = [];
            $searchTerm = strtolower($search);

            // Get ALL pools and pool orders, then filter domains in PHP
            // This ensures we don't miss domains due to pool-level filtering
            $poolOrdersByDomain = [];

            // First, get all pool orders to build lookup map
            PoolOrder::with([
                'user' => function ($query) {
                    $query->select('id', 'name', 'email');
                }
            ])
                ->select('id', 'user_id', 'domains', 'status', 'status_manage_by_admin')
                ->whereNotNull('domains')
                ->when($userId, function ($query) use ($userId) {
                    $query->where('user_id', (int) $userId);
                })
                ->chunk($this->chunkSize, function ($poolOrders) use (&$poolOrdersByDomain, $poolId) {
                    foreach ($poolOrders as $poolOrder) {
                        $this->processPoolOrderForLookup($poolOrder, $poolOrdersByDomain, $poolId);
                    }
                });

            // Get all pools and filter domains based on search
            Pool::with([
                'user' => function ($query) {
                    $query->select('id', 'name', 'email');
                }
            ])
                ->select('id', 'user_id', 'domains', 'prefix_variants', 'provider_type')
                ->whereNotNull('domains')
                ->whereNotNull('purchase_date')
                ->whereRaw('DATE_ADD(purchase_date, INTERVAL 356 DAY) >= CURDATE()')
                ->when($userId, function ($query) use ($userId) {
                    $query->where('user_id', (int) $userId);
                })
                ->when($poolId, function ($query) use ($poolId) {
                    $query->where('id', (int) $poolId);
                })
                ->when($providerType, function ($query) use ($providerType) {
                    $query->where('provider_type', $providerType);
                })
                ->chunk($this->chunkSize, function ($pools) use (&$results, $poolOrdersByDomain, $searchTerm, $poolId) {
                    foreach ($pools as $pool) {
                        $this->processPoolDomainsWithSearch($pool, $poolOrdersByDomain, $results, $searchTerm, $poolId);
                    }
                });

            // Apply pagination to results
            if ($length > 0) {
                $results = array_slice($results, $start, $length);
            }

            return array_values($results);

        } catch (Exception $e) {
            Log::error('Error filtering pool domains data: ' . $e->getMessage());

            // Fallback to original method if database search fails
            $data = $this->getPoolDomainsData(true, $userId, $poolId);
            return $this->applyPhpSearch($data, $search, $start, $length);
        }
    }

    /**
     * Process pool domains with search filtering - expands each domain into multiple rows per prefix variant
     */
    private function processPoolDomainsWithSearch($pool, $poolOrdersByDomain, &$results, $searchTerm, $poolId = null)
    {
        $poolDomains = $pool->domains;
        if (!is_array($poolDomains)) {
            return;
        }

        $poolPrefixes = is_array($pool->prefix_variants) ? $pool->prefix_variants : [];
        $customer = $pool->user ?? $this->createDefaultUser();

        foreach ($poolDomains as $domain) {
            // Normalize domain keys
            $domainId = $domain['id'] ?? $domain['domain_id'] ?? null;
            if (!$domainId)
                continue;

            $domainName = $domain['name'] ?? $domain['domain_name'] ?? 'Unknown Domain';
            $prefixStatuses = $domain['prefix_statuses'] ?? null;

            // Base lookup key
            $baseLookupKey = $domainId . '_' . $pool->id;

            // If domain has prefix_statuses, create a row for each prefix variant
            if ($prefixStatuses && is_array($prefixStatuses) && count($prefixStatuses) > 0) {
                foreach ($prefixStatuses as $prefixKey => $prefixData) {
                    $prefixNumber = (int) preg_replace('/\D/', '', $prefixKey);
                    $prefixValue = $poolPrefixes[$prefixKey] ?? $poolPrefixes["prefix_variant_{$prefixNumber}"] ?? "Prefix {$prefixNumber}";
                    $status = $prefixData['status'] ?? 'unknown';

                    // Try granular lookup first, then fallback to base
                    $granularKey = $baseLookupKey . '_' . $prefixKey;
                    $poolOrderInfo = $poolOrdersByDomain[$granularKey] ?? $poolOrdersByDomain[$baseLookupKey] ?? null;

                    // Set customer and order info
                    if ($poolOrderInfo) {
                        $customer = $poolOrderInfo['user'];
                        $poolOrderId = $poolOrderInfo['id'];
                        $perInbox = $poolOrderInfo['per_inbox'];
                        $poolOrderStatus = $poolOrderInfo['status'];
                        $poolOrderAdminStatus = $poolOrderInfo['admin_status'];
                    } else {
                        $customer = $pool->user ?? $this->createDefaultUser();
                        $poolOrderId = null;
                        $perInbox = (int) ($domain['available_inboxes'] ?? 0);
                        $poolOrderStatus = 'no_order';
                        $poolOrderAdminStatus = 'no_order';
                    }

                    // Fix: If assigned to an order (Pool Order Exists) and status is 'available', it should be 'in-progress'
                    if ($poolOrderInfo && $status === 'available') {
                        $status = 'in-progress';
                    }

                    $customerName = $customer ? $customer->name : 'Unknown';
                    $customerEmail = $customer ? $customer->email : 'unknown@example.com';

                    // Construct email address for search (same format as frontend: prefix@domain)
                    $emailAccount = $prefixValue && $domainName ? $prefixValue . '@' . $domainName : '';

                    // Check if any field matches the search term (including constructed email)
                    $matchesSearch = stripos($customerName, $searchTerm) !== false ||
                        stripos($customerEmail, $searchTerm) !== false ||
                        stripos($domainName, $searchTerm) !== false ||
                        stripos($domainId, $searchTerm) !== false ||
                        stripos($status, $searchTerm) !== false ||
                        stripos($prefixValue, $searchTerm) !== false ||
                        stripos($emailAccount, $searchTerm) !== false ||
                        stripos((string) $pool->id, $searchTerm) !== false;

                    if (!$matchesSearch) {
                        continue;
                    }

                    $results[] = [
                        'customer_name' => $customerName,
                        'customer_email' => $customerEmail,
                        'domain_id' => $domainId,
                        'pool_id' => (int) $pool->id,
                        'pool_order_id' => $status !== 'available' ? $poolOrderId : null,
                        'domain_name' => $domainName,
                        'prefix_key' => $prefixKey,
                        'prefix_value' => $prefixValue,
                        'prefix_number' => $prefixNumber,
                        'status' => $status,
                        'start_date' => $prefixData['start_date'] ?? null,
                        'end_date' => $prefixData['end_date'] ?? null,
                        'prefixes' => $poolPrefixes,
                        'per_inbox' => $perInbox,
                        'pool_order_status' => $poolOrderStatus,
                        'pool_order_admin_status' => $poolOrderAdminStatus,
                        'is_used' => (bool) ($domain['is_used'] ?? false),
                        'created_by' => $pool->user ? $pool->user->name : 'Unknown',
                        'provider_type' => $pool->provider_type ?? null,
                        'email_account' => $emailAccount, // Add email_account field for DataTables search
                    ];
                }
            } else {
                // Fallback for domains without prefix_statuses (old format)
                $poolOrderInfo = $poolOrdersByDomain[$baseLookupKey] ?? null;

                if ($poolOrderInfo) {
                    $customer = $poolOrderInfo['user'];
                    $poolOrderId = $poolOrderInfo['id'];
                    $perInbox = $poolOrderInfo['per_inbox'];
                    $poolOrderStatus = $poolOrderInfo['status'];
                    $poolOrderAdminStatus = $poolOrderInfo['admin_status'];
                } else {
                    $customer = $pool->user ?? $this->createDefaultUser();
                    $poolOrderId = null;
                    $perInbox = (int) ($domain['available_inboxes'] ?? 0);
                    $poolOrderStatus = 'no_order';
                    $poolOrderAdminStatus = 'no_order';
                }

                $customerName = $customer ? $customer->name : 'Unknown';
                $customerEmail = $customer ? $customer->email : 'unknown@example.com';

                $status = $domain['status'] ?? 'unknown';

                // Construct email address for old format (use first prefix if available)
                $firstPrefix = !empty($poolPrefixes) ? reset($poolPrefixes) : '';
                $emailAccount = $firstPrefix && $domainName ? $firstPrefix . '@' . $domainName : '';

                $matchesSearch = stripos($customerName, $searchTerm) !== false ||
                    stripos($customerEmail, $searchTerm) !== false ||
                    stripos($domainName, $searchTerm) !== false ||
                    stripos($domainId, $searchTerm) !== false ||
                    stripos($status, $searchTerm) !== false ||
                    stripos($emailAccount, $searchTerm) !== false ||
                    stripos((string) $pool->id, $searchTerm) !== false;

                if (!$matchesSearch) {
                    continue;
                }

                $results[] = [
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'domain_id' => $domainId,
                    'pool_id' => (int) $pool->id,
                    'pool_order_id' => $poolOrderId,
                    'domain_name' => $domainName,
                    'prefix_key' => null,
                    'prefix_value' => null,
                    'prefix_number' => null,
                    'status' => $status,
                    'start_date' => $domain['start_date'] ?? null,
                    'end_date' => $domain['end_date'] ?? null,
                    'prefixes' => $poolPrefixes,
                    'per_inbox' => $perInbox,
                    'pool_order_status' => $poolOrderStatus,
                    'pool_order_admin_status' => $poolOrderAdminStatus,
                    'is_used' => (bool) ($domain['is_used'] ?? false),
                    'email_account' => $emailAccount, // Add email_account field for DataTables search
                    'created_by' => $pool->user ? $pool->user->name : 'Unknown',
                    'provider_type' => $pool->provider_type ?? null,
                ];
            }
        }
    }

    /**
     * Fallback PHP-based search and pagination
     */
    private function applyPhpSearch($data, $search, $start = 0, $length = 10)
    {
        $searchTerm = strtolower($search);

        $filtered = array_filter($data, function ($item) use ($searchTerm) {
            return stripos($item['customer_name'] ?? '', $searchTerm) !== false ||
                stripos($item['customer_email'] ?? '', $searchTerm) !== false ||
                stripos($item['domain_name'] ?? '', $searchTerm) !== false ||
                stripos($item['domain_id'] ?? '', $searchTerm) !== false ||
                stripos($item['status'] ?? '', $searchTerm) !== false;
        });

        // Apply pagination
        if ($length > 0) {
            $filtered = array_slice($filtered, $start, $length);
        }

        return array_values($filtered);
    }

    /**
     * Get total count for DataTables pagination
     */
    public function getTotalCount($search = '')
    {
        if (empty(trim($search))) {
            // For no search, get actual domain count from all pools
            $totalDomains = 0;

            Pool::whereNotNull('domains')
                ->whereNotNull('purchase_date')
                ->whereRaw('DATE_ADD(purchase_date, INTERVAL 356 DAY) >= CURDATE()')
                ->chunk($this->chunkSize, function ($pools) use (&$totalDomains) {
                    foreach ($pools as $pool) {
                        if (is_array($pool->domains)) {
                            $totalDomains += count($pool->domains);
                        }
                    }
                });

            return $totalDomains;
        }

        // For search, get count from filtered results
        return count($this->getFilteredPoolDomainsData($search, 0, 0));
    }

    /**
     * Get count of all domains (without search filtering)
     */
    public function getAllDomainsCount()
    {
        $totalDomains = 0;

        Pool::whereNotNull('domains')
            ->whereNotNull('purchase_date')
            ->whereRaw('DATE_ADD(purchase_date, INTERVAL 356 DAY) >= CURDATE()')
            ->chunk($this->chunkSize, function ($pools) use (&$totalDomains) {
                foreach ($pools as $pool) {
                    if (is_array($pool->domains)) {
                        $totalDomains += count($pool->domains);
                    }
                }
            });

        return $totalDomains;
    }

    /**
     * Debug method to check what's being returned
     */
    public function debugPoolDomains()
    {
        $results = $this->getPoolDomainsData(false); // Force fresh data

        Log::info('Debug Pool Domains', [
            'total_results' => count($results),
            'sample_results' => array_slice($results, 0, 5),
            'pools_count' => Pool::whereNotNull('domains')->count(),
            'pool_orders_count' => PoolOrder::whereNotNull('domains')->count()
        ]);

        return [
            'total_domains' => count($results),
            'pools_with_domains' => Pool::whereNotNull('domains')->count(),
            'pool_orders_with_domains' => PoolOrder::whereNotNull('domains')->count(),
            'sample_domains' => array_slice($results, 0, 3)
        ];
    }
}
