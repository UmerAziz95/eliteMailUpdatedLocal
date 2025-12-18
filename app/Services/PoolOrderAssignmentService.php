<?php

namespace App\Services;

use App\Models\PoolOrder;
use App\Models\PoolPanel;
use App\Models\Pool;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\SlackNotificationService;
use App\Models\Configuration;

class PoolOrderAssignmentService
{
    /**
     * Auto assign domains to pool order from a single panel
     *
     * @param PoolOrder $poolOrder
     * @return array
     */
    public function autoAssignDomains(PoolOrder $poolOrder)
    {
        Log::info("Starting auto-assignment for PoolOrder #{$poolOrder->id}, Quantity: {$poolOrder->quantity}");

        try {
            DB::beginTransaction();

            // 1. Get all active panels with their splits
            // We need to find ONE panel that has enough available inboxes
            
            // Filter by Provider Type from Configuration
            $providerType = Configuration::get('PROVIDER_TYPE');
            
            $query = PoolPanel::with(['poolPanelSplits'])
                ->where('is_active', 1);
            
            if ($providerType) {
                // Assuming PoolPanel has 'provider_type' column as per earlier investigation/Panel model logic
                // If PoolPanel table structure mirrors Panel or if concept is shared.
                // Re-verifying PoolPanel model structure from Step 62: it HAS 'provider_type'.
                $query->where('provider_type', $providerType);
                Log::info("Filtering panels by provider_type: {$providerType}");
            }

            $panels = $query->get();

            $selectedPanel = null;
            $candidateInboxes = []; // Will hold array of ['pool_id' => x, 'domain_id' => y, 'prefix_key' => z, 'email' => ...]
            $neededQuantity = $poolOrder->quantity;

            foreach ($panels as $panel) {
                Log::info("Checking Panel #{$panel->id} ({$panel->title})");
                
                $panelAvailableInboxes = [];
                
                foreach ($panel->poolPanelSplits as $split) {
                    $pool = Pool::find($split->pool_id);
                    if (!$pool || empty($pool->domains)) {
                        continue;
                    }

                    $poolDomains = is_string($pool->domains) ? json_decode($pool->domains, true) : $pool->domains;
                    $splitDomainIds = $split->domains; // Array of domain IDs in this split
                    
                    if (!is_array($splitDomainIds)) {
                        continue;
                    }

                    // Iterate through pool domains to find ones that are in this split
                    foreach ($poolDomains as $domainIndex => $domain) {
                        $domainId = $domain['id'] ?? null;
                        
                        // Check if this domain is part of the split
                        if ($domainId && in_array($domainId, $splitDomainIds)) {
                            // Now check specific prefix availability
                            $prefixStatuses = $domain['prefix_statuses'] ?? [];
                            $domainName = $domain['name'] ?? 'unknown';
                            
                            foreach ($prefixStatuses as $prefixKey => $statusData) {
                                if (isset($statusData['status']) && $statusData['status'] === 'available') {
                                    $panelAvailableInboxes[] = [
                                        'pool_id' => $pool->id,
                                        'domain_id' => $domainId,
                                        'domain_index' => $domainIndex, // Needed to update the specific index in pool
                                        'domain_name' => $domainName,
                                        'prefix_key' => $prefixKey,
                                        'email' => $this->generateEmail($prefixKey, $domainName, $pool),
                                        // Store other metadata if needed
                                    ];
                                }
                            }
                        }
                    }
                }

                Log::info("Panel #{$panel->id} has " . count($panelAvailableInboxes) . " available inboxes.");

                if (count($panelAvailableInboxes) >= $neededQuantity) {
                    $selectedPanel = $panel;
                    $candidateInboxes = array_slice($panelAvailableInboxes, 0, $neededQuantity);
                    Log::info("Found suitable Panel #{$panel->id}");
                    break; // Found our winner
                }
            }

            if (!$selectedPanel) {
                Log::warning("No single panel found with enough capacity for PoolOrder #{$poolOrder->id}");
                SlackNotificationService::sendAssignmentFailedNotification($poolOrder, "No single panel has enough available inboxes (Required: {$neededQuantity})");
                DB::rollBack();
                return ['success' => false, 'message' => 'No available panel found with sufficient capacity.'];
            }

            // 2. Perform Assignment
            // We need to update pools (mark prefixes used) and update pool order (save assignments)
            
            // Group by pool to minimize DB writes
            $inboxesByPool = [];
            foreach ($candidateInboxes as $inbox) {
                $inboxesByPool[$inbox['pool_id']][] = $inbox;
            }

            foreach ($inboxesByPool as $poolId => $inboxes) {
                $pool = Pool::find($poolId);
                $domains = is_string($pool->domains) ? json_decode($pool->domains, true) : $pool->domains;
                
                foreach ($inboxes as $inbox) {
                    $idx = $inbox['domain_index'];
                    $pKey = $inbox['prefix_key'];
                    
                    if (isset($domains[$idx]['prefix_statuses'][$pKey])) {
                        $domains[$idx]['prefix_statuses'][$pKey]['status'] = 'used';
                        // Keep start/end dates if they exist, or set them? 
                        // Usually we set them when "warming" starts, but for direct assignment we might just mark status.
                        // For now, just update status.
                        
                        // Also mark domain as used? The domain might be partially used.
                        // The 'is_used' flag on domain level might be legacy or for full domain usage.
                        // We will set is_used = true if at least one prefix is used, just to be safe with legacy logic if any.
                        $domains[$idx]['is_used'] = true; 
                        $domains[$idx]['status'] = 'used'; // Legacy status
                    }
                }

                // Save back to DB
                // DB::table('pools')->where('id', $poolId)->update(['domains' => json_encode($domains)]);
                 $pool->domains = $domains;
                 $pool->save();
            }

            // 3. Update Pool Order
            $assignedDomainsData = [];
            foreach ($candidateInboxes as $inbox) {
                $assignedDomainsData[] = [
                    'domain_id' => $inbox['domain_id'],
                    'pool_id' => $inbox['pool_id'],
                    'domain_name' => $inbox['domain_name'],
                    'status' => 'used',
                    'prefixes' => [$inbox['prefix_key']], // Store which prefix was assigned
                    'selected_prefixes' => [$inbox['prefix_key'] => ['email' => $inbox['email']]] // More detailed info
                ];
            }
            
            // Consolidate if multiple prefixes from same domain are assigned (though rare if we iterate linearly)
            // But structure in pool_orders.domains usually is list of domains.
            // If we assign multiple prefixes from SAME domain, we should merge them?
            // current structure of pool_orders.domains is list of objects.
            // Let's group by domain_id for the order record
            $finalOrderDomains = [];
            $groupedByDomain = [];
            
            foreach ($assignedDomainsData as $item) {
                $dId = $item['domain_id'];
                if (!isset($groupedByDomain[$dId])) {
                    $groupedByDomain[$dId] = $item;
                    // Ensure prefixes is array
                    $groupedByDomain[$dId]['prefixes'] = $item['prefixes'];
                    $groupedByDomain[$dId]['per_inbox'] = 1; // Start count
                } else {
                    $groupedByDomain[$dId]['prefixes'] = array_merge($groupedByDomain[$dId]['prefixes'], $item['prefixes']);
                    $groupedByDomain[$dId]['selected_prefixes'] = array_merge($groupedByDomain[$dId]['selected_prefixes'], $item['selected_prefixes']);
                    $groupedByDomain[$dId]['per_inbox'] += 1;
                }
            }
            
            $poolOrder->domains = array_values($groupedByDomain);
            // Update status?
            if ($poolOrder->status_manage_by_admin === 'draft' || $poolOrder->status_manage_by_admin === 'pending') {
                // $poolOrder->status_manage_by_admin = 'in-progress';
            }
            
            $poolOrder->save();

            DB::commit();
            Log::info("Successfully assigned " . count($candidateInboxes) . " inboxes to PoolOrder #{$poolOrder->id}");
            
            return ['success' => true, 'message' => 'Domains assigned successfully', 'count' => count($candidateInboxes)];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error in autoAssignDomains: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return ['success' => false, 'message' => 'Error assigning domains: ' . $e->getMessage()];
        }
    }

    /**
     * Helper to reconstruct email from prefix key and domain
     */
    private function generateEmail($prefixKey, $domainName, $pool)
    {
        // prefixKey like 'prefix_variant_1'
        $variants = $pool->prefix_variants; // This is an array
        
        // CASE 1: Associative Array where key matches prefixKey
        // Example: ['prefix_variant_1' => 'info']
        if (isset($variants[$prefixKey])) {
            return $variants[$prefixKey] . '@' . $domainName;
        }

        // CASE 2: Indexed Array (fallback)
        // Example: ['info', 'sales'] (0 => 'info', 1 => 'sales')
        // We try to extract number from 'prefix_variant_N' -> index N-1
        if (preg_match('/prefix_variant_(\d+)/', $prefixKey, $matches)) {
            $index = $matches[1] - 1; // 1-based to 0-based
            if (isset($variants[$index])) {
                return $variants[$index] . '@' . $domainName;
            }
        }
        
        return 'unknown@' . $domainName;
    }
}
