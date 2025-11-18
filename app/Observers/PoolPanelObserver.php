<?php

namespace App\Observers;
use App\Models\PoolPanel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PoolPanelObserver
{
     /**
     * Handle the Pool-Panel "creating" event.
     *
     * Auto-assigns a provider-specific incremental panel_sr_no when creating a panel.
     */
    public function creating(PoolPanel $pool_panel): void
    {
        // Ensure provider_type has a default prior to DB default trigger
        if (empty($pool_panel->provider_type)) {
            $pool_panel->provider_type = 'Google';
        }

        // Only assign if provider_type is present and panel_sr_no not already provided
        if (!empty($pool_panel->provider_type) && empty($pool_panel->panel_sr_no)) {
            try {
                // Find current max serial for this provider_type and increment
                $pool_panel->panel_sr_no = PoolPanel::getNextSerialForProvider($pool_panel->provider_type);
            } catch (\Throwable $e) {
                Log::error('PanelObserver: Failed assigning pool_panel_sr_no', [
                    'provider_type' => $panel->provider_type,
                    'error' => $e->getMessage(),
                ]);
                // Allow creation to proceed; panel_sr_no will remain null
            }
        }
    }
}
