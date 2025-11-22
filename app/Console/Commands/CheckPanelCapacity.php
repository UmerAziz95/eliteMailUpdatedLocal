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
use App\Models\Configuration;
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
     * Provider type used when fetching panels
     */
    public $PROVIDER_TYPE;
    
    /**
     * Maximum inboxes per panel split
     * 
     * This setting controls how orders are split across panels:
     * - Orders <= MAX_SPLIT_CAPACITY: Try to fit in existing panels or create single panel
     * - Orders > MAX_SPLIT_CAPACITY: Split into chunks of MAX_SPLIT_CAPACITY or less
     * 
     * Example with MAX_SPLIT_CAPACITY = 358:
     * - 999 inboxes with inboxes_per_domain = 1: Creates 3 splits: 358, 358, 283
     * - 999 inboxes with inboxes_per_domain = 2: Creates 3 splits: 358, 358, 284 (rounded to fit domains)
     * - 999 inboxes with inboxes_per_domain = 3: Creates 3 splits: 357, 357, 285 (rounded to fit domains)
     */
    public $MAX_SPLIT_CAPACITY;
    
    /**
     * Constructor to initialize dynamic properties
     */
    public function __construct()
    {
        parent::__construct();
        // Resolve provider-specific panel capacity with sensible fallbacks
        $providerType = Configuration::get('PROVIDER_TYPE', env('PROVIDER_TYPE', 'Google'));
        $this->PROVIDER_TYPE = $providerType;
        if (strtolower($providerType) === 'microsoft 365') {
            $this->PANEL_CAPACITY = Configuration::get('MICROSOFT_365_CAPACITY', env('MICROSOFT_365_CAPACITY', env('PANEL_CAPACITY', 1790)));
            $this->MAX_SPLIT_CAPACITY = Configuration::get('MICROSOFT_365_MAX_SPLIT_CAPACITY', env('MICROSOFT_365_MAX_SPLIT_CAPACITY', env('MAX_SPLIT_CAPACITY', 358)));
        } else {
            $this->PANEL_CAPACITY = Configuration::get('GOOGLE_PANEL_CAPACITY', env('GOOGLE_PANEL_CAPACITY', env('PANEL_CAPACITY', 1790)));
            $this->MAX_SPLIT_CAPACITY = Configuration::get('GOOGLE_MAX_SPLIT_CAPACITY', env('GOOGLE_MAX_SPLIT_CAPACITY', env('MAX_SPLIT_CAPACITY', 358)));
        }
    
        // Provider-specific split toggles
        $enableMaxSplit = true;
        if (strtolower($providerType) === 'microsoft 365') {
            $enableMaxSplit = Configuration::get('ENABLE_MICROSOFT_365_MAX_SPLIT_CAPACITY', env('ENABLE_MICROSOFT_365_MAX_SPLIT_CAPACITY', true));
        } else {
            $enableMaxSplit = Configuration::get('ENABLE_GOOGLE_MAX_SPLIT_CAPACITY', env('ENABLE_GOOGLE_MAX_SPLIT_CAPACITY', true));
        }
        if (!$enableMaxSplit) {
            $this->MAX_SPLIT_CAPACITY = $this->PANEL_CAPACITY;
        }
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
    private function getAvailablePanelSpaceForOrder(int $orderSize, int $inboxesPerDomain): int
    {
        if ($orderSize >= $this->PANEL_CAPACITY) {
            // For large orders, prioritize full capacity panels
            $fullCapacityPanels = Panel::where('is_active', 1)
                                        ->where('limit', $this->PANEL_CAPACITY)
                                        ->where('provider_type', $this->PROVIDER_TYPE)
                                        // ->where('remaining_limit', $this->PANEL_CAPACITY)
                                        ->where('remaining_limit', '>=', $inboxesPerDomain)
                                        ->get();
            
            $fullCapacitySpace = 0;
            foreach ($fullCapacityPanels as $panel) {
                $fullCapacitySpace += min($panel->remaining_limit, $this->MAX_SPLIT_CAPACITY);
            }

            $this->info("🔍 Available space for large order ({$orderSize} inboxes):");
            $this->info("   Full capacity panels: {$fullCapacityPanels->count()} panels, {$fullCapacitySpace} space");
            
            return $fullCapacitySpace;
            
        } else {
            // For smaller orders, use any panel with remaining space that can accommodate at least one domain
            $availablePanels = Panel::where('is_active', 1)
                                    ->where('limit', $this->PANEL_CAPACITY)
                                    ->where('provider_type', $this->PROVIDER_TYPE)
                                    ->where('remaining_limit', '>=', $inboxesPerDomain)
                                    ->get();
            
            $totalSpace = 0;
            foreach ($availablePanels as $panel) {
                $totalSpace += min($panel->remaining_limit, $this->MAX_SPLIT_CAPACITY);
            }
            
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
            $orderSpecificSpace = $this->getAvailablePanelSpaceForOrder($order->total_inboxes, $order->inboxes_per_domain);
            // dd($orderSpecificSpace, $order->total_inboxes, $order->inboxes_per_domain, $this->PANEL_CAPACITY, $this->MAX_SPLIT_CAPACITY);
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
                $orderSpecificSpace = $this->getAvailablePanelSpaceForOrder($order->total_inboxes, $order->inboxes_per_domain);
                $this->warn("   ⚠ Order ID {$order->order_id}: {$order->total_inboxes} inboxes - Insufficient space");
                $this->warn("     Order-specific available space: {$orderSpecificSpace}");
                
                // Calculate panels needed for this order
                $panelsNeeded = ceil($order->total_inboxes / $this->MAX_SPLIT_CAPACITY);
                
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
            // get panel greater than max split 
            $availablePanelCount = Panel::where('is_active', true)
                ->where('limit', $this->PANEL_CAPACITY)
                ->where('provider_type', $this->PROVIDER_TYPE)
                ->where('remaining_limit', '>=', $this->MAX_SPLIT_CAPACITY)
                ->count();
            $totalPanelsNeeded -= $availablePanelCount; // Adjust total panels needed based on available panels
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
                'max_split_capacity' => $this->MAX_SPLIT_CAPACITY,
                'panel_capacity' => $this->PANEL_CAPACITY,
                'domain_count' => $domainCount,
                'inboxes_per_domain' => $reorderInfo->inboxes_per_domain
            ]);
            // Only try to use existing panels - no automatic panel creation
            if ($totalSpaceNeeded <= $this->MAX_SPLIT_CAPACITY) {
                // Try to find existing panel with sufficient space for small orders (<= 358 inboxes)
                $suitablePanel = $this->findSuitablePanel($totalSpaceNeeded);
                
                if ($suitablePanel) {
                    // Assign entire order to this panel
                    $this->assignDomainsToPanel($suitablePanel, $order, $reorderInfo, $domains, $totalSpaceNeeded, 1);
                    Log::info("Order #{$order->id} assigned to existing panel #{$suitablePanel->id}");
                } else {
                    // No single panel can fit, try intelligent splitting across available panels
                    $this->handleOrderSplitAcrossAvailablePanels($order, $reorderInfo, $domains, $totalSpaceNeeded);
                }
            } else {
                // Large orders: try intelligent splitting across available panels only
                $this->handleOrderSplitAcrossAvailablePanels($order, $reorderInfo, $domains, $totalSpaceNeeded);
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
     * Handle intelligent splitting across existing available panels
     */
    private function handleOrderSplitAcrossAvailablePanels($order, $reorderInfo, $domains, $totalSpaceNeeded)
    {
        $remainingSpace = $totalSpaceNeeded;
        $domainsProcessed = 0;
        $splitNumber = 1;
        $usedPanelIds = []; // Track panels already used for this order
        
        // Keep looping until all domains are processed or no more panels available
        while ($remainingSpace > 0 && $domainsProcessed < count($domains)) {
            // Re-fetch available panels with updated remaining_limit for each iteration
            // Exclude panels that have already been used for this order
            $availablePanel = Panel::where('is_active', true)
                ->where('limit', $this->PANEL_CAPACITY)
                ->where('provider_type', $this->PROVIDER_TYPE)
                // ->where('remaining_limit', '>', 0)
                ->where('remaining_limit', '>=', $reorderInfo->inboxes_per_domain)
                ->whereNotIn('id', $usedPanelIds) // Exclude already used panels
                ->orderBy('remaining_limit', 'desc')
                ->first();
            
            if (!$availablePanel) {
                // No available panels, skip the remaining order
                Log::warning("No available panels found for order #{$order->id} - remaining domains will be skipped", [
                    'total_space_needed' => $totalSpaceNeeded,
                    'remaining_space' => $remainingSpace,
                    'domains_processed' => $domainsProcessed,
                    'total_domains' => count($domains),
                    'used_panels_count' => count($usedPanelIds),
                    'excluded_panel_ids' => $usedPanelIds,
                    'reason' => 'no_existing_panels_available'
                ]);
                break;
            }
            
            $availableSpace = $availablePanel->remaining_limit;
            // Limit space to use based on MAX_SPLIT_CAPACITY
            $spaceToUse = min($availableSpace, $remainingSpace, $this->MAX_SPLIT_CAPACITY);
            
            // Calculate maximum domains that can fit in space without exceeding MAX_SPLIT_CAPACITY
            $maxDomainsForSpace = floor($spaceToUse / $reorderInfo->inboxes_per_domain);
            
            // Ensure we don't process more domains than remaining
            $remainingDomains = count($domains) - $domainsProcessed;
            $domainsToAssign = min($maxDomainsForSpace, $remainingDomains);
            
            // Extract domains for this panel
            $domainSlice = array_slice($domains, $domainsProcessed, $domainsToAssign);
            $actualSpaceUsed = count($domainSlice) * $reorderInfo->inboxes_per_domain;
            
            // Only proceed if we can actually use this panel and respect MAX_SPLIT_CAPACITY
            if ($actualSpaceUsed <= $availableSpace && $actualSpaceUsed <= $this->MAX_SPLIT_CAPACITY && count($domainSlice) > 0) {
                $this->assignDomainsToPanel($availablePanel, $order, $reorderInfo, $domainSlice, $actualSpaceUsed, $splitNumber);
                
                // Add this panel to the used panels list to prevent reuse for this order
                $usedPanelIds[] = $availablePanel->id;
                
                Log::info("Assigned to existing panel #{$availablePanel->id} (split #{$splitNumber}) for order #{$order->id}", [
                    'space_used' => $actualSpaceUsed,
                    'max_split_capacity' => $this->MAX_SPLIT_CAPACITY,
                    'domains_count' => count($domainSlice),
                    'panel_remaining_before' => $availableSpace,
                    'panel_remaining_after' => $availableSpace - $actualSpaceUsed,
                    'used_panels_count' => count($usedPanelIds)
                ]);
                
                $remainingSpace -= $actualSpaceUsed;
                $domainsProcessed += count($domainSlice);
                $splitNumber++;
            } else {
                // If we can't use the available panel, break to avoid infinite loop
                Log::warning("Cannot use available panel #{$availablePanel->id} for order #{$order->id}", [
                    'actual_space_used' => $actualSpaceUsed,
                    'available_space' => $availableSpace,
                    'max_split_capacity' => $this->MAX_SPLIT_CAPACITY,
                    'domains_count' => count($domainSlice),
                    'used_panels_count' => count($usedPanelIds)
                ]);
                break;
            }
        }
        
        // Check if all domains have been processed
        $totalDomainsToProcess = count($domains);
        if ($domainsProcessed < $totalDomainsToProcess) {
            $remainingDomains = array_slice($domains, $domainsProcessed);
            $remainingSpaceNeeded = count($remainingDomains) * $reorderInfo->inboxes_per_domain;
            
            Log::warning("Incomplete order processing - rolling back all splits for order #{$order->id}", [
                'order_id' => $order->id,
                'domains_processed' => $domainsProcessed,
                'total_domains' => $totalDomainsToProcess,
                'remaining_domains' => count($remainingDomains),
                'remaining_space' => $remainingSpaceNeeded,
                'reason' => 'insufficient_existing_panel_space'
            ]);
            
            // Rollback all splits for this order
            $this->rollbackOrderSplits($order);
            
            // Throw exception to rollback the entire transaction
            throw new \Exception("Order #{$order->id} could not be fully processed - all splits rolled back");
        }
    }
    
    /**
     * Find suitable existing panel with sufficient space based on order size
     */
    private function findSuitablePanel($spaceNeeded)
    {
        return Panel::where('is_active', true)
            ->where('limit', $this->PANEL_CAPACITY)
            ->where('provider_type', $this->PROVIDER_TYPE)
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
            ->where('provider_type', $this->PROVIDER_TYPE)
            ->where('remaining_limit', '>=', $spaceNeeded)
            ->orderBy('remaining_limit', 'desc') // Use panel with most available space first for efficiency
            ->first();
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
                'contractor_id' => $order->assigned_to ?? null, // Assign to specific contractor if order has one
                'space_assigned' => $spaceToAssign,
                'inboxes_per_domain' => $reorderInfo->inboxes_per_domain,
                'status' => $order->assigned_to ? 'allocated' : 'unallocated', // Default to unallocated if no contractor assigned
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
    
    /**
     * Rollback all splits for an order - restore panel capacity and delete records
     */
    private function rollbackOrderSplits($order)
    {
        try {
            // Get all order panels for this order
            $orderPanels = OrderPanel::where('order_id', $order->id)->get();
            
            if ($orderPanels->isEmpty()) {
                Log::info("No order panels found to rollback for order #{$order->id}");
                return;
            }
            
            $rollbackCount = 0;
            foreach ($orderPanels as $orderPanel) {
                // Restore panel capacity
                $panel = Panel::find($orderPanel->panel_id);
                if ($panel) {
                    $panel->increment('remaining_limit', $orderPanel->space_assigned);
                    Log::info("Restored panel capacity", [
                        'panel_id' => $panel->id,
                        'space_restored' => $orderPanel->space_assigned,
                        'panel_remaining_limit' => $panel->remaining_limit
                    ]);
                }
                
                // Delete order panel splits
                OrderPanelSplit::where('order_panel_id', $orderPanel->id)->delete();
                
                // Delete order panel
                $orderPanel->delete();
                
                $rollbackCount++;
            }
            
            Log::info("Successfully rolled back all splits for order #{$order->id}", [
                'order_id' => $order->id,
                'splits_rolled_back' => $rollbackCount
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to rollback splits for order #{$order->id}", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
