<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\Order;
use App\Models\Panel;
use Illuminate\Support\Facades\Log;

class OrderCapacityService
{
    /**
     * Validate that the target provider has enough panel space for the order.
     *
     * Returns:
     * [
     *   'success' => bool,
     *   'message' => string|null,
     *   'data' => [
     *       'required_inboxes' => int,
     *       'available_inboxes' => int,
     *       'provider_type' => string,
     *   ]
     * ]
     */
    public function validateProviderCapacity(Order $order, string $targetProviderType): array
    {
        $requirements = $this->calculateRequirements($order);
        if (!$requirements['success']) {
            return [
                'success' => false,
                'message' => $requirements['message'],
                'data' => [],
            ];
        }

        $config = $this->getProviderConfig($targetProviderType);
        $availableSpace = $this->computeAvailableSpace(
            $requirements['inboxes_per_domain'],
            $targetProviderType,
            $config['panel_capacity'],
            $config['max_split_capacity']
        );

        if ($requirements['total_inboxes'] > $availableSpace) {
            return [
                'success' => false,
                'message' => "Insufficient panel capacity for {$targetProviderType}. Required: {$requirements['total_inboxes']}, available: {$availableSpace}.",
                'data' => [
                    'required_inboxes' => $requirements['total_inboxes'],
                    'available_inboxes' => $availableSpace,
                    'provider_type' => $targetProviderType,
                ],
            ];
        }

        return [
            'success' => true,
            'message' => null,
            'data' => [
                'required_inboxes' => $requirements['total_inboxes'],
                'available_inboxes' => $availableSpace,
                'provider_type' => $targetProviderType,
            ],
        ];
    }

    /**
     * Calculate total inboxes and inboxes per domain from reorder info or tracking.
     */
    private function calculateRequirements(Order $order): array
    {
        $reorderInfo = $order->reorderInfo->first();
        $tracking = $order->orderTracking;

        $inboxesPerDomain = (int) ($reorderInfo->inboxes_per_domain ?? $tracking->inboxes_per_domain ?? 0);
        $domains = $reorderInfo ? array_filter(preg_split('/[\r\n,]+/', $reorderInfo->domains ?? '')) : [];
        $totalFromDomains = ($inboxesPerDomain > 0 && count($domains) > 0)
            ? count($domains) * $inboxesPerDomain
            : 0;
        $fallbackTotal = (int) ($reorderInfo->total_inboxes ?? $tracking->total_inboxes ?? 0);
        $totalInboxes = $totalFromDomains > 0 ? $totalFromDomains : $fallbackTotal;

        if ($totalInboxes <= 0 || $inboxesPerDomain <= 0) {
            Log::warning('Order inbox data is incomplete; cannot verify capacity', [
                'order_id' => $order->id,
                'total_inboxes' => $totalInboxes,
                'inboxes_per_domain' => $inboxesPerDomain,
                'domains_count' => count($domains),
            ]);
            return [
                'success' => false,
                'message' => 'Order inbox data is incomplete; cannot verify capacity for provider change.',
            ];
        }

        return [
            'success' => true,
            'message' => null,
            'total_inboxes' => $totalInboxes,
            'inboxes_per_domain' => $inboxesPerDomain,
        ];
    }

    /**
     * Mirror capacity settings used by CheckPanelCapacity command.
     */
    private function getProviderConfig(string $providerType): array
    {
        $providerLower = strtolower($providerType);
        if ($providerLower === 'microsoft 365') {
            $panelCapacity = Configuration::get('MICROSOFT_365_CAPACITY', env('MICROSOFT_365_CAPACITY', env('PANEL_CAPACITY', 1790)));
            $maxSplitCapacity = Configuration::get('MICROSOFT_365_MAX_SPLIT_CAPACITY', env('MICROSOFT_365_MAX_SPLIT_CAPACITY', env('MAX_SPLIT_CAPACITY', 358)));
            $enableMaxSplit = Configuration::get('ENABLE_MICROSOFT_365_MAX_SPLIT_CAPACITY', env('ENABLE_MICROSOFT_365_MAX_SPLIT_CAPACITY', true));
        } else {
            $panelCapacity = Configuration::get('GOOGLE_PANEL_CAPACITY', env('GOOGLE_PANEL_CAPACITY', env('PANEL_CAPACITY', 1790)));
            $maxSplitCapacity = Configuration::get('GOOGLE_MAX_SPLIT_CAPACITY', env('GOOGLE_MAX_SPLIT_CAPACITY', env('MAX_SPLIT_CAPACITY', 358)));
            $enableMaxSplit = Configuration::get('ENABLE_GOOGLE_MAX_SPLIT_CAPACITY', env('ENABLE_GOOGLE_MAX_SPLIT_CAPACITY', true));
        }

        if (!$enableMaxSplit) {
            $maxSplitCapacity = $panelCapacity;
        }

        return [
            'panel_capacity' => (int) $panelCapacity,
            'max_split_capacity' => (int) $maxSplitCapacity,
        ];
    }

    /**
     * Compute available panel space for the target provider.
     */
    private function computeAvailableSpace(int $inboxesPerDomain, string $providerType, int $panelCapacity, int $maxSplitCapacity): int
    {
        $panels = Panel::where('is_active', 1)
            ->where('limit', $panelCapacity)
            ->where('provider_type', $providerType)
            ->where('remaining_limit', '>=', $inboxesPerDomain)
            ->get();

        $availableSpace = 0;
        foreach ($panels as $panel) {
            $availableSpace += min($panel->remaining_limit, $maxSplitCapacity);
        }

        return (int) $availableSpace;
    }
}
