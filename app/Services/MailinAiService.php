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

    /**
     * Constructor - accepts optional credentials from split table
     * Falls back to config if not provided
     * 
     * @param array|null $credentials ['base_url' => string, 'email' => string, 'password' => string]
     */
    public function __construct(array $credentials = null)
    {
        // Use provided credentials from split table, or fallback to config
        if ($credentials && !empty($credentials['email']) && !empty($credentials['password'])) {
            $this->baseUrl = $credentials['base_url'] ?: config('mailin_ai.base_url');
            $this->email = $credentials['email'];
            $this->password = $credentials['password'];
            
            Log::channel('mailin-ai')->debug('MailinAiService initialized with split table credentials', [
                'action' => 'constructor',
                'has_base_url' => !empty($credentials['base_url']),
                'email_preview' => substr($this->email, 0, 3) . '***',
            ]);
        } else {
            // Fallback to existing config
            $this->baseUrl = config('mailin_ai.base_url');
            $this->email = config('mailin_ai.email');
            $this->password = config('mailin_ai.password');
            
            Log::channel('mailin-ai')->debug('MailinAiService initialized with config fallback', [
                'action' => 'constructor',
                'has_credentials' => !empty($credentials),
            ]);
        }
        
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
     * Make an authenticated API request with rate limit handling
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint API endpoint (without base URL)
     * @param array $data Request data (for POST/PUT)
     * @param int $maxRetries Maximum number of retries for rate limits (default: 3)
     * @return \Illuminate\Http\Client\Response
     * @throws \Exception
     */
    public function makeRequest($method, $endpoint, $data = [], $maxRetries = 3)
    {
        $token = $this->getToken();
        
        if (!$token) {
            throw new \Exception('Failed to authenticate with Mailin.ai API');
        }

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        $retryCount = 0;
        $baseDelay = 2; // Base delay in seconds for exponential backoff

        while ($retryCount <= $maxRetries) {
            Log::channel('mailin-ai')->info('Making Mailin.ai API request', [
                'action' => 'make_request',
                'method' => $method,
                'url' => $url,
                'retry_attempt' => $retryCount,
                'max_retries' => $maxRetries,
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

            $statusCode = $response->status();

            // If we get a 401, token might be expired, try to re-authenticate once
            if ($statusCode === 401) {
                Log::channel('mailin-ai')->warning('Mailin.ai API returned 401, re-authenticating', [
                    'action' => 'make_request',
                    'endpoint' => $endpoint,
                ]);

                $this->clearToken();
                $token = $this->authenticate();

                if ($token) {
                    // Retry the request with new token (don't count this as a retry)
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
                    $statusCode = $response->status();
                }
            }

            // Handle rate limiting (429 Too Many Requests)
            if ($statusCode === 429) {
                $responseBody = $response->json();
                $errorMessage = $responseBody['message'] ?? 'Too Many Attempts.';
                
                // Calculate exponential backoff delay: 2^retryCount * baseDelay seconds
                $delay = pow(2, $retryCount) * $baseDelay;
                
                // Cap delay at 60 seconds to avoid extremely long waits
                $delay = min($delay, 60);
                
                if ($retryCount < $maxRetries) {
                    Log::channel('mailin-ai')->warning('Mailin.ai API returned 429 (rate limit), retrying with exponential backoff', [
                        'action' => 'make_request',
                        'endpoint' => $endpoint,
                        'retry_attempt' => $retryCount + 1,
                        'max_retries' => $maxRetries,
                        'delay_seconds' => $delay,
                        'error_message' => $errorMessage,
                    ]);
                    
                    // Wait before retrying
                    sleep($delay);
                    $retryCount++;
                    continue; // Retry the request
                } else {
                    // Max retries reached, throw exception
                    Log::channel('mailin-ai')->error('Mailin.ai API rate limit exceeded after max retries', [
                        'action' => 'make_request',
                        'endpoint' => $endpoint,
                        'retry_attempts' => $retryCount,
                        'error_message' => $errorMessage,
                    ]);
                    
                    throw new \Exception('Mailin.ai API rate limit exceeded. ' . $errorMessage . ' Please try again later.');
                }
            }

            // If we get here, request was successful or non-rate-limit error
            Log::channel('mailin-ai')->info('Mailin.ai API response received', [
                'action' => 'make_request',
                'method' => $method,
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'retry_attempts' => $retryCount,
            ]);

            return $response;
        }

        // This should never be reached, but just in case
        throw new \Exception('Unexpected error in makeRequest');
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
            // Validate mailboxes array
            if (empty($mailboxes)) {
                throw new \Exception('Mailboxes array is empty');
            }

            // Validate each mailbox structure - username is required (domain is embedded in username)
            foreach ($mailboxes as $index => $mailbox) {
                if (!isset($mailbox['username'])) {
                    throw new \Exception("Mailbox at index {$index} is missing required field: username");
                }
            }

            Log::channel('mailin-ai')->info('Creating mailboxes via Mailin.ai API', [
                'action' => 'create_mailboxes',
                'mailbox_count' => count($mailboxes),
                'mailboxes' => array_map(function($mb) {
                    return [
                        'username' => $mb['username'] ?? 'missing',
                        'name' => $mb['name'] ?? 'missing',
                        'has_password' => isset($mb['password']),
                    ];
                }, $mailboxes),
            ]);

            $response = $this->makeRequest(
                'POST',
                '/mailboxes',
                ['mailboxes' => $mailboxes]
            );

            $statusCode = $response->status();
            $responseBody = $response->json();
            $rawBody = $response->body();

            // Log raw response for debugging
            Log::channel('mailin-ai')->debug('Mailin.ai mailbox creation raw response', [
                'action' => 'create_mailboxes',
                'status_code' => $statusCode,
                'raw_body' => $rawBody,
                'json_body' => $responseBody,
            ]);

            // Handle null response body (invalid JSON or empty response)
            if ($responseBody === null && !empty($rawBody)) {
                Log::channel('mailin-ai')->error('Mailin.ai mailbox creation returned invalid JSON', [
                    'action' => 'create_mailboxes',
                    'status_code' => $statusCode,
                    'raw_body' => $rawBody,
                ]);
                throw new \Exception('Mailin.ai API returned invalid JSON response. Status: ' . $statusCode . '. Body: ' . substr($rawBody, 0, 500));
            }

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
                        'raw_body' => $rawBody,
                    ]);

                    throw new \Exception('Mailin.ai mailbox creation response missing UUID. Status: ' . $statusCode . '. Response: ' . json_encode($responseBody));
                }
            } else {
                // Build comprehensive error message
                $errorMessage = 'Unknown error';
                if (is_array($responseBody)) {
                    // First try to get error from message or error fields
                    $errorMessage = $responseBody['message'] ?? $responseBody['error'] ?? $errorMessage;
                    
                    // Check for nested error messages in data
                    if (isset($responseBody['data']) && is_array($responseBody['data'])) {
                        if (isset($responseBody['data']['message'])) {
                            $errorMessage = $responseBody['data']['message'];
                        }
                    }
                    
                    // Extract error messages from errors array (Laravel validation format)
                    if (isset($responseBody['errors']) && is_array($responseBody['errors'])) {
                        $errorMessages = [];
                        foreach ($responseBody['errors'] as $field => $messages) {
                            if (is_array($messages)) {
                                foreach ($messages as $message) {
                                    $errorMessages[] = $message;
                                }
                            } elseif (is_string($messages)) {
                                $errorMessages[] = $messages;
                            }
                        }
                        if (!empty($errorMessages)) {
                            $errorMessage = implode('. ', $errorMessages);
                        }
                    }
                } elseif (!empty($rawBody)) {
                    $errorMessage = 'HTTP ' . $statusCode . ': ' . substr($rawBody, 0, 200);
                } else {
                    $errorMessage = 'HTTP ' . $statusCode . ' with empty response body';
                }
                
                // Check if error is about domain not being registered
                $domainNotRegistered = false;
                $unregisteredDomains = [];
                
                // Check if error is about mailboxes already being registered
                $mailboxesAlreadyExist = false;
                $existingMailboxEmails = [];
                
                if (isset($responseBody['errors']) && is_array($responseBody['errors'])) {
                    foreach ($responseBody['errors'] as $field => $messages) {
                        if (is_array($messages)) {
                            foreach ($messages as $message) {
                                if (preg_match("/domain '([^']+)' is not registered/i", $message, $matches)) {
                                    $domainNotRegistered = true;
                                    $unregisteredDomains[] = $matches[1];
                                }
                                // Check for "already registered" errors and extract mailbox email
                                if (preg_match("/mailbox '([^']+)' is already registered to your account/i", $message, $matches)) {
                                    $mailboxesAlreadyExist = true;
                                    $existingMailboxEmails[] = $matches[1];
                                } elseif (preg_match("/already registered to your account/i", $message)) {
                                    $mailboxesAlreadyExist = true;
                                }
                            }
                        } elseif (is_string($messages)) {
                            if (preg_match("/domain '([^']+)' is not registered/i", $messages, $matches)) {
                                $domainNotRegistered = true;
                                $unregisteredDomains[] = $matches[1];
                            }
                            // Check for "already registered" errors and extract mailbox email
                            if (preg_match("/mailbox '([^']+)' is already registered to your account/i", $messages, $matches)) {
                                $mailboxesAlreadyExist = true;
                                $existingMailboxEmails[] = $matches[1];
                            } elseif (preg_match("/already registered to your account/i", $messages)) {
                                $mailboxesAlreadyExist = true;
                            }
                        }
                    }
                }
                
                // Also check main error message
                if (preg_match("/domain '([^']+)' is not registered/i", $errorMessage, $matches)) {
                    $domainNotRegistered = true;
                    $unregisteredDomains[] = $matches[1];
                }
                
                // Check main error message for "already registered" and extract email
                if (preg_match("/mailbox '([^']+)' is already registered to your account/i", $errorMessage, $matches)) {
                    $mailboxesAlreadyExist = true;
                    $existingMailboxEmails[] = $matches[1];
                } elseif (preg_match("/already registered to your account/i", $errorMessage)) {
                    $mailboxesAlreadyExist = true;
                }
                
                // If all mailboxes already exist, treat as success (they're already on Mailin.ai)
                if ($mailboxesAlreadyExist && !$domainNotRegistered) {
                    Log::channel('mailin-ai')->info('Mailboxes already exist on Mailin.ai - treating as success', [
                        'action' => 'create_mailboxes',
                        'status_code' => $statusCode,
                        'mailbox_count' => count($mailboxes),
                        'existing_mailbox_emails' => $existingMailboxEmails,
                        'mailbox_usernames' => array_map(function($mb) {
                            return $mb['username'] ?? 'unknown';
                        }, $mailboxes),
                    ]);
                    
                    // Return success response without UUID (mailboxes already exist)
                    return [
                        'success' => true,
                        'uuid' => null, // No UUID since mailboxes already exist
                        'already_exists' => true, // Flag to indicate mailboxes already exist
                        'existing_mailbox_emails' => array_unique($existingMailboxEmails), // Specific mailboxes that already exist
                        'message' => 'Mailboxes already exist on Mailin.ai',
                        'response' => $responseBody,
                    ];
                }
                
                Log::channel('mailin-ai')->error('Mailin.ai mailbox creation failed', [
                    'action' => 'create_mailboxes',
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                    'response' => $responseBody,
                    'raw_body' => $rawBody,
                    'domain_not_registered' => $domainNotRegistered,
                    'unregistered_domains' => $unregisteredDomains,
                    'mailboxes_already_exist' => $mailboxesAlreadyExist,
                    'mailbox_count' => count($mailboxes),
                    'mailbox_usernames' => array_map(function($mb) {
                        return $mb['username'] ?? 'unknown';
                    }, $mailboxes),
                ]);

                // If domain not registered, include domains in error message for easier parsing
                if ($domainNotRegistered) {
                    $domainsList = implode(', ', array_unique($unregisteredDomains));
                    throw new \Exception('Failed to create mailboxes via Mailin.ai: Domain not registered. Domains: ' . $domainsList . '. Error: ' . $errorMessage);
                }
                
                throw new \Exception('Failed to create mailboxes via Mailin.ai: ' . $errorMessage);
            }

        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Handle HTTP client exceptions (network errors, timeouts, etc.)
            $errorMessage = 'Network error: ' . $e->getMessage();
            if ($e->response) {
                $errorMessage .= '. Status: ' . $e->response->status() . '. Body: ' . substr($e->response->body(), 0, 500);
            }
            
            Log::channel('mailin-ai')->error('Mailin.ai mailbox creation HTTP exception', [
                'action' => 'create_mailboxes',
                'error' => $errorMessage,
                'exception_type' => get_class($e),
                'response_status' => $e->response ? $e->response->status() : null,
                'response_body' => $e->response ? $e->response->body() : null,
            ]);

            throw new \Exception('Failed to create mailboxes via Mailin.ai: ' . $errorMessage, 0, $e);
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mailin.ai mailbox creation exception', [
                'action' => 'create_mailboxes',
                'error' => $e->getMessage(),
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Transfer domain to Mailin.ai with rate limit handling
     * POST /domains/transfer
     * 
     * @param string $domainName The domain name to transfer
     * @param int $maxRetries Maximum number of retries for rate limits (default: 3)
     * @return array Response with message and name_servers if successful
     * @throws \Exception
     */
    public function transferDomain(string $domainName, $maxRetries = 3)
    {
        try {
            Log::channel('mailin-ai')->info('Transferring domain via Mailin.ai API', [
                'action' => 'transfer_domain',
                'domain_name' => $domainName,
                'max_retries' => $maxRetries,
            ]);

            $response = $this->makeRequest(
                'POST',
                '/domains/transfer',
                ['domain_name' => $domainName],
                $maxRetries
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
                // Build comprehensive error message - check multiple sources
                $errorMessage = 'Unknown error';
                
                if (isset($responseBody['message'])) {
                    $errorMessage = $responseBody['message'];
                } elseif (isset($responseBody['error'])) {
                    $errorMessage = $responseBody['error'];
                }
                
                // Extract error messages from 'errors' array (Laravel validation format)
                if (isset($responseBody['errors']) && is_array($responseBody['errors'])) {
                    $errorMessages = [];
                    foreach ($responseBody['errors'] as $field => $messages) {
                        if (is_array($messages)) {
                            foreach ($messages as $message) {
                                $errorMessages[] = $message;
                            }
                        } elseif (is_string($messages)) {
                            $errorMessages[] = $messages;
                        }
                    }
                    if (!empty($errorMessages)) {
                        $errorMessage = implode('. ', $errorMessages);
                    }
                }
                
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
            // Check if it's a rate limit error
            $isRateLimitError = str_contains($e->getMessage(), 'rate limit') 
                || str_contains($e->getMessage(), 'Too Many Attempts')
                || str_contains($e->getMessage(), '429');
            
            Log::channel('mailin-ai')->error('Mailin.ai domain transfer exception', [
                'action' => 'transfer_domain',
                'domain_name' => $domainName,
                'error' => $e->getMessage(),
                'is_rate_limit' => $isRateLimitError,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw with a specific exception type for rate limits
            if ($isRateLimitError) {
                throw new \Exception('Rate limit exceeded while transferring domain: ' . $domainName . '. ' . $e->getMessage(), 429, $e);
            }
            
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

    /**
     * Delete a mailbox from Mailin.ai
     * DELETE /mailboxes/{mailbox_id}
     * 
     * @param int $mailboxId The Mailin.ai mailbox ID to delete
     * @return array Response with success status
     * @throws \Exception
     */
    public function deleteMailbox(int $mailboxId)
    {
        try {
            Log::channel('mailin-ai')->info('Deleting mailbox via Mailin.ai API', [
                'action' => 'delete_mailbox',
                'mailbox_id' => $mailboxId,
            ]);

            $response = $this->makeRequest(
                'DELETE',
                '/mailboxes/' . $mailboxId,
                []
            );

            $statusCode = $response->status();
            $responseBody = $response->json();

            if ($response->successful()) {
                Log::channel('mailin-ai')->info('Mailin.ai mailbox deleted successfully', [
                    'action' => 'delete_mailbox',
                    'mailbox_id' => $mailboxId,
                    'status_code' => $statusCode,
                ]);

                return [
                    'success' => true,
                    'message' => $responseBody['message'] ?? 'Mailbox deleted successfully',
                    'response' => $responseBody,
                ];
            } else {
                $errorMessage = $responseBody['message'] ?? $responseBody['error'] ?? 'Unknown error';
                
                Log::channel('mailin-ai')->error('Failed to delete mailbox from Mailin.ai', [
                    'action' => 'delete_mailbox',
                    'mailbox_id' => $mailboxId,
                    'status_code' => $statusCode,
                    'error' => $errorMessage,
                    'response' => $responseBody,
                ]);

                throw new \Exception('Failed to delete mailbox from Mailin.ai: ' . $errorMessage);
            }

        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Handle HTTP client exceptions (network errors, timeouts, etc.)
            $errorMessage = 'Network error: ' . $e->getMessage();
            if ($e->response) {
                $errorMessage .= '. Status: ' . $e->response->status() . '. Body: ' . substr($e->response->body(), 0, 500);
            }
            
            Log::channel('mailin-ai')->error('Mailin.ai mailbox deletion HTTP exception', [
                'action' => 'delete_mailbox',
                'mailbox_id' => $mailboxId,
                'error' => $errorMessage,
                'exception_type' => get_class($e),
                'response_status' => $e->response ? $e->response->status() : null,
                'response_body' => $e->response ? $e->response->body() : null,
            ]);

            throw new \Exception('Failed to delete mailbox from Mailin.ai: ' . $errorMessage, 0, $e);
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mailin.ai mailbox deletion exception', [
                'action' => 'delete_mailbox',
                'mailbox_id' => $mailboxId,
                'error' => $e->getMessage(),
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get mailboxes by domain from Mailin.ai API
     * GET /mailboxes?domain={domain_name}
     * 
     * @param string $domainName Domain name to fetch mailboxes for
     * @return array List of mailboxes with their IDs
     * @throws \Exception
     */
    public function getMailboxesByDomain(string $domainName)
    {
        try {
            Log::channel('mailin-ai')->info('Fetching mailboxes by domain from Mailin.ai API', [
                'action' => 'get_mailboxes_by_domain',
                'domain_name' => $domainName,
            ]);

            $response = $this->makeRequest(
                'GET',
                '/mailboxes',
                ['name' => $domainName]
            );

            $statusCode = $response->status();
            $responseBody = $response->json();

            if ($response->successful() && isset($responseBody['data']) && is_array($responseBody['data'])) {
                Log::channel('mailin-ai')->info('Mailboxes fetched successfully', [
                    'action' => 'get_mailboxes_by_domain',
                    'domain_name' => $domainName,
                    'mailbox_count' => count($responseBody['data']),
                ]);

                return [
                    'success' => true,
                    'mailboxes' => $responseBody['data'],
                ];
            } else {
                // If API doesn't return data array, try alternative response format
                if ($response->successful() && is_array($responseBody)) {
                    Log::channel('mailin-ai')->info('Mailboxes fetched successfully (alternative format)', [
                        'action' => 'get_mailboxes_by_domain',
                        'domain_name' => $domainName,
                        'mailbox_count' => count($responseBody),
                    ]);

                    return [
                        'success' => true,
                        'mailboxes' => $responseBody,
                    ];
                }

                Log::channel('mailin-ai')->warning('No mailboxes found or unexpected response format', [
                    'action' => 'get_mailboxes_by_domain',
                    'domain_name' => $domainName,
                    'status_code' => $statusCode,
                    'response' => $responseBody,
                ]);

                return [
                    'success' => false,
                    'mailboxes' => [],
                    'message' => 'No mailboxes found or unexpected response format',
                ];
            }
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $errorMessage = 'Network error';
            if ($e->response) {
                $errorMessage .= '. Status: ' . $e->response->status() . '. Body: ' . substr($e->response->body(), 0, 500);
            }

            Log::channel('mailin-ai')->error('Failed to fetch mailboxes by domain', [
                'action' => 'get_mailboxes_by_domain',
                'domain_name' => $domainName,
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'mailboxes' => [],
                'message' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Failed to fetch mailboxes by domain', [
                'action' => 'get_mailboxes_by_domain',
                'domain_name' => $domainName,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'mailboxes' => [],
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check domain status via Mailin.ai API
     * GET /public/domains?name={domain_name} (authenticated)
     * 
     * @param string $domainName Domain name to check
     * @return array Domain status information
     * @throws \Exception
     */
    public function checkDomainStatus(string $domainName)
    {
        try {
            Log::channel('mailin-ai')->info('Checking domain status via Mailin.ai API', [
                'action' => 'check_domain_status',
                'domain_name' => $domainName,
            ]);

            // Use makeRequest() for consistent authentication handling
            // Base URL is https://api.mailin.ai/api/v1/public
            // Endpoint /domains supports filtering by name parameter
            $response = $this->makeRequest(
                'GET',
                '/domains',
                ['name' => $domainName]
            );

            $statusCode = $response->status();
            $responseBody = $response->json();

            // The API returns paginated results, get the first domain if available
            $domainData = null;
            if ($response->successful()) {
                if (isset($responseBody['data']) && is_array($responseBody['data']) && count($responseBody['data']) > 0) {
                    // API returns filtered results in data array
                    $domainData = $responseBody['data'][0];
                } elseif (isset($responseBody['name']) && strtolower($responseBody['name']) === strtolower($domainName)) {
                    // Sometimes API returns single domain object directly
                    $domainData = $responseBody;
                }
            }

            if ($domainData) {
                // Convert status "1" to "active" for consistency
                $status = $domainData['status'] ?? null;
                if ($status === '1' || $status === 1) {
                    $status = 'active';
                }

                // Convert name_server_status "1" to "active"
                $nameServerStatus = $domainData['name_server_status'] ?? null;
                if ($nameServerStatus === '1' || $nameServerStatus === 1) {
                    $nameServerStatus = 'active';
                }

                // Handle name_servers (can be string or array)
                $nameServers = $domainData['name_servers'] ?? null;
                if (is_string($nameServers)) {
                    $nameServers = array_map('trim', explode(',', $nameServers));
                } elseif (!is_array($nameServers)) {
                    $nameServers = [];
                }

                Log::channel('mailin-ai')->info('Domain status retrieved successfully', [
                    'action' => 'check_domain_status',
                    'domain_name' => $domainName,
                    'status' => $status,
                    'name_server_status' => $nameServerStatus,
                    'name_servers' => $nameServers,
                ]);

                return [
                    'success' => true,
                    'domain_name' => $domainName,
                    'status' => $status,
                    'name_server_status' => $nameServerStatus,
                    'name_servers' => $nameServers,
                    'data' => $domainData,
                ];
            } else {
                // Domain not found in API response
                $errorMessage = $responseBody['message'] ?? $responseBody['error'] ?? 'Unknown error';
                
                // 404 means domain not found in Mailin.ai system yet (normal for newly transferred domains)
                if ($statusCode === 404) {
                    Log::channel('mailin-ai')->info('Domain not found in Mailin.ai system yet (may still be transferring)', [
                        'action' => 'check_domain_status',
                        'domain_name' => $domainName,
                        'status_code' => $statusCode,
                        'message' => 'Domain may still be in transfer process',
                    ]);

                    return [
                        'success' => false,
                        'domain_name' => $domainName,
                        'status' => null,
                        'name_server_status' => null,
                        'name_servers' => [],
                        'message' => 'Domain not found in Mailin.ai system yet',
                        'not_found' => true,
                    ];
                }
                
                Log::channel('mailin-ai')->warning('Domain not found in API response', [
                    'action' => 'check_domain_status',
                    'domain_name' => $domainName,
                    'status_code' => $statusCode,
                    'response' => $responseBody,
                ]);

                return [
                    'success' => false,
                    'message' => 'Domain not found',
                    'domain_name' => $domainName,
                    'not_found' => true,
                ];
            }

        } catch (\Exception $e) {
            // Check if it's a network/connection error
            $errorMessage = $e->getMessage();
            $isNetworkError = str_contains($errorMessage, 'Could not resolve host') 
                || str_contains($errorMessage, 'cURL error 6')
                || str_contains($errorMessage, 'Connection timed out')
                || str_contains($errorMessage, 'Network is unreachable');
            
            if ($isNetworkError) {
                // Network/DNS errors - log but don't fail completely
                Log::channel('mailin-ai')->warning('Network error checking domain status (will retry later)', [
                    'action' => 'check_domain_status',
                    'domain_name' => $domainName,
                    'error' => $errorMessage,
                ]);
                
                return [
                    'success' => false,
                    'domain_name' => $domainName,
                    'status' => null,
                    'name_server_status' => null,
                    'name_servers' => [],
                    'message' => 'Network error - will retry later',
                    'network_error' => true,
                ];
            }
            
            // For other exceptions, log and throw
            Log::channel('mailin-ai')->error('Domain status check exception', [
                'action' => 'check_domain_status',
                'domain_name' => $domainName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get mailboxes by email/name from Mailin.ai API
     * GET /mailboxes?name={email}&per_page=10
     * 
     * @param string $email Email address to search for (e.g., "john.doe@example.com")
     * @param int $perPage Number of results per page (default: 10)
     * @return array List of mailboxes with their IDs
     * @throws \Exception
     */
    public function getMailboxesByName(string $email, int $perPage = 10)
    {
        try {
            Log::channel('mailin-ai')->info('Fetching mailboxes by email/name from Mailin.ai API', [
                'action' => 'get_mailboxes_by_name',
                'email' => $email,
                'per_page' => $perPage,
            ]);

            $response = $this->makeRequest(
                'GET',
                '/mailboxes',
                [
                    'name' => $email,
                    'per_page' => $perPage,
                ]
            );

            $statusCode = $response->status();
            $responseBody = $response->json();

            if ($response->successful() && isset($responseBody['data']) && is_array($responseBody['data'])) {
                Log::channel('mailin-ai')->info('Mailboxes fetched successfully by email/name', [
                    'action' => 'get_mailboxes_by_name',
                    'email' => $email,
                    'mailbox_count' => count($responseBody['data']),
                ]);

                return [
                    'success' => true,
                    'mailboxes' => $responseBody['data'],
                    'total' => $responseBody['total'] ?? count($responseBody['data']),
                    'current_page' => $responseBody['current_page'] ?? 1,
                ];
            } else {
                // If API doesn't return data array, try alternative response format
                if ($response->successful() && is_array($responseBody)) {
                    Log::channel('mailin-ai')->info('Mailboxes fetched successfully by email/name (alternative format)', [
                        'action' => 'get_mailboxes_by_name',
                        'email' => $email,
                        'mailbox_count' => count($responseBody),
                    ]);

                    return [
                        'success' => true,
                        'mailboxes' => $responseBody,
                        'total' => count($responseBody),
                        'current_page' => 1,
                    ];
                }

                Log::channel('mailin-ai')->warning('No mailboxes found or unexpected response format', [
                    'action' => 'get_mailboxes_by_name',
                    'email' => $email,
                    'status_code' => $statusCode,
                    'response' => $responseBody,
                ]);

                return [
                    'success' => false,
                    'mailboxes' => [],
                    'message' => 'No mailboxes found or unexpected response format',
                ];
            }
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $errorMessage = 'Network error';
            if ($e->response) {
                $errorMessage .= '. Status: ' . $e->response->status() . '. Body: ' . substr($e->response->body(), 0, 500);
            }

            Log::channel('mailin-ai')->error('Failed to fetch mailboxes by email/name', [
                'action' => 'get_mailboxes_by_name',
                'email' => $email,
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'mailboxes' => [],
                'message' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Failed to fetch mailboxes by email/name', [
                'action' => 'get_mailboxes_by_name',
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'mailboxes' => [],
                'message' => $e->getMessage(),
            ];
        }
    }
}

