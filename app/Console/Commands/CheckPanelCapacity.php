<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderTracking;
use App\Models\Panel;
use App\Models\User;
use App\Mail\AdminPanelNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
        
        try {            // Get pending orders from order_tracking table
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
              // Get available panel capacity
            $availablePanelSpace = $this->getAvailablePanelSpace();
            $this->info("📦 Available panel space: {$availablePanelSpace}");
            
            // Calculate how many panels we need (always calculate, even if sufficient)
            $shortfall = max(0, $totalInboxesNeeded - $availablePanelSpace);
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
            
            $this->info("📧 Sending notifications to {$adminUsers->count()} admin(s)...");
            
            // Send notifications
            $sentCount = 0;
            foreach ($adminUsers as $admin) {
                if ($this->sendNotificationToAdmin($admin, $panelsNeeded, $totalInboxesNeeded, $availablePanelSpace, $isDryRun)) {
                    $sentCount++;
                }
            }
              if (!$isDryRun) {
                // Log the status report sent
                $this->logNotificationSent($panelsNeeded, $totalInboxesNeeded, $availablePanelSpace);
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
                      ->where('remaining_limit', 1790)
                      ->get();
        $totalAvailableSpace = 0;
        
        $this->info("📋 Found {$panels->count()} active panel(s) with remaining_limit = 1790:");
        
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
}
