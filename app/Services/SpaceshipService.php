<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpaceshipService
{
    protected $baseUrl = 'https://spaceship.dev/api/v1';
    protected $timeout = 30;

    /**
     * Update nameservers for a domain via Spaceship API
     * 
     * @param string $domain Domain name
     * @param array $nameServers Array of nameserver hostnames
     * @param string $apiKey Spaceship API Key
     * @param string $apiSecretKey Spaceship API Secret Key
     * @return array
     */
    public function updateNameservers(string $domain, array $nameServers, string $apiKey, string $apiSecretKey)
    {
        try {
            Log::channel('mailin-ai')->info('Updating nameservers via Spaceship API', [
                'action' => 'update_nameservers',
                'domain' => $domain,
                'name_servers' => $nameServers,
            ]);

            $url = $this->baseUrl . '/domains/' . $domain . '/nameservers';
            
            $requestBody = [
                'provider' => 'custom',
                'hosts' => $nameServers,
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-API-Key' => $apiKey,
                    'X-API-Secret' => $apiSecretKey,
                ])
                ->put($url, $requestBody);

            $statusCode = $response->status();
            $responseBody = $response->json();

            if ($response->successful()) {
                Log::channel('mailin-ai')->info('Spaceship nameserver update successful', [
                    'action' => 'update_nameservers',
                    'domain' => $domain,
                    'status_code' => $statusCode,
                    'response' => $responseBody,
                ]);

                return [
                    'success' => true,
                    'message' => 'Nameservers updated successfully',
                    'response' => $responseBody,
                ];
            } else {
                $errorMessage = $responseBody['message'] ?? $responseBody['error'] ?? 'Unknown error';
                
                Log::channel('mailin-ai')->error('Spaceship nameserver update failed', [
                    'action' => 'update_nameservers',
                    'domain' => $domain,
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                    'response' => $responseBody,
                ]);

                throw new \Exception('Failed to update nameservers via Spaceship API: ' . $errorMessage);
            }

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Spaceship nameserver update exception', [
                'action' => 'update_nameservers',
                'domain' => $domain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}



