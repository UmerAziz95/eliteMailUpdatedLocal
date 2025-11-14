<?php

namespace App\Observers;

use App\Models\Panel;
use App\Console\Commands\PanelCapacityNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PanelObserver
{
    /**
     * Handle the Panel "creating" event.
     *
     * Auto-assigns a provider-specific incremental panel_sr_no when creating a panel.
     */
    public function creating(Panel $panel): void
    {
        // Ensure provider_type has a default prior to DB default trigger
        if (empty($panel->provider_type)) {
            $panel->provider_type = 'Google';
        }

        // Only assign if provider_type is present and panel_sr_no not already provided
        if (!empty($panel->provider_type) && empty($panel->panel_sr_no)) {
            try {
                // Find current max serial for this provider_type and increment
                $max = Panel::query()
                    ->where('provider_type', $panel->provider_type)
                    ->max('panel_sr_no');

                $panel->panel_sr_no = ((int) $max) + 1;
            } catch (\Throwable $e) {
                Log::error('PanelObserver: Failed assigning panel_sr_no', [
                    'provider_type' => $panel->provider_type,
                    'error' => $e->getMessage(),
                ]);
                // Allow creation to proceed; panel_sr_no will remain null
            }
        }
    }

    /**
     * Handle the Panel "created" event.
     */
    public function created(Panel $panel): void
    {
        try {
            // Send Slack notification when a new panel is created
            PanelCapacityNotification::sendNewPanelNotification($panel);
            
            Log::info('PanelObserver: New panel notification sent', [
                'panel_id' => $panel->id,
                'panel_capacity' => $panel->limit,
                'created_by' => $panel->created_by
            ]);
            
        } catch (\Exception $e) {
            Log::error('PanelObserver: Failed to send new panel notification', [
                'panel_id' => $panel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
