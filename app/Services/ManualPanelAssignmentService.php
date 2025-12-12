<?php

namespace App\Services;

use App\Models\Pool;
use App\Models\PoolPanel;
use App\Models\PoolPanelSplit;
use App\Models\Configuration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ManualPanelAssignmentService
{
    /**
     * Process manual assignments for a pool
     *
     * @param Pool $pool
     * @param array $assignments
     * @return array
     * @throws Exception
     */
    public function processManualAssignments(Pool $pool, array $assignments)
    {
        DB::beginTransaction();

        try {
            // Set pool as splitting to prevent concurrent processing
            $pool->update(['is_splitting' => 1]);

            // Validate assignments before processing
            $validation = $this->validateManualAssignments($pool, $assignments);
            
            if (!$validation['valid']) {
                throw new Exception('Validation failed: ' . implode(', ', $validation['errors']));
            }

            // Parse pool domains
            $poolDomains = is_string($pool->domains) ? json_decode($pool->domains, true) : $pool->domains;
            
            if (!is_array($poolDomains)) {
                throw new Exception('Invalid pool domains format');
            }

            $splitNumber = 1;
            $assignedDomainIds = [];

            // Process each batch assignment
            foreach ($assignments as $batch) {
                $panelId = $batch['panel_id'];
                $domainStart = (int) $batch['domain_start'];
                $domainEnd = (int) $batch['domain_end'];

                // Extract domain slice for this batch
                $batchDomainIds = $this->extractDomainSlice($poolDomains, $domainStart, $domainEnd);
                
                // Calculate space needed
                $spaceNeeded = count($batchDomainIds) * $pool->inboxes_per_domain;

                // Assign batch to panel
                $this->assignBatchToPanel($pool, $panelId, $batchDomainIds, $spaceNeeded, $splitNumber);

                $assignedDomainIds = array_merge($assignedDomainIds, $batchDomainIds);
                $splitNumber++;
            }

            // Verify all domains were assigned
            if (count($assignedDomainIds) !== count($poolDomains)) {
                throw new Exception('Not all domains were assigned. Expected: ' . count($poolDomains) . ', Got: ' . count($assignedDomainIds));
            }

            // Release splitting lock
            $pool->update(['is_splitting' => 0]);

            DB::commit();

            Log::info('Manual panel assignment completed', [
                'pool_id' => $pool->id,
                'batches' => count($assignments),
                'domains_assigned' => count($assignedDomainIds)
            ]);

            return [
                'success' => true,
                'message' => 'Pool successfully assigned to panels',
                'batches' => count($assignments),
                'domains_assigned' => count($assignedDomainIds)
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            // Release splitting lock on error
            $pool->update(['is_splitting' => 0]);

            Log::error('Manual panel assignment failed', [
                'pool_id' => $pool->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Validate manual assignments before processing
     *
     * @param Pool|array $poolData
     * @param array $assignments
     * @return array
     */
    public function validateManualAssignments($poolData, array $assignments)
    {
        $errors = [];
        $warnings = [];

        // Handle both Pool model and array data
        if ($poolData instanceof Pool) {
            $pool = $poolData;
            $poolDomains = is_string($pool->domains) ? json_decode($pool->domains, true) : $pool->domains;
            $totalDomains = is_array($poolDomains) ? count($poolDomains) : 0;
            $inboxesPerDomain = $pool->inboxes_per_domain;
            $providerType = $pool->provider_type;
        } else {
            // Array data from form validation
            $totalDomains = $poolData['total_domains'] ?? 0;
            $inboxesPerDomain = $poolData['inboxes_per_domain'] ?? 1;
            $providerType = $poolData['provider_type'] ?? 'Google';
        }

        if (empty($assignments)) {
            $errors[] = 'No assignments provided';
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $assignedDomains = [];
        $usedPanels = [];

        foreach ($assignments as $index => $batch) {
            $batchNum = $index + 1;

            // Validate required fields
            if (empty($batch['panel_id'])) {
                $errors[] = "Batch {$batchNum}: Panel is required";
                continue;
            }

            if (!isset($batch['domain_start']) || !isset($batch['domain_end'])) {
                $errors[] = "Batch {$batchNum}: Domain range is required";
                continue;
            }

            $panelId = $batch['panel_id'];
            $domainStart = (int) $batch['domain_start'];
            $domainEnd = (int) $batch['domain_end'];

            // Validate domain range
            if ($domainStart < 1 || $domainEnd < 1) {
                $errors[] = "Batch {$batchNum}: Domain range must start from 1";
            }

            if ($domainEnd < $domainStart) {
                $errors[] = "Batch {$batchNum}: End domain must be >= start domain";
            }

            if ($domainStart > $totalDomains || $domainEnd > $totalDomains) {
                $errors[] = "Batch {$batchNum}: Domain range exceeds total domains ({$totalDomains})";
            }

            // Check for overlapping domains
            for ($i = $domainStart; $i <= $domainEnd; $i++) {
                if (in_array($i, $assignedDomains)) {
                    $errors[] = "Batch {$batchNum}: Domain {$i} is already assigned in another batch";
                } else {
                    $assignedDomains[] = $i;
                }
            }

            // Validate panel
            $panel = PoolPanel::find($panelId);
            
            if (!$panel) {
                $errors[] = "Batch {$batchNum}: Panel not found";
                continue;
            }

            if (!$panel->is_active) {
                $errors[] = "Batch {$batchNum}: Panel is not active";
            }

            // Validate provider type against configuration
            $allowedProviders = $this->getAllowedProviderTypes();
            if (!in_array($providerType, $allowedProviders)) {
                $errors[] = "Invalid provider type: {$providerType}. Allowed types: " . implode(', ', $allowedProviders);
            }

            if ($panel->provider_type !== $providerType) {
                $errors[] = "Batch {$batchNum}: Panel provider type ({$panel->provider_type}) does not match pool provider type ({$providerType})";
            }

            // Calculate space needed
            $domainCount = $domainEnd - $domainStart + 1;
            $spaceNeeded = $domainCount * $inboxesPerDomain;

            // Get provider-specific panel capacity limits from configuration
            $panelCapacityLimit = $this->getPanelCapacityLimit($providerType);
            $maxSplitCapacity = $this->getMaxSplitCapacity($providerType);
            $splitCapacityEnabled = $this->isSplitCapacityEnabled($providerType);
            
            // Check panel capacity
            if ($spaceNeeded > $panel->remaining_limit) {
                $errors[] = "Batch {$batchNum}: Insufficient panel capacity. Needed: {$spaceNeeded}, Available: {$panel->remaining_limit}";
            }
            
            // Warn if exceeding recommended capacity per assignment (if split capacity is enabled)
            if ($splitCapacityEnabled && $spaceNeeded > $maxSplitCapacity) {
                $warnings[] = "Batch {$batchNum}: Assignment size ({$spaceNeeded}) exceeds recommended split limit ({$maxSplitCapacity}) for {$providerType}";
            }
            
            // Warn if exceeding total panel capacity
            if ($spaceNeeded > $panelCapacityLimit) {
                $warnings[] = "Batch {$batchNum}: Assignment size ({$spaceNeeded}) exceeds panel capacity limit ({$panelCapacityLimit}) for {$providerType}";
            }

            // Check if panel is already used in this assignment
            if (in_array($panelId, $usedPanels)) {
                $errors[] = "Batch {$batchNum}: Panel {$panel->auto_generated_id} is already assigned to another batch. Each panel can only be used once per pool.";
            }

            // Track used panels
            $usedPanels[] = $panelId;
        }

        // Check if all domains are assigned
        $uniqueAssigned = array_unique($assignedDomains);
        sort($uniqueAssigned);
        
        if (count($uniqueAssigned) !== $totalDomains) {
            $errors[] = "Not all domains are assigned. Assigned: " . count($uniqueAssigned) . ", Total: {$totalDomains}";
        } else {
            // Check for gaps in assignment
            for ($i = 1; $i <= $totalDomains; $i++) {
                if (!in_array($i, $uniqueAssigned)) {
                    $errors[] = "Domain {$i} is not assigned";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'assigned_count' => count($uniqueAssigned),
            'total_domains' => $totalDomains
        ];
    }

    /**
     * Assign a batch to a panel
     *
     * @param Pool $pool
     * @param int $panelId
     * @param array $domainIds
     * @param int $spaceNeeded
     * @param int $splitNumber
     * @return PoolPanelSplit
     * @throws Exception
     */
    protected function assignBatchToPanel(Pool $pool, int $panelId, array $domainIds, int $spaceNeeded, int $splitNumber)
    {
        $panel = PoolPanel::findOrFail($panelId);

        // Verify panel has sufficient capacity
        if (!$this->verifyPanelCapacity($panelId, $spaceNeeded)) {
            throw new Exception("Panel {$panel->auto_generated_id} does not have sufficient capacity. Needed: {$spaceNeeded}, Available: {$panel->remaining_limit}");
        }

        // Create pool panel split
        $split = $this->createPoolPanelSplit($pool, $panel, $domainIds, $spaceNeeded, $splitNumber);

        // Update panel limits
        $this->updatePanelLimits($panel, $spaceNeeded);

        Log::info('Batch assigned to panel', [
            'pool_id' => $pool->id,
            'panel_id' => $panelId,
            'panel_name' => $panel->auto_generated_id,
            'split_number' => $splitNumber,
            'domains_count' => count($domainIds),
            'space_needed' => $spaceNeeded
        ]);

        return $split;
    }

    /**
     * Extract domain IDs for a specific range
     *
     * @param array $poolDomains
     * @param int $start
     * @param int $end
     * @return array
     */
    protected function extractDomainSlice(array $poolDomains, int $start, int $end)
    {
        $domainIds = [];
        $index = 1;

        foreach ($poolDomains as $domain) {
            if ($index >= $start && $index <= $end) {
                $domainIds[] = $domain['id'];
            }
            
            if ($index > $end) {
                break;
            }
            
            $index++;
        }

        return $domainIds;
    }

    /**
     * Calculate space needed for domains
     *
     * @param array $domains
     * @param int $inboxesPerDomain
     * @return int
     */
    public function calculateBatchSpace(array $domains, int $inboxesPerDomain)
    {
        return count($domains) * $inboxesPerDomain;
    }

    /**
     * Verify if panel has sufficient capacity
     *
     * @param int $panelId
     * @param int $spaceNeeded
     * @return bool
     */
    public function verifyPanelCapacity(int $panelId, int $spaceNeeded)
    {
        $panel = PoolPanel::find($panelId);

        if (!$panel) {
            return false;
        }

        return $panel->remaining_limit >= $spaceNeeded && $panel->is_active;
    }

    /**
     * Create a pool panel split record
     *
     * @param Pool $pool
     * @param PoolPanel $panel
     * @param array $domainIds
     * @param int $spaceNeeded
     * @param int $splitNumber
     * @return PoolPanelSplit
     */
    protected function createPoolPanelSplit(Pool $pool, PoolPanel $panel, array $domainIds, int $spaceNeeded, int $splitNumber)
    {
        return PoolPanelSplit::create([
            'pool_panel_id' => $panel->id,
            'pool_id' => $pool->id,
            'inboxes_per_domain' => $pool->inboxes_per_domain,
            'domains' => $domainIds, // Store domain IDs as array, not JSON string
            'assigned_space' => $spaceNeeded,
        ]);
    }

    /**
     * Update panel capacity limits
     *
     * @param PoolPanel $panel
     * @param int $spaceUsed
     * @return void
     * @throws Exception
     */
    protected function updatePanelLimits(PoolPanel $panel, int $spaceUsed)
    {
        $newRemainingLimit = $panel->remaining_limit - $spaceUsed;
        $newUsedLimit = $panel->used_limit + $spaceUsed;

        if ($newRemainingLimit < 0) {
            throw new Exception("Panel {$panel->auto_generated_id} would have negative remaining limit after assignment");
        }

        if ($newUsedLimit > $panel->limit) {
            throw new Exception("Panel {$panel->auto_generated_id} would exceed total limit after assignment");
        }

        // Update panel capacity using decrement/increment for atomic operations
        $panel->decrement('remaining_limit', $spaceUsed);
        $panel->increment('used_limit', $spaceUsed);
        
        // Ensure remaining_limit never goes below 0 and used_limit doesn't exceed limit
        // This matches the safety checks in pool:assigned-panel command
        if ($panel->remaining_limit < 0) {
            $panel->update(['remaining_limit' => 0]);
        }
        if ($panel->used_limit > $panel->limit) {
            $panel->update(['used_limit' => $panel->limit]);
        }

        Log::info('Panel limits updated', [
            'panel_id' => $panel->id,
            'panel_name' => $panel->auto_generated_id,
            'space_used' => $spaceUsed,
            'remaining_limit' => $panel->remaining_limit,
            'used_limit' => $panel->used_limit
        ]);
    }

    /**
     * Rollback manual assignments for a pool
     *
     * @param Pool $pool
     * @return array
     */
    public function rollbackManualAssignments(Pool $pool)
    {
        DB::beginTransaction();

        try {
            $splits = PoolPanelSplit::where('pool_id', $pool->id)->get();
            $rolledBackCount = 0;

            foreach ($splits as $split) {
                $panel = $split->poolPanel;
                
                if ($panel) {
                    // Restore panel capacity using increment/decrement for atomic operations
                    $panel->increment('remaining_limit', $split->assigned_space);
                    $panel->decrement('used_limit', $split->assigned_space);
                    
                    // Ensure used_limit never goes below 0 (matches auto-assignment command)
                    if ($panel->used_limit < 0) {
                        $panel->update(['used_limit' => 0]);
                    }

                    Log::info('Panel capacity restored during rollback', [
                        'panel_id' => $panel->id,
                        'panel_name' => $panel->auto_generated_id,
                        'space_restored' => $split->assigned_space,
                        'panel_remaining_limit' => $panel->remaining_limit,
                        'panel_used_limit' => $panel->used_limit
                    ]);
                }

                // Delete split record
                $split->delete();
                $rolledBackCount++;
            }

            DB::commit();

            Log::info('Manual assignments rolled back', [
                'pool_id' => $pool->id,
                'splits_removed' => $rolledBackCount
            ]);

            return [
                'success' => true,
                'message' => 'Assignments rolled back successfully',
                'splits_removed' => $rolledBackCount
            ];

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Rollback failed', [
                'pool_id' => $pool->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get available panels for a provider type
     *
     * @param string $providerType
     * @return array
     */
    public function getAvailablePanels(string $providerType)
    {
        // Validate provider type against allowed types from configuration
        $allowedProviders = $this->getAllowedProviderTypes();
        if (!in_array($providerType, $allowedProviders)) {
            Log::warning('Invalid provider type requested', [
                'requested' => $providerType,
                'allowed' => $allowedProviders
            ]);
            // Default to Google if invalid
            $providerType = 'Google';
        }

        $panels = PoolPanel::where('provider_type', $providerType)
            ->where('is_active', true)
            ->where('remaining_limit', '>', 0)
            ->orderBy('remaining_limit', 'desc')
            ->get(['id', 'auto_generated_id', 'title', 'limit', 'remaining_limit', 'used_limit', 'provider_type']);

        return $panels->map(function ($panel) {
            return [
                'id' => $panel->id,
                'auto_generated_id' => $panel->auto_generated_id,
                'title' => $panel->title,
                'limit' => $panel->limit,
                'remaining_limit' => $panel->remaining_limit,
                'used_limit' => $panel->used_limit,
                'provider_type' => $panel->provider_type,
            ];
        })->toArray();
    }

    /**
     * Validate that all domains are assigned
     *
     * @param Pool $pool
     * @param array $assignments
     * @return bool
     */
    public function validateAllDomainsAssigned(Pool $pool, array $assignments)
    {
        $poolDomains = is_string($pool->domains) ? json_decode($pool->domains, true) : $pool->domains;
        $totalDomains = count($poolDomains);

        $assignedCount = 0;
        
        foreach ($assignments as $batch) {
            $domainStart = (int) $batch['domain_start'];
            $domainEnd = (int) $batch['domain_end'];
            $assignedCount += ($domainEnd - $domainStart + 1);
        }

        return $assignedCount === $totalDomains;
    }

    /**
     * Get allowed provider types from configuration
     *
     * @return array
     */
    protected function getAllowedProviderTypes()
    {
        return Configuration::getProviderTypes();
    }

    /**
     * Get panel capacity limit from configuration (provider-specific)
     *
     * @param string|null $providerType
     * @return int
     */
    protected function getPanelCapacityLimit($providerType = null)
    {
        if (!$providerType) {
            return (int) Configuration::get('PANEL_CAPACITY', 1790);
        }

        // Provider-specific capacity
        if (strtolower($providerType) === 'microsoft 365') {
            return (int) Configuration::get('MICROSOFT_365_CAPACITY', 
                Configuration::get('PANEL_CAPACITY', 1790));
        }
        
        return (int) Configuration::get('GOOGLE_PANEL_CAPACITY', 
            Configuration::get('PANEL_CAPACITY', 1790));
    }

    /**
     * Get maximum split capacity from configuration (provider-specific)
     *
     * @param string|null $providerType
     * @return int
     */
    protected function getMaxSplitCapacity($providerType = null)
    {
        if (!$providerType) {
            return (int) Configuration::get('MAX_SPLIT_CAPACITY', 358);
        }

        // Provider-specific max split capacity
        if (strtolower($providerType) === 'microsoft 365') {
            return (int) Configuration::get('MICROSOFT_365_MAX_SPLIT_CAPACITY', 
                Configuration::get('MAX_SPLIT_CAPACITY', 358));
        }
        
        return (int) Configuration::get('GOOGLE_MAX_SPLIT_CAPACITY', 
            Configuration::get('MAX_SPLIT_CAPACITY', 358));
    }

    /**
     * Check if split capacity is enabled from configuration (provider-specific)
     *
     * @param string|null $providerType
     * @return bool
     */
    protected function isSplitCapacityEnabled($providerType = null)
    {
        if (!$providerType) {
            return (bool) Configuration::get('ENABLE_MAX_SPLIT_CAPACITY', true);
        }

        // Provider-specific split capacity toggle
        if (strtolower($providerType) === 'microsoft 365') {
            return (bool) Configuration::get('ENABLE_MICROSOFT_365_MAX_SPLIT_CAPACITY', true);
        }
        
        return (bool) Configuration::get('ENABLE_GOOGLE_MAX_SPLIT_CAPACITY', true);
    }
}
