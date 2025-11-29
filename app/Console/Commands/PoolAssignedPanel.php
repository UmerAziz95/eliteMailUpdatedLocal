<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pool;
use App\Models\PoolPanel;
use App\Models\PoolPanelSplit;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderPanel;
use App\Models\ReorderInfo;
use App\Models\Configuration;
use App\Mail\AdminPanelNotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;

class PoolAssignedPanel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pool:assigned-panel 
                            {--dry-run : Run without sending actual emails}
                            {--force : Force send even if already sent today}
                            {--disable-split-capacity : Disable MAX_SPLIT_CAPACITY functionality}
                            {--enable-split-capacity : Enable MAX_SPLIT_CAPACITY functionality}
                            {--provider= : Force provider type (Google or Microsoft 365) instead of configuration}';

    /**
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
     * Flag to enable/disable MAX_SPLIT_CAPACITY functionality
     * 
     * When enabled (true): Uses MAX_SPLIT_CAPACITY to limit splits
     * When disabled (false): Uses full panel capacity without split limits
     */
    public $ENABLE_MAX_SPLIT_CAPACITY;
    
    /**
     * Provider type used when fetching panels
     */
    public $PROVIDER_TYPE;

    /**
     * Constructor to initialize dynamic properties
     */
    public function __construct()
    {
        parent::__construct();
        $this->PANEL_CAPACITY = env('PANEL_CAPACITY', 1790); // Default to 1790 if not set in config
        $this->MAX_SPLIT_CAPACITY = env('MAX_SPLIT_CAPACITY', 358); // Maximum inboxes per split
        $this->ENABLE_MAX_SPLIT_CAPACITY = env('ENABLE_MAX_SPLIT_CAPACITY', false); // Enable/disable split capacity functionality
        $this->PROVIDER_TYPE = null; 
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
        $forceProvider = $this->option('provider');
        
        // Initialize provider type and capacities
        $this->initializeProviderSettings($forceProvider);
        
        // Handle split capacity flag options
        if ($this->option('disable-split-capacity')) {
            $this->ENABLE_MAX_SPLIT_CAPACITY = false;
        } elseif ($this->option('enable-split-capacity')) {
            $this->ENABLE_MAX_SPLIT_CAPACITY = true;
        }
        
        $this->info('🔍 Starting panel capacity check...');
        $this->info("   Provider: {$this->PROVIDER_TYPE}");
        $this->info("   Panel Capacity: {$this->PANEL_CAPACITY}");
        $this->info("⚙️  MAX_SPLIT_CAPACITY functionality: " . ($this->ENABLE_MAX_SPLIT_CAPACITY ? 'ENABLED' : 'DISABLED'));
        if ($this->ENABLE_MAX_SPLIT_CAPACITY) {
            $this->info("📏 Max split capacity: {$this->MAX_SPLIT_CAPACITY} inboxes");
        } else {
            $this->info("📏 Using full panel capacity: {$this->PANEL_CAPACITY} inboxes");
        }

        try {
            // First, fix any inconsistent used_limit values when uncommented then needed
            // $this->fixPanelUsedLimits();
            
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
     * Initialize provider type and capacity settings
     */
    private function initializeProviderSettings(?string $forceProvider): void
    {
        // Determine provider type
        if ($forceProvider) {
            // Use forced provider from command option
            $this->PROVIDER_TYPE = $forceProvider;
            $this->info("🔧 Using forced provider type: {$forceProvider}");
        } else {
            // Use configuration table
            $this->PROVIDER_TYPE = Configuration::get('PROVIDER_TYPE', env('PROVIDER_TYPE', 'Google'));
            $this->info("📋 Using provider type from configuration: {$this->PROVIDER_TYPE}");
        }
        
        // Resolve provider-specific panel capacity with sensible fallbacks
        if (strtolower($this->PROVIDER_TYPE) === 'microsoft 365') {
            $this->PANEL_CAPACITY = Configuration::get('MICROSOFT_365_CAPACITY', env('MICROSOFT_365_CAPACITY', env('PANEL_CAPACITY', 1790)));
            $this->MAX_SPLIT_CAPACITY = Configuration::get('MICROSOFT_365_MAX_SPLIT_CAPACITY', env('MICROSOFT_365_MAX_SPLIT_CAPACITY', env('MAX_SPLIT_CAPACITY', 358)));
        } else {
            $this->PANEL_CAPACITY = Configuration::get('GOOGLE_PANEL_CAPACITY', env('GOOGLE_PANEL_CAPACITY', env('PANEL_CAPACITY', 1790)));
            $this->MAX_SPLIT_CAPACITY = Configuration::get('GOOGLE_MAX_SPLIT_CAPACITY', env('GOOGLE_MAX_SPLIT_CAPACITY', env('MAX_SPLIT_CAPACITY', 358)));
        }
    
        // Provider-specific split toggles
        $enableMaxSplit = true;
        if (strtolower($this->PROVIDER_TYPE) === 'microsoft 365') {
            $enableMaxSplit = Configuration::get('ENABLE_MICROSOFT_365_MAX_SPLIT_CAPACITY', env('ENABLE_MICROSOFT_365_MAX_SPLIT_CAPACITY', true));
        } else {
            $enableMaxSplit = Configuration::get('ENABLE_GOOGLE_MAX_SPLIT_CAPACITY', env('ENABLE_GOOGLE_MAX_SPLIT_CAPACITY', true));
        }
        
        // Override with command options if provided
        if ($this->option('disable-split-capacity')) {
            $enableMaxSplit = false;
        } elseif ($this->option('enable-split-capacity')) {
            $enableMaxSplit = true;
        }
        
        if (!$enableMaxSplit) {
            $this->MAX_SPLIT_CAPACITY = $this->PANEL_CAPACITY;
        }
    }
    
    /**
     * Fix panel used_limit values based on existing pool_panel_splits
     */
    private function fixPanelUsedLimits(): void
    {
        $this->info('🔧 Fixing panel used_limit values...');
        
        // Get all panels for the current provider
        $panels = PoolPanel::where('provider_type', $this->PROVIDER_TYPE)->get();
        $fixedCount = 0;
        
        foreach ($panels as $panel) {
            // Calculate actual used space from pool_panel_splits
            $actualUsedSpace = PoolPanelSplit::where('pool_panel_id', $panel->id)
                ->get()
                ->sum(function ($split) {
                    return $split->getDomainCount() * $split->inboxes_per_domain;
                });
            
            // Calculate what remaining_limit should be
            $expectedRemainingLimit = $panel->limit - $actualUsedSpace;
            
            // Fix if there's a discrepancy
            if ($panel->used_limit != $actualUsedSpace || $panel->remaining_limit != $expectedRemainingLimit) {
                $panel->update([
                    'used_limit' => $actualUsedSpace,
                    'remaining_limit' => $expectedRemainingLimit
                ]);
                
                $this->info("   ✓ Fixed Panel ID {$panel->id}: used_limit = {$actualUsedSpace}, remaining_limit = {$expectedRemainingLimit}");
                $fixedCount++;
                
                Log::info('Fixed panel capacity values', [
                    'panel_id' => $panel->id,
                    'provider_type' => $this->PROVIDER_TYPE,
                    'old_used_limit' => $panel->used_limit,
                    'new_used_limit' => $actualUsedSpace,
                    'old_remaining_limit' => $panel->remaining_limit,
                    'new_remaining_limit' => $expectedRemainingLimit
                ]);
            }
        }
        
        if ($fixedCount > 0) {
            $this->info("🔧 Fixed {$fixedCount} panel(s) with incorrect used_limit values");
        } else {
            $this->info("✓ All panel used_limit values are correct");
        }
    }
    
    /**
     * Get available panel space for specific order size
     */
    private function getAvailablePanelSpaceForOrder(int $orderSize, int $inboxesPerDomain): int
    {
        if ($orderSize >= $this->PANEL_CAPACITY) {
            // For large orders, prioritize full capacity panels
            $fullCapacityPanels = PoolPanel::where('is_active', 1)
                                        ->where('limit', $this->PANEL_CAPACITY)
                                        ->where('provider_type', $this->PROVIDER_TYPE)
                                        // ->where('remaining_limit', $this->PANEL_CAPACITY)
                                        ->where('remaining_limit', '>=', $inboxesPerDomain)
                                        ->get();
            
            $fullCapacitySpace = 0;
            foreach ($fullCapacityPanels as $panel) {
                if ($this->ENABLE_MAX_SPLIT_CAPACITY) {
                    $fullCapacitySpace += min($panel->remaining_limit, $this->MAX_SPLIT_CAPACITY);
                } else {
                    $fullCapacitySpace += $panel->remaining_limit;
                }
            }

            $this->info("🔍 Available space for large order ({$orderSize} inboxes):");
            $this->info("   Full capacity panels: {$fullCapacityPanels->count()} panels, {$fullCapacitySpace} space");
            
            return $fullCapacitySpace;
            
        } else {
            // For smaller orders, use any panel with remaining space that can accommodate at least one domain
            $availablePanels = PoolPanel::where('is_active', 1)
                                    ->where('limit', $this->PANEL_CAPACITY)
                                    ->where('provider_type', $this->PROVIDER_TYPE)
                                    ->where('remaining_limit', '>=', $inboxesPerDomain)
                                    ->get();
            
            $totalSpace = 0;
            foreach ($availablePanels as $panel) {
                if ($this->ENABLE_MAX_SPLIT_CAPACITY) {
                    $totalSpace += min($panel->remaining_limit, $this->MAX_SPLIT_CAPACITY);
                } else {
                    $totalSpace += $panel->remaining_limit;
                }
            }
            
            $this->info("🔍 Available space for small order ({$orderSize} inboxes): {$totalSpace} total space");

            return $totalSpace;
        }
    }
    
    /**
     * Update pool status to 'completed' for pools that have available space on panels
     */
    private function updateOrderStatusForAvailableSpace(): void
    {
        // Get pools that can be accommodated with available space and are not currently being split
        $pendingPools = Pool::where('status', 'pending')
            ->where('is_splitting', 0) // Only get pools that are not currently being split
            ->whereNotNull('total_inboxes')
            ->where('total_inboxes', '>', 0)
            ->orderBy('created_at', 'asc') // Process older pools first
            ->get();
        
        if ($pendingPools->isEmpty()) {
            $this->info('ℹ️  No pools to update.');
            return;
        }

        $updatedCount = 0;
        $totalProcessed = 0;
        $remainingTotalInboxes = 0;
        
        $this->info("🔄 Processing pools for status updates...");
        
        foreach ($pendingPools as $pool) {
            $totalProcessed++;            
            
            // Get pool-specific available space
            $poolSpecificSpace = $this->getAvailablePanelSpaceForOrder($pool->total_inboxes, $pool->inboxes_per_domain);
            // dd($poolSpecificSpace, $pool->total_inboxes, $pool->inboxes_per_domain, $this->PANEL_CAPACITY, $this->MAX_SPLIT_CAPACITY);
            if ($pool->total_inboxes <= $poolSpecificSpace) {
                try {
                    // Set is_splitting to 1 to indicate this pool is being processed
                    $pool->is_splitting = 1;
                    $pool->save();
                    
                    // Create panel splits before updating status
                    $this->info("   🔄 Creating panel splits for Pool ID {$pool->id}...");
                    try {
                        $this->pannelCreationAndPoolSplitOnPannels($pool);
                        $this->info("   ✓ Panel splits created successfully for Pool ID {$pool->id}");
                        
                        // Log successful panel split creation
                        Log::info('Panel splits created for pool', [
                            'pool_id' => $pool->id,
                            'provider_type' => $this->PROVIDER_TYPE,
                            'total_inboxes' => $pool->total_inboxes,
                            'created_at' => Carbon::now()
                        ]);
                        
                    } catch (\Exception $splitException) {
                        $this->error("   ✗ Failed to create panel splits for Pool ID {$pool->id}: " . $splitException->getMessage());
                        Log::error('Failed to create panel splits', [
                            'pool_id' => $pool->id,
                            'provider_type' => $this->PROVIDER_TYPE,
                            'error' => $splitException->getMessage(),
                            'trace' => $splitException->getTraceAsString()
                        ]);
                        // Continue with status update even if split creation fails
                        // Reset is_splitting flag on error
                        $pool->is_splitting = 0;
                        $pool->save();
                    }
                    
                    // Update status to completed and set is_splitting flag to 1
                    $pool->status = 'completed';
                    $pool->is_splitting = 1; // Set splitting flag to 1 when completed
                    $pool->save();
                    
                    $updatedCount++;
                    
                    // Calculate domain count for display
                    $domainsRaw = $pool->domains;
                    $domainCount = 0;
                    if (is_array($domainsRaw)) {
                        foreach ($domainsRaw as $domain) {
                            if (is_array($domain) && isset($domain['name'])) {
                                $domainCount++;
                            } elseif (is_string($domain)) {
                                $domainCount++;
                            }
                        }
                    } elseif (is_string($domainsRaw)) {
                        $domainCount = count(array_filter(preg_split('/[\r\n,]+/', $domainsRaw)));
                    }
                    
                    $this->info("   ✓ Pool ID {$pool->id}: {$pool->total_inboxes} inboxes - Status updated to 'completed'");
                    $this->info("     Domains processed: {$domainCount} domains");
                    
                } catch (\Exception $e) {
                    $this->error("   ✗ Failed to update Pool ID {$pool->id}: " . $e->getMessage());
                    Log::error('Failed to update pool status', [
                        'pool_id' => $pool->id,
                        'provider_type' => $this->PROVIDER_TYPE,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Reset is_splitting flag on error
                    $pool->is_splitting = 0;
                    $pool->save();
                    // Add to remaining total if failed to update
                    $remainingTotalInboxes += $pool->total_inboxes;
                }
            } else {
                $poolSpecificSpace = $this->getAvailablePanelSpaceForOrder($pool->total_inboxes, $pool->inboxes_per_domain);
                $this->warn("   ⚠ Pool ID {$pool->id}: {$pool->total_inboxes} inboxes - Insufficient space");
                $this->warn("     Pool-specific available space: {$poolSpecificSpace}");
                
                // Calculate panels needed for this pool
                $capacityPerPanel = $this->ENABLE_MAX_SPLIT_CAPACITY ? $this->MAX_SPLIT_CAPACITY : $this->PANEL_CAPACITY;
                $panelsNeeded = ceil($pool->total_inboxes / $capacityPerPanel);
                
                // Add to insufficient space pools for email notification
                $this->insufficientSpaceOrders[] = [
                    'pool_id' => $pool->id,
                    'required_space' => $pool->total_inboxes,
                    'available_space' => $poolSpecificSpace,
                    'panels_needed' => $panelsNeeded,
                    'status' => 'pending'
                ];
                
                // Add to remaining total for unprocessed pools
                $remainingTotalInboxes += $pool->total_inboxes;
            }
        }        
        $this->info("📊 Pool Status Update Summary:");
        $this->info("   Total pools processed: {$totalProcessed}");
        $this->info("   Pools updated to 'completed': {$updatedCount}");
        
        if ($updatedCount > 0) {
            // Calculate total space used by successful pools
            $totalSpaceUsed = $pendingPools->slice(0, $updatedCount)->sum('total_inboxes');
            // Log the pool updates
            $this->logPoolStatusUpdates($updatedCount, $totalSpaceUsed);
        }
        
        // Send email notification if there are pools with insufficient space
        if (!empty($this->insufficientSpaceOrders)) {
            $this->sendInsufficientSpaceNotification();
        }
    }
    
    /**
     * Log pool status updates
     */
    private function logPoolStatusUpdates(int $updatedCount, int $spaceUsed): void
    {
        $logFile = storage_path('logs/pool-status-updates.log');
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = sprintf(
            "[%s] Pool status updates - Provider: %s, Pools updated: %d, Space allocated: %d inboxes\n",
            Carbon::now()->format('Y-m-d H:i:s'),
            $this->PROVIDER_TYPE,
            $updatedCount,
            $spaceUsed
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also log to Laravel log
        Log::info('Pool status updated', [
            'provider_type' => $this->PROVIDER_TYPE,
            'pools_updated' => $updatedCount,
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
                // if ($this->sendInsufficientSpaceEmail($admin, $isDryRun)) {
                //     $sentCount++;
                // }
            }
            
            $this->info("✅ Insufficient space notifications sent: {$sentCount}");
            
            // Log the notification
            Log::info('Insufficient space notifications sent', [
                'provider_type' => $this->PROVIDER_TYPE,
                'orders_count' => count($this->insufficientSpaceOrders),
                'admins_notified' => $sentCount,
                'orders' => $this->insufficientSpaceOrders
            ]);
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to send insufficient space notifications: " . $e->getMessage());
            Log::error('Failed to send insufficient space notifications', [
                'provider_type' => $this->PROVIDER_TYPE,
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
            // get panel greater than max split or minimum capacity based on flag
            $minCapacityRequired = $this->ENABLE_MAX_SPLIT_CAPACITY ? $this->MAX_SPLIT_CAPACITY : 1;
            $availablePanelCount = PoolPanel::where('is_active', true)
                ->where('limit', $this->PANEL_CAPACITY)
                ->where('provider_type', $this->PROVIDER_TYPE)
                ->where('remaining_limit', '>=', $minCapacityRequired)
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
                'provider_type' => $this->PROVIDER_TYPE,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Panel creation and pool split on panels
     */
    private function pannelCreationAndPoolSplitOnPannels($pool)
    {
        try {
            // Wrap everything in a database transaction for consistency
            DB::beginTransaction();
            
            // Calculate total space needed from pool data
            // Handle different domain formats properly
            $domainsRaw = $pool->domains;
            $domains = []; // Will store domain names for processing
            $domainIds = []; // Will store domain IDs for database storage
            
            if (is_array($domainsRaw)) {
                // Handle array format (could be array of objects or array of strings)
                foreach ($domainsRaw as $domain) {
                    if (is_array($domain) && isset($domain['name'])) {
                        // New format: array of objects with 'id' and 'name' properties
                        $domains[] = $domain['name'];
                        $domainIds[] = isset($domain['id']) ? $domain['id'] : $domain['name'];
                    } elseif (is_string($domain)) {
                        // Old format: array of strings
                        $domains[] = $domain;
                        $domainIds[] = $domain; // Use name as fallback when no ID available
                    }
                }
            } elseif (is_string($domainsRaw)) {
                // Handle string format (line-break separated)
                $domains = array_filter(preg_split('/[\r\n,]+/', $domainsRaw));
                $domainIds = $domains; // Use names as fallback when no IDs available
            }
            
            // Remove empty domains and trim whitespace
            $domains = array_filter(array_map('trim', $domains));
            $domainIds = array_filter(array_map('trim', $domainIds));
            $domainCount = count($domains);
            $totalSpaceNeeded = $domainCount * $pool->inboxes_per_domain;
            
            Log::info("Panel creation started for pool #{$pool->id}", [
                'provider_type' => $this->PROVIDER_TYPE,
                'total_space_needed' => $totalSpaceNeeded,
                'max_split_capacity' => $this->MAX_SPLIT_CAPACITY,
                'panel_capacity' => $this->PANEL_CAPACITY,
                'domain_count' => $domainCount,
                'inboxes_per_domain' => $pool->inboxes_per_domain,
                'domains_format' => is_array($pool->domains) ? 'array' : 'string',
                'sample_domains' => array_slice($domains, 0, 3), // Show first 3 domain names for debugging
                'sample_domain_ids' => array_slice($domainIds, 0, 3) // Show first 3 domain IDs for debugging
            ]);
            // Only try to use existing panels - no automatic panel creation
            $splitCapacityLimit = $this->ENABLE_MAX_SPLIT_CAPACITY ? $this->MAX_SPLIT_CAPACITY : $this->PANEL_CAPACITY;
            if ($totalSpaceNeeded <= $splitCapacityLimit) {
                // Try to find existing panel with sufficient space for small pools (<= 358 inboxes)
                $suitablePanel = $this->findSuitablePanel($totalSpaceNeeded);
                
                if ($suitablePanel) {
                    // Assign entire pool to this panel
                    $this->assignDomainsToPanel($suitablePanel, $pool, $domains, $domainIds, $totalSpaceNeeded, 1);
                    Log::info("Pool #{$pool->id} assigned to existing panel #{$suitablePanel->id}");
                } else {
                    // No single panel can fit, try intelligent splitting across available panels
                    $this->handlePoolSplitAcrossAvailablePanels($pool, $domains, $domainIds, $totalSpaceNeeded);
                }
            } else {
                // Large pools: try intelligent splitting across available panels only
                $this->handlePoolSplitAcrossAvailablePanels($pool, $domains, $domainIds, $totalSpaceNeeded);
            }
            
            DB::commit();
            Log::info("Panel creation completed successfully for pool #{$pool->id}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Panel creation failed for pool #{$pool->id}", [
                'provider_type' => $this->PROVIDER_TYPE,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Handle intelligent splitting across existing available panels
     */
    private function handlePoolSplitAcrossAvailablePanels($pool, $domains, $domainIds, $totalSpaceNeeded)
    {
        $remainingSpace = $totalSpaceNeeded;
        $domainsProcessed = 0;
        $splitNumber = 1;
        $usedPanelIds = []; // Track panels already used for this order
        
        // Keep looping until all domains are processed or no more panels available
        while ($remainingSpace > 0 && $domainsProcessed < count($domains)) {
            // Re-fetch available panels with updated remaining_limit for each iteration
            // Exclude panels that have already been used for this order
            $availablePanel = PoolPanel::where('is_active', true)
                ->where('limit', $this->PANEL_CAPACITY)
                ->where('provider_type', $this->PROVIDER_TYPE)
                // ->where('remaining_limit', '>', 0)
                ->where('remaining_limit', '>=', $pool->inboxes_per_domain)
                ->whereNotIn('id', $usedPanelIds) // Exclude already used panels
                ->orderBy('remaining_limit', 'desc')
                ->first();
            
            if (!$availablePanel) {
                // No available panels, skip the remaining pool
                Log::warning("No available panels found for pool #{$pool->id} - remaining domains will be skipped", [
                    'provider_type' => $this->PROVIDER_TYPE,
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
            // Limit space to use based on MAX_SPLIT_CAPACITY if enabled
            if ($this->ENABLE_MAX_SPLIT_CAPACITY) {
                $spaceToUse = min($availableSpace, $remainingSpace, $this->MAX_SPLIT_CAPACITY);
            } else {
                $spaceToUse = min($availableSpace, $remainingSpace);
            }
            
            // Calculate maximum domains that can fit in space without exceeding MAX_SPLIT_CAPACITY
            $maxDomainsForSpace = floor($spaceToUse / $pool->inboxes_per_domain);
            
            // Ensure we don't process more domains than remaining
            $remainingDomains = count($domains) - $domainsProcessed;
            $domainsToAssign = min($maxDomainsForSpace, $remainingDomains);
            
            // Extract domains for this panel
            $domainSlice = array_slice($domains, $domainsProcessed, $domainsToAssign);
            $domainIdSlice = array_slice($domainIds, $domainsProcessed, $domainsToAssign);
            $actualSpaceUsed = count($domainSlice) * $pool->inboxes_per_domain;
            
            // Only proceed if we can actually use this panel and respect MAX_SPLIT_CAPACITY if enabled
            $splitCapacityCheck = $this->ENABLE_MAX_SPLIT_CAPACITY ? ($actualSpaceUsed <= $this->MAX_SPLIT_CAPACITY) : true;
            if ($actualSpaceUsed <= $availableSpace && $splitCapacityCheck && count($domainSlice) > 0) {
                $this->assignDomainsToPanel($availablePanel, $pool, $domainSlice, $domainIdSlice, $actualSpaceUsed, $splitNumber);
                
                // Add this panel to the used panels list to prevent reuse for this pool
                $usedPanelIds[] = $availablePanel->id;
                
                Log::info("Assigned to existing panel #{$availablePanel->id} (split #{$splitNumber}) for pool #{$pool->id}", [
                    'provider_type' => $this->PROVIDER_TYPE,
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
                Log::warning("Cannot use available panel #{$availablePanel->id} for pool #{$pool->id}", [
                    'provider_type' => $this->PROVIDER_TYPE,
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
            $remainingSpaceNeeded = count($remainingDomains) * $pool->inboxes_per_domain;
            
            Log::warning("Incomplete pool processing - rolling back all splits for pool #{$pool->id}", [
                'pool_id' => $pool->id,
                'provider_type' => $this->PROVIDER_TYPE,
                'domains_processed' => $domainsProcessed,
                'total_domains' => $totalDomainsToProcess,
                'remaining_domains' => count($remainingDomains),
                'remaining_space' => $remainingSpaceNeeded,
                'reason' => 'insufficient_existing_panel_space'
            ]);
            
            // Rollback all splits for this pool
            $this->rollbackPoolSplits($pool);
            
            // Throw exception to rollback the entire transaction
            throw new \Exception("Pool #{$pool->id} could not be fully processed - all splits rolled back");
        }
    }
    
    /**
     * Find suitable existing panel with sufficient space based on order size
     */
    private function findSuitablePanel($spaceNeeded)
    {
        return PoolPanel::where('is_active', true)
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
        return PoolPanel::where('is_active', true)
            ->where('limit', $this->PANEL_CAPACITY)
            ->where('provider_type', $this->PROVIDER_TYPE)
            ->where('remaining_limit', '>=', $spaceNeeded)
            ->orderBy('remaining_limit', 'desc') // Use panel with most available space first for efficiency
            ->first();
    }
    
    /**
     * Assign domains to a specific panel and create all necessary records
     */
    private function assignDomainsToPanel($panel, $pool, $domainsToAssign, $domainIdsToAssign, $spaceToAssign, $splitNumber)
    {
        try {
            // Create pool_panel_split record
            PoolPanelSplit::create([
                'pool_panel_id' => $panel->id,
                'pool_id' => $pool->id,
                'inboxes_per_domain' => $pool->inboxes_per_domain,
                'domains' => $domainIdsToAssign, // Store domain IDs instead of domain names
                'assigned_space' => $spaceToAssign // Track the space assigned from this split
            ]);
            
            // Update panel remaining capacity and used capacity
            $panel->decrement('remaining_limit', $spaceToAssign);
            $panel->increment('used_limit', $spaceToAssign);
            
            // Ensure remaining_limit never goes below 0 and used_limit doesn't exceed limit
            if ($panel->remaining_limit < 0) {
                $panel->update(['remaining_limit' => 0]);
            }
            if ($panel->used_limit > $panel->limit) {
                $panel->update(['used_limit' => $panel->limit]);
            }
            
            Log::info("Successfully assigned domains to panel", [
                'panel_id' => $panel->id,
                'pool_id' => $pool->id,
                'provider_type' => $this->PROVIDER_TYPE,
                'space_assigned' => $spaceToAssign,
                'domains_count' => count($domainsToAssign),
                'domain_names' => array_slice($domainsToAssign, 0, 3), // Sample domain names for debugging
                'domain_ids' => array_slice($domainIdsToAssign, 0, 3), // Sample domain IDs stored in database
                'panel_remaining_limit' => $panel->remaining_limit,
                'panel_used_limit' => $panel->used_limit
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to assign domains to panel", [
                'panel_id' => $panel->id,
                'pool_id' => $pool->id,
                'provider_type' => $this->PROVIDER_TYPE,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Rollback all splits for a pool - restore panel capacity and delete records
     */
    private function rollbackPoolSplits($pool)
    {
        try {
            // Get all pool panel splits for this pool
            $poolPanelSplits = PoolPanelSplit::where('pool_id', $pool->id)->get();
            
            if ($poolPanelSplits->isEmpty()) {
                Log::info("No pool panel splits found to rollback for pool #{$pool->id}");
                return;
            }
            
            $rollbackCount = 0;
            foreach ($poolPanelSplits as $split) {
                // Restore panel capacity
                $panel = PoolPanel::find($split->pool_panel_id);
                if ($panel) {
                    $spaceToRestore = $split->getDomainCount() * $split->inboxes_per_domain;
                    $panel->increment('remaining_limit', $spaceToRestore);
                    $panel->decrement('used_limit', $spaceToRestore);
                    
                    // Ensure used_limit never goes below 0
                    if ($panel->used_limit < 0) {
                        $panel->update(['used_limit' => 0]);
                    }
                    
                    Log::info("Restored panel capacity", [
                        'panel_id' => $panel->id,
                        'provider_type' => $this->PROVIDER_TYPE,
                        'space_restored' => $spaceToRestore,
                        'panel_remaining_limit' => $panel->remaining_limit,
                        'panel_used_limit' => $panel->used_limit
                    ]);
                }
                
                // Delete pool panel split
                $split->delete();
                
                $rollbackCount++;
            }
            
            Log::info("Successfully rolled back all splits for pool #{$pool->id}", [
                'pool_id' => $pool->id,
                'provider_type' => $this->PROVIDER_TYPE,
                'splits_rolled_back' => $rollbackCount
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to rollback splits for pool #{$pool->id}", [
                'pool_id' => $pool->id,
                'provider_type' => $this->PROVIDER_TYPE,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}