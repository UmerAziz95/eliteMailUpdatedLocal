<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pool;
use Carbon\Carbon;

class UpdatePoolStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pools:update-status {--dry-run : Show what would be updated without making changes} {--force : Force update without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update pool and domain status from warming to available after 3 weeks of creation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting pool status update process...');
        
        // Calculate the date 3 weeks ago
        $threeWeeksAgo = Carbon::now()->subWeeks(3);
        
        $this->info("Looking for pools created before: {$threeWeeksAgo->format('Y-m-d H:i:s')}");

        // Find pools that are older than 3 weeks and still in warming status
        $query = Pool::where('created_at', '<=', $threeWeeksAgo)
            ->where(function ($q) {
                $q->where('status_manage_by_admin', 'warming')
                  ->orWhereNull('status_manage_by_admin');
            });

        $poolsToUpdate = $query->get();
        $totalCount = $poolsToUpdate->count();

        if ($totalCount === 0) {
            $this->info('No pools found that need status update.');
            return 0;
        }

        $this->info("Found {$totalCount} pool(s) that need status update:");
        
        // Display the pools that will be updated
        $this->table(
            ['Pool ID', 'Created Date', 'Current Status', 'Domains Status', 'Days Old'],
            $poolsToUpdate->map(function ($pool) {
                $daysOld = $pool->created_at->diffInDays(now());
                
                // Check domains status
                $domainStatusInfo = 'N/A';
                if ($pool->domains && is_array($pool->domains)) {
                    $warmingCount = 0;
                    $availableCount = 0;
                    $otherCount = 0;
                    
                    foreach ($pool->domains as $domain) {
                        $status = $domain['status'] ?? 'warming';
                        if ($status === 'warming') {
                            $warmingCount++;
                        } elseif ($status === 'available') {
                            $availableCount++;
                        } else {
                            $otherCount++;
                        }
                    }
                    
                    $domainStatusInfo = "W:{$warmingCount}, A:{$availableCount}";
                    if ($otherCount > 0) {
                        $domainStatusInfo .= ", O:{$otherCount}";
                    }
                }
                
                return [
                    $pool->id,
                    $pool->created_at->format('Y-m-d H:i:s'),
                    $pool->status_manage_by_admin ?? 'warming (null)',
                    $domainStatusInfo,
                    $daysOld
                ];
            })
        );

        // If dry-run option is used, don't make actual changes
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN: No changes were made. Use --force to apply changes.');
            return 0;
        }

        // Ask for confirmation unless --force is used
        if (!$this->option('force')) {
            if (!$this->confirm("Do you want to update {$totalCount} pool(s) status to 'available'?")) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Perform the update
        $updatedCount = 0;
        
        foreach ($poolsToUpdate as $pool) {
            // Update the pool status
            $pool->status_manage_by_admin = 'available';
            
            // Update domains status from warming to available
            if ($pool->domains && is_array($pool->domains)) {
                $updatedDomains = [];
                foreach ($pool->domains as $domain) {
                    if (isset($domain['status']) && $domain['status'] === 'warming') {
                        $domain['status'] = 'available';
                    } elseif (!isset($domain['status'])) {
                        // Add status field if it doesn't exist (backward compatibility)
                        $domain['status'] = 'available';
                    }
                    $updatedDomains[] = $domain;
                }
                $pool->domains = $updatedDomains;
            }
            
            $pool->updated_at = now();
            $pool->save();
            $updatedCount++;
        }

        $this->info("Successfully updated {$updatedCount} pool(s) status to 'available'.");
        $this->info("Also updated domain statuses from 'warming' to 'available' where applicable.");
        
        // Log the action
        \Log::info("Pool status update completed. Updated {$updatedCount} pools and their domains to available status.", [
            'command' => 'pools:update-status',
            'updated_count' => $updatedCount,
            'cutoff_date' => $threeWeeksAgo->toDateTimeString(),
            'updated_domains' => 'Domain statuses also updated from warming to available'
        ]);

        return 0;
    }
}
