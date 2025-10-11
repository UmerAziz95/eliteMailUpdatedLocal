<?php

namespace App\Services;

use App\Models\Pool;
use App\Models\PoolOrder;
use App\Models\User;

class PoolDomainService
{
    
    /**
     * Get aggregated pool domains data - shows all domains (subscribed and available)
     */
    public function getPoolDomainsData()
    {
        $results = [];

        // Get all pools with domains
        $pools = Pool::with(['user'])->whereNotNull('domains')->get();

        foreach ($pools as $pool) {
            $poolDomains = is_string($pool->domains) ? json_decode($pool->domains, true) : $pool->domains;
            if (!is_array($poolDomains)) continue;

            foreach ($poolDomains as $domain) {
                $domainId = $domain['id'] ?? $domain['domain_id'] ?? null;
                $domainName = $domain['name'] ?? $domain['domain_name'] ?? null;
                $status = $domain['status'] ?? 'unknown';

                if (!$domainId) continue;

                // Check if this domain is used in any pool order
                $poolOrderInfo = $this->findPoolOrderForDomain($domainId, $pool->id);
                
                // Get customer - prefer from pool order if exists, otherwise from pool
                $customer = null;
                $poolOrderId = null;
                $perInbox = null;
                $poolOrderStatus = null;
                $poolOrderAdminStatus = null;

                if ($poolOrderInfo) {
                    $customer = $poolOrderInfo['customer'];
                    $poolOrderId = $poolOrderInfo['pool_order_id'];
                    $perInbox = $poolOrderInfo['per_inbox'];
                    $poolOrderStatus = $poolOrderInfo['pool_order_status'];
                    $poolOrderAdminStatus = $poolOrderInfo['pool_order_admin_status'];
                } else {
                    // If no pool order, get customer from pool owner
                    $customer = $pool->user;
                }
                
                // Get prefixes from pool
                $prefixes = [];
                if ($pool->prefix_variants) {
                    $prefixes = is_array($pool->prefix_variants) ? $pool->prefix_variants : [];
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
                    'per_inbox' => $perInbox ?? ($domain['available_inboxes'] ?? 0),
                    'pool_order_status' => $poolOrderStatus ?? 'no_order',
                    'pool_order_admin_status' => $poolOrderAdminStatus ?? 'no_order',
                    'is_used' => $domain['is_used'] ?? false,
                ];
            }
        }

        return $results;
    }

    /**
     * Find pool order information for a specific domain
     */
    private function findPoolOrderForDomain($domainId, $poolId)
    {
        $poolOrder = PoolOrder::with('user')->whereNotNull('domains')->get()->first(function($order) use ($domainId, $poolId) {
            if (!is_array($order->domains)) return false;
            
            foreach ($order->domains as $d) {
                if ((isset($d['domain_id']) && (string)$d['domain_id'] === (string)$domainId) && 
                    (isset($d['pool_id']) && (string)$d['pool_id'] === (string)$poolId)) {
                    return true;
                }
            }
            return false;
        });

        if (!$poolOrder) return null;

        // Find the specific domain data in the pool order
        $orderDomain = null;
        foreach ($poolOrder->domains as $d) {
            if ((isset($d['domain_id']) && (string)$d['domain_id'] === (string)$domainId) && 
                (isset($d['pool_id']) && (string)$d['pool_id'] === (string)$poolId)) {
                $orderDomain = $d;
                break;
            }
        }

        return [
            'customer' => $poolOrder->user,
            'pool_order_id' => $poolOrder->id,
            'per_inbox' => $orderDomain['per_inbox'] ?? 1,
            'pool_order_status' => $poolOrder->status ?? 'unknown',
            'pool_order_admin_status' => $poolOrder->status_manage_by_admin ?? 'unknown',
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
}