<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Pool;
use App\Models\Configuration;
use Carbon\Carbon;

class MigrateDomainsToPrefixStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pools:migrate-prefix-statuses 
                            {--dry-run : Show what would be updated without making changes} 
                            {--force : Force update without confirmation}
                            {--pool-id= : Migrate a specific pool by ID}
                            {--raw-sql : Use raw SQL for large pools (bypasses max_allowed_packet in some cases)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing pool domains from domain-level status to prefix_statuses format';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting domain prefix_statuses migration...');

        // Handle specific pool migration with --pool-id option
        if ($poolId = $this->option('pool-id')) {
            return $this->migrateSpecificPool((int) $poolId);
        }

        // Count pools needing migration first
        $totalPoolsToCheck = Pool::count();
        $this->info("Total pools in database: {$totalPoolsToCheck}");

        $poolsNeedingMigration = 0;
        $poolsMigrated = 0;
        $poolsFailed = 0;
        $totalDomainsMigrated = 0;
        $failedPoolIds = [];

        // If dry-run, just count what needs migration
        if ($this->option('dry-run')) {
            $this->info("\nScanning pools for migration needs...");
            
            // Use cursor to iterate without loading all into memory
            foreach (Pool::cursor() as $pool) {
                if ($this->poolNeedsMigration($pool)) {
                    $poolsNeedingMigration++;
                    $domainCount = is_array($pool->domains) ? count($pool->domains) : 0;
                    $this->line("Pool ID {$pool->id}: {$domainCount} domains (inboxes_per_domain: " . ($pool->inboxes_per_domain ?? 1) . ")");
                }
            }

            $this->info("\nDRY RUN COMPLETE:");
            $this->info("Pools needing migration: {$poolsNeedingMigration}");
            $this->warn('No changes were made. Remove --dry-run to apply changes.');
            return 0;
        }

        // Ask for confirmation unless --force is used
        if (!$this->option('force')) {
            if (!$this->confirm("Do you want to migrate pools to prefix_statuses format? Each pool is saved individually.")) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Process pools one by one using cursor (memory efficient)
        $this->info('Processing pools one by one...');
        
        foreach (Pool::cursor() as $pool) {
            if (!$this->poolNeedsMigration($pool)) {
                continue;
            }
            
            $poolsNeedingMigration++;
            
            try {
                $inboxesPerDomain = $pool->inboxes_per_domain ?? 1;
                $migratedDomains = [];
                $domainsMigratedInPool = 0;

                if ($pool->domains && is_array($pool->domains)) {
                    foreach ($pool->domains as $domain) {
                        if (!isset($domain['prefix_statuses'])) {
                            $migratedDomains[] = $this->migrateDomain($domain, $inboxesPerDomain);
                            $domainsMigratedInPool++;
                        } else {
                            $migratedDomains[] = $domain;
                        }
                    }

                    // Save this pool
                    $pool->domains = $migratedDomains;
                    $pool->save();
                    
                    $poolsMigrated++;
                    $totalDomainsMigrated += $domainsMigratedInPool;
                    $this->line("✓ Pool ID {$pool->id}: {$domainsMigratedInPool} domains migrated");
                }
            } catch (\Exception $e) {
                $poolsFailed++;
                $failedPoolIds[] = $pool->id;
                $this->error("✗ Pool ID {$pool->id}: Failed - " . $e->getMessage());
            }
        }

        // Summary
        $this->info("\n" . str_repeat('=', 50));
        $this->info("MIGRATION SUMMARY");
        $this->info(str_repeat('=', 50));
        $this->info("Pools needing migration: {$poolsNeedingMigration}");
        $this->info("Pools migrated successfully: {$poolsMigrated}");
        $this->info("Pools failed: {$poolsFailed}");
        $this->info("Total domains migrated: {$totalDomainsMigrated}");
        
        if ($poolsFailed > 0) {
            $this->warn("\nFailed pool IDs: " . implode(', ', $failedPoolIds));
            $this->warn("You may need to increase MySQL max_allowed_packet for these pools.");
        }

        \Log::info("Domain prefix_statuses migration completed.", [
            'command' => 'pools:migrate-prefix-statuses',
            'pools_needing_migration' => $poolsNeedingMigration,
            'pools_migrated' => $poolsMigrated,
            'pools_failed' => $poolsFailed,
            'failed_pool_ids' => $failedPoolIds,
            'domains_migrated' => $totalDomainsMigrated,
        ]);

        return $poolsFailed > 0 ? 1 : 0;
    }

    /**
     * Check if a pool needs migration
     */
    private function poolNeedsMigration(Pool $pool): bool
    {
        if (!$pool->domains || !is_array($pool->domains)) {
            return false;
        }

        foreach ($pool->domains as $domain) {
            if (!isset($domain['prefix_statuses'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Migrate a single domain from old format to prefix_statuses format
     *
     * @param array $domain The domain data in old format
     * @param int $inboxesPerDomain Number of prefix variants
     * @return array The domain data in new format
     */
    private function migrateDomain(array $domain, int $inboxesPerDomain): array
    {
        // Get existing status, dates from old format
        $oldStatus = $domain['status'] ?? 'warming';
        $oldStartDate = $domain['start_date'] ?? Carbon::now()->format('Y-m-d');
        $oldEndDate = $domain['end_date'] ?? Carbon::now()->addDays(21)->format('Y-m-d');

        // Build prefix_statuses for each variant
        $prefixStatuses = [];
        for ($i = 1; $i <= $inboxesPerDomain; $i++) {
            $prefixKey = "prefix_variant_{$i}";
            $prefixStatuses[$prefixKey] = [
                'status' => $oldStatus,
                'start_date' => $oldStartDate,
                'end_date' => $oldEndDate,
            ];
        }

        // Return new format (remove old status, start_date, end_date)
        return [
            'id' => $domain['id'] ?? null,
            'name' => $domain['name'] ?? '',
            'is_used' => $domain['is_used'] ?? false,
            'prefix_statuses' => $prefixStatuses,
        ];
    }

    /**
     * Migrate a specific pool by ID (for handling large pools that fail in batch migration)
     */
    private function migrateSpecificPool(int $poolId): int
    {
        $pool = Pool::find($poolId);
        
        if (!$pool) {
            $this->error("Pool ID {$poolId} not found.");
            return 1;
        }

        if (!$this->poolNeedsMigration($pool)) {
            $this->info("Pool ID {$poolId} does not need migration (already has prefix_statuses).");
            return 0;
        }

        $inboxesPerDomain = $pool->inboxes_per_domain ?? 1;
        $domainCount = is_array($pool->domains) ? count($pool->domains) : 0;
        
        $this->info("Pool ID: {$poolId}");
        $this->info("Domains: {$domainCount}");
        $this->info("Inboxes per domain: {$inboxesPerDomain}");

        // Migrate domains
        $migratedDomains = [];
        foreach ($pool->domains as $domain) {
            if (!isset($domain['prefix_statuses'])) {
                $migratedDomains[] = $this->migrateDomain($domain, $inboxesPerDomain);
            } else {
                $migratedDomains[] = $domain;
            }
        }

        // Use raw SQL if --raw-sql option is set (for very large pools)
        if ($this->option('raw-sql')) {
            $this->info('Using raw SQL update with increased packet size...');
            
            try {
                $jsonData = json_encode($migratedDomains, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                
                if ($jsonData === false) {
                    $this->error("Failed to encode domains to JSON: " . json_last_error_msg());
                    return 1;
                }
                
                $jsonSizeBytes = strlen($jsonData);
                $jsonSizeMB = round($jsonSizeBytes / 1024 / 1024, 2);
                $this->info("JSON size: {$jsonSizeBytes} bytes ({$jsonSizeMB} MB)");
                
                // Increase session max_allowed_packet to 256MB (enough for ~9000 domains)
                $this->info('Setting session max_allowed_packet to 256MB...');
                DB::statement("SET SESSION max_allowed_packet = 268435456"); // 256MB
                
                // Reconnect to ensure the setting takes effect
                DB::reconnect();
                DB::statement("SET SESSION max_allowed_packet = 268435456"); // 256MB
                
                $this->info('Executing update...');
                
                // Use raw SQL update
                DB::statement("UPDATE pools SET domains = ?, updated_at = NOW() WHERE id = ?", [$jsonData, $poolId]);
                
                $this->info("✓ Pool ID {$poolId}: Successfully migrated using raw SQL");
                return 0;
                
            } catch (\Exception $e) {
                $this->error("✗ Pool ID {$poolId}: Failed - " . $e->getMessage());
                $this->warn("\nThe domains JSON is too large for MySQL. You need to increase max_allowed_packet globally:");
                $this->line("1. Edit my.cnf or my.ini");
                $this->line("2. Add under [mysqld]: max_allowed_packet=256M");
                $this->line("3. Restart MySQL server");
                $this->line("4. Then run this command again");
                return 1;
            }
        }

        // Standard Eloquent save
        try {
            $pool->domains = $migratedDomains;
            $pool->save();
            
            $this->info("✓ Pool ID {$poolId}: Successfully migrated {$domainCount} domains");
            return 0;
            
        } catch (\Exception $e) {
            $this->error("✗ Pool ID {$poolId}: Failed - " . $e->getMessage());
            $this->warn("\nTry using --raw-sql option: php artisan pools:migrate-prefix-statuses --pool-id={$poolId} --raw-sql");
            return 1;
        }
    }
}
