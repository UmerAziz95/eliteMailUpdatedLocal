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
    public function getPoolDomainsData($useCache = true, $userId = null, $poolId = null)
    {
        if (!$useCache) {
            return $this->fetchPoolDomainsData($userId, $poolId);
        }
        
        // Use specific cache keys based on filters to reduce cache size
        $cacheKey = $this->buildCacheKey($userId, $poolId);
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($userId, $poolId) {
            return $this->fetchPoolDomainsData($userId, $poolId);
        });
    }

    /**
     * Build cache key based on filters
     */
    private function buildCacheKey($userId = null, $poolId = null)
    {
        $key = 'pool_domains';
        if ($userId) {
            $key .= "_user_{$userId}";
        }
        if ($poolId) {
            $key .= "_pool_{$poolId}";
        }
        return $key;
    }

    /**
     * Internal method to fetch pool domains data with proper error handling and chunking
     */
    private function fetchPoolDomainsData($userId = null, $poolId = null)
    {
        try {
            $results = [];
            $poolOrdersByDomain = [];
            
            // Build queries with optional filters
            $poolQuery = Pool::with(['user' => function($query) {
                $query->select('id', 'name', 'email');
            }])
            ->select('id', 'user_id', 'domains', 'prefix_variants')
            ->whereNotNull('domains');
            
            $poolOrderQuery = PoolOrder::with(['user' => function($query) {
                $query->select('id', 'name', 'email');
            }])
            ->select('id', 'user_id', 'domains', 'status', 'status_manage_by_admin')
            ->whereNotNull('domains');
            
            // Apply filters if provided
            if ($userId) {
                $poolQuery->where('user_id', (int) $userId);
                $poolOrderQuery->where('user_id', (int) $userId);
            }
            
            if ($poolId) {
                $poolQuery->where('id', (int) $poolId);
            }

            // First, process pool orders to create lookup map
            $poolOrderQuery->chunk($this->chunkSize, function ($poolOrders) use (&$poolOrdersByDomain) {
                foreach ($poolOrders as $poolOrder) {
                    $this->processPoolOrderForLookup($poolOrder, $poolOrdersByDomain);
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
    private function processPoolOrderForLookup($poolOrder, &$poolOrdersByDomain)
    {
        if (!is_array($poolOrder->domains)) {
            return;
        }

        foreach ($poolOrder->domains as $orderDomain) {
            // Normalize domain keys for consistency
            $domainId = $orderDomain['domain_id'] ?? $orderDomain['id'] ?? null;
            $poolId = $orderDomain['pool_id'] ?? null;
            
            if ($domainId && $poolId) {
                $lookupKey = $domainId . '_' . $poolId;
                $poolOrdersByDomain[$lookupKey] = [
                    'id' => (int) $poolOrder->id,
                    'user' => $poolOrder->user ?? $this->createDefaultUser(),
                    'per_inbox' => (int) ($orderDomain['per_inbox'] ?? 1),
                    'status' => $poolOrder->status ?? 'unknown',
                    'admin_status' => $poolOrder->status_manage_by_admin ?? 'unknown',
                ];
            }
        }
    }

    /**
     * Process individual pool domains
     */
    private function processPoolDomains($pool, $poolOrdersByDomain, &$results)
    {
        // No need for manual JSON decode due to model casts
        $poolDomains = $pool->domains;
        if (!is_array($poolDomains)) {
            return;
        }

        $prefixes = is_array($pool->prefix_variants) ? $pool->prefix_variants : [];

        foreach ($poolDomains as $domain) {
            // Normalize domain keys for consistency  
            $domainId = $domain['id'] ?? $domain['domain_id'] ?? null;
            if (!$domainId) continue;

            $domainName = $domain['name'] ?? $domain['domain_name'] ?? null;
            $status = $domain['status'] ?? 'unknown';

            // Use lookup map for O(1) access
            $lookupKey = $domainId . '_' . $pool->id;
            $poolOrderInfo = $poolOrdersByDomain[$lookupKey] ?? null;
            
            // Set customer and order info with proper type casting
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

            $results[] = [
                'customer_name' => $customer ? $customer->name : 'Unknown',
                'customer_email' => $customer ? $customer->email : 'unknown@example.com',
                'domain_id' => $domainId,
                'pool_id' => (int) $pool->id,
                'pool_order_id' => $poolOrderId,
                'domain_name' => $domainName ?? 'Unknown Domain',
                'status' => $status,
                'prefixes' => $prefixes,
                'per_inbox' => $perInbox,
                'pool_order_status' => $poolOrderStatus,
                'pool_order_admin_status' => $poolOrderAdminStatus,
                'is_used' => (bool) ($domain['is_used'] ?? false),
            ];
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
        if ($userId || $poolId) {
            // Clear specific cache
            $cacheKey = $this->buildCacheKey($userId, $poolId);
            Cache::forget($cacheKey);
        } else {
            // Clear all pool domain caches using pattern matching
            $this->clearAllPoolDomainCaches();
        }
    }

    /**
     * Clear all pool domain caches
     */
    private function clearAllPoolDomainCaches()
    {
        // For Laravel, we need to use tags or clear specific keys
        // Since we can't use wildcards easily, we'll track cache keys
        $cacheKeys = [
            'pool_domains',
            'pool_domains_user_*',
            'pool_domains_pool_*'
        ];

        foreach ($cacheKeys as $pattern) {
            if (str_contains($pattern, '*')) {
                // For production, you might want to use cache tags
                // For now, we'll clear the main cache
                Cache::forget('pool_domains');
            } else {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Clear cache when pool or pool order is updated
     */
    public function clearRelatedCache($poolId = null, $userId = null)
    {
        // Clear general cache
        Cache::forget('pool_domains');
        
        // Clear user-specific cache if provided
        if ($userId) {
            Cache::forget("pool_domains_user_{$userId}");
        }
        
        // Clear pool-specific cache if provided  
        if ($poolId) {
            Cache::forget("pool_domains_pool_{$poolId}");
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
        
        // If search is provided, use database-level filtering for better performance
        if (!empty($search)) {
            return $this->getFilteredPoolDomainsData($search, $start, $length);
        }
        
        // For no search, get cached data and apply pagination
        $data = $this->getPoolDomainsData();
        
        // Apply pagination
        if ($length > 0) {
            $data = array_slice($data, $start, $length);
        }
        
        return array_values($data); // Reset array keys
    }

    /**
     * Get filtered pool domains data using database queries for better performance
     */
    private function getFilteredPoolDomainsData($search, $start = 0, $length = 10)
    {
        try {
            $results = [];
            $searchTerm = '%' . strtolower($search) . '%';
            
            // Build efficient database queries with search
            $poolQuery = Pool::with(['user' => function($query) {
                $query->select('id', 'name', 'email');
            }])
            ->select('id', 'user_id', 'domains', 'prefix_variants')
            ->whereNotNull('domains')
            ->whereHas('user', function($query) use ($searchTerm) {
                $query->where(DB::raw('LOWER(name)'), 'LIKE', $searchTerm)
                      ->orWhere(DB::raw('LOWER(email)'), 'LIKE', $searchTerm);
            })
            ->orWhere('id', 'LIKE', $searchTerm);
            
            // Apply pagination at database level
            if ($length > 0) {
                $pools = $poolQuery->offset($start)->limit($length)->get();
            } else {
                $pools = $poolQuery->get();
            }
            
            // Process pools for results
            foreach ($pools as $pool) {
                $poolDomains = $pool->domains;
                if (!is_array($poolDomains)) continue;
                
                $prefixes = is_array($pool->prefix_variants) ? $pool->prefix_variants : [];
                
                foreach ($poolDomains as $domain) {
                    // Normalize domain keys
                    $domainId = $domain['id'] ?? $domain['domain_id'] ?? null;
                    if (!$domainId) continue;
                    
                    $domainName = $domain['name'] ?? $domain['domain_name'] ?? 'Unknown Domain';
                    
                    // Check if domain name matches search
                    if (stripos($domainName, trim($search)) === false && 
                        stripos($domainId, trim($search)) === false) {
                        continue;
                    }
                    
                    $customer = $pool->user ?? $this->createDefaultUser();
                    
                    $results[] = [
                        'customer_name' => $customer ? $customer->name : 'Unknown',
                        'customer_email' => $customer ? $customer->email : 'unknown@example.com',
                        'domain_id' => $domainId,
                        'pool_id' => (int) $pool->id,
                        'pool_order_id' => null,
                        'domain_name' => $domainName,
                        'status' => $domain['status'] ?? 'unknown',
                        'prefixes' => $prefixes,
                        'per_inbox' => (int) ($domain['available_inboxes'] ?? 0),
                        'pool_order_status' => 'no_order',
                        'pool_order_admin_status' => 'no_order',
                        'is_used' => (bool) ($domain['is_used'] ?? false),
                    ];
                }
            }
            
            return $results;
            
        } catch (Exception $e) {
            Log::error('Error filtering pool domains data: ' . $e->getMessage());
            
            // Fallback to original method if database search fails
            $data = $this->getPoolDomainsData();
            return $this->applyPhpSearch($data, $search, $start, $length);
        }
    }

    /**
     * Fallback PHP-based search and pagination
     */
    private function applyPhpSearch($data, $search, $start = 0, $length = 10)
    {
        $searchTerm = strtolower($search);
        
        $filtered = array_filter($data, function($item) use ($searchTerm) {
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
        if (empty($search)) {
            // Use efficient count query
            return Pool::whereNotNull('domains')->count() + 
                   PoolOrder::whereNotNull('domains')->count();
        }
        
        // For search, estimate count from filtered results
        return count($this->getFilteredPoolDomainsData($search, 0, 0));
    }
}