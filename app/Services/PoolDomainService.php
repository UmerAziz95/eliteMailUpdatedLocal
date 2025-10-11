<?php

namespace App\Services;

use App\Models\Pool;
use App\Models\PoolOrder;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PoolDomainService
{
    private $cacheKey = 'pool_domains_data';
    private $cacheTime = 300; // 5 minutes
    
    /**
     * Get aggregated pool domains data - optimized for performance with caching
     */
    public function getPoolDomainsData($useCache = true)
    {
        if ($useCache) {
            return Cache::remember($this->cacheKey, $this->cacheTime, function () {
                return $this->fetchPoolDomainsData();
            });
        }
        
        return $this->fetchPoolDomainsData();
    }

    /**
     * Internal method to fetch pool domains data
     */
    private function fetchPoolDomainsData()
    {
        $results = [];

        // Load all data in single queries with proper relationships and selective fields
        $pools = Pool::with(['user' => function($query) {
            $query->select('id', 'name', 'email');
        }])
        ->select('id', 'user_id', 'domains', 'prefix_variants')
        ->whereNotNull('domains')
        ->get();
        
        $poolOrders = PoolOrder::with(['user' => function($query) {
            $query->select('id', 'name', 'email');
        }])
        ->select('id', 'user_id', 'domains', 'status', 'status_manage_by_admin')
        ->whereNotNull('domains')
        ->get();

        // Create lookup maps for O(1) access
        $poolOrdersByDomain = $this->createPoolOrderLookupMap($poolOrders);
        $usersByPoolId = [];
        
        // Pre-populate users by pool ID for faster access
        foreach ($pools as $pool) {
            $usersByPoolId[$pool->id] = $pool->user;
        }

        foreach ($pools as $pool) {
            $poolDomains = is_string($pool->domains) ? json_decode($pool->domains, true) : $pool->domains;
            if (!is_array($poolDomains)) continue;

            $prefixes = is_array($pool->prefix_variants) ? $pool->prefix_variants : [];

            foreach ($poolDomains as $domain) {
                $domainId = $domain['id'] ?? $domain['domain_id'] ?? null;
                if (!$domainId) continue;

                $domainName = $domain['name'] ?? $domain['domain_name'] ?? null;
                $status = $domain['status'] ?? 'unknown';

                // Use lookup map instead of nested queries
                $lookupKey = $domainId . '_' . $pool->id;
                $poolOrderInfo = $poolOrdersByDomain[$lookupKey] ?? null;
                
                // Set customer and order info
                if ($poolOrderInfo) {
                    $customer = $poolOrderInfo['user'];
                    $poolOrderId = $poolOrderInfo['id'];
                    $perInbox = $poolOrderInfo['per_inbox'];
                    $poolOrderStatus = $poolOrderInfo['status'];
                    $poolOrderAdminStatus = $poolOrderInfo['admin_status'];
                } else {
                    $customer = $usersByPoolId[$pool->id] ?? null;
                    $poolOrderId = null;
                    $perInbox = $domain['available_inboxes'] ?? 0;
                    $poolOrderStatus = 'no_order';
                    $poolOrderAdminStatus = 'no_order';
                }

                $results[] = [
                    'customer_name' => $customer ? $customer->name : null,
                    'customer_email' => $customer ? $customer->email : null,
                    'domain_id' => $domainId,
                    'pool_id' => $pool->id,
                    'pool_order_id' => $poolOrderId,
                    'domain_name' => $domainName,
                    'status' => $status,
                    'prefixes' => $prefixes,
                    'per_inbox' => $perInbox,
                    'pool_order_status' => $poolOrderStatus,
                    'pool_order_admin_status' => $poolOrderAdminStatus,
                    'is_used' => $domain['is_used'] ?? false,
                ];
            }
        }

        return $results;
    }

    /**
     * Create optimized lookup map for pool orders by domain
     */
    private function createPoolOrderLookupMap($poolOrders)
    {
        $lookupMap = [];

        foreach ($poolOrders as $poolOrder) {
            if (!is_array($poolOrder->domains)) continue;

            foreach ($poolOrder->domains as $orderDomain) {
                $domainId = $orderDomain['domain_id'] ?? null;
                $poolId = $orderDomain['pool_id'] ?? null;
                
                if ($domainId && $poolId) {
                    $lookupKey = $domainId . '_' . $poolId;
                    $lookupMap[$lookupKey] = [
                        'id' => $poolOrder->id,
                        'user' => $poolOrder->user,
                        'per_inbox' => $orderDomain['per_inbox'] ?? 1,
                        'status' => $poolOrder->status ?? 'unknown',
                        'admin_status' => $poolOrder->status_manage_by_admin ?? 'unknown',
                    ];
                }
            }
        }

        return $lookupMap;
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
     * Clear the cached pool domains data
     */
    public function clearCache()
    {
        Cache::forget($this->cacheKey);
    }

    /**
     * Refresh cache with fresh data
     */
    public function refreshCache()
    {
        $this->clearCache();
        return $this->getPoolDomainsData(true);
    }

    /**
     * Get pool domains data with pagination for DataTables
     */
    public function getPoolDomainsForDataTable($request)
    {
        $data = $this->getPoolDomainsData();
        
        // Apply search filtering if provided
        if ($request->has('search') && !empty($request->search['value'])) {
            $search = strtolower($request->search['value']);
            $data = array_filter($data, function($item) use ($search) {
                return strpos(strtolower($item['customer_name'] ?? ''), $search) !== false ||
                       strpos(strtolower($item['customer_email'] ?? ''), $search) !== false ||
                       strpos(strtolower($item['domain_name'] ?? ''), $search) !== false ||
                       strpos(strtolower($item['domain_id'] ?? ''), $search) !== false ||
                       strpos(strtolower($item['status'] ?? ''), $search) !== false;
            });
        }

        return array_values($data); // Reset array keys
    }
}