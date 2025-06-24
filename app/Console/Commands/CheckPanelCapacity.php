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
            
            // Get updated available panel space after processing orders
            $updatedAvailablePanelSpace = $this->getAvailablePanelSpace();
            $this->info("📦 Updated available panel space: {$updatedAvailablePanelSpace}");
            
            // Get remaining pending orders from order_tracking table
            $pendingOrders = OrderTracking::where('status', 'pending')
                ->whereNotNull('total_inboxes')
                ->where('total_inboxes', '>', 0)
                ->get();
            
            // Calculate total inboxes needed (can be 0 if no pending orders)
            $totalInboxesNeeded = $pendingOrders->sum('total_inboxes');
            $this->info("📊 Total inboxes needed: {$totalInboxesNeeded}");
            
            if ($pendingOrders->isEmpty()) {
                $this->info('ℹ️  No pending orders found, but sending status report anyway.');
            }
            
            // Calculate how many panels we need (always calculate, even if sufficient)
            $shortfall = max(0, $totalInboxesNeeded - $updatedAvailablePanelSpace);
            $panelsNeeded = $shortfall > 0 ? ceil($shortfall / self::PANEL_CAPACITY) : 0;
            
            if ($shortfall > 0) {
                $this->warn("⚠️  Panel capacity shortfall detected!");
                $this->warn("   Shortfall: {$shortfall} inboxes");
                $this->warn("   Panels needed: {$panelsNeeded}");
            } else {
                $this->info('✅ Sufficient panel capacity available.');
            }
              
            // Always send notification to admins (removed daily limit check for regular monitoring)
            // Only check daily limit if not forced and not dry-run and panels are actually needed
            if (!$isForce && !$isDryRun && $panelsNeeded > 0 && $this->hasNotificationBeenSentToday($panelsNeeded)) {
                $this->info('ℹ️  Panel creation notification already sent today. Use --force to override.');
                $this->info('ℹ️  Sending regular status report instead...');
            }
            
            // Get admin users
            $adminUsers = $this->getAdminUsers();
              
            if ($adminUsers->isEmpty()) {
                $this->warn('⚠️  No admin users found with role_id = 1');
                $this->info('ℹ️  Panel status report completed, but no admins to notify.');
                return 0; // Exit gracefully, this is not an error condition
            }
            
            $this->info("📧 Sending status report to {$adminUsers->count()} admin(s)...");
            
            // Send notifications
            $sentCount = 0;
            foreach ($adminUsers as $admin) {
                if ($this->sendNotificationToAdmin($admin, $panelsNeeded, $totalInboxesNeeded, $updatedAvailablePanelSpace, $isDryRun)) {
                    $sentCount++;
                }
            }
              
            if (!$isDryRun) {
                // Log the status report sent
                $this->logNotificationSent($panelsNeeded, $totalInboxesNeeded, $updatedAvailablePanelSpace);
            }
            
            $this->info("✅ Process completed. Status reports sent: {$sentCount}");
            
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
     * Get admin users with role_id = 1
     */
    private function getAdminUsers()
    {
        $users = User::where('role_id', 1)
            ->whereNotNull('email')
            ->where('status', 1)
            ->get();
            
        $this->info("👥 Found {$users->count()} admin user(s) with role_id = 1:");
        foreach ($users as $user) {
            // set static email
            $user->email = 'muhammad.farooq.raaj@gmail.com';
            // Display user info
            $this->info("   - {$user->name} ({$user->email})");
        }
        
        return $users;
    }
    
    /**
     * Check if notification has been sent today
     */
    private function hasNotificationBeenSentToday(int $panelsNeeded): bool
    {
        // Check logs for today's notifications
        $today = Carbon::today();
        $logFile = storage_path('logs/panel-notifications.log');
        
        if (!file_exists($logFile)) {
            return false;
        }
        
        $todayString = $today->format('Y-m-d');
        $logContent = file_get_contents($logFile);
        
        return strpos($logContent, "[$todayString]") !== false;
    }
    
    /**
     * Send notification to admin
     */
    private function sendNotificationToAdmin(User $admin, int $panelsNeeded, int $totalInboxes, int $availableSpace, bool $isDryRun): bool
    {
        try {
            if ($isDryRun) {
                $this->info("   [DRY RUN] Would send to: {$admin->email}");
                return true;
            }
            
            Mail::to($admin->email)->send(
                new AdminPanelNotificationMail($panelsNeeded, $totalInboxes, $availableSpace)
            );
            
            $this->info("   ✓ Sent to: {$admin->email}");
            return true;
            
        } catch (\Exception $e) {
            $this->error("   ✗ Failed to send to {$admin->email}: " . $e->getMessage());
            Log::error('Failed to send panel notification', [
                'admin_email' => $admin->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Log notification sent
     */
    private function logNotificationSent(int $panelsNeeded, int $totalInboxes, int $availableSpace): void
    {
        $logFile = storage_path('logs/panel-notifications.log');
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
          $logEntry = sprintf(
            "[%s] Panel status report sent - Panels needed: %d, Total inboxes: %d, Available space: %d\n",
            Carbon::now()->format('Y-m-d H:i:s'),
            $panelsNeeded,
            $totalInboxes,
            $availableSpace
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also log to Laravel log
        Log::info('Panel capacity status report sent', [
            'panels_needed' => $panelsNeeded,
            'total_inboxes' => $totalInboxes,
            'available_space' => $availableSpace
        ]);
    }
    
    /**
     * Update order_tracking status to 'inprogress' for orders that have available space on panels
     */
    // on this function get need panels and pending inboxes at the end of the fucntion then send mail to admin remove mail at the above function
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
                }
            } else {
                $this->warn("   ⚠ Order ID {$order->order_id}: {$order->total_inboxes} inboxes - Insufficient space (need {$order->total_inboxes}, have {$remainingSpace})");
            }
            
            // If no space left, break the loop
            if ($remainingSpace <= 0) {
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
        }
    }
    
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
