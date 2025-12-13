@props([
    // ID of the badge to update when pending pools exist
    'badgeId' => 'pendingPoolsBadge',
])

<div id="poolPanelCapacityAlertContainer"></div>

@once
@push('scripts')
<script>
(function() {
    const badgeId = @json($badgeId);
    const alertContainerId = 'poolPanelCapacityAlertContainer';

    function updatePendingBadge(count) {
        const badge = document.getElementById(badgeId);
        if (!badge) return;
        if (count && Number(count) > 0) {
            badge.classList.remove('d-none');
            badge.textContent = count;
        } else {
            badge.classList.add('d-none');
            badge.textContent = '0';
        }
    }

    window.refreshPoolPanelCapacityAlert = function refreshPoolPanelCapacityAlert() {
        fetch(@json(route('admin.pool-panels.capacity-alert')), {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(response => {
            const container = document.getElementById(alertContainerId);
            if (!container || !response.success) {
                return;
            }

            if (response.show_alert) {
                updatePendingBadge(response.insufficient_pools_count || 0);
                const alertHtml = `
                    <div id="poolPanelCapacityAlert" class="alert alert-warning alert-dismissible fade show py-2 rounded-1" role="alert"
                        style="background-color: rgba(255, 193, 7, 0.2); color: #fff; border: 2px solid #ffc107;">
                        <i class="ti ti-layer-group me-2 alert-icon"></i>
                        <strong>Pool Panel Capacity Alert:</strong>
                        ${response.total_pool_panels_needed} new pool panel${response.total_pool_panels_needed != 1 ? 's' : ''} required for ${response.insufficient_pools_count} pending pool${response.insufficient_pools_count != 1 ? 's' : ''} (${response.provider_type || ''}).
                        <a href="javascript:void(0)" onclick="showPoolAllocationDetails()" class="text-light alert-link">View Details</a> to see pending pools.
                        <button type="button" class="btn-close" style="padding: 11px" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                container.innerHTML = alertHtml;
            } else {
                container.innerHTML = '';
                updatePendingBadge(0);
            }
        })
        .catch(error => {
            console.error('Error refreshing pool panel capacity alert:', error);
        });
    };
})();
</script>
@endpush
@endonce
