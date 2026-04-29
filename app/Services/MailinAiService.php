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

  public function makeRequest($method, $endpoint, $data = [], $maxRetries = null)
{
    $token = $this->getToken();

    if (!$token) {
        throw new \Exception('Failed to authenticate with Mailin.ai API');
    }

    if ($maxRetries === null) {
        $maxRetries = (int) config('mailin_ai.rate_limit_max_retries', 6);
    }

    $baseDelay = (int) config('mailin_ai.rate_limit_base_delay', 15);
    $delayCap = (int) config('mailin_ai.rate_limit_delay_cap', 180);
    $requestThrottleMs = (int) config('mailin_ai.request_throttle_ms', 2500);

    $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
    $method = strtoupper($method);
    $retryCount = 0;
    $tokenRefreshed = false;

    while ($retryCount <= $maxRetries) {
        try {
            Log::channel('mailin-ai')->info('Making Mailin.ai API request', [
                'action' => 'make_request',
                'method' => $method,
                'url' => $url,
                'endpoint' => $endpoint,
                'retry_attempt' => $retryCount,
                'max_retries' => $maxRetries,
                'request_throttle_ms' => $requestThrottleMs,
            ]);

            // Proactive throttling BEFORE request
            if ($requestThrottleMs > 0) {
                usleep($requestThrottleMs * 1000);
            }

            $client = Http::timeout($this->timeout)
                ->connectTimeout(15)
                ->withToken($token)
                ->acceptJson()
                ->asJson();

            switch ($method) {
                case 'GET':
                    $response = $client->get($url, $data);
                    break;
                case 'POST':
                    $response = $client->post($url, $data);
                    break;
                case 'PUT':
                    $response = $client->put($url, $data);
                    break;
                case 'DELETE':
                    $response = $client->delete($url, $data);
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
            }

            $statusCode = $response->status();

            if ($statusCode === 401) {
                Log::channel('mailin-ai')->warning('Mailin.ai API returned 401, re-authenticating', [
                    'action' => 'make_request',
                    'endpoint' => $endpoint,
                ]);

                if ($tokenRefreshed) {
                    throw new \Exception('Mailin.ai API authentication failed after token refresh.');
                }

                $this->clearToken();
                $token = $this->authenticate();

                if (!$token) {
                    throw new \Exception('Failed to re-authenticate with Mailin.ai API');
                }

                $tokenRefreshed = true;
                continue;
            }

            if ($statusCode === 429) {
                $responseBody = $response->json();
                $errorMessage = $responseBody['message'] ?? $responseBody['error'] ?? 'Too Many Attempts.';
                $retryAfterHeader = $response->header('Retry-After');

                $delay = is_numeric($retryAfterHeader)
                    ? (int) $retryAfterHeader
                    : min($baseDelay * (int) pow(2, $retryCount), $delayCap);

                if ($retryCount < $maxRetries) {
                    Log::channel('mailin-ai')->warning('Mailin.ai API returned 429 (rate limit), retrying', [
                        'action' => 'make_request',
                        'endpoint' => $endpoint,
                        'retry_attempt' => $retryCount + 1,
                        'max_retries' => $maxRetries,
                        'delay_seconds' => $delay,
                        'retry_after_header' => $retryAfterHeader,
                        'error_message' => $errorMessage,
                    ]);

                    sleep($delay);
                    $retryCount++;
                    continue;
                }

                Log::channel('mailin-ai')->error('Mailin.ai API rate limit exceeded after max retries', [
                    'action' => 'make_request',
                    'endpoint' => $endpoint,
                    'retry_attempts' => $retryCount,
                    'error_message' => $errorMessage,
                ]);

                throw new \Exception('Mailin.ai API rate limit exceeded. ' . $errorMessage . ' Please try again later.');
            }

            Log::channel('mailin-ai')->info('Mailin.ai API response received', [
                'action' => 'make_request',
                'method' => $method,
                'endpoint' => $endpoint,
                'status_code' => $statusCode,
                'retry_attempts' => $retryCount,
            ]);

            return $response;
        } catch (\Throwable $e) {
            if ($retryCount < $maxRetries) {
                $delay = min($baseDelay * (int) pow(2, $retryCount), $delayCap);

                Log::channel('mailin-ai')->warning('Mailin.ai request exception, retrying', [
                    'action' => 'make_request',
                    'endpoint' => $endpoint,
                    'retry_attempt' => $retryCount + 1,
                    'max_retries' => $maxRetries,
                    'delay_seconds' => $delay,
                    'exception_message' => $e->getMessage(),
                ]);

                sleep($delay);
                $retryCount++;
                continue;
            }

            Log::channel('mailin-ai')->error('Mailin.ai request failed after max retries', [
                'action' => 'make_request',
                'endpoint' => $endpoint,
                'retry_attempts' => $retryCount,
                'exception_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

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
            if (empty($mailboxes)) {
                throw new \Exception('Mailboxes array is empty');
            }
    
            // Normalize + validate input
            $normalizedMailboxes = [];
            foreach ($mailboxes as $index => $mailbox) {
                if (!is_array($mailbox)) {
                    throw new \Exception("Mailbox at index {$index} must be an array");
                }
    
                $username = trim((string) ($mailbox['username'] ?? ''));
                if ($username === '') {
                    throw new \Exception("Mailbox at index {$index} is missing required field: username");
                }
    
                $normalizedMailboxes[] = [
                    'username' => $username,
                    'name'     => isset($mailbox['name']) ? trim((string) $mailbox['name']) : $username,
                    'password' => $mailbox['password'] ?? null,
                ];
            }
    
            Log::channel('mailin-ai')->info('Creating mailboxes via Mailin.ai API', [
                'action' => 'create_mailboxes',
                'mailbox_count' => count($normalizedMailboxes),
                'mailboxes' => array_map(function ($mb) {
                    return [
                        'username' => $mb['username'],
                        'name' => $mb['name'],
                        'has_password' => !empty($mb['password']),
                    ];
                }, $normalizedMailboxes),
            ]);
    
            // Chunk to reduce risk of 429 on large bulk requests
            $chunkSize = 10; // adjust smaller if API is strict
            $delayBetweenChunksSeconds = 3;
    
            $chunks = array_chunk($normalizedMailboxes, $chunkSize);
            $jobUuids = [];
            $alreadyExistsEmails = [];
            $unregisteredDomains = [];
            $allResponses = [];
    
            foreach ($chunks as $chunkIndex => $chunk) {
                Log::channel('mailin-ai')->info('Sending mailbox creation chunk', [
                    'action' => 'create_mailboxes',
                    'chunk_index' => $chunkIndex + 1,
                    'total_chunks' => count($chunks),
                    'chunk_size' => count($chunk),
                ]);
    
                $response = $this->makeRequest(
                    'POST',
                    '/mailboxes',
                    ['mailboxes' => $chunk]
                );
    
                $statusCode = $response->status();
                $responseBody = $response->json();
                $rawBody = $response->body();
    
                Log::channel('mailin-ai')->debug('Mailin.ai mailbox creation raw response', [
                    'action' => 'create_mailboxes',
                    'chunk_index' => $chunkIndex + 1,
                    'status_code' => $statusCode,
                    'raw_body' => $rawBody,
                    'json_body' => $responseBody,
                ]);
    
                if ($responseBody === null && !empty($rawBody)) {
                    Log::channel('mailin-ai')->error('Mailin.ai mailbox creation returned invalid JSON', [
                        'action' => 'create_mailboxes',
                        'chunk_index' => $chunkIndex + 1,
                        'status_code' => $statusCode,
                        'raw_body' => $rawBody,
                    ]);
    
                    throw new \Exception(
                        'Mailin.ai API returned invalid JSON response. Status: ' .
                        $statusCode . '. Body: ' . substr($rawBody, 0, 500)
                    );
                }
    
                // Success / accepted
                if ($statusCode === 210 || $response->successful()) {
                    if (isset($responseBody['uuid'])) {
                        $jobUuids[] = $responseBody['uuid'];
    
                        Log::channel('mailin-ai')->info('Mailin.ai mailbox creation request successful', [
                            'action' => 'create_mailboxes',
                            'chunk_index' => $chunkIndex + 1,
                            'job_uuid' => $responseBody['uuid'],
                            'mailbox_count' => count($chunk),
                        ]);
    
                        $allResponses[] = $responseBody;
                    } else {
                        Log::channel('mailin-ai')->warning('Mailin.ai mailbox creation response missing UUID', [
                            'action' => 'create_mailboxes',
                            'chunk_index' => $chunkIndex + 1,
                            'status_code' => $statusCode,
                            'response' => $responseBody,
                            'raw_body' => $rawBody,
                        ]);
    
                        throw new \Exception(
                            'Mailin.ai mailbox creation response missing UUID. Status: ' .
                            $statusCode . '. Response: ' . json_encode($responseBody)
                        );
                    }
                } else {
                    // Build detailed error message
                    $errorMessage = 'Unknown error';
    
                    if (is_array($responseBody)) {
                        $errorMessage = $responseBody['message'] ?? $responseBody['error'] ?? $errorMessage;
    
                        if (isset($responseBody['data']) && is_array($responseBody['data']) && isset($responseBody['data']['message'])) {
                            $errorMessage = $responseBody['data']['message'];
                        }
    
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
    
                    $domainNotRegistered = false;
                    $mailboxesAlreadyExist = false;
    
                    if (isset($responseBody['errors']) && is_array($responseBody['errors'])) {
                        foreach ($responseBody['errors'] as $field => $messages) {
                            $messages = is_array($messages) ? $messages : [$messages];
    
                            foreach ($messages as $message) {
                                if (preg_match("/domain '([^']+)' is not registered/i", $message, $matches)) {
                                    $domainNotRegistered = true;
                                    $unregisteredDomains[] = $matches[1];
                                }
    
                                if (preg_match("/mailbox '([^']+)' is already registered to your account/i", $message, $matches)) {
                                    $mailboxesAlreadyExist = true;
                                    $alreadyExistsEmails[] = $matches[1];
                                } elseif (preg_match("/already registered to your account/i", $message)) {
                                    $mailboxesAlreadyExist = true;
                                }
                            }
                        }
                    }
    
                    if (preg_match("/domain '([^']+)' is not registered/i", $errorMessage, $matches)) {
                        $domainNotRegistered = true;
                        $unregisteredDomains[] = $matches[1];
                    }
    
                    if (preg_match("/mailbox '([^']+)' is already registered to your account/i", $errorMessage, $matches)) {
                        $mailboxesAlreadyExist = true;
                        $alreadyExistsEmails[] = $matches[1];
                    } elseif (preg_match("/already registered to your account/i", $errorMessage)) {
                        $mailboxesAlreadyExist = true;
                    }
    
                    if ($mailboxesAlreadyExist && !$domainNotRegistered) {
                        Log::channel('mailin-ai')->info('Mailboxes already exist on Mailin.ai - treating as success', [
                            'action' => 'create_mailboxes',
                            'chunk_index' => $chunkIndex + 1,
                            'status_code' => $statusCode,
                            'existing_mailbox_emails' => $alreadyExistsEmails,
                        ]);
    
                        $allResponses[] = $responseBody;
                    } elseif ($domainNotRegistered) {
                        Log::channel('mailin-ai')->warning('Mailin.ai createMailboxes: Domain not registered', [
                            'action' => 'create_mailboxes',
                            'chunk_index' => $chunkIndex + 1,
                            'unregistered_domains' => $unregisteredDomains,
                            'order_update_required' => true,
                        ]);
    
                        return [
                            'success' => false,
                            'domain_not_registered' => true,
                            'unregistered_domains' => array_values(array_unique($unregisteredDomains)),
                            'error' => $errorMessage,
                            'response' => $responseBody,
                        ];
                    } else {
                        Log::channel('mailin-ai')->error('Mailin.ai mailbox creation failed', [
                            'action' => 'create_mailboxes',
                            'chunk_index' => $chunkIndex + 1,
                            'status_code' => $statusCode,
                            'error' => $errorMessage,
                            'response' => $responseBody,
                            'raw_body' => $rawBody,
                        ]);
    
                        throw new \Exception('Failed to create mailboxes via Mailin.ai: ' . $errorMessage);
                    }
                }
    
                // Delay between chunks to further reduce 429 risk
                if (count($chunks) > 1 && $chunkIndex < count($chunks) - 1) {
                    sleep($delayBetweenChunksSeconds);
                }
            }
    
            // Final combined success response
            return [
                'success' => true,
                'uuid' => $jobUuids[0] ?? null,
                'uuids' => $jobUuids,
                'already_exists' => !empty($alreadyExistsEmails),
                'existing_mailbox_emails' => array_values(array_unique($alreadyExistsEmails)),
                'message' => !empty($jobUuids)
                    ? 'Mailbox creation job(s) started successfully'
                    : 'Mailboxes already exist on Mailin.ai',
                'response' => $allResponses,
            ];
    
        } catch (\Illuminate\Http\Client\RequestException $e) {
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
        } catch (\Throwable $e) {
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
            $domainName = strtolower(trim($domainName));
    
            if ($domainName === '') {
                throw new \InvalidArgumentException('Domain name is required.');
            }
    
            Log::channel('mailin-ai')->info('Transferring domain via Mailin.ai API', [
                'action' => 'transfer_domain',
                'domain_name' => $domainName,
                'max_retries' => $maxRetries,
            ]);
    
            // 429 / retry / throttling should be handled inside makeRequest()
            $response = $this->makeRequest(
                'POST',
                '/domains/transfer',
                ['domain_name' => $domainName],
                $maxRetries
            );
    
            $statusCode = $response->status();
            $responseBody = $response->json();
            $rawBody = $response->body();
    
            Log::channel('mailin-ai')->debug('Mailin.ai domain transfer raw response', [
                'action' => 'transfer_domain',
                'domain_name' => $domainName,
                'status_code' => $statusCode,
                'raw_body' => $rawBody,
                'json_body' => $responseBody,
            ]);
    
            if ($responseBody === null && !empty($rawBody)) {
                throw new \Exception(
                    'Mailin.ai API returned invalid JSON response. Status: ' .
                    $statusCode . '. Body: ' . substr($rawBody, 0, 500)
                );
            }
    
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
            }
    
            // Build comprehensive error message
            $errorMessage = 'Unknown error';
    
            if (is_array($responseBody)) {
                $errorMessage = $responseBody['message'] ?? $responseBody['error'] ?? $errorMessage;
    
                if (isset($responseBody['errors']) && is_array($responseBody['errors'])) {
                    $errorMessages = [];
    
                    foreach ($responseBody['errors'] as $field => $messages) {
                        $messages = is_array($messages) ? $messages : [$messages];
    
                        foreach ($messages as $message) {
                            if (is_string($message) && trim($message) !== '') {
                                $errorMessages[] = trim($message);
                            }
                        }
                    }
    
                    if (!empty($errorMessages)) {
                        $errorMessage = implode('. ', $errorMessages);
                    }
                }
            } elseif (!empty($rawBody)) {
                $errorMessage = 'HTTP ' . $statusCode . ': ' . substr($rawBody, 0, 300);
            } else {
                $errorMessage = 'HTTP ' . $statusCode . ' with empty response body';
            }
    
            $errorMessageLower = strtolower($errorMessage);
    
            // Treat already-existing domain as success
            $domainAlreadyExists = str_contains($errorMessageLower, 'domain already exists in your account')
                || str_contains($errorMessageLower, 'already exists in your account')
                || str_contains($errorMessageLower, 'domain is already registered')
                || str_contains($errorMessageLower, 'already registered');
    
            if ($domainAlreadyExists) {
                Log::channel('mailin-ai')->info('Domain already exists in Mailin.ai account - treating as success', [
                    'action' => 'transfer_domain',
                    'domain_name' => $domainName,
                    'status_code' => $statusCode,
                    'message' => $errorMessage,
                ]);
    
                return [
                    'success' => true,
                    'already_exists' => true,
                    'message' => 'Domain already exists in your account',
                    'name_servers' => $responseBody['name_servers'] ?? [],
                    'response' => $responseBody,
                ];
            }
    
            Log::channel('mailin-ai')->error('Mailin.ai domain transfer failed', [
                'action' => 'transfer_domain',
                'domain_name' => $domainName,
                'status_code' => $statusCode,
                'error' => $errorMessage,
                'response' => $responseBody,
                'raw_body' => $rawBody,
            ]);
    
            throw new \Exception('Failed to transfer domain via Mailin.ai: ' . $errorMessage);
    
        } catch (\Throwable $e) {
            $isRateLimitError = str_contains(strtolower($e->getMessage()), 'rate limit')
                || str_contains(strtolower($e->getMessage()), 'too many attempts')
                || str_contains($e->getMessage(), '429');
    
            Log::channel('mailin-ai')->error('Mailin.ai domain transfer exception', [
                'action' => 'transfer_domain',
                'domain_name' => $domainName ?? null,
                'error' => $e->getMessage(),
                'is_rate_limit' => $isRateLimitError,
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
    
            if ($isRateLimitError) {
                throw new \Exception(
                    'Rate limit exceeded while transferring domain: ' . ($domainName ?? 'unknown') . '. ' . $e->getMessage(),
                    429,
                    $e
                );
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
            $jobId = trim($jobId);
    
            if ($jobId === '') {
                throw new \InvalidArgumentException('Job ID is required.');
            }
    
            Log::channel('mailin-ai')->info('Checking mailbox job status', [
                'action' => 'get_mailbox_job_status',
                'job_id' => $jobId,
            ]);
    
            // 429 / retry / throttling is handled inside makeRequest()
            $response = $this->makeRequest(
                'GET',
                '/mailboxes/status/' . $jobId,
                []
            );
    
            $statusCode = $response->status();
            $responseBody = $response->json();
            $rawBody = $response->body();
    
            Log::channel('mailin-ai')->debug('Mailbox job status raw response', [
                'action' => 'get_mailbox_job_status',
                'job_id' => $jobId,
                'status_code' => $statusCode,
                'raw_body' => $rawBody,
                'json_body' => $responseBody,
            ]);
    
            // Handle invalid JSON response
            if ($responseBody === null && !empty($rawBody)) {
                throw new \Exception(
                    'Mailin.ai API returned invalid JSON response. Status: ' .
                    $statusCode . '. Body: ' . substr($rawBody, 0, 500)
                );
            }
    
            if ($response->successful()) {
                Log::channel('mailin-ai')->info('Mailbox job status retrieved', [
                    'action' => 'get_mailbox_job_status',
                    'job_id' => $jobId,
                    'status_code' => $statusCode,
                    'job_status' => $responseBody['status'] ?? 'unknown',
                ]);
    
                return [
                    'success' => true,
                    'status' => $responseBody['status'] ?? 'unknown',
                    'message' => $responseBody['message'] ?? null,
                    'data' => $responseBody,
                ];
            }
    
            $errorMessage = 'Unknown error';
    
            if (is_array($responseBody)) {
                $errorMessage = $responseBody['message'] ?? $responseBody['error'] ?? $errorMessage;
    
                if (isset($responseBody['errors']) && is_array($responseBody['errors'])) {
                    $errorMessages = [];
    
                    foreach ($responseBody['errors'] as $field => $messages) {
                        $messages = is_array($messages) ? $messages : [$messages];
    
                        foreach ($messages as $message) {
                            if (is_string($message) && trim($message) !== '') {
                                $errorMessages[] = trim($message);
                            }
                        }
                    }
    
                    if (!empty($errorMessages)) {
                        $errorMessage = implode('. ', $errorMessages);
                    }
                }
            } elseif (!empty($rawBody)) {
                $errorMessage = 'HTTP ' . $statusCode . ': ' . substr($rawBody, 0, 300);
            } else {
                $errorMessage = 'HTTP ' . $statusCode . ' with empty response body';
            }
    
            Log::channel('mailin-ai')->error('Failed to get mailbox job status', [
                'action' => 'get_mailbox_job_status',
                'job_id' => $jobId,
                'status_code' => $statusCode,
                'error' => $errorMessage,
                'response' => $responseBody,
                'raw_body' => $rawBody,
            ]);
    
            throw new \Exception('Failed to get mailbox job status: ' . $errorMessage);
    
        } catch (\Throwable $e) {
            $isRateLimitError = str_contains(strtolower($e->getMessage()), 'rate limit')
                || str_contains(strtolower($e->getMessage()), 'too many attempts')
                || str_contains($e->getMessage(), '429');
    
            Log::channel('mailin-ai')->error('Mailbox job status check exception', [
                'action' => 'get_mailbox_job_status',
                'job_id' => $jobId ?? null,
                'error' => $e->getMessage(),
                'is_rate_limit' => $isRateLimitError,
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
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

                // Check if mailbox doesn't exist (404 or 500 with "not found" message)
                $isNotFound = $statusCode === 404
                    || str_contains(strtolower($errorMessage), 'not found')
                    || str_contains(strtolower($errorMessage), 'no query results')
                    || str_contains(strtolower($errorMessage), 'does not exist');

                if ($isNotFound) {
                    // Mailbox doesn't exist - treat as already deleted
                    Log::channel('mailin-ai')->info('Mailin.ai mailbox not found (already deleted or never existed)', [
                        'action' => 'delete_mailbox',
                        'mailbox_id' => $mailboxId,
                        'status_code' => $statusCode,
                        'error' => $errorMessage,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Mailbox not found (already deleted)',
                        'not_found' => true,
                        'response' => $responseBody,
                    ];
                }

                // Actual error - log and throw
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
            $statusCode = null;
            $responseBody = null;

            if ($e->response) {
                $statusCode = $e->response->status();
                $responseBody = $e->response->json();
                $errorMessage .= '. Status: ' . $statusCode . '. Body: ' . substr($e->response->body(), 0, 500);

                // Check if mailbox doesn't exist (404 or 500 with "not found" message)
                $apiErrorMessage = $responseBody['message'] ?? $responseBody['error'] ?? '';
                $isNotFound = $statusCode === 404
                    || str_contains(strtolower($apiErrorMessage), 'not found')
                    || str_contains(strtolower($apiErrorMessage), 'no query results')
                    || str_contains(strtolower($apiErrorMessage), 'does not exist');

                if ($isNotFound) {
                    // Mailbox doesn't exist - treat as already deleted
                    Log::channel('mailin-ai')->info('Mailin.ai mailbox not found (already deleted or never existed) - HTTP exception', [
                        'action' => 'delete_mailbox',
                        'mailbox_id' => $mailboxId,
                        'status_code' => $statusCode,
                        'error' => $apiErrorMessage,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Mailbox not found (already deleted)',
                        'not_found' => true,
                        'response' => $responseBody,
                    ];
                }
            }

            Log::channel('mailin-ai')->error('Mailin.ai mailbox deletion HTTP exception', [
                'action' => 'delete_mailbox',
                'mailbox_id' => $mailboxId,
                'error' => $errorMessage,
                'exception_type' => get_class($e),
                'response_status' => $statusCode,
                'response_body' => $responseBody,
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
                // Filter mailboxes to only include those that exactly match the domain
                // API 'name' param does text search, may return partial matches
                $filteredMailboxes = [];
                foreach ($responseBody['data'] as $mb) {
                    $email = $mb['email'] ?? $mb['username'] ?? '';
                    // Check if email ends with @domain (exact domain match)
                    if (str_ends_with(strtolower($email), '@' . strtolower($domainName))) {
                        $filteredMailboxes[] = $mb;
                    }
                }

                Log::channel('mailin-ai')->info('Mailboxes fetched successfully', [
                    'action' => 'get_mailboxes_by_domain',
                    'domain_name' => $domainName,
                    'api_mailbox_count' => count($responseBody['data']),
                    'filtered_mailbox_count' => count($filteredMailboxes),
                ]);

                return [
                    'success' => true,
                    'mailboxes' => $filteredMailboxes,
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
            $email = strtolower(trim($email));
            $perPage = max(1, min($perPage, 10)); // keep search small to reduce API load
    
            if ($email === '') {
                throw new \InvalidArgumentException('Email is required.');
            }
    
            Log::channel('mailin-ai')->info('Fetching mailboxes by email/name from Mailin.ai API', [
                'action' => 'get_mailboxes_by_name',
                'email' => $email,
                'per_page' => $perPage,
            ]);
    
            // 429 / retry / throttling should be handled inside makeRequest()
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
            $rawBody = $response->body();
    
            Log::channel('mailin-ai')->debug('Mailin.ai getMailboxesByName raw response', [
                'action' => 'get_mailboxes_by_name',
                'email' => $email,
                'status_code' => $statusCode,
                'raw_body' => $rawBody,
                'json_body' => $responseBody,
            ]);
    
            // Invalid JSON / empty body handling
            if ($responseBody === null && !empty($rawBody)) {
                Log::channel('mailin-ai')->error('Mailin.ai getMailboxesByName returned invalid JSON', [
                    'action' => 'get_mailboxes_by_name',
                    'email' => $email,
                    'status_code' => $statusCode,
                    'raw_body' => $rawBody,
                ]);
    
                return [
                    'success' => false,
                    'mailboxes' => [],
                    'message' => 'Invalid JSON response from Mailin.ai',
                ];
            }
    
            if ($response->successful() && isset($responseBody['data']) && is_array($responseBody['data'])) {
                // Prefer exact matches first to reduce bad assumptions
                $mailboxes = $responseBody['data'];
    
                usort($mailboxes, function ($a, $b) use ($email) {
                    $aName = strtolower($a['name'] ?? $a['email'] ?? $a['username'] ?? '');
                    $bName = strtolower($b['name'] ?? $b['email'] ?? $b['username'] ?? '');
    
                    $aExact = $aName === $email ? 0 : 1;
                    $bExact = $bName === $email ? 0 : 1;
    
                    return $aExact <=> $bExact;
                });
    
                Log::channel('mailin-ai')->info('Mailboxes fetched successfully by email/name', [
                    'action' => 'get_mailboxes_by_name',
                    'email' => $email,
                    'mailbox_count' => count($mailboxes),
                    'total' => $responseBody['total'] ?? count($mailboxes),
                ]);
    
                return [
                    'success' => true,
                    'mailboxes' => $mailboxes,
                    'total' => $responseBody['total'] ?? count($mailboxes),
                    'current_page' => $responseBody['current_page'] ?? 1,
                    'message' => $responseBody['message'] ?? null,
                ];
            }
    
            // Alternative API response shape
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
                    'message' => null,
                ];
            }
    
            $errorMessage = 'No mailboxes found or unexpected response format';
    
            if (is_array($responseBody)) {
                $errorMessage = $responseBody['message'] ?? $responseBody['error'] ?? $errorMessage;
            } elseif (!empty($rawBody)) {
                $errorMessage = 'HTTP ' . $statusCode . ': ' . substr($rawBody, 0, 300);
            }
    
            Log::channel('mailin-ai')->warning('No mailboxes found or unexpected response format', [
                'action' => 'get_mailboxes_by_name',
                'email' => $email,
                'status_code' => $statusCode,
                'response' => $responseBody,
                'raw_body' => $rawBody,
            ]);
    
            return [
                'success' => false,
                'mailboxes' => [],
                'message' => $errorMessage,
            ];
    
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $errorMessage = $e->getMessage();
            $isTimeout = str_contains(strtolower($errorMessage), 'timeout')
                || str_contains(strtolower($errorMessage), 'curl error 28');
    
            Log::channel('mailin-ai')->warning('Connection timeout when fetching mailboxes by email/name', [
                'action' => 'get_mailboxes_by_name',
                'email' => $email ?? null,
                'error' => $errorMessage,
                'is_timeout' => $isTimeout,
            ]);
    
            return [
                'success' => false,
                'mailboxes' => [],
                'message' => $errorMessage,
                'timeout' => $isTimeout,
            ];
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $errorMessage = 'Network error';
    
            if ($e->response) {
                $errorMessage .= '. Status: ' . $e->response->status() . '. Body: ' . substr($e->response->body(), 0, 500);
            }
    
            Log::channel('mailin-ai')->error('Failed to fetch mailboxes by email/name', [
                'action' => 'get_mailboxes_by_name',
                'email' => $email ?? null,
                'error' => $errorMessage,
            ]);
    
            return [
                'success' => false,
                'mailboxes' => [],
                'message' => $errorMessage,
            ];
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            $isTimeout = str_contains(strtolower($errorMessage), 'timeout')
                || str_contains(strtolower($errorMessage), 'connection')
                || str_contains(strtolower($errorMessage), 'curl error 28');
    
            Log::channel('mailin-ai')->error('Failed to fetch mailboxes by email/name', [
                'action' => 'get_mailboxes_by_name',
                'email' => $email ?? null,
                'error' => $errorMessage,
                'is_timeout' => $isTimeout,
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
    
            return [
                'success' => false,
                'mailboxes' => [],
                'message' => $errorMessage,
                'timeout' => $isTimeout,
            ];
        }
    }

    /**
     * Lookup mailbox ID by email address (single source of truth for Mailin.ai).
     * Used by MailinProviderService (split deletion) and OrderCancelledService (Google/365 legacy path).
     *
     * @param string $email Email address to look up
     * @return array ['success' => bool, 'mailbox_id' => ?int, 'timeout' => ?bool, 'not_found' => ?bool, 'message' => ?string]
     */
    public function lookupMailboxIdByEmail(string $email): array
    {
        try {
            $email = strtolower(trim($email));
    
            if ($email === '') {
                return [
                    'success' => false,
                    'message' => 'Email is required.',
                    'not_found' => false,
                    'timeout' => false,
                ];
            }
    
            Log::channel('mailin-ai')->info('Looking up mailbox ID by email', [
                'action' => 'lookup_mailbox_id_by_email',
                'email' => $email,
            ]);
    
            // Uses getMailboxesByName(), which should already rely on makeRequest()
            // for throttling + 429 retry handling.
            $result = $this->getMailboxesByName($email, 10);
    
            if (!empty($result['timeout'])) {
                return [
                    'success' => false,
                    'timeout' => true,
                    'message' => $result['message'] ?? 'Connection timeout',
                ];
            }
    
            if (!empty($result['success']) && !empty($result['mailboxes']) && is_array($result['mailboxes'])) {
                $mailboxes = $result['mailboxes'];
    
                // 1) Prefer exact matches first across likely fields
                foreach ($mailboxes as $mailbox) {
                    $candidates = array_filter([
                        strtolower(trim((string) ($mailbox['name'] ?? ''))),
                        strtolower(trim((string) ($mailbox['email'] ?? ''))),
                        strtolower(trim((string) ($mailbox['username'] ?? ''))),
                    ]);
    
                    if (in_array($email, $candidates, true)) {
                        $mailboxId = $mailbox['id'] ?? null;
    
                        if ($mailboxId) {
                            Log::channel('mailin-ai')->info('Exact match found for email', [
                                'action' => 'lookup_mailbox_id_by_email',
                                'email' => $email,
                                'mailbox_id' => $mailboxId,
                            ]);
    
                            return [
                                'success' => true,
                                'mailbox_id' => $mailboxId,
                                'exact_match' => true,
                            ];
                        }
                    }
                }
    
                // 2) Fallback: first result only if it looks close enough
                $first = $mailboxes[0] ?? null;
                if (is_array($first) && !empty($first['id'])) {
                    $firstComparable = strtolower(trim((string) (
                        $first['name']
                        ?? $first['email']
                        ?? $first['username']
                        ?? ''
                    )));
    
                    if ($firstComparable !== '') {
                        Log::channel('mailin-ai')->warning('No exact mailbox match found; using first search result as fallback', [
                            'action' => 'lookup_mailbox_id_by_email',
                            'email' => $email,
                            'fallback_value' => $firstComparable,
                            'mailbox_id' => $first['id'],
                        ]);
    
                        return [
                            'success' => true,
                            'mailbox_id' => $first['id'],
                            'exact_match' => false,
                            'fallback_used' => true,
                        ];
                    }
                }
            }
    
            Log::channel('mailin-ai')->info('Email not found on Mailin.ai', [
                'action' => 'lookup_mailbox_id_by_email',
                'email' => $email,
            ]);
    
            return [
                'success' => false,
                'not_found' => true,
                'timeout' => false,
                'message' => 'Mailbox not found on Mailin.ai',
            ];
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
    
            $isTimeout = str_contains(strtolower($errorMessage), 'timeout')
                || str_contains(strtolower($errorMessage), 'connection')
                || str_contains(strtolower($errorMessage), 'curl error 28')
                || ($e instanceof \Illuminate\Http\Client\ConnectionException);
    
            Log::channel('mailin-ai')->error('Failed to lookup mailbox ID by email', [
                'action' => 'lookup_mailbox_id_by_email',
                'email' => $email ?? null,
                'error' => $errorMessage,
                'is_timeout' => $isTimeout,
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
    
            return [
                'success' => false,
                'message' => $errorMessage,
                'timeout' => $isTimeout,
                'not_found' => false,
            ];
        }
    }
}

