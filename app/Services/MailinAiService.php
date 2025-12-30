<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MailinAiService
{
    protected $baseUrl;
    protected $email;
    protected $password;
    protected $deviceName;
    protected $timeout;
    protected $token = null;

    public function __construct()
    {
        $this->baseUrl = config('mailin_ai.base_url');
        $this->email = config('mailin_ai.email');
        $this->password = config('mailin_ai.password');
        $this->deviceName = config('mailin_ai.device_name', 'project inbox');
        $this->timeout = config('mailin_ai.timeout', 30);
    }

    /**
     * Authenticate with Mailin.ai API and get access token
     * 
     * @return string|null Token if successful, null otherwise
     */
    public function authenticate()
    {
        try {
            // Check if we have a cached token
            $cacheKey = 'mailin_ai_token';
            $cachedToken = Cache::get($cacheKey);
            
            if ($cachedToken) {
                Log::channel('mailin-ai')->info('Using cached Mailin.ai token', [
                    'action' => 'authenticate',
                    'token_preview' => substr($cachedToken, 0, 20) . '...',
                ]);
                $this->token = $cachedToken;
                return $cachedToken;
            }

            // Validate configuration
            if (empty($this->baseUrl)) {
                Log::channel('mailin-ai')->error('Mailin.ai Base URL not configured', [
                    'action' => 'authenticate',
                    'error' => 'MAILIN_BASE_URL is not set in .env file',
                ]);
                return null;
            }

            if (empty($this->email) || empty($this->password)) {
                Log::channel('mailin-ai')->error('Mailin.ai Credentials not configured', [
                    'action' => 'authenticate',
                    'error' => 'MAILIN_EMAIL or MAILIN_PASSWORD is not set in .env file',
                ]);
                return null;
            }

            // Prepare login request
            $loginUrl = rtrim($this->baseUrl, '/') . '/auth/login';
            $requestBody = [
                'email' => $this->email,
                'password' => $this->password,
                'device_name' => $this->deviceName,
            ];

            Log::channel('mailin-ai')->info('Authenticating with Mailin.ai', [
                'action' => 'authenticate',
                'url' => $loginUrl,
                'email' => substr($this->email, 0, 3) . '***',
                'device_name' => $this->deviceName,
            ]);

            // Make API request
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($loginUrl, $requestBody);

            $statusCode = $response->status();
            $responseBody = $response->json();

            if ($response->successful() && isset($responseBody['token'])) {
                $token = $responseBody['token'];
                $expiresIn = $responseBody['expires_in'] ?? 3600; // Default to 1 hour if not provided

                // Cache the token (cache for slightly less time than expires_in to be safe)
                $cacheTime = $expiresIn > 0 ? $expiresIn - 60 : 3540; // Subtract 1 minute or default to 59 minutes
                Cache::put($cacheKey, $token, now()->addSeconds($cacheTime));

                $this->token = $token;

                Log::channel('mailin-ai')->info('Mailin.ai authentication successful', [
                    'action' => 'authenticate',
                    'status' => 'success',
                    'token_preview' => substr($token, 0, 20) . '...',
                    'expires_in' => $expiresIn,
                    'cached_for_seconds' => $cacheTime,
                ]);

                return $token;
            } else {
                $errorMessage = $responseBody['message'] ?? $responseBody['error'] ?? 'Unknown error';
                
                Log::channel('mailin-ai')->error('Mailin.ai authentication failed', [
                    'action' => 'authenticate',
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                    'response' => $responseBody,
                ]);

                return null;
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::channel('mailin-ai')->error('Mailin.ai authentication connection error', [
                'action' => 'authenticate',
                'error' => $e->getMessage(),
            ]);
            return null;

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mailin.ai authentication exception', [
                'action' => 'authenticate',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return null;
        }
    }

    /**
     * Get the current authentication token
     * Will authenticate if token is not available
     * 
     * @return string|null
     */
    public function getToken()
    {
        if ($this->token) {
            return $this->token;
        }

        return $this->authenticate();
    }

    /**
     * Clear cached token (useful for testing or forced re-authentication)
     */
    public function clearToken()
    {
        Cache::forget('mailin_ai_token');
        $this->token = null;
        
        Log::channel('mailin-ai')->info('Mailin.ai token cleared', [
            'action' => 'clear_token',
        ]);
    }

    /**
     * Make an authenticated API request
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint API endpoint (without base URL)
     * @param array $data Request data (for POST/PUT)
     * @return \Illuminate\Http\Client\Response
     */
    public function makeRequest($method, $endpoint, $data = [])
    {
        $token = $this->getToken();
        
        if (!$token) {
            throw new \Exception('Failed to authenticate with Mailin.ai API');
        }

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

        Log::channel('mailin-ai')->info('Making Mailin.ai API request', [
            'action' => 'make_request',
            'method' => $method,
            'url' => $url,
        ]);

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);

        // Handle different HTTP methods
        switch (strtoupper($method)) {
            case 'GET':
                $response = $response->get($url, $data);
                break;
            case 'POST':
                $response = $response->post($url, $data);
                break;
            case 'PUT':
                $response = $response->put($url, $data);
                break;
            case 'DELETE':
                $response = $response->delete($url, $data);
                break;
            default:
                throw new \Exception("Unsupported HTTP method: {$method}");
        }

        // If we get a 401, token might be expired, try to re-authenticate once
        if ($response->status() === 401) {
            Log::channel('mailin-ai')->warning('Mailin.ai API returned 401, re-authenticating', [
                'action' => 'make_request',
                'endpoint' => $endpoint,
            ]);

            $this->clearToken();
            $token = $this->authenticate();

            if ($token) {
                // Retry the request with new token
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ]);

                switch (strtoupper($method)) {
                    case 'GET':
                        $response = $response->get($url, $data);
                        break;
                    case 'POST':
                        $response = $response->post($url, $data);
                        break;
                    case 'PUT':
                        $response = $response->put($url, $data);
                        break;
                    case 'DELETE':
                        $response = $response->delete($url, $data);
                        break;
                }
            }
        }

        Log::channel('mailin-ai')->info('Mailin.ai API response received', [
            'action' => 'make_request',
            'method' => $method,
            'endpoint' => $endpoint,
            'status_code' => $response->status(),
        ]);

        return $response;
    }

    /**
     * Create mailboxes (async)
     * POST /mailboxes
     * 
     * @param array $mailboxes Array of mailbox data: [['username' => 'user@domain.com', 'name' => 'User', 'password' => 'pass123'], ...]
     * @return array Response with uuid if successful
     * @throws \Exception
     */
    public function createMailboxes(array $mailboxes)
    {
        try {
            Log::channel('mailin-ai')->info('Creating mailboxes via Mailin.ai API', [
                'action' => 'create_mailboxes',
                'mailbox_count' => count($mailboxes),
            ]);

            $response = $this->makeRequest(
                'POST',
                '/mailboxes',
                ['mailboxes' => $mailboxes]
            );

            $statusCode = $response->status();
            $responseBody = $response->json();

            // Expect 210 Accepted for async operations
            if ($statusCode === 210 || $response->successful()) {
                if (isset($responseBody['uuid'])) {
                    Log::channel('mailin-ai')->info('Mailin.ai mailbox creation request successful', [
                        'action' => 'create_mailboxes',
                        'job_uuid' => $responseBody['uuid'],
                        'mailbox_count' => count($mailboxes),
                    ]);

                    return [
                        'success' => true,
                        'uuid' => $responseBody['uuid'],
                        'message' => $responseBody['message'] ?? 'Mailbox creation job started',
                        'response' => $responseBody,
                    ];
                } else {
                    Log::channel('mailin-ai')->warning('Mailin.ai mailbox creation response missing UUID', [
                        'action' => 'create_mailboxes',
                        'status_code' => $statusCode,
                        'response' => $responseBody,
                    ]);

                    throw new \Exception('Mailin.ai mailbox creation response missing UUID');
                }
            } else {
                $errorMessage = $responseBody['message'] ?? $responseBody['error'] ?? 'Unknown error';
                
                // Check if error is about domain not being registered
                $domainNotRegistered = false;
                $unregisteredDomains = [];
                
                if (isset($responseBody['errors']) && is_array($responseBody['errors'])) {
                    foreach ($responseBody['errors'] as $field => $messages) {
                        if (is_array($messages)) {
                            foreach ($messages as $message) {
                                if (preg_match("/domain '([^']+)' is not registered/i", $message, $matches)) {
                                    $domainNotRegistered = true;
                                    $unregisteredDomains[] = $matches[1];
                                }
                            }
                        }
                    }
                }
                
                // Also check main error message
                if (preg_match("/domain '([^']+)' is not registered/i", $errorMessage, $matches)) {
                    $domainNotRegistered = true;
                    $unregisteredDomains[] = $matches[1];
                }
                
                Log::channel('mailin-ai')->error('Mailin.ai mailbox creation failed', [
                    'action' => 'create_mailboxes',
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                    'response' => $responseBody,
                    'domain_not_registered' => $domainNotRegistered,
                    'unregistered_domains' => $unregisteredDomains,
                ]);

                // If domain not registered, include domains in error message for easier parsing
                if ($domainNotRegistered) {
                    $domainsList = implode(', ', array_unique($unregisteredDomains));
                    throw new \Exception('Failed to create mailboxes via Mailin.ai: Domain not registered. Domains: ' . $domainsList . '. Error: ' . $errorMessage);
                }
                
                throw new \Exception('Failed to create mailboxes via Mailin.ai: ' . $errorMessage);
            }

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mailin.ai mailbox creation exception', [
                'action' => 'create_mailboxes',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Transfer domain to Mailin.ai
     * POST /domains/transfer
     * 
     * @param string $domainName The domain name to transfer
     * @return array Response with message and name_servers if successful
     * @throws \Exception
     */
    public function transferDomain(string $domainName)
    {
        try {
            Log::channel('mailin-ai')->info('Transferring domain via Mailin.ai API', [
                'action' => 'transfer_domain',
                'domain_name' => $domainName,
            ]);

            $response = $this->makeRequest(
                'POST',
                '/domains/transfer',
                ['domain_name' => $domainName]
            );

            $statusCode = $response->status();
            $responseBody = $response->json();

            if ($response->successful()) {
                Log::channel('mailin-ai')->info('Mailin.ai domain transfer request successful', [
                    'action' => 'transfer_domain',
                    'domain_name' => $domainName,
                    'status_code' => $statusCode,
                ]);

                return [
                    'success' => true,
                    'message' => $responseBody['message'] ?? 'Domain transfer process started',
                    'name_servers' => $responseBody['name_servers'] ?? [],
                    'response' => $responseBody,
                ];
            } else {
                $errorMessage = $responseBody['message'] ?? $responseBody['error'] ?? 'Unknown error';
                
                Log::channel('mailin-ai')->error('Mailin.ai domain transfer failed', [
                    'action' => 'transfer_domain',
                    'domain_name' => $domainName,
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                    'response' => $responseBody,
                ]);

                throw new \Exception('Failed to transfer domain via Mailin.ai: ' . $errorMessage);
            }

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mailin.ai domain transfer exception', [
                'action' => 'transfer_domain',
                'domain_name' => $domainName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get mailbox job status
     * GET /mailboxes/status/{job_id}
     * 
     * @param string $jobId The job UUID from createMailboxes response
     * @return array Job status information
     * @throws \Exception
     */
    public function getMailboxJobStatus(string $jobId)
    {
        try {
            Log::channel('mailin-ai')->info('Checking mailbox job status', [
                'action' => 'get_mailbox_job_status',
                'job_id' => $jobId,
            ]);

            $response = $this->makeRequest(
                'GET',
                '/mailboxes/status/' . $jobId,
                []
            );

            $statusCode = $response->status();
            $responseBody = $response->json();

            if ($response->successful()) {
                Log::channel('mailin-ai')->info('Mailbox job status retrieved', [
                    'action' => 'get_mailbox_job_status',
                    'job_id' => $jobId,
                    'status_code' => $statusCode,
                ]);

                return [
                    'success' => true,
                    'status' => $responseBody['status'] ?? 'unknown',
                    'data' => $responseBody,
                ];
            } else {
                $errorMessage = $responseBody['message'] ?? $responseBody['error'] ?? 'Unknown error';
                
                Log::channel('mailin-ai')->error('Failed to get mailbox job status', [
                    'action' => 'get_mailbox_job_status',
                    'job_id' => $jobId,
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                ]);

                throw new \Exception('Failed to get mailbox job status: ' . $errorMessage);
            }

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mailbox job status check exception', [
                'action' => 'get_mailbox_job_status',
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
