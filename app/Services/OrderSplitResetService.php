<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderEmail;
use App\Models\OrderPanelSplit;
use App\Models\OrderTracking;
use App\Models\Panel;
use App\Models\UserOrderPanelAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderSplitResetService
{
    /**
     * Remove all order panel splits for the given order, restore panel capacity,
     * clear related records, and queue the order for fresh split creation.
     */
    public function resetOrderSplits(Order $order, ?int $changedBy = null, ?string $reason = null, bool $wrapInTransaction = true): array
    {
        $operation = function () use ($order, $changedBy, $reason) {
            $order->loadMissing(['orderPanels.orderPanelSplits', 'reorderInfo', 'orderTracking']);

            $restoredCapacity = 0;
            $splitsDeleted = 0;
            $panelsDeleted = 0;
            $emailsDeleted = 0;
            $assignmentsDeleted = 0;

            foreach ($order->orderPanels as $orderPanel) {
                $panel = Panel::find($orderPanel->panel_id);
                $spaceAssigned = max((int) $orderPanel->space_assigned, 0);

                // Restore panel capacity, but never exceed the panel's limit
                if ($panel && $spaceAssigned > 0) {
                    $newRemaining = $panel->remaining_limit + $spaceAssigned;
                    if (isset($panel->limit)) {
                        $newRemaining = min($newRemaining, $panel->limit);
                    }
                    $panel->remaining_limit = $newRemaining;
                    $panel->save();

                    $restoredCapacity += $spaceAssigned;
                }

                $splitIds = $orderPanel->orderPanelSplits->pluck('id');
                if ($splitIds->isNotEmpty()) {
                    $emailsDeleted += OrderEmail::whereIn('order_split_id', $splitIds)->delete();
                    $assignmentsDeleted += UserOrderPanelAssignment::whereIn('order_panel_split_id', $splitIds)->delete();
                    $splitsDeleted += OrderPanelSplit::whereIn('id', $splitIds)->delete();
                }

                // Remove any assignments tied to the order panel itself
                $assignmentsDeleted += UserOrderPanelAssignment::where('order_panel_id', $orderPanel->id)->delete();

                $orderPanel->delete();
                $panelsDeleted++;
            }

            // Clean up any lingering assignments tied directly to the order
            $assignmentsDeleted += UserOrderPanelAssignment::where('order_id', $order->id)->delete();

            $trackingData = $this->buildTrackingPayload($order);
            $tracking = null;

            if ($trackingData) {
                $tracking = OrderTracking::updateOrCreate(
                    ['order_id' => $order->id],
                    $trackingData
                );
            }

            $result = [
                'panels_deleted' => $panelsDeleted,
                'splits_deleted' => $splitsDeleted,
                'emails_deleted' => $emailsDeleted,
                'assignments_deleted' => $assignmentsDeleted,
                'capacity_restored' => $restoredCapacity,
                'tracking_reset' => (bool) $trackingData,
            ];

            Log::info('Order splits reset for provider change', array_merge($result, [
                'order_id' => $order->id,
                'changed_by' => $changedBy,
                'reason' => $reason,
                'tracking_status' => $trackingData['status'] ?? null,
                'tracking_total_inboxes' => $trackingData['total_inboxes'] ?? null,
                'tracking_inboxes_per_domain' => $trackingData['inboxes_per_domain'] ?? null,
            ]));

            return $result;
        };

        return $wrapInTransaction ? DB::transaction($operation) : $operation();
    }

    /**
     * Build order_tracking payload to re-queue split creation.
     */
    private function buildTrackingPayload(Order $order): ?array
    {
        $reorderInfo = $order->reorderInfo->first();
        $existingTracking = $order->orderTracking;

        $inboxesPerDomain = (int) ($reorderInfo->inboxes_per_domain ?? $existingTracking->inboxes_per_domain ?? 0);

        $domains = $reorderInfo ? array_filter(preg_split('/[\r\n,]+/', $reorderInfo->domains ?? '')) : [];
        $totalFromDomains = ($inboxesPerDomain > 0 && count($domains) > 0)
            ? count($domains) * $inboxesPerDomain
            : 0;

        $fallbackTotal = (int) ($reorderInfo->total_inboxes ?? $existingTracking->total_inboxes ?? 0);
        $totalInboxes = $totalFromDomains > 0 ? $totalFromDomains : $fallbackTotal;

        if ($totalInboxes <= 0 || $inboxesPerDomain <= 0) {
            Log::warning('Skipping order_tracking reset due to missing inbox data', [
                'order_id' => $order->id,
                'domains_found' => count($domains),
                'total_from_domains' => $totalFromDomains,
                'fallback_total' => $fallbackTotal,
                'inboxes_per_domain' => $inboxesPerDomain,
            ]);
            return null;
        }

        return [
            'status' => 'pending',
            'cron_run_time' => now(),
            'inboxes_per_domain' => $inboxesPerDomain,
            'total_inboxes' => $totalInboxes,
        ];
    }
}
