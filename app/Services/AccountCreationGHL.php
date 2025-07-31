<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class AccountCreationGHL
{
    /**
     * GHL API base URL
     */
    private string $baseUrl;

    /**
     * GHL API token
     */
    private string $apiToken;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->baseUrl = config('services.ghl.base_url', 'https://services.leadconnectorhq.com');
        $this->apiToken = config('services.ghl.api_token');
    }

    /**
     * Get authentication headers for GHL API
     *
     * @return array
     */
    private function getAuthHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $authType = config('services.ghl.auth_type', 'bearer');
        $apiVersion = config('services.ghl.api_version', '2021-07-28'); // Back to official version

        // Auto-detect JWT tokens (they start with 'eyJ')
        if (strpos($this->apiToken, 'eyJ') === 0) {
            // JWT tokens still need the Version header according to the error
            $headers['Authorization'] = 'Bearer ' . $this->apiToken;
            $headers['Version'] = $apiVersion;
        } else {
            // Handle non-JWT tokens based on auth type
            switch ($authType) {
                case 'api_key':
                    $headers['X-API-Key'] = $this->apiToken;
                    $headers['Version'] = $apiVersion;
                    break;
                case 'bearer':
                case 'jwt':
                default:
                    $headers['Authorization'] = 'Bearer ' . $this->apiToken;
                    $headers['Version'] = $apiVersion;
                    break;
            }
        }

        return $headers;
    }

    /**
     * Test the API connection
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            if (!$this->apiToken) {
                Log::error('GHL API token not configured for connection test');
                return false;
            }

            $headers = $this->getAuthHeaders();
            
            // For JWT tokens, try the locations endpoint with location ID
            $locationId = config('services.ghl.location_id');
            if ($locationId && strpos($this->apiToken, 'eyJ') === 0) {
                // Test with specific location endpoint for JWT
                $response = Http::withHeaders($headers)->get($this->baseUrl . '/locations/' . $locationId);
            } else {
                // Test with general locations endpoint
                $response = Http::withHeaders($headers)->get($this->baseUrl . '/locations/');
            }
            
            if ($response->successful()) {
                Log::info('GHL API connection test successful');
                return true;
            } else {
                Log::error('GHL API connection test failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'headers_sent' => $headers
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception during GHL API connection test', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Create a contact in GHL
     *
     * @param User $user
     * @param string $contactType
     * @return array|null
     */
    public function createContact(User $user, string $contactType = 'lead'): ?array
    {
        try {
            if (!$this->apiToken) {
                Log::error('GHL API token not configured');
                return null;
            }

            $contactData = $this->prepareContactData($user, $contactType);
            // Get the authorization headers
            $headers = $this->getAuthHeaders();
            
            // Determine the correct endpoint based on token type
            $endpoint = $this->getContactsEndpoint();
            
            $response = Http::withHeaders($headers)->post($endpoint, $contactData);
            
            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('GHL contact created successfully', [
                    'user_id' => $user->id,
                    'ghl_contact_id' => $responseData['contact']['id'] ?? null,
                    'contact_type' => $contactType,
                    'endpoint_used' => $endpoint
                ]);

                // Update user with GHL contact ID if available
                if (isset($responseData['contact']['id'])) {
                    $this->updateUserWithGHLId($user, $responseData['contact']['id']);
                }

                return $responseData;
            } else {
                Log::error('Failed to create GHL contact', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'headers_sent' => $headers,
                    'endpoint_used' => $endpoint
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception while creating GHL contact', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get the appropriate contacts endpoint based on token type
     *
     * @return string
     */
    private function getContactsEndpoint(): string
    {
        $locationId = config('services.ghl.location_id');
        
        // For JWT tokens, use location-specific endpoint
        if (strpos($this->apiToken, 'eyJ') === 0 && $locationId) {
            return $this->baseUrl . '/locations/' . $locationId . '/contacts/';
        }
        
        // Default endpoint
        return $this->baseUrl . '/contacts/';
    }

    /**
     * Prepare contact data for GHL API
     *
     * @param User $user
     * @param string $contactType
     * @return array
     */
    private function prepareContactData(User $user, string $contactType): array
    {
        $firstName = $this->extractFirstName($user->name);
        $lastName = $this->extractLastName($user->name);
        
        $contactData = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'name' => $user->name, // Add full name field as in official example
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'source' => 'website', // Changed from 'public api' to match our use case
            'tags' => [$contactType, 'auto-created'],
            'customFields' => [
                [
                    'key' => 'contact_type',
                    'field_value' => $contactType
                ],
                [
                    'key' => 'user_id', 
                    'field_value' => (string) $user->id
                ],
                [
                    'key' => 'registration_date',
                    'field_value' => $user->created_at->format('Y-m-d H:i:s')
                ]
            ]
        ];

        // Add location ID if configured (required field according to official example)
        $locationId = config('services.ghl.location_id');
        if ($locationId) {
            $contactData['locationId'] = $locationId;
        }

        // Add billing information if available
        if ($user->billing_company) {
            $contactData['companyName'] = $user->billing_company;
        }

        if ($user->billing_address) {
            $contactData['address1'] = $user->billing_address;
            $contactData['city'] = $user->billing_city ?? '';
            $contactData['state'] = $user->billing_state ?? '';
            $contactData['country'] = $user->billing_country ?? 'US'; // Default to US as in example
            $contactData['postalCode'] = $user->billing_zip ?? '';
        }

        return $contactData;
    }

    /**
     * Extract first name from full name
     *
     * @param string $fullName
     * @return string
     */
    private function extractFirstName(string $fullName): string
    {
        $nameParts = explode(' ', trim($fullName));
        return $nameParts[0] ?? '';
    }

    /**
     * Extract last name from full name
     *
     * @param string $fullName
     * @return string
     */
    private function extractLastName(string $fullName): string
    {
        $nameParts = explode(' ', trim($fullName));
        if (count($nameParts) > 1) {
            array_shift($nameParts); // Remove first name
            return implode(' ', $nameParts);
        }
        return '';
    }

    /**
     * Update user with GHL contact ID
     *
     * @param User $user
     * @param string $ghlContactId
     * @return void
     */
    private function updateUserWithGHLId(User $user, string $ghlContactId): void
    {
        try {
            $user->update(['ghl_contact_id' => $ghlContactId]);
        } catch (\Exception $e) {
            Log::error('Failed to update user with GHL contact ID', [
                'user_id' => $user->id,
                'ghl_contact_id' => $ghlContactId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update a contact in GHL
     *
     * @param User $user
     * @param string $ghlContactId
     * @return array|null
     */
    public function updateContact(User $user, string $ghlContactId): ?array
    {
        try {
            if (!$this->apiToken) {
                Log::error('GHL API token not configured');
                return null;
            }

            $contactData = $this->prepareContactData($user, 'customer');
            unset($contactData['locationId']); // Don't update location on existing contact

            // Get the authorization headers
            $headers = $this->getAuthHeaders();

            // Determine the correct endpoint
            $endpoint = $this->getContactUpdateEndpoint($ghlContactId);

            $response = Http::withHeaders($headers)->put($endpoint, $contactData);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('GHL contact updated successfully', [
                    'user_id' => $user->id,
                    'ghl_contact_id' => $ghlContactId,
                    'endpoint_used' => $endpoint
                ]);

                return $responseData;
            } else {
                Log::error('Failed to update GHL contact', [
                    'user_id' => $user->id,
                    'ghl_contact_id' => $ghlContactId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'headers_sent' => $headers,
                    'endpoint_used' => $endpoint
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception while updating GHL contact', [
                'user_id' => $user->id,
                'ghl_contact_id' => $ghlContactId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get the appropriate contact update endpoint
     *
     * @param string $ghlContactId
     * @return string
     */
    private function getContactUpdateEndpoint(string $ghlContactId): string
    {
        $locationId = config('services.ghl.location_id');
        
        // For JWT tokens, use location-specific endpoint
        if (strpos($this->apiToken, 'eyJ') === 0 && $locationId) {
            return $this->baseUrl . '/locations/' . $locationId . '/contacts/' . $ghlContactId;
        }
        
        // Default endpoint
        return $this->baseUrl . '/contacts/' . $ghlContactId;
    }

    /**
     * Delete a contact from GHL
     *
     * @param string $ghlContactId
     * @return bool
     */
    public function deleteContact(string $ghlContactId): bool
    {
        try {
            if (!$this->apiToken) {
                Log::error('GHL API token not configured');
                return false;
            }

            // Get the authorization headers
            $headers = $this->getAuthHeaders();
            // Remove Content-Type for DELETE request
            unset($headers['Content-Type']);

            // Determine the correct endpoint
            $endpoint = $this->getContactDeleteEndpoint($ghlContactId);

            $response = Http::withHeaders($headers)->delete($endpoint);

            if ($response->successful()) {
                Log::info('GHL contact deleted successfully', [
                    'ghl_contact_id' => $ghlContactId,
                    'endpoint_used' => $endpoint
                ]);
                return true;
            } else {
                Log::error('Failed to delete GHL contact', [
                    'ghl_contact_id' => $ghlContactId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'headers_sent' => $headers,
                    'endpoint_used' => $endpoint
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception while deleting GHL contact', [
                'ghl_contact_id' => $ghlContactId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get the appropriate contact delete endpoint
     *
     * @param string $ghlContactId
     * @return string
     */
    private function getContactDeleteEndpoint(string $ghlContactId): string
    {
        $locationId = config('services.ghl.location_id');
        
        // For JWT tokens, use location-specific endpoint
        if (strpos($this->apiToken, 'eyJ') === 0 && $locationId) {
            return $this->baseUrl . '/locations/' . $locationId . '/contacts/' . $ghlContactId;
        }
        
        // Default endpoint
        return $this->baseUrl . '/contacts/' . $ghlContactId;
    }
}
