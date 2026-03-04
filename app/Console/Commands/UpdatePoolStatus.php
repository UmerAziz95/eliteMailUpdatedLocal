<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pool;
use App\Models\Configuration;
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
    protected $description = 'Update pool and domain status from warming to available based on domain end_date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting pool status update process...');

        $warmingPeriodDays = (int) Configuration::get('POOL_WARMING_PERIOD', 21);
        if ($warmingPeriodDays < 0) {
            $warmingPeriodDays = 0;
        }

        $today = Carbon::now()->format('Y-m-d');
        $this->info("Configured warming period: {$warmingPeriodDays} day(s)");
        $this->info("Checking domains with end_date on or before: {$today}");

        // Find all pools that have domains with warming status
        // $query = Pool::where(function ($q) {
        //     $q->where('status_manage_by_admin', 'warming')
        //       ->orWhereNull('status_manage_by_admin');
        // });
        
        $poolsToCheck = Pool::all();
        $poolsToUpdate = collect();
        
        // Filter pools that have domains with expired warming period
        foreach ($poolsToCheck as $pool) {
            if ($pool->domains && is_array($pool->domains)) {
                $hasExpiredDomains = false;
                foreach ($pool->domains as $domain) {
                    // Check for new prefix_statuses format
                    if (isset($domain['prefix_statuses']) && is_array($domain['prefix_statuses'])) {
                        foreach ($domain['prefix_statuses'] as $prefixKey => $prefixStatus) {
                            if (isset($prefixStatus['status']) && $prefixStatus['status'] === 'warming') {
                                // Check if prefix has end_date and it's expired
                                if (isset($prefixStatus['end_date']) && $prefixStatus['end_date'] <= $today) {
                                    $hasExpiredDomains = true;
                                    break 2; // Break out of both loops
                                }
                            }
                        }
                    }
                    // Fallback: Check for old domain-level status format
                    elseif (isset($domain['status']) && $domain['status'] === 'warming') {
                        // Check if domain has end_date and it's expired
                        if (isset($domain['end_date']) && $domain['end_date'] <= $today) {
                            $hasExpiredDomains = true;
                            break;
                        }
                    }
                }
                if ($hasExpiredDomains) {
                    $poolsToUpdate->push($pool);
                }
            }
        }
        
        $totalCount = $poolsToUpdate->count();

        if ($totalCount === 0) {
            $this->info('No pools found that need status update.');
            return 0;
        }

        $this->info("Found {$totalCount} pool(s) that need status update:");
        
        // Display the pools that will be updated
        $this->table(
            ['Pool ID', 'Created Date', 'Current Status', 'Domains Status', 'Expired Domains', 'Warming Expired Names', 'Available Domain Names'],
            $poolsToUpdate->map(function ($pool) use ($today) {
                // Check domains status
                $domainStatusInfo = 'N/A';
                $expiredDomains = 0;
                $expiredDomainNames = [];
                $availableDomainNames = [];
                
                if ($pool->domains && is_array($pool->domains)) {
                    $warmingCount = 0;
                    $availableCount = 0;
                    $otherCount = 0;
                    
                    foreach ($pool->domains as $domain) {
                        $status = $domain['status'] ?? 'warming';
                        if ($status === 'warming') {
                            $warmingCount++;
                            // Check if warming period expired
                            if (isset($domain['end_date']) && $domain['end_date'] <= $today) {
                                $expiredDomains++;
                                $expiredDomainNames[] = $domain['name'] ?? 'N/A';
                            }
                        } elseif ($status === 'available') {
                            $availableCount++;
                            $availableDomainNames[] = $domain['name'] ?? 'N/A';
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
                    $expiredDomains,
                    implode(', ', $expiredDomainNames) ?: 'None',
                    implode(', ', $availableDomainNames) ?: 'None'
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
        $totalDomainsUpdated = 0;
        $totalPrefixesUpdated = 0;
        
        foreach ($poolsToUpdate as $pool) {
            $poolUpdated = false;
            $domainsUpdatedInPool = 0;
            
            // Update domains status from warming to available based on end_date
            if ($pool->domains && is_array($pool->domains)) {
                $updatedDomains = [];
                foreach ($pool->domains as $domain) {
                    // Handle new prefix_statuses format
                    if (isset($domain['prefix_statuses']) && is_array($domain['prefix_statuses'])) {
                        $prefixUpdated = false;
                        foreach ($domain['prefix_statuses'] as $prefixKey => &$prefixStatus) {
                            if (isset($prefixStatus['status']) && $prefixStatus['status'] === 'warming') {
                                if (isset($prefixStatus['end_date']) && $prefixStatus['end_date'] <= $today) {
                                    $prefixStatus['status'] = 'available';
                                    $prefixUpdated = true;
                                    $totalPrefixesUpdated++;
                                }
                            }
                        }
                        if ($prefixUpdated) {
                            $domainsUpdatedInPool++;
                            $poolUpdated = true;
                        }
                    }
                    // Fallback: Handle old domain-level status format (for backward compatibility)
                    elseif (isset($domain['status']) && $domain['status'] === 'warming') {
                        if (isset($domain['end_date']) && $domain['end_date'] <= $today) {
                            $domain['status'] = 'available';
                            $domainsUpdatedInPool++;
                            $poolUpdated = true;
                            // Count as updated prefix/inbox equivalent (using inboxes_per_domain or 1)
                            $inboxesCount = $pool->inboxes_per_domain ?? 1;
                            $totalPrefixesUpdated += $inboxesCount;
                        }
                    } elseif (!isset($domain['status']) && !isset($domain['prefix_statuses'])) {
                        // Add status field if it doesn't exist (backward compatibility)
                        // Check end_date if available
                        if (isset($domain['end_date']) && $domain['end_date'] <= $today) {
                            $domain['status'] = 'available';
                            $domainsUpdatedInPool++;
                            $poolUpdated = true;
                            $inboxesCount = $pool->inboxes_per_domain ?? 1;
                            $totalPrefixesUpdated += $inboxesCount;
                        } else {
                            $domain['status'] = 'warming';
                        }
                    }
                    $updatedDomains[] = $domain;
                }
                $pool->domains = $updatedDomains;
            }
            
            // Check if all domains are now available or non-warming status
            $allDomainsReady = true;
            if ($pool->domains && is_array($pool->domains)) {
                foreach ($pool->domains as $domain) {
                    // Check new prefix_statuses format
                    if (isset($domain['prefix_statuses']) && is_array($domain['prefix_statuses'])) {
                        foreach ($domain['prefix_statuses'] as $prefixStatus) {
                            if (isset($prefixStatus['status']) && $prefixStatus['status'] === 'warming') {
                                $allDomainsReady = false;
                                break 2;
                            }
                        }
                    }
                    // Fallback: check old domain-level status
                    elseif (isset($domain['status']) && $domain['status'] === 'warming') {
                        $allDomainsReady = false;
                        break;
                    }
                }
            }
            
            // Update pool status only if all domains are ready
            if ($allDomainsReady && ($pool->status_manage_by_admin === 'warming' || $pool->status_manage_by_admin === null)) {
                $pool->status_manage_by_admin = 'available';
                $poolUpdated = true;
            }
            
            if ($poolUpdated) {
                $pool->updated_at = now();
                $pool->save();
                $updatedCount++;
                $totalDomainsUpdated += $domainsUpdatedInPool;
            }
        }

        $this->info("Successfully updated {$updatedCount} pool(s).");
        $this->info("Total domains updated from 'warming' to 'available': {$totalDomainsUpdated}");
        $this->info("Total inboxes/prefixes updated: {$totalPrefixesUpdated}");
        
        // Log the action
        \Log::info("Pool status update completed based on domain end_date.", [
            'command' => 'pools:update-status',
            'updated_pools' => $updatedCount,
            'updated_domains' => $totalDomainsUpdated,
            'updated_prefixes' => $totalPrefixesUpdated,
            'check_date' => $today,
            'method' => 'Domain end_date based expiration'
        ]);

        return 0;
    }
}
