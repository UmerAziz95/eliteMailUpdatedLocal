<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderTracking;
use App\Models\Panel;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\ReorderInfo;
use App\Mail\AdminPanelNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;

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
     * Panel capacity
     */
    public $PANEL_CAPACITY;
    
    /**
     * Constructor to initialize dynamic properties
     */
    public function __construct()
    {
        parent::__construct();
        $this->PANEL_CAPACITY = env('PANEL_CAPACITY', 1790); // Default to 1790 if not set in config
    }
    /**
     * Track orders with insufficient space for email notifications
     */
    private $insufficientSpaceOrders = [];

    /**
     * Execute the console command.
    */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');
        
        $this->info('🔍 Starting panel capacity check...');
        
        try {            
            // Update order status to completed where space is available
            $this->updateOrderStatusForAvailableSpace();
            
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
     * Get available panel space for specific order size
     */
    private function getAvailablePanelSpaceForOrder(int $orderSize): int
    {
        if ($orderSize >= $this->PANEL_CAPACITY) {
            // For large orders, prioritize full capacity panels
            $fullCapacityPanels = Panel::where('is_active', 1)
                                      ->where('remaining_limit', $this->PANEL_CAPACITY)
                                      ->get();
            
            $fullCapacitySpace = $fullCapacityPanels->sum('remaining_limit');

            $this->info("🔍 Available space for large order ({$orderSize} inboxes):");
            $this->info("   Full capacity panels: {$fullCapacityPanels->count()} panels, {$fullCapacitySpace} space");
            return $fullCapacitySpace;
            
        } else {
            // For smaller orders, use any panel with remaining space   
            $availablePanels = Panel::where('is_active', 1)
                                   ->where('remaining_limit', '>', 0)
                                   ->get();
            
            $totalSpace = $availablePanels->sum('remaining_limit');
            
            $this->info("🔍 Available space for small order ({$orderSize} inboxes): {$totalSpace} total space");

            return $totalSpace;
        }
    }
    
    /**
     * Update order_tracking status to 'completed' for orders that have available space on panels
     */
    private function updateOrderStatusForAvailableSpace(): void
    {
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

        $updatedCount = 0;
        $totalProcessed = 0;
        $remainingTotalInboxes = 0;
        
        $this->info("🔄 Processing pending orders for status updates...");
        
        foreach ($pendingOrders as $order) {
            $totalProcessed++;            
            
            // Get order-specific available space
            $orderSpecificSpace = $this->getAvailablePanelSpaceForOrder($order->total_inboxes);
            
            if ($order->total_inboxes <= $orderSpecificSpace) {
                try {
                    // Get the actual Order model for panel split creation
                    $orderModel = Order::find($order->order_id);
                    
                    if ($orderModel) {
                        // Create panel splits before updating status
                        $this->info("   🔄 Creating panel splits for Order ID {$order->order_id}...");
                          try {
                            $this->pannelCreationAndOrderSplitOnPannels($orderModel);
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
                $orderSpecificSpace = $this->getAvailablePanelSpaceForOrder($order->total_inboxes);
                $this->warn("   ⚠ Order ID {$order->order_id}: {$order->total_inboxes} inboxes - Insufficient space");
                $this->warn("     Order-specific available space: {$orderSpecificSpace}");
                
                // Calculate panels needed for this order
                $panelsNeeded = ceil($order->total_inboxes / $this->PANEL_CAPACITY);
                
                // Add to insufficient space orders for email notification
                $this->insufficientSpaceOrders[] = [
                    'order_id' => $order->order_id,
                    'required_space' => $order->total_inboxes,
                    'available_space' => $orderSpecificSpace,
                    'panels_needed' => $panelsNeeded,
                    'status' => 'pending'
                ];
                
                // Add to remaining total for unprocessed orders
                $remainingTotalInboxes += $order->total_inboxes;
            }
        }        
        $this->info("📊 Order Status Update Summary:");
        $this->info("   Total orders processed: {$totalProcessed}");
        $this->info("   Orders updated to 'completed': {$updatedCount}");
        
        if ($updatedCount > 0) {
            // Calculate total space used by successful orders
            $totalSpaceUsed = $pendingOrders->slice(0, $updatedCount)->sum('total_inboxes');
            // Log the order updates
            $this->logOrderStatusUpdates($updatedCount, $totalSpaceUsed);
        }
        
        // Send email notification if there are orders with insufficient space
        if (!empty($this->insufficientSpaceOrders)) {
            $this->sendInsufficientSpaceNotification();
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
    
    /**
     * Send email notification for orders with insufficient space
     */
    private function sendInsufficientSpaceNotification(): void
    {
        try {
            $isDryRun = $this->option('dry-run');
            
            // Get users with admin permissions using Spatie Permission
            $permissionName = 'Panels'; // You can change this to the appropriate permission name
            $adminUsers = User::permission($permissionName)->get();
            // Fallback: if no users found with permission, try role-based approach
            if ($adminUsers->isEmpty()) {
                $adminUsers = User::whereIn('role_id', [1, 2])
                    ->whereNotNull('email')
                    ->where('status', 1)
                    ->get();
            }
            
            if ($adminUsers->isEmpty()) {
                $this->warn('⚠️  No admin users found to send insufficient space notifications');
                return;
            }
            
            $this->info("📧 Sending insufficient space notifications to {$adminUsers->count()} admin(s)...");
            
            $sentCount = 0;
            foreach ($adminUsers as $admin) {
                if ($this->sendInsufficientSpaceEmail($admin, $isDryRun)) {
                    $sentCount++;
                }
            }
            
            $this->info("✅ Insufficient space notifications sent: {$sentCount}");
            
            // Log the notification
            Log::info('Insufficient space notifications sent', [
                'orders_count' => count($this->insufficientSpaceOrders),
                'admins_notified' => $sentCount,
                'orders' => $this->insufficientSpaceOrders
            ]);
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to send insufficient space notifications: " . $e->getMessage());
            Log::error('Failed to send insufficient space notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Send insufficient space email to a specific admin
     */
    private function sendInsufficientSpaceEmail(User $admin, bool $isDryRun): bool
    {
        try {
            if ($isDryRun) {
                $this->info("   [DRY RUN] Would send insufficient space notification to: {$admin->email}");
                return true;
            }
            
            // Calculate total panels needed
            $totalPanelsNeeded = array_sum(array_column($this->insufficientSpaceOrders, 'panels_needed'));
            $totalSpaceNeeded = array_sum(array_column($this->insufficientSpaceOrders, 'required_space'));
            
            Mail::to($admin->email)->send(
                new AdminPanelNotificationMail(
                    $totalPanelsNeeded,
                    $totalSpaceNeeded,
                    0, // Available space is 0 since orders couldn't be processed
                    $this->insufficientSpaceOrders
                )
            );
            
            $this->info("   ✓ Sent insufficient space notification to: {$admin->email}");
            return true;
            
        } catch (\Exception $e) {
            $this->error("   ✗ Failed to send to {$admin->email}: " . $e->getMessage());
            Log::error('Failed to send insufficient space notification', [
                'admin_email' => $admin->email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Panel creation and order split on panels - moved from OrderController
     */
    private function pannelCreationAndOrderSplitOnPannels($order)
    {
        try {
            // Wrap everything in a database transaction for consistency
            DB::beginTransaction();
            
            // Get the reorder info for this orders
            $reorderInfo = $order->reorderInfo()->first();
            
            if (!$reorderInfo) {
                Log::warning("No reorder info found for order #{$order->id}");
                DB::rollBack();
                return;
            }
            
            // Calculate total space needed
            $domains = array_filter(preg_split('/[\r\n,]+/', $reorderInfo->domains));
            $domainCount = count($domains);
            $totalSpaceNeeded = $domainCount * $reorderInfo->inboxes_per_domain;
            
            Log::info("Panel creation started for order #{$order->id}", [
                'total_space_needed' => $totalSpaceNeeded,
                'domain_count' => $domainCount,
                'inboxes_per_domain' => $reorderInfo->inboxes_per_domain
            ]);
            
            // Decision point: >= PANEL_CAPACITY creates new panels, < PANEL_CAPACITY tries to use existing panels
            if ($totalSpaceNeeded >= $this->PANEL_CAPACITY) {
                $this->createNewPanel($order, $reorderInfo, $domains, $totalSpaceNeeded);
            } else {
                // Try to find existing panel with sufficient space
                $suitablePanel = $this->findSuitablePanel($totalSpaceNeeded);
                
                if ($suitablePanel) {
                    // Assign entire order to this panel
                    $this->assignDomainsToPanel($suitablePanel, $order, $reorderInfo, $domains, $totalSpaceNeeded, 1);
                    Log::info("Order #{$order->id} assigned to existing panel #{$suitablePanel->id}");
                } else {
                    // No single panel can fit, try intelligent splitting across available panels
                    $this->handleOrderSplitAcrossAvailablePanels($order, $reorderInfo, $domains, $totalSpaceNeeded);
                }
            }
            
            DB::commit();
            Log::info("Panel creation completed successfully for order #{$order->id}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Panel creation failed for order #{$order->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Create new panel(s) - first check for existing unused 1790 panels before creating new ones
     */
    private function createNewPanel($order, $reorderInfo, $domains, $spaceNeeded)
    {
        if ($spaceNeeded > $this->PANEL_CAPACITY) {
            // Split across multiple panels - but first check for existing PANEL_CAPACITY panels
            $this->splitOrderAcrossMultiplePanels($order, $reorderInfo, $domains, $spaceNeeded);
        } else {
            // First check if there's an existing PANEL_CAPACITY panel with sufficient space
            $existing1790Panel = $this->findExisting1790Panel($spaceNeeded);
            
            if ($existing1790Panel) {
                // Use existing PANEL_CAPACITY panel instead of creating new one
                $this->assignDomainsToPanel($existing1790Panel, $order, $reorderInfo, $domains, $spaceNeeded, 1);
                Log::info("Used existing PANEL_CAPACITY panel #{$existing1790Panel->id} for order #{$order->id} (space needed: {$spaceNeeded})");
            } else {
                // No suitable existing PANEL_CAPACITY panel found, create new one
                $panel = $this->createSinglePanel($spaceNeeded);
                $this->assignDomainsToPanel($panel, $order, $reorderInfo, $domains, $spaceNeeded, 1);
                Log::info("Created new panel #{$panel->id} for order #{$order->id} (no suitable PANEL_CAPACITY panel available)");
            }
        }
    }
    
    /**
     * Split large orders across multiple new panels
     */
    private function splitOrderAcrossMultiplePanels($order, $reorderInfo, $domains, $totalSpaceNeeded)
    {
        $remainingSpace = $totalSpaceNeeded;
        $splitNumber = 1;
        $domainsProcessed = 0;
        
        while ($remainingSpace > 0 && $domainsProcessed < count($domains) && $splitNumber <= 20) { // Safety check to prevent infinite loops
            $spaceForThisPanel = min($this->PANEL_CAPACITY, $remainingSpace);
            
            // Calculate maximum domains that can fit in this panel without exceeding capacity
            $maxDomainsForThisPanel = floor($spaceForThisPanel / $reorderInfo->inboxes_per_domain);
            
            // Ensure we don't process more domains than remaining
            $remainingDomains = count($domains) - $domainsProcessed;
            $domainsForThisPanel = min($maxDomainsForThisPanel, $remainingDomains);
            
            Log::info("Panel split calculation", [
                'split_number' => $splitNumber,
                'space_for_panel' => $spaceForThisPanel,
                'inboxes_per_domain' => $reorderInfo->inboxes_per_domain,
                'max_domains_for_panel' => $maxDomainsForThisPanel,
                'remaining_domains' => $remainingDomains,
                'domains_for_this_panel' => $domainsForThisPanel,
                'domains_processed_so_far' => $domainsProcessed
            ]);
            
            // Extract domains for this panel
            $domainsToAssign = array_slice($domains, $domainsProcessed, $domainsForThisPanel);
            $actualSpaceUsed = count($domainsToAssign) * $reorderInfo->inboxes_per_domain;
            
            $panel = null;

            // Always check for existing PANEL_CAPACITY panels first before creating new ones
            $existing1790Panel = $this->findExisting1790Panel($actualSpaceUsed);
            
            if ($existing1790Panel) {
                $panel = $existing1790Panel;
                Log::info("Using existing PANEL_CAPACITY panel #{$panel->id} (split #{$splitNumber})", [
                    'remaining_space' => $remainingSpace,
                    'space_needed' => $actualSpaceUsed,
                    'panel_available_space' => $panel->remaining_limit,
                    'panel_limit' => $panel->limit
                ]);
            } else {
                // If no PANEL_CAPACITY panel available, check for any other existing panel with sufficient space
                $existingPanel = Panel::where('is_active', true)
                    ->where('remaining_limit', '>=', $actualSpaceUsed)
                    ->orderBy('remaining_limit', 'desc')
                    ->first();
                
                if ($existingPanel) {
                    $panel = $existingPanel;
                    Log::info("Using existing panel #{$panel->id} (split #{$splitNumber}) - no PANEL_CAPACITY panel available", [
                        'remaining_space' => $remainingSpace,
                        'space_needed' => $actualSpaceUsed,
                        'panel_available_space' => $panel->remaining_limit,
                        'panel_limit' => $panel->limit
                    ]);
                }
            }
            
            // If no suitable existing panel found, create new PANEL_CAPACITY panel
            if (!$panel) {
                $panel = $this->createSinglePanel($this->PANEL_CAPACITY);
                Log::info("Created new PANEL_CAPACITY panel #{$panel->id} (split #{$splitNumber}) for order #{$order->id}", [
                    'remaining_space' => $remainingSpace,
                    'space_needed' => $actualSpaceUsed,
                    'reason' => 'no_existing_panel_with_sufficient_space'
                ]);
            }
            
            // Assign domains to this panel
            $this->assignDomainsToPanel($panel, $order, $reorderInfo, $domainsToAssign, $actualSpaceUsed, $splitNumber);
            
            Log::info("Assigned to panel #{$panel->id} (split #{$splitNumber}) for order #{$order->id}", [
                'space_used' => $actualSpaceUsed,
                'domains_count' => count($domainsToAssign),
                'remaining_space' => $remainingSpace - $actualSpaceUsed,
                'panel_type' => $panel->wasRecentlyCreated ? 'new' : 'existing'
            ]);
            
            $remainingSpace -= $actualSpaceUsed;
            $domainsProcessed += count($domainsToAssign);
            $splitNumber++;
        }
        
        // Check if all domains have been processed
        $totalDomainsToProcess = count($domains);
        if ($domainsProcessed < $totalDomainsToProcess) {
            $remainingDomains = array_slice($domains, $domainsProcessed);
            $remainingSpace = count($remainingDomains) * $reorderInfo->inboxes_per_domain;
            
            Log::warning("Some domains were not processed, creating additional panel", [
                'order_id' => $order->id,
                'domains_processed' => $domainsProcessed,
                'total_domains' => $totalDomainsToProcess,
                'remaining_domains' => count($remainingDomains),
                'remaining_space' => $remainingSpace
            ]);
            
            // Create additional panel for remaining domains
            $panel = $this->createSinglePanel($this->PANEL_CAPACITY);
            $this->assignDomainsToPanel($panel, $order, $reorderInfo, $remainingDomains, $remainingSpace, $splitNumber);
        }
        
        if ($remainingSpace > 0) {
            Log::warning("Still have remaining space after panel creation", [
                'order_id' => $order->id,
                'remaining_space' => $remainingSpace
            ]);
        }
    }
    
    /**
     * Handle intelligent splitting across existing available panels
     */
    private function handleOrderSplitAcrossAvailablePanels($order, $reorderInfo, $domains, $totalSpaceNeeded)
    {
        // Get all panels with available space, ordered by remaining space (least first for optimal allocation)
        $availablePanels = Panel::where('is_active', true)
            ->where('remaining_limit', '>', 0)
            ->orderBy('remaining_limit', 'desc')
            ->get();
        
        if ($availablePanels->isEmpty()) {
            // No available panels, create new one
            $panel = $this->createSinglePanel($this->PANEL_CAPACITY);
            $this->assignDomainsToPanel($panel, $order, $reorderInfo, $domains, $totalSpaceNeeded, 1);
            Log::info("No available panels found, created new panel #{$panel->id} for order #{$order->id}");
            return;
        }
        
        $remainingSpace = $totalSpaceNeeded;
        $domainsProcessed = 0;
        $splitNumber = 1;
        
        foreach ($availablePanels as $panel) {
            if ($remainingSpace <= 0) break;
            
            $availableSpace = $panel->remaining_limit;
            $spaceToUse = min($availableSpace, $remainingSpace);
            
            // Calculate maximum domains that can fit in available space without exceeding capacity
            $maxDomainsForSpace = floor($spaceToUse / $reorderInfo->inboxes_per_domain);
            
            // Ensure we don't process more domains than remaining
            $remainingDomains = count($domains) - $domainsProcessed;
            $domainsToAssign = min($maxDomainsForSpace, $remainingDomains);
            
            // Extract domains for this panel
            $domainSlice = array_slice($domains, $domainsProcessed, $domainsToAssign);
            $actualSpaceUsed = count($domainSlice) * $reorderInfo->inboxes_per_domain;
            
            // Only proceed if we can actually use this panel
            if ($actualSpaceUsed <= $availableSpace && count($domainSlice) > 0) {
                $this->assignDomainsToPanel($panel, $order, $reorderInfo, $domainSlice, $actualSpaceUsed, $splitNumber);
                Log::info("Assigned to existing panel #{$panel->id} (split #{$splitNumber}) for order #{$order->id}", [
                    'space_used' => $actualSpaceUsed,
                    'domains_count' => count($domainSlice),
                    'panel_remaining_before' => $availableSpace,
                    'panel_remaining_after' => $availableSpace - $actualSpaceUsed
                ]);
                
                $remainingSpace -= $actualSpaceUsed;
                $domainsProcessed += count($domainSlice);
                $splitNumber++;
            }
        }
        
        // Check if all domains have been processed
        $totalDomainsToProcess = count($domains);
        if ($domainsProcessed < $totalDomainsToProcess) {
            $remainingDomains = array_slice($domains, $domainsProcessed);
            $remainingSpace = count($remainingDomains) * $reorderInfo->inboxes_per_domain;
            
            Log::info("Processing remaining domains not assigned to existing panels", [
                'order_id' => $order->id,
                'domains_processed' => $domainsProcessed,
                'total_domains' => $totalDomainsToProcess,
                'remaining_domains' => count($remainingDomains),
                'remaining_space' => $remainingSpace
            ]);
            
            if (!empty($remainingDomains)) {
                $panel = $this->createSinglePanel($this->PANEL_CAPACITY);
                $this->assignDomainsToPanel($panel, $order, $reorderInfo, $remainingDomains, $remainingSpace, $splitNumber);
                Log::info("Created additional panel #{$panel->id} for remaining domains in order #{$order->id}", [
                    'remaining_domains' => count($remainingDomains),
                    'remaining_space' => $remainingSpace
                ]);
            }
        }
        
        // Legacy check for remaining space (should be covered by domain check above)
        if ($remainingSpace > 0 && $domainsProcessed >= $totalDomainsToProcess) {
            Log::warning("Remaining space detected but all domains processed - this should not happen", [
                'order_id' => $order->id,
                'remaining_space' => $remainingSpace,
                'domains_processed' => $domainsProcessed,
                'total_domains' => $totalDomainsToProcess
            ]);
        }
    }
    
    /**
     * Find suitable existing panel with sufficient space based on order size
     */
    private function findSuitablePanel($spaceNeeded)
    {
        return Panel::where('is_active', true)
            ->where('remaining_limit', '>=', $spaceNeeded)
            ->orderBy('remaining_limit', 'desc') // Use panel with least available space first
            ->first();
    }
    
    /**
     * Find existing PANEL_CAPACITY panel with sufficient space - prioritize full capacity panels
     */
    private function findExisting1790Panel($spaceNeeded)
    {
        return Panel::where('is_active', true)
            ->where('limit', $this->PANEL_CAPACITY)
            ->where('remaining_limit', '>=', $spaceNeeded)
            ->orderBy('remaining_limit', 'desc') // Use panel with most available space first for efficiency
            ->first();
    }
    
    /**
     * Create a single panel with specified capacity
     */
    private function createSinglePanel($capacity = null)
    {
        if ($capacity === null) {
            $capacity = $this->PANEL_CAPACITY;
        }
        
        $panel = Panel::create([
            'auto_generated_id' => 'PANEL_' . strtoupper(uniqid()),
            'title' => 'Auto Generated Panel - ' . date('Y-m-d H:i:s'),
            'description' => 'Automatically created panel for order processing',
            'limit' => $capacity,
            'remaining_limit' => $capacity,
            'is_active' => true,
            'created_by' => 'system'
        ]);
        
        Log::info("Created new panel #{$panel->id} with capacity {$capacity}");
        return $panel;
    }
    
    /**
     * Assign domains to a specific panel and create all necessary records
     */
    private function assignDomainsToPanel($panel, $order, $reorderInfo, $domainsToAssign, $spaceToAssign, $splitNumber)
    {
        try {
            // Create order_panel record
            $orderPanel = OrderPanel::create([
                'panel_id' => $panel->id,
                'order_id' => $order->id,
                'contractor_id' => null, // Will be assigned later
                'space_assigned' => $spaceToAssign,
                'inboxes_per_domain' => $reorderInfo->inboxes_per_domain,
                'status' => 'unallocated',
                'note' => "Auto-assigned split #{$splitNumber} - {$spaceToAssign} inboxes across " . count($domainsToAssign) . " domains"
            ]);
            
            // Create order_panel_split record
            OrderPanelSplit::create([
                'panel_id' => $panel->id,
                'order_panel_id' => $orderPanel->id,
                'order_id' => $order->id,
                'inboxes_per_domain' => $reorderInfo->inboxes_per_domain,
                'domains' => $domainsToAssign
            ]);
            
            // Update panel remaining capacity
            $panel->decrement('remaining_limit', $spaceToAssign);
            // Ensure remaining_limit never goes below 0
            if ($panel->remaining_limit < 0) {
                $panel->update(['remaining_limit' => 0]);
            }
            
            Log::info("Successfully assigned domains to panel", [
                'panel_id' => $panel->id,
                'order_id' => $order->id,
                'order_panel_id' => $orderPanel->id,
                'space_assigned' => $spaceToAssign,
                'domains_count' => count($domainsToAssign),
                'panel_remaining_limit' => $panel->remaining_limit - $spaceToAssign
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to assign domains to panel", [
                'panel_id' => $panel->id,
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
