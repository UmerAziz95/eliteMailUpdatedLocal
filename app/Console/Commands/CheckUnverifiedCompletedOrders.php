<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\Log;

class CheckUnverifiedCompletedOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:check-unverified-completed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for completed orders that are not verified and send Slack notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting check for unverified completed orders...');
        
        try {
            // Get orders with status 'completed' but not verified
            $orders = Order::where('status_manage_by_admin', 'completed')
                          ->where('is_verified', 0)
                          ->with(['user', 'assignedTo', 'plan'])
                          ->get();
            
            $this->info("Found {$orders->count()} unverified completed orders");
            
            if ($orders->count() > 0) {
                foreach ($orders as $order) {
                    $this->sendUnverifiedNotification($order);
                }
                
                $this->info("Sent notifications for {$orders->count()} unverified orders");
            } else {
                $this->info('No unverified completed orders found');
            }
            
            $this->info('Check completed successfully');
            
        } catch (\Exception $e) {
            $this->error('Error checking unverified orders: ' . $e->getMessage());
            Log::error('CheckUnverifiedCompletedOrders Command Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send notification for unverified completed order
     *
     * @param \App\Models\Order $order
     * @return void
     */
    private function sendUnverifiedNotification($order)
    {
        try {
            SlackNotificationService::sendUnverifiedOrderNotification($order);
            
            Log::channel('slack_notifications')->info('Unverified order notification sent', [
                'order_id' => $order->id
            ]);
        } catch (\Exception $e) {
            Log::channel('slack_notifications')->error('Failed to send unverified order notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
