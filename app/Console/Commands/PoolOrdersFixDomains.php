<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PoolOrder;
use App\Models\Pool;
use Illuminate\Support\Facades\Log;

class PoolOrdersFixDomains extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pool-orders:fix-domains';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate pool order domains to new rich structure compatible with updated logic';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting pool orders domain migration...');
        
        $count = 0;
        $errors = 0;

        PoolOrder::chunk(100, function ($orders) use (&$count, &$errors) {
            foreach ($orders as $order) {
                try {
                    $originalDomains = $order->domains ?? [];
                    if (empty($originalDomains) || !is_array($originalDomains)) {
                        continue;
                    }

                    $newDomains = [];
                    $isModified = false;

                    foreach ($originalDomains as $domainEntry) {
                        // Check if already migrated (has selected_prefixes)
                        if (isset($domainEntry['selected_prefixes'])) {
                            $newDomains[] = $domainEntry;
                            continue;
                        }

                        $domainId = $domainEntry['domain_id'] ?? null;
                        
                        // Try to find pool_id in order domains or fallback to pool_id on order (if we added the accessor logic recently)
                        // The snippet provided in previous turns suggests we might need to find the pool.
                        // Ideally the domain entry represents a domain from a pool.
                        // If pool_id is missing in the entry, we might need to guess or it might be there.
                        // Looking at the user request: {"domain_id":"1003_126","pool_id":1003...}
                        // So usually pool_id is part of domain_id or valid in entry.
                        
                        $poolId = $domainEntry['pool_id'] ?? null;

                        // Heuristic: if domain_id looks like "1003_126", pool_id is 1003.
                        if (!$poolId && $domainId && strpos($domainId, '_') !== false) {
                            $parts = explode('_', $domainId);
                            $poolId = $parts[0];
                        }

                        if (!$poolId) {
                             $this->warn("Order {$order->id}: No pool_id found for domain {$domainId}, skipping enhancement.");
                             $newDomains[] = $domainEntry;
                             continue;
                        }

                        $pool = Pool::find($poolId);
                        if (!$pool) {
                            $this->warn("Order {$order->id}: Pool {$poolId} not found, skipping enhancement.");
                            $newDomains[] = $domainEntry;
                            continue;
                        }

                        // Get Domain Name from Pool
                        $poolDomains = $pool->domains; // Array of domains
                        // Pool domains might be JSON string or array depending on accessor
                         if (is_string($poolDomains)) {
                            $poolDomains = json_decode($poolDomains, true);
                        }

                        $matchingDomain = null;
                        if (is_array($poolDomains)) {
                            $matchingDomain = collect($poolDomains)->firstWhere('id', $domainId) 
                                           ?? collect($poolDomains)->firstWhere('domain_id', $domainId);
                        }

                        if (!$matchingDomain) {
                            $this->warn("Order {$order->id}: Domain {$domainId} not found in Pool {$poolId}.");
                             $newDomains[] = $domainEntry;
                             continue;
                        }

                        $domainName = $matchingDomain['name'] ?? $matchingDomain['domain_name'] ?? 'unknown.com';
                        
                        // Construct Prefixes
                        $prefixes = [];
                        $selectedPrefixes = [];
                        
                        // Use pool prefix variants
                        $variants = $pool->prefix_variants ?? [];
                        // Ensure it's array
                        if (is_string($variants)) $variants = json_decode($variants, true);

                        if (is_array($variants)) {
                            foreach ($variants as $key => $prefix) {
                                if (empty($prefix)) continue;
                                
                                $prefixes[] = $key; // e.g. "prefix_variant_1"
                                
                                // Construct email
                                $email = $prefix . '@' . $domainName;
                                $selectedPrefixes[$key] = [
                                    'email' => $email
                                ];
                            }
                        }

                        // Build new entry
                        $newEntry = [
                            'domain_id' => $domainId,
                            'pool_id' => (int)$poolId,
                            'domain_name' => $domainName,
                            'status' => $domainEntry['status'] ?? 'in-progress',
                            'prefixes' => $prefixes,
                            'selected_prefixes' => empty($selectedPrefixes) ? (object)[] : $selectedPrefixes, // empty object for JSON {}
                            'per_inbox' => $domainEntry['per_inbox'] ?? 1,
                        ];

                        $newDomains[] = $newEntry;
                        $isModified = true;
                    }

                    if ($isModified) {
                        $order->domains = $newDomains;
                        $order->save();
                        $count++;
                        $this->line("Order {$order->id} migrated.");
                    }

                } catch (\Exception $e) {
                    $errors++;
                    $this->error("Error processing Order {$order->id}: " . $e->getMessage());
                    Log::error("PoolOrdersFixDomains Error: " . $e->getMessage(), ['order_id' => $order->id]);
                }
            }
        });

        $this->info("Migration completed. Updated {$count} orders. Encountered {$errors} errors.");
        return 0;
    }
}
