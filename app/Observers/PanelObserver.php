<?php

namespace App\Observers;

use App\Models\Panel;
use App\Console\Commands\PanelCapacityNotification;
use Illuminate\Support\Facades\Log;

class PanelObserver
{
    /**
     * Handle the Panel "created" event.
     */
    public function created(Panel $panel): void
    {
        try {
            // Send Slack notification when a new panel is created
            PanelCapacityNotification::sendNewPanelNotification($panel);
            \App\Models\PanelCapacityAlert::cleanupOldAlerts();
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
