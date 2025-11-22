@props([
    'panelCapacity' => null,
    'maxSplitCapacity' => null,
])

@php
    // Pull dynamic capacities from configuration with provider-specific fallbacks
    $providerType = \App\Models\Configuration::get('PROVIDER_TYPE', env('PROVIDER_TYPE', 'Google'));
    $panelCapacity = $panelCapacity ?? (strtolower($providerType) === 'microsoft 365'
        ? \App\Models\Configuration::get('MICROSOFT_365_CAPACITY', env('MICROSOFT_365_CAPACITY', env('PANEL_CAPACITY', 1790)))
        : \App\Models\Configuration::get('GOOGLE_PANEL_CAPACITY', env('GOOGLE_PANEL_CAPACITY', env('PANEL_CAPACITY', 1790))));
    $maxSplitCapacity = $maxSplitCapacity ?? (strtolower($providerType) === 'microsoft 365'
        ? \App\Models\Configuration::get('MICROSOFT_365_MAX_SPLIT_CAPACITY', env('MICROSOFT_365_MAX_SPLIT_CAPACITY', env('MAX_SPLIT_CAPACITY', 358)))
        : \App\Models\Configuration::get('GOOGLE_MAX_SPLIT_CAPACITY', env('GOOGLE_MAX_SPLIT_CAPACITY', env('MAX_SPLIT_CAPACITY', 358))));

    // Build alert data on initial render
    $pendingOrders = \App\Models\OrderTracking::where('status', 'pending')
        ->whereNotNull('total_inboxes')
        ->where('total_inboxes', '>', 0)
        ->get();

    $insufficientSpaceOrders = [];
    $totalPanelsNeeded = 0;
    $totalInboxes = 0;

    $getAvailablePanelSpaceForOrder = function (int $orderSize, int $inboxesPerDomain) use ($panelCapacity, $maxSplitCapacity, $providerType) {
        if ($orderSize >= $panelCapacity) {
            // For large orders, prioritize full-capacity panels
            $fullCapacityPanels = \App\Models\Panel::where('is_active', 1)
                ->where('limit', $panelCapacity)
                ->where('provider_type', $providerType)
                ->where('remaining_limit', '>=', $inboxesPerDomain)
                ->get();

            $fullCapacitySpace = 0;
            foreach ($fullCapacityPanels as $panel) {
                $fullCapacitySpace += min($panel->remaining_limit, $maxSplitCapacity);
            }

            return $fullCapacitySpace;
        }

        // For smaller orders, use any panel with remaining space that can accommodate at least one domain
        $availablePanels = \App\Models\Panel::where('is_active', 1)
            ->where('limit', $panelCapacity)
            ->where('provider_type', $providerType)
            ->where('remaining_limit', '>=', $inboxesPerDomain)
            ->get();

        $totalSpace = 0;
        foreach ($availablePanels as $panel) {
            $totalSpace += min($panel->remaining_limit, $maxSplitCapacity);
        }

        return $totalSpace;
    };

    foreach ($pendingOrders as $order) {
        $inboxesPerDomain = $order->inboxes_per_domain ?? 1;
        $totalInboxes += $order->total_inboxes ?? 0;

        $availableSpace = $getAvailablePanelSpaceForOrder(
            $order->total_inboxes,
            $inboxesPerDomain
        );

        if ($order->total_inboxes > $availableSpace) {
            $panelsNeeded = ceil($order->total_inboxes / $maxSplitCapacity);
            $insufficientSpaceOrders[] = $order;
            $totalPanelsNeeded += $panelsNeeded;
        }
    }

    $availablePanelCount = \App\Models\Panel::where('is_active', true)
        ->where('limit', $panelCapacity)
        ->where('provider_type', $providerType)
        ->where('remaining_limit', '>=', $maxSplitCapacity)
        ->count();

    // Account for free space on active panels to avoid over-counting required panels
    $availablePanels = \App\Models\Panel::where('is_active', true)
        ->where('limit', $panelCapacity)
        ->where('provider_type', $providerType)
        ->where('remaining_limit', '>', 0)
        ->get();

    $totalSpaceAvailable = 0;
    foreach ($availablePanels as $panel) {
        $totalSpaceAvailable += min($panel->remaining_limit, $maxSplitCapacity);
    }

    $remainingAfterAvailable = max(0, $totalInboxes - $totalSpaceAvailable);
    $adjustedPanelsNeeded = (int) max(0, ceil($remainingAfterAvailable / $maxSplitCapacity));

@endphp

@if ($adjustedPanelsNeeded > 0)
    <div id="panelCapacityAlert" class="alert alert-danger alert-dismissible fade show py-2 rounded-1" role="alert"
        style="background-color: rgba(220, 53, 69, 0.2); color: #fff; border: 2px solid #dc3545;">
        <i class="ti ti-server me-2 alert-icon"></i>
        <strong>Panel Capacity Alert:</strong>
        {{ $adjustedPanelsNeeded }} new panel{{ $adjustedPanelsNeeded != 1 ? 's' : '' }} required for
        {{ count($insufficientSpaceOrders) }} pending order{{ count($insufficientSpaceOrders) != 1 ? 's' : '' }}
        ({{ $providerType }}).
        <a href="{{ route('admin.panels.index') }}" class="text-light alert-link">Manage Panels</a> to create additional
        capacity.
        <button type="button" class="btn-close" style="padding: 11px" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@once
    @push('scripts')
        <script>
            // Expose refresh function so pages can trigger live updates without duplicating logic
            window.refreshPanelCapacityAlert = function() {
                $.ajax({
                    url: '{{ route('admin.panels.capacity-alert') }}',
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        if (!response.success) {
                            return;
                        }

                        if (response.show_alert) {
                            const alertHtml = `
                                <div id="panelCapacityAlert" class="alert alert-danger alert-dismissible fade show py-2 rounded-1" role="alert"
                                    style="background-color: rgba(220, 53, 69, 0.2); color: #fff; border: 2px solid #dc3545;">
                                    <i class="ti ti-server me-2 alert-icon"></i>
                                    <strong>Panel Capacity Alert:</strong>
                                    ${response.total_panels_needed} new panel${response.total_panels_needed != 1 ? 's' : ''} required for ${response.insufficient_orders_count} pending order${response.insufficient_orders_count != 1 ? 's' : ''}.
                                    <a href="{{ route('admin.panels.index') }}" class="text-light alert-link">Manage Panels</a> to create additional capacity.
                                    <button type="button" class="btn-close" style="padding: 11px" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            `;

                            if ($('#panelCapacityAlert').length) {
                                $('#panelCapacityAlert').replaceWith(alertHtml);
                            } else {
                                $('#orderTrackingTableBody').closest('.card').after(alertHtml);
                            }
                        } else {
                            $('#panelCapacityAlert').remove();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error refreshing panel capacity alert:', error);
                    }
                });
            };
        </script>
    @endpush
@endonce
