<?php

namespace App\Services;

use App\Models\PoolPanelSplit;
use App\Models\PoolPanel;
use App\Models\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PoolSplitResetService
{
    /**
     * Remove all order panel splits for the given order, restore panel capacity,
     * clear related records, and queue the order for fresh split creation.
     */
    public function resetOrderSplits(Pool $pool, ?int $changedBy = null, ?string $reason = null, bool $wrapInTransaction = true): array
    {
        $operation = function () use ($pool, $changedBy, $reason) {
            $pool->loadMissing(['poolPanelSplits']);
            $restoredCapacity = 0;
            $splitsDeleted = 0;
            $panelsDeleted = 0;

            foreach ($pool->poolPanelSplits as $poolSplit) {
                $poolPanel = PoolPanel::find($poolSplit->pool_panel_id);
                $spaceAssigned = max((int) $poolSplit->assigned_space, 0);

                // Restore panel capacity
                if ($poolPanel && $spaceAssigned > 0) {
                    $newRemaining = $poolPanel->remaining_limit + $spaceAssigned;
                    if (isset($poolPanel->limit)) {
                        $newRemaining = min($newRemaining, $poolPanel->limit);
                    }

                    $newUsed = max($poolPanel->used_limit - $spaceAssigned, 0);

                    $poolPanel->remaining_limit = $newRemaining;
                    $poolPanel->used_limit = $newUsed;
                    $poolPanel->save();

                    $restoredCapacity += $spaceAssigned;
                }

                // Delete the split
                $poolSplit->delete();
                $splitsDeleted++;
                $panelsDeleted++;
            }

            $result = [
                'panels_deleted' => $panelsDeleted,
                'splits_deleted' => $splitsDeleted,
                'capacity_restored' => $restoredCapacity,
            ];

            Log::info('Pool splits reset for provider change', array_merge($result, [
                    'pool_id' => $pool->id,
                    'changed_by' => $changedBy,
                    'reason' => $reason,
                ]),
            );

            return $result;
        };

        return $wrapInTransaction ? DB::transaction($operation) : $operation();
    }
}
