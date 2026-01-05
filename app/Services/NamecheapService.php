<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NamecheapService
{
    protected $baseUrl = 'https://api.namecheap.com/xml.response';
    protected $timeout = 30;
    /**
     * Static server IP that must be whitelisted in Namecheap account
     * IMPORTANT: The server's actual outbound IP address must match this IP.
     * Namecheap validates both the ClientIp parameter AND the actual source IP of the HTTP request.
     * If the server's outbound IP doesn't match this value, requests will be rejected with "Invalid request IP" error.
     * Server must be configured to use 144.172.95.185 as its outbound IP, or route through a proxy/VPN using this IP.
     */
    protected $clientIp = '144.172.95.185';

    /**
     * Update nameservers for a domain via Namecheap API
     * 
     * @param string $domain Domain name (e.g., 'example.com')
     * @param array $nameServers Array of nameserver hostnames
     * @param string $apiUser Namecheap API User (username - same as UserName)
     * @param string $apiKey Namecheap API Key
     * @return array
     */
    public function updateNameservers(string $domain, array $nameServers, string $apiUser, string $apiKey)
    {
        try {
            Log::channel('mailin-ai')->info('Updating nameservers via Namecheap API', [
                'action' => 'update_nameservers',
                'domain' => $domain,
                'name_servers' => $nameServers,
                'api_user' => $apiUser,
            ]);

            // Parse domain into SLD and TLD
            $domainParts = $this->parseDomain($domain);
            if (!$domainParts) {
                throw new \Exception('Invalid domain format: ' . $domain);
            }

            $sld = $domainParts['sld'];
            $tld = $domainParts['tld'];

            // Convert nameservers array to comma-separated string
            $nameServersString = implode(',', $nameServers);

            // Build query parameters
            // ApiUser and UserName are both set to the username (same value)
            $queryParams = [
                'ApiUser' => $apiUser,
                'ApiKey' => $apiKey,
                'UserName' => $apiUser, // Same as ApiUser (username)
                'ClientIp' => $this->clientIp,
                'Command' => 'namecheap.domains.dns.setCustom',
                'SLD' => $sld,
                'TLD' => $tld,
                'NameServers' => $nameServersString,
            ];

            Log::channel('mailin-ai')->info('Namecheap API request parameters', [
                'action' => 'update_nameservers',
                'domain' => $domain,
                'sld' => $sld,
                'tld' => $tld,
                'name_servers' => $nameServersString,
                'client_ip_parameter' => $this->clientIp,
                'note' => 'Namecheap validates that the actual source IP matches the ClientIp parameter. Server must use ' . $this->clientIp . ' as outbound IP.',
            ]);

            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl, $queryParams);

            $statusCode = $response->status();
            $responseBody = $response->body();

            // Parse XML response
            $xml = simplexml_load_string($responseBody);
            if (!$xml) {
                throw new \Exception('Failed to parse Namecheap API response');
            }

            // Convert XML to array for easier handling
            $responseArray = json_decode(json_encode($xml), true);

            // Check if request was successful
            $status = (string) $xml['Status'];
            $isSuccess = $status === 'OK';

            if ($isSuccess) {
                // Check if nameservers were actually updated
                $commandResponse = $xml->CommandResponse;
                $domainDnsResult = $commandResponse->DomainDNSSetCustomResult ?? null;
                $updated = $domainDnsResult ? (string) $domainDnsResult['Updated'] : 'false';
                $isUpdated = strtolower($updated) === 'true';

                if ($isUpdated) {
                    Log::channel('mailin-ai')->info('Namecheap nameserver update successful', [
                        'action' => 'update_nameservers',
                        'domain' => $domain,
                        'status_code' => $statusCode,
                        'updated' => $updated,
                        'response' => $responseArray,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Nameservers updated successfully',
                        'response' => $responseArray,
                    ];
                } else {
                    $errorMessage = 'Nameservers were not updated. Updated attribute: ' . $updated;
                    
                    Log::channel('mailin-ai')->warning('Namecheap nameserver update returned false', [
                        'action' => 'update_nameservers',
                        'domain' => $domain,
                        'status_code' => $statusCode,
                        'updated' => $updated,
                        'response' => $responseArray,
                    ]);

                    throw new \Exception('Failed to update nameservers via Namecheap API: ' . $errorMessage);
                }
            } else {
                // Extract error message from XML
                $errors = [];
                if (isset($xml->Errors)) {
                    foreach ($xml->Errors->Error as $error) {
                        $errors[] = (string) $error;
                    }
                }
                $errorMessage = !empty($errors) ? implode(', ', $errors) : 'Unknown error';
                
                // Check if error is related to IP validation
                $isIpError = false;
                $actualSourceIp = null;
                if (preg_match('/Invalid request IP: ([\d\.]+)/', $errorMessage, $matches)) {
                    $isIpError = true;
                    $actualSourceIp = $matches[1];
                }
                
                Log::channel('mailin-ai')->error('Namecheap nameserver update failed', [
                    'action' => 'update_nameservers',
                    'domain' => $domain,
                    'status_code' => $statusCode,
                    'status' => $status,
                    'errors' => $errors,
                    'error_message' => $errorMessage,
                    'is_ip_error' => $isIpError,
                    'actual_source_ip' => $actualSourceIp,
                    'expected_client_ip' => $this->clientIp,
                    'response' => $responseArray,
                ]);
                
                // Provide more helpful error message for IP validation errors
                if ($isIpError && $actualSourceIp) {
                    $errorMessage = "Invalid request IP: {$actualSourceIp}. The server's actual outbound IP ({$actualSourceIp}) does not match the whitelisted IP ({$this->clientIp}). The server must be configured to use {$this->clientIp} as its outbound IP address, or the request must be routed through a proxy/VPN using {$this->clientIp}.";
                }

                throw new \Exception('Failed to update nameservers via Namecheap API: ' . $errorMessage);
            }

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Namecheap nameserver update exception', [
                'action' => 'update_nameservers',
                'domain' => $domain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Parse domain into SLD and TLD
     * 
     * @param string $domain Domain name (e.g., 'example.com' or 'sub.example.com')
     * @return array|null Array with 'sld' and 'tld' keys, or null if invalid
     */
    protected function parseDomain(string $domain): ?array
    {
        $domain = strtolower(trim($domain));
        
        // Remove protocol if present
        $domain = preg_replace('#^https?://#', '', $domain);
        
        // Remove www. if present
        $domain = preg_replace('#^www\.#', '', $domain);
        
        // Remove trailing slash
        $domain = rtrim($domain, '/');
        
        // Split domain into parts
        $parts = explode('.', $domain);
        
        if (count($parts) < 2) {
            return null;
        }
        
        // Get TLD (last part)
        $tld = array_pop($parts);
        
        // Get SLD (second-level domain)
        // For Namecheap API, we always want the second-level domain (the part before TLD)
        // For example: sub.example.com -> SLD = example, TLD = com
        //              example.com -> SLD = example, TLD = com
        // So we take the last remaining part after removing TLD
        $sld = array_pop($parts);
        
        // If there are still parts left, it means we had a subdomain
        // But for Namecheap API, we only need SLD and TLD, so we ignore subdomains
        
        if (empty($sld)) {
            return null;
        }
        
        return [
            'sld' => $sld,
            'tld' => $tld,
        ];
    }
}

