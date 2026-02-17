<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Low-level API client for PremiumInboxes API
 * Handles HTTP requests, error handling, and rate limiting
 */
class PremiumInboxesService
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    /**
     * Constructor
     * 
     * @param array $credentials ['base_url' => string, 'api_key' => string|'password' => string]
     */
    public function __construct(array $credentials)
    {
        $this->baseUrl = $credentials['base_url'] ?? config('premiuminboxes.api_url', 'https://api.piwhitelabel.dev/api/v1');
        $this->apiKey = $credentials['api_key'] ?? $credentials['password'] ?? '';
        $this->timeout = config('premiuminboxes.timeout', 30);
    }

    /**
     * Create order (includes domain transfer + mailbox creation)
     * POST /purchase
     * 
     * @param array $orderData Order data: client_order_id, domains, inboxes_per_domain, persona, email_password, sequencer
     * @return array ['success' => bool, 'status_code' => int, 'data' => array, 'error' => string|null]
     */
    public function createOrder(array $orderData): array
    {
        try {
            Log::channel('mailin-ai')->info('Step 3: API Client - Final Purchase Request Payload', [
                'action' => 'createOrder',
                'url' => rtrim($this->baseUrl, '/') . '/purchase',
                'payload' => $orderData
            ]);

            Log::channel('mailin-ai')->info('Creating PremiumInboxes order', [
                'action' => 'createOrder',
                'client_order_id' => $orderData['client_order_id'] ?? 'N/A',
                'domain_count' => count($orderData['domains'] ?? []),
                'inboxes_per_domain' => $orderData['inboxes_per_domain'] ?? 0,
            ]);

            $result = $this->makeRequest('POST', '/purchase', $orderData);

            if ($result['success']) {
                Log::channel('mailin-ai')->info('PremiumInboxes order created successfully', [
                    'action' => 'createOrder',
                    'order_id' => $result['data']['order_id'] ?? 'N/A',
                    'status' => $result['data']['status'] ?? 'N/A',
                ]);
            } else {
                Log::channel('mailin-ai')->error('PremiumInboxes order creation failed', [
                    'action' => 'createOrder',
                    'status_code' => $result['status_code'],
                    'error' => $result['error'],
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('PremiumInboxes order creation exception', [
                'action' => 'createOrder',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'status_code' => 500,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get order status
     * GET /orders/{order_id}
     * 
     * @param string $orderId PremiumInboxes order ID (UUID)
     * @return array ['success' => bool, 'status_code' => int, 'data' => array, 'error' => string|null]
     */
    public function getOrder(string $orderId): array
    {
        try {
            $result = $this->makeRequest('GET', "/orders/{$orderId}");

            if ($result['success']) {
                Log::channel('mailin-ai')->debug('PremiumInboxes order retrieved', [
                    'action' => 'getOrder',
                    'order_id' => $orderId,
                    'status' => $result['data']['status'] ?? 'N/A',
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('PremiumInboxes get order exception', [
                'action' => 'getOrder',
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status_code' => 500,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify nameservers (trigger real-time verification against Cloudflare)
     * POST /orders/{order_id}/verify-ns
     *
     * @param string $orderId PremiumInboxes order ID (UUID)
     * @return array ['success' => bool, 'status_code' => int, 'data' => array, 'error' => string|null]
     *               data: order_id, order_status, domains (domain, ns_status, expected_nameservers), all_validated
     */
    public function verifyNameservers(string $orderId): array
    {
        try {
            Log::channel('mailin-ai')->info('PremiumInboxes verify nameservers', [
                'action' => 'verifyNameservers',
                'order_id' => $orderId,
            ]);

            $result = $this->makeRequest('POST', "/orders/{$orderId}/verify-ns", []);

            if ($result['success']) {
                Log::channel('mailin-ai')->debug('PremiumInboxes verify-ns response', [
                    'action' => 'verifyNameservers',
                    'order_id' => $orderId,
                    'order_status' => $result['data']['order_status'] ?? 'N/A',
                    'all_validated' => $result['data']['all_validated'] ?? false,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('PremiumInboxes verifyNameservers exception', [
                'action' => 'verifyNameservers',
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status_code' => 500,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * List orders (for debugging)
     * GET /orders
     * 
     * @param array $filters Query parameters: limit, offset, status
     * @return array ['success' => bool, 'status_code' => int, 'data' => array, 'error' => string|null]
     */
    public function listOrders(array $filters = []): array
    {
        $queryString = http_build_query($filters);
        $endpoint = '/orders' . ($queryString ? '?' . $queryString : '');

        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Cancel order
     * POST /orders/{order_id}/cancel
     * 
     * @param string $orderId PremiumInboxes order ID (UUID)
     * @return array ['success' => bool, 'status_code' => int, 'data' => array, 'error' => string|null]
     */
    public function cancelOrder(string $orderId): array
    {
        try {
            Log::channel('mailin-ai')->info('Cancelling PremiumInboxes order', [
                'action' => 'cancelOrder',
                'order_id' => $orderId,
            ]);

            $result = $this->makeRequest('POST', "/orders/{$orderId}/cancel");

            return $result;
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('PremiumInboxes cancel order exception', [
                'action' => 'cancelOrder',
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status_code' => 500,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel single email account
     * DELETE /email-accounts/{email_account_id}
     * 
     * @param string $emailAccountId Email account ID (UUID)
     * @return array ['success' => bool, 'status_code' => int, 'data' => array, 'error' => string|null]
     */
    public function cancelEmailAccount(string $emailAccountId): array
    {
        try {
            Log::channel('mailin-ai')->info('Cancelling PremiumInboxes email account', [
                'action' => 'cancelEmailAccount',
                'email_account_id' => $emailAccountId,
            ]);

            $result = $this->makeRequest('POST', "/email-accounts/{$emailAccountId}/cancel");

            return $result;
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('PremiumInboxes cancel email account exception', [
                'action' => 'cancelEmailAccount',
                'email_account_id' => $emailAccountId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status_code' => 500,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * List email accounts by order ID
     * GET /email-accounts?order_id={order_id}&limit=50&offset=0
     * Used to pre-fill missing mailbox IDs in split JSON for deletion.
     *
     * @param string $orderId PremiumInboxes order ID (UUID)
     * @param int $limit
     * @param int $offset
     * @return array ['success' => bool, 'data' => array, 'error' => string|null, 'email_accounts' => array]
     */
    public function listEmailAccountsByOrderId(string $orderId, int $limit = 50, int $offset = 0): array
    {
        try {
            Log::channel('mailin-ai')->info('Listing PremiumInboxes email accounts by order ID', [
                'action' => 'listEmailAccountsByOrderId',
                'order_id' => $orderId,
                'limit' => $limit,
                'offset' => $offset,
            ]);

            $result = $this->makeRequest('GET', '/email-accounts', [
                'order_id' => $orderId,
                'limit' => $limit,
                'offset' => $offset,
            ]);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => $result['error'] ?? 'Unknown error',
                    'email_accounts' => [],
                    'timeout' => str_contains(strtolower($result['error'] ?? ''), 'timeout')
                        || str_contains(strtolower($result['error'] ?? ''), 'connection'),
                ];
            }

            $data = $result['data'] ?? [];
            $emailAccounts = is_array($data) && isset($data['email_accounts'])
                ? $data['email_accounts']
                : (is_array($data) && isset($data['data']) ? $data['data'] : (is_array($data) ? $data : []));

            if (!is_array($emailAccounts)) {
                $emailAccounts = [];
            }

            return [
                'success' => true,
                'data' => $data,
                'error' => null,
                'email_accounts' => $emailAccounts,
                'timeout' => false,
            ];
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('PremiumInboxes listEmailAccountsByOrderId exception', [
                'action' => 'listEmailAccountsByOrderId',
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            $errorMessage = $e->getMessage();
            $isTimeout = str_contains(strtolower($errorMessage), 'timeout')
                || str_contains(strtolower($errorMessage), 'connection');

            return [
                'success' => false,
                'data' => null,
                'error' => $errorMessage,
                'email_accounts' => [],
                'timeout' => $isTimeout,
            ];
        }
    }

    /**
     * Get email accounts by domain (from order)
     * Helper method - extracts from getOrder()
     * 
     * @param string $orderId PremiumInboxes order ID
     * @param string $domain Domain name
     * @return array Email accounts for the domain
     */
    public function getEmailAccountsByDomain(string $orderId, string $domain): array
    {
        $order = $this->getOrder($orderId);

        if (!$order['success']) {
            return [];
        }

        $accounts = $order['data']['email_accounts'] ?? [];

        return array_filter($accounts, function ($account) use ($domain) {
            return ($account['domain'] ?? '') === $domain;
        });
    }

    /**
     * Make authenticated API request
     * 
     * @param string $method HTTP method (GET, POST, DELETE)
     * @param string $endpoint API endpoint
     * @param array $data Request body data (for POST)
     * @return array ['success' => bool, 'status_code' => int, 'data' => array, 'error' => string|null]
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        // Validate API key
        if (empty($this->apiKey)) {
            Log::channel('mailin-ai')->error('PremiumInboxes API key not configured', [
                'action' => 'makeRequest',
                'endpoint' => $endpoint,
            ]);

            return [
                'success' => false,
                'status_code' => 401,
                'data' => null,
                'error' => 'API key not configured',
            ];
        }

        $url = rtrim($this->baseUrl, '/') . $endpoint;
        $maxRetries = 3;
        $backoff = [1, 2, 5]; // seconds

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            try {
                $response = Http::timeout($this->timeout)
                            ->withHeaders([
                                'X-API-Key' => $this->apiKey,
                                'Content-Type' => 'application/json',
                                'Accept' => 'application/json',
                            ])
                    ->{strtolower($method)}($url, !empty($data) ? $data : []);

                $statusCode = $response->status();
                $responseBody = $response->json();

                // Handle rate limiting (429) with retry
                if ($statusCode === 429 && $attempt < $maxRetries - 1) {
                    $waitTime = $backoff[$attempt];
                    Log::channel('mailin-ai')->warning('PremiumInboxes rate limited, retrying', [
                        'action' => 'makeRequest',
                        'endpoint' => $endpoint,
                        'attempt' => $attempt + 1,
                        'wait_seconds' => $waitTime,
                    ]);
                    sleep($waitTime);
                    continue;
                }

                // Handle errors
                if ($response->failed()) {
                    $errorMessage = $responseBody['detail'] ?? $responseBody['message'] ?? 'Unknown error';

                    Log::channel('mailin-ai')->error('PremiumInboxes API request failed', [
                        'action' => 'makeRequest',
                        'endpoint' => $endpoint,
                        'status_code' => $statusCode,
                        'error' => $errorMessage,
                    ]);

                    return [
                        'success' => false,
                        'status_code' => $statusCode,
                        'data' => $responseBody,
                        'error' => $errorMessage,
                    ];
                }

                // Success
                return [
                    'success' => true,
                    'status_code' => $statusCode,
                    'data' => $responseBody,
                    'error' => null,
                ];

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                if ($attempt < $maxRetries - 1) {
                    $waitTime = $backoff[$attempt];
                    Log::channel('mailin-ai')->warning('PremiumInboxes connection error, retrying', [
                        'action' => 'makeRequest',
                        'endpoint' => $endpoint,
                        'attempt' => $attempt + 1,
                        'wait_seconds' => $waitTime,
                        'error' => $e->getMessage(),
                    ]);
                    sleep($waitTime);
                    continue;
                }

                Log::channel('mailin-ai')->error('PremiumInboxes connection error after retries', [
                    'action' => 'makeRequest',
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'status_code' => 503,
                    'data' => null,
                    'error' => 'Connection error: ' . $e->getMessage(),
                ];
            } catch (\Exception $e) {
                Log::channel('mailin-ai')->error('PremiumInboxes request exception', [
                    'action' => 'makeRequest',
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return [
                    'success' => false,
                    'status_code' => 500,
                    'data' => null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Should not reach here, but just in case
        return [
            'success' => false,
            'status_code' => 500,
            'data' => null,
            'error' => 'Max retries exceeded',
        ];
    }
}
