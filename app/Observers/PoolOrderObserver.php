<?php

namespace App\Observers;

use App\Models\PoolOrder;
use App\Services\PoolDomainService;

class PoolOrderObserver
{
    protected $poolDomainService;

    public function __construct(PoolDomainService $poolDomainService)
    {
        $this->poolDomainService = $poolDomainService;
    }

    /**
     * Handle the PoolOrder "created" event.
     */
    public function created(PoolOrder $poolOrder): void
    {
        $this->clearRelatedCaches($poolOrder);
    }

    /**
     * Handle the PoolOrder "updated" event.
     */
    public function updated(PoolOrder $poolOrder): void
    {
        $this->clearRelatedCaches($poolOrder);
    }

    /**
     * Handle the PoolOrder "deleted" event.
     */
    public function deleted(PoolOrder $poolOrder): void
    {
        $this->clearRelatedCaches($poolOrder);
    }

    /**
     * Clear related caches when pool order changes
     */
    private function clearRelatedCaches(PoolOrder $poolOrder): void
    {
        // Clear cache for the user and any pools that might be affected
        $this->poolDomainService->clearRelatedCache(null, $poolOrder->user_id);
        
        // If the pool order has domains, we might need to clear pool-specific caches too
        if (is_array($poolOrder->domains)) {
            foreach ($poolOrder->domains as $domain) {
                $poolId = $domain['pool_id'] ?? null;
                if ($poolId) {
                    $this->poolDomainService->clearRelatedCache($poolId, $poolOrder->user_id);
                }
            }
        }
    }
}