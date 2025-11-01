<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PoolOrderMigrationTask;
use App\Models\PoolOrder;
use App\Models\Pool;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessCompletedCancellationTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    
    protected $signature = 'pool:process-completed-cancellations 
                            {--dry-run : Run without making changes}
                            {--task-id= : Process specific task ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process completed cancellation tasks and update domain statuses from warming to available';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $taskId = $this->option('task-id');
        
        $this->info('=== Processing Completed Cancellation Tasks ===');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        // Build query
        $query = PoolOrderMigrationTask::with(['poolOrder'])
            ->where('task_type', 'cancellation')
            ->where('status', 'completed')
            ->whereNull('domains_processed_at') // Only unprocessed tasks
            ->where('completed_at', '<=', now()->subDays(7)); // Completed at least 7 days ago
        
        if ($taskId) {
            $query->where('id', $taskId);
            $this->info("Processing specific task ID: {$taskId}");
        }
        
        $tasks = $query->get();
        
        if ($tasks->isEmpty()) {
            $this->info('No completed cancellation tasks found that meet the criteria:');
            $this->line('  - Task type: cancellation');
            $this->line('  - Status: completed');
            $this->line('  - Completed at least 7 days ago');
            $this->line('  - Not yet processed (domains_processed_at is null)');
            return 0;
        }
        
        $this->info("Found {$tasks->count()} completed cancellation task(s)");
        $this->newLine();
        
        $stats = [
            'tasks_processed' => 0,
            'pool_orders_processed' => 0,
            'domains_updated_in_pool_orders' => 0,
            'domains_updated_in_pools' => 0,
            'errors' => 0
        ];
        
        foreach ($tasks as $task) {
            try {
                $this->info("Processing Task ID: {$task->id}");
                $this->line("  Pool Order ID: {$task->pool_order_id}");
                $this->line("  Completed At: {$task->completed_at}");
                $this->line("  Days Since Completion: " . $task->completed_at->diffInDays(now()));
                
                $poolOrder = $task->poolOrder;
                
                if (!$poolOrder) {
                    $this->error("  Pool order not found!");
                    $stats['errors']++;
                    continue;
                }
                
                if (!$poolOrder->domains || !is_array($poolOrder->domains)) {
                    $this->warn("  No domains found in pool order");
                    
                    // Mark as processed even if no domains
                    if (!$isDryRun) {
                        $task->domains_processed_at = now();
                        $task->save();
                    }
                    continue;
                }
                
                $this->line("  Found " . count($poolOrder->domains) . " domain(s)");
                
                // Process pool order domains
                $orderDomainsUpdated = $this->processPoolOrderDomains($poolOrder, $isDryRun);
                $stats['domains_updated_in_pool_orders'] += $orderDomainsUpdated;
                
                if ($orderDomainsUpdated > 0) {
                    $this->info("  ✓ Updated {$orderDomainsUpdated} domain(s) in pool_orders table");
                    $stats['pool_orders_processed']++;
                }
                
                // Process domains in pools table
                $poolDomainsUpdated = $this->processPoolDomains($poolOrder->domains, $isDryRun);
                $stats['domains_updated_in_pools'] += $poolDomainsUpdated;
                
                if ($poolDomainsUpdated > 0) {
                    $this->info("  ✓ Updated {$poolDomainsUpdated} domain(s) in pools table");
                }
                
                // Mark task as processed
                if (!$isDryRun) {
                    $task->domains_processed_at = now();
                    $task->save();
                    $this->line("  ✓ Task marked as processed");
                }
                
                $stats['tasks_processed']++;
                $this->newLine();
                
            } catch (\Exception $e) {
                $this->error("  Error processing task {$task->id}: " . $e->getMessage());
                Log::error("Error processing cancellation task {$task->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $stats['errors']++;
            }
        }
        
        // Display summary
        $this->newLine();
        $this->info('=== Summary ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Tasks Processed', $stats['tasks_processed']],
                ['Pool Orders Updated', $stats['pool_orders_processed']],
                ['Domains Updated (pool_orders)', $stats['domains_updated_in_pool_orders']],
                ['Domains Updated (pools)', $stats['domains_updated_in_pools']],
                ['Errors', $stats['errors']]
            ]
        );
        
        if ($isDryRun) {
            $this->warn('DRY RUN completed - No actual changes were made');
        } else {
            $this->info('Processing completed successfully!');
        }
        
        return 0;
    }
    
    /**
     * Process domains in pool_orders table
     */
    private function processPoolOrderDomains(PoolOrder $poolOrder, bool $isDryRun): int
    {
        $domains = $poolOrder->domains;
        $updateCount = 0;
        
        foreach ($domains as &$domain) {
            if (isset($domain['status']) && $domain['status'] === 'warming') {
                $domainName = $domain['domain_name'] ?? $domain['name'] ?? 'Unknown';

                $this->line("    - {$domainName}: warming → cancelled");

                if (!$isDryRun) {
                    $domain['status'] = 'cancelled';
                    // $domain['is_used'] = true;
                }
                
                $updateCount++;
            }
        }
        
        if ($updateCount > 0 && !$isDryRun) {
            $poolOrder->domains = $domains;
            $poolOrder->save();
            
            Log::info("Updated {$updateCount} domains in pool order {$poolOrder->id}");
        }
        
        return $updateCount;
    }
    
    /**
     * Process domains in pools table
     */
    private function processPoolDomains(array $orderDomains, bool $isDryRun): int
    {
        $updateCount = 0;
        $domainsByPool = [];
        
        // Group domains by pool_id
        foreach ($orderDomains as $domain) {
            $poolId = $domain['pool_id'] ?? null;
            $domainId = $domain['domain_id'] ?? $domain['id'] ?? null;
            
            if ($poolId && $domainId) {
                if (!isset($domainsByPool[$poolId])) {
                    $domainsByPool[$poolId] = [];
                }
                $domainsByPool[$poolId][] = $domainId;
            }
        }
        
        // Process each pool
        foreach ($domainsByPool as $poolId => $domainIds) {
            try {
                $pool = Pool::find($poolId);
                
                if (!$pool || !is_array($pool->domains)) {
                    continue;
                }
                
                $poolDomains = $pool->domains;
                $poolUpdated = false;
                
                foreach ($poolDomains as &$poolDomain) {
                    $poolDomainId = $poolDomain['id'] ?? null;
                    
                    if ($poolDomainId && in_array($poolDomainId, $domainIds)) {
                        if (isset($poolDomain['status']) && $poolDomain['status'] === 'warming') {
                            $domainName = $poolDomain['name'] ?? 'Unknown';
                            $this->line("    - Pool {$poolId} - {$domainName}: warming → available");
                            
                            if (!$isDryRun) {
                                $poolDomain['status'] = 'available';
                                $poolDomain['is_used'] = false;
                            }
                            
                            $poolUpdated = true;
                            $updateCount++;
                        }
                    }
                }
                
                if ($poolUpdated && !$isDryRun) {
                    $pool->domains = $poolDomains;
                    $pool->save();
                    
                    Log::info("Updated domains in pool {$poolId}");
                }
                
            } catch (\Exception $e) {
                $this->error("    Error updating pool {$poolId}: " . $e->getMessage());
                Log::error("Error updating pool {$poolId}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $updateCount;
    }
}

