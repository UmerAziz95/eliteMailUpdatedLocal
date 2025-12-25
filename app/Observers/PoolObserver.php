<?php

namespace App\Observers;

use App\Models\Pool;
use App\Services\PoolDomainService;

class PoolObserver
{
    protected $poolDomainService;

    public function __construct(PoolDomainService $poolDomainService)
    {
        $this->poolDomainService = $poolDomainService;
    }
    /**
     * Handle the Pool "created" event.
     * Update domain IDs from timestamp-based temporary IDs to actual pool ID.
     *
     * @param  \App\Models\Pool  $pool
     * @return void
     */
    public function created(Pool $pool)
    {
        // Check if pool has domains and the pool ID is now available
        if ($pool->domains && $pool->id) {
            $domains = $pool->domains;
            $updated = false;

            // Process each domain to update temporary timestamp-based IDs
            foreach ($domains as &$domain) {
                if (isset($domain['id'])) {
                    $currentId = $domain['id'];

                    // Check if this is a timestamp-based temporary ID
                    // Conditions: numeric, very large (13+ digits), or contains timestamp pattern
                    if ($this->isTemporaryTimestampId($currentId)) {
                        // Extract the sequence number if it exists (format: timestamp_sequence)
                        $parts = explode('_', $currentId);
                        $sequence = count($parts) > 1 ? $parts[1] : '1';

                        // Replace with actual pool ID format: poolId_sequence
                        $domain['id'] = $pool->id . '_' . $sequence;
                        $updated = true;

                        \Log::info("PoolObserver: Updated domain ID from '{$currentId}' to '{$domain['id']}' for pool {$pool->id}");
                    }
                }
            }

            // If any domains were updated, save the changes
            if ($updated) {
                try {
                    // Update the domains field without triggering observers again
                    // Use updateQuietly to prevent recursion
                    $pool->updateQuietly(['domains' => $domains]);

                    \Log::info("PoolObserver: Successfully updated domain IDs for pool {$pool->id}");
                } catch (\Exception $e) {
                    \Log::error("PoolObserver: Failed to update domain IDs for pool {$pool->id}: " . $e->getMessage());
                }
            }
        }

        // Clear related caches when pool is created
        $this->clearRelatedCaches($pool);
    }

    /**
     * Check if an ID is a temporary timestamp-based ID
     *
     * @param string $id
     * @return bool
     */
    private function isTemporaryTimestampId($id)
    {
        // Convert to string to handle numeric values
        $idStr = (string) $id;

        // Check if it starts with 'new_' prefix (used for newly created domains including SMTP)
        if (strpos($idStr, 'new_') === 0) {
            return true;
        }

        // Extract the first part (before underscore if exists)
        $parts = explode('_', $idStr);
        $timestampPart = $parts[0];

        // Check if it's numeric and has characteristics of a timestamp
        if (is_numeric($timestampPart)) {
            $timestampLength = strlen($timestampPart);

            // JavaScript Date.now() returns milliseconds since epoch (13 digits)
            // Also check for PHP time() which returns seconds since epoch (10 digits)
            if ($timestampLength >= 10 && $timestampLength <= 13) {
                // Additional validation: check if it's a reasonable timestamp
                // (between year 2000 and year 2100)
                $timestamp = intval($timestampPart);
                $minTimestamp = strtotime('2000-01-01') * ($timestampLength === 13 ? 1000 : 1);
                $maxTimestamp = strtotime('2100-01-01') * ($timestampLength === 13 ? 1000 : 1);

                return $timestamp >= $minTimestamp && $timestamp <= $maxTimestamp;
            }
        }

        return false;
    }

    /**
     * Handle the Pool "updated" event.
     * This can be used for future domain ID management if needed.
     *
     * @param  \App\Models\Pool  $pool
     * @return void
     */
    public function updated(Pool $pool)
    {
        // Handle any domain ID updates during pool updates if necessary
        // This could be useful for handling domain additions/modifications

        // Clear related caches when pool is updated
        $this->clearRelatedCaches($pool);
    }

    /**
     * Handle the Pool "deleted" event.
     */
    public function deleted(Pool $pool): void
    {
        // Clear related caches when pool is deleted
        $this->clearRelatedCaches($pool);
    }

    /**
     * Clear related caches when pool changes
     */
    private function clearRelatedCaches(Pool $pool): void
    {
        $this->poolDomainService->clearRelatedCache($pool->id, $pool->user_id);
    }
}