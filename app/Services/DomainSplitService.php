<?php

namespace App\Services;

use App\Models\SmtpProviderSplit;
use Illuminate\Support\Facades\Log;

class DomainSplitService
{
    /**
     * Split domains across active providers based on split percentages
     * 
     * @param array $domains Array of domain names
     * @return array Array with provider slug as key and domains array as value
     *                Example: ['mailin' => ['domain1.com', 'domain2.com'], 'mailrun' => ['domain3.com']]
     */
    public function splitDomains(array $domains): array
    {
        $totalDomains = count($domains);
        
        if ($totalDomains === 0) {
            Log::channel('mailin-ai')->warning('No domains to split', [
                'action' => 'split_domains',
            ]);
            return [];
        }

        // Get active providers ordered by priority
        $activeProviders = SmtpProviderSplit::getActiveProviders();
        
        if ($activeProviders->isEmpty()) {
            Log::channel('mailin-ai')->warning('No active providers found for domain splitting', [
                'action' => 'split_domains',
                'total_domains' => $totalDomains,
            ]);
            return [];
        }

        // Calculate total percentage to ensure it's 100%
        $totalPercentage = $activeProviders->sum('split_percentage');
        
        if (abs($totalPercentage - 100.00) > 0.01) {
            Log::channel('mailin-ai')->error('Active provider percentages do not total 100%', [
                'action' => 'split_domains',
                'total_percentage' => $totalPercentage,
                'total_domains' => $totalDomains,
            ]);
            // Still proceed, but log the issue
        }

        // Calculate domain distribution
        $providerDomains = [];
        $assignedCount = 0;
        $domainIndex = 0;

        // Sort providers by priority (ascending - lower priority number = higher priority)
        $sortedProviders = $activeProviders->sortBy('priority')->values();

        Log::channel('mailin-ai')->info('Starting domain split calculation', [
            'action' => 'split_domains',
            'total_domains' => $totalDomains,
            'active_providers' => $sortedProviders->map(function($p) {
                return [
                    'slug' => $p->slug,
                    'percentage' => $p->split_percentage,
                    'priority' => $p->priority,
                ];
            })->toArray(),
        ]);

        // Calculate and assign domains proportionally
        foreach ($sortedProviders as $provider) {
            $percentage = (float) $provider->split_percentage;
            $domainCount = (int) round($totalDomains * ($percentage / 100));
            
            // Ensure we don't assign more than available
            $remainingDomains = $totalDomains - $assignedCount;
            $domainCount = min($domainCount, $remainingDomains);
            
            if ($domainCount > 0) {
                $providerDomains[$provider->slug] = array_slice($domains, $domainIndex, $domainCount);
                $assignedCount += $domainCount;
                $domainIndex += $domainCount;
                
                Log::channel('mailin-ai')->info('Assigned domains to provider', [
                    'action' => 'split_domains',
                    'provider' => $provider->slug,
                    'percentage' => $percentage,
                    'assigned_count' => $domainCount,
                    'domains' => $providerDomains[$provider->slug],
                ]);
            } else {
                // Provider gets 0 domains (percentage too small)
                $providerDomains[$provider->slug] = [];
                
                Log::channel('mailin-ai')->debug('Provider assigned 0 domains (percentage too small)', [
                    'action' => 'split_domains',
                    'provider' => $provider->slug,
                    'percentage' => $percentage,
                    'calculated_count' => $totalDomains * ($percentage / 100),
                ]);
            }
        }

        // Handle remaining domains (due to rounding) - assign by priority
        if ($assignedCount < $totalDomains) {
            $remaining = array_slice($domains, $domainIndex);
            $remainingCount = count($remaining);
            
            Log::channel('mailin-ai')->info('Assigning remaining domains by priority', [
                'action' => 'split_domains',
                'remaining_count' => $remainingCount,
                'remaining_domains' => $remaining,
            ]);

            // Assign remaining domains to providers by priority (round-robin if needed)
            $providerIndex = 0;
            foreach ($remaining as $domain) {
                $provider = $sortedProviders[$providerIndex % $sortedProviders->count()];
                $providerDomains[$provider->slug][] = $domain;
                $providerIndex++;
                
                Log::channel('mailin-ai')->debug('Assigned remaining domain to provider', [
                    'action' => 'split_domains',
                    'domain' => $domain,
                    'provider' => $provider->slug,
                    'priority' => $provider->priority,
                ]);
            }
        }

        // Log final distribution
        $finalDistribution = [];
        foreach ($providerDomains as $slug => $domains) {
            $finalDistribution[$slug] = count($domains);
        }

        Log::channel('mailin-ai')->info('Domain split completed', [
            'action' => 'split_domains',
            'total_domains' => $totalDomains,
            'final_distribution' => $finalDistribution,
        ]);

        return $providerDomains;
    }

    /**
     * Get provider assignment for a specific domain
     * 
     * @param array $domainSplitResult Result from splitDomains()
     * @param string $domain Domain name
     * @return string|null Provider slug or null if not found
     */
    public function getProviderForDomain(array $domainSplitResult, string $domain): ?string
    {
        foreach ($domainSplitResult as $providerSlug => $domains) {
            if (in_array($domain, $domains)) {
                return $providerSlug;
            }
        }
        return null;
    }
}

