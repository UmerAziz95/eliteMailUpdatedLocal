<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderTracking;
use App\Models\Panel;
use App\Models\User;
use App\Models\Order;
use App\Mail\AdminPanelNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Controllers\Customer\OrderController;

class CheckPanelCapacity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'panels:check-capacity 
                            {--dry-run : Run without sending actual emails}
                            {--force : Force send even if already sent today}';    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check pending orders and send regular status reports to all admins about panel capacity';

    /**
     * Panel capacity constant
     */
    const PANEL_CAPACITY = 1790;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');
        
        $this->info('🔍 Starting panel capacity check...');
        
        try {            
            // Get available panel capacity first
            $availablePanelSpace = $this->getAvailablePanelSpace();
            $this->info("📦 Available panel space: {$availablePanelSpace}");
            // Update order status to completed where space is available
            $this->updateOrderStatusForAvailableSpace($availablePanelSpace);
            
        } catch (\Exception $e) {
            $this->error("❌ Error occurred: " . $e->getMessage());
            Log::error('Panel capacity check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }    
    /**
     * Get available panel space
    */
    
    private function getAvailablePanelSpace(): int
    {
        $panels = Panel::where('is_active', 1)
                      ->where('remaining_limit', '>', 0)
                      ->get();
        $totalAvailableSpace = 0;
        
        $this->info("📋 Found {$panels->count()} active panel(s) with available space:");
        
        foreach ($panels as $panel) {
            $availableSpace = $panel->remaining_limit;
            $totalAvailableSpace += $availableSpace;
            $this->info("   Panel ID {$panel->id}: {$availableSpace} remaining");
        }
        
        return $totalAvailableSpace;
    }
    
    /**
     * Update order_tracking status to 'inprogress' for orders that have available space on panels
     */
    private function updateOrderStatusForAvailableSpace(int $availablePanelSpace): void
    {
        if ($availablePanelSpace <= 0) {
            $this->info('ℹ️  No available panel space, skipping order status updates.');
            return;
        }
        
        // Get pending orders that can be accommodated with available space
        $pendingOrders = OrderTracking::where('status', 'pending')
            ->whereNotNull('total_inboxes')
            ->where('total_inboxes', '>', 0)
            ->orderBy('created_at', 'asc') // Process older orders first
            ->get();
        
        if ($pendingOrders->isEmpty()) {
            $this->info('ℹ️  No pending orders to update.');
            return;
        }
        
        $remainingSpace = $availablePanelSpace;
        $updatedCount = 0;
        $totalProcessed = 0;
        $remainingTotalInboxes = 0;
        
        $this->info("🔄 Processing pending orders for status updates...");
        $this->info("   Available space: {$availablePanelSpace} inboxes");
        
        foreach ($pendingOrders as $order) {
            $totalProcessed++;            
            if ($order->total_inboxes <= $remainingSpace) {
                try {
                    // Get the actual Order model for panel split creation
                    $orderModel = Order::find($order->order_id);
                    
                    if ($orderModel) {
                        // Create panel splits before updating status
                        $this->info("   🔄 Creating panel splits for Order ID {$order->order_id}...");
                        
                        try {
                            $orderController = new OrderController();
                            $orderController->pannelCreationAndOrderSplitOnPannels($orderModel);
                            $this->info("   ✓ Panel splits created successfully for Order ID {$order->order_id}");
                            
                            // Log successful panel split creation
                            Log::info('Panel splits created for order', [
                                'order_id' => $order->order_id,
                                'total_inboxes' => $order->total_inboxes,
                                'created_at' => Carbon::now()
                            ]);
                            
                        } catch (\Exception $splitException) {
                            $this->error("   ✗ Failed to create panel splits for Order ID {$order->order_id}: " . $splitException->getMessage());
                            Log::error('Failed to create panel splits', [
                                'order_id' => $order->order_id,
                                'error' => $splitException->getMessage(),
                                'trace' => $splitException->getTraceAsString()
                            ]);
                            // Continue with status update even if split creation fails
                        }
                    } else {
                        $this->warn("   ⚠ Order model not found for Order ID {$order->order_id} - skipping panel split creation");
                        Log::warning('Order model not found for panel split creation', [
                            'order_tracking_id' => $order->id,
                            'order_id' => $order->order_id
                        ]);
                    }
                    
                    // Update status to completed
                    $order->status = 'completed';
                    $order->cron_run_time = Carbon::now();
                    $order->save();
                    
                    $remainingSpace -= $order->total_inboxes;
                    $updatedCount++;
                    
                    $this->info("   ✓ Order ID {$order->order_id}: {$order->total_inboxes} inboxes - Status updated to 'completed'");
                    
                } catch (\Exception $e) {
                    $this->error("   ✗ Failed to update Order ID {$order->order_id}: " . $e->getMessage());
                    Log::error('Failed to update order_tracking status', [
                        'order_id' => $order->order_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Add to remaining total if failed to update
                    $remainingTotalInboxes += $order->total_inboxes;
                }
            } else {
                $this->warn("   ⚠ Order ID {$order->order_id}: {$order->total_inboxes} inboxes - Insufficient space (need {$order->total_inboxes}, have {$remainingSpace})");
                // Add to remaining total for unprocessed orders
                $remainingTotalInboxes += $order->total_inboxes;
            }
            
            // If no space left, add remaining orders to total
            if ($remainingSpace <= 0) {
                // Add remaining orders in the collection to the total
                $remainingOrdersInLoop = $pendingOrders->slice($totalProcessed);
                $remainingTotalInboxes += $remainingOrdersInLoop->sum('total_inboxes');
                
                $this->info("   ℹ️  No remaining space, stopping order processing.");
                break;
            }
        }        
        $this->info("📊 Order Status Update Summary:");
        $this->info("   Total orders processed: {$totalProcessed}");
        $this->info("   Orders updated to 'completed': {$updatedCount}");
        $this->info("   Remaining panel space: {$remainingSpace} inboxes");
        
        if ($updatedCount > 0) {
            // Log the order updates
            $this->logOrderStatusUpdates($updatedCount, $availablePanelSpace - $remainingSpace);
        }    }
    
    /**
     * Log order status updates
     */
    private function logOrderStatusUpdates(int $updatedCount, int $spaceUsed): void
    {
        $logFile = storage_path('logs/order-status-updates.log');
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = sprintf(
            "[%s] Order status updates - Orders updated: %d, Space allocated: %d inboxes\n",
            Carbon::now()->format('Y-m-d H:i:s'),
            $updatedCount,
            $spaceUsed
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also log to Laravel log
        Log::info('Order tracking status updated', [
            'orders_updated' => $updatedCount,
            'space_allocated' => $spaceUsed
        ]);
    }
}
