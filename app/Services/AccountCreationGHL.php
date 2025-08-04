<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\GhlSetting;

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
     * GHL API version
     */
    private string $apiVersion;

    /**
     * GHL Location ID
     */
    private string $locationId;

    /**
     * GHL Settings
     */
    private GhlSetting $settings;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->settings = GhlSetting::getCurrentSettings();
        $this->baseUrl = $this->settings->base_url ?: 'https://rest.gohighlevel.com/v1';
        $this->apiToken = $this->settings->api_token ?: '';
        $this->apiVersion = $this->settings->api_version ?: '2021-07-28';
        $this->locationId = $this->settings->location_id ?: '';
    }

    /**
     * Check if GHL integration is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->settings->enabled && !empty($this->apiToken);
    }

    /**
     * Get authentication headers for GHL API
     *
     * @return array
     */
    private function getAuthHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Version' => $this->apiVersion,
        ];
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
            
            // Test with general locations endpoint
            $response = Http::withHeaders($headers)->get($this->baseUrl . '/locations/');
            // dd($response->successful()); // Debugging line to check response
            // Check if the response is successful
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
            if (!$this->isEnabled()) {
                Log::info('GHL integration is disabled');
                return null;
            }

            if (!$this->apiToken) {
                Log::error('GHL API token not configured');
                return null;
            }

            $contactData = $this->prepareContactData($user, $contactType);
            // Get the authorization headers
            $headers = $this->getAuthHeaders();
            
            // Use the standard contacts endpoint
            $endpoint = $this->baseUrl . '/contacts/';
            
            $response = Http::withHeaders($headers)->post($endpoint, $contactData);
            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('GHL contact created successfully RESPONSE', [
                    'user_id' => $user->id,
                    'ghl_contact_id' => $responseData ?? null,
                    'contact_type' => $contactType,
                    'endpoint_used' => $endpoint
                ]);
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
            'firstName' => $firstName ?: null,
            'lastName' => $lastName ?: null,
            'name' => $user->name ?: null,
            'email' => $user->email,
            'locationId' => $this->locationId,
            'phone' => $user->phone ?? null,
            'address1' => $user->billing_address ?? null,
            'city' => $user->billing_city ?? null,
            'state' => $user->billing_state ?? null,
            'postalCode' => $user->billing_zip ?? null,
            'country' => $user->billing_country ?? null,
            // 'timezone' => 'America/New_York',
            'type' => $contactType,
            'source' => 'api',
            'tags' => [$contactType, 'created-app.projectinbox.ai'],
            'customField' => [
                [
                    'key' => 'type',
                    'field_value' => $contactType
                ],
                [
                    'key' => 'contact.type',
                    'field_value' => $contactType
                ],
                [
                    'key' => 'contact_type',
                    'field_value' => $contactType
                ],
                // custom_action
                // [
                //     'key' => 'custom_action',
                //     'field_value' => 'payment-failed'
                // ],
                // [
                //     'key' => 'failed_payment_counter',
                //     'field_value' => 10 // Default value, can be updated later
                // ],
                // [
                //     'key' => 'order_id',
                //     'field_value' => 67
                // ]
            ]
        ];

        // Add billing information if available
        if ($user->billing_company) {
            $contactData['companyName'] = $user->billing_company;
        }

        if ($user->billing_address) {
            $contactData['address1'] = $user->billing_address;
            $contactData['city'] = $user->billing_city ?? null;
            $contactData['state'] = $user->billing_state ?? null;
            $contactData['postalCode'] = $user->billing_zip ?? null;
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
            Log::info('User updated with GHL contact ID', [
                'user_id' => $user->id,
                'ghl_contact_id' => $ghlContactId
            ]);
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
            if (!$this->isEnabled()) {
                Log::info('GHL integration is disabled');
                return null;
            }

            if (!$this->apiToken) {
                Log::error('GHL API token not configured');
                return null;
            }

            $contactData = $this->prepareContactData($user, 'lead');
            unset($contactData['locationId']); // Don't update location on existing contact

            // Get the authorization headers
            $headers = $this->getAuthHeaders();

            // Use standard endpoint
            $endpoint = $this->baseUrl . '/contacts/' . $ghlContactId;

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
     * Get GHL contact ID from user table
     *
     * @param User $user
     * @return string|null
     */
    public function getGhlContactId(User $user): ?string
    {
        return $user->ghl_contact_id;
    }

    /**
     * Update contact type and tags (convert lead to customer)
     *
     * @param User $user
     * @param string $newContactType
     * @return array|null
     */
    public function updateContactToCustomer(User $user, string $newContactType = 'customer'): ?array
    {
        try {
            if (!$this->isEnabled()) {
                Log::info('GHL integration is disabled');
                return null;
            }

            if (!$this->apiToken) {
                Log::error('GHL API token not configured');
                return null;
            }

            // Get GHL contact ID from user table
            $ghlContactId = $this->getGhlContactId($user);
            
            if (!$ghlContactId) {
                Log::warning('No GHL contact ID found for user', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                return null;
            }

            // Prepare full contact data for customer conversion
            $contactData = $this->prepareContactData($user, $newContactType);
            
            // Remove locationId as we don't want to update it on existing contact
            unset($contactData['locationId']);
            
            // Override tags to ensure customer tag is set and lead tag is removed
            $contactData['tags'] = ['customer', 'created-app.projectinbox.ai'];
            
            // Add conversion tracking to custom fields
            // $contactData['customField'][] = [
            //     'key' => 'type',
            //     'field_value' => $newContactType
            // ];

            $headers = $this->getAuthHeaders();
            $endpoint = $this->baseUrl . '/contacts/' . $ghlContactId;

            // First, remove 'lead' tag if it exists
            $this->removeTagFromContact($ghlContactId, 'lead');

            // Then update the full contact with customer data
            $response = Http::withHeaders($headers)->put($endpoint, $contactData);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('GHL contact converted to customer successfully', [
                    'user_id' => $user->id,
                    'ghl_contact_id' => $ghlContactId,
                    'old_type' => 'lead',
                    'new_type' => $newContactType
                ]);

                return $responseData;
            } else {
                Log::error('Failed to convert GHL contact to customer', [
                    'user_id' => $user->id,
                    'ghl_contact_id' => $ghlContactId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Exception while converting GHL contact to customer', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }


    /**
     * Remove a tag from contact
     *
     * @param string $ghlContactId
     * @param string $tagToRemove
     * @return bool
     */
    private function removeTagFromContact(string $ghlContactId, string $tagToRemove): bool
    {
        try {
            $headers = $this->getAuthHeaders();
            $endpoint = $this->baseUrl . '/contacts/' . $ghlContactId . '/tags/' . $tagToRemove;

            $response = Http::withHeaders($headers)->delete($endpoint);

            if ($response->successful()) {
                Log::info('Tag removed from GHL contact', [
                    'ghl_contact_id' => $ghlContactId,
                    'tag_removed' => $tagToRemove
                ]);
                return true;
            } else {
                Log::warning('Failed to remove tag from GHL contact', [
                    'ghl_contact_id' => $ghlContactId,
                    'tag_to_remove' => $tagToRemove,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception while removing tag from GHL contact', [
                'ghl_contact_id' => $ghlContactId,
                'tag_to_remove' => $tagToRemove,
                'error' => $e->getMessage()
            ]);
            return false;
        }
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
            if (!$this->isEnabled()) {
                Log::info('GHL integration is disabled');
                return false;
            }

            if (!$this->apiToken) {
                Log::error('GHL API token not configured');
                return false;
            }

            // Get the authorization headers
            $headers = $this->getAuthHeaders();
            // Remove Content-Type for DELETE request
            unset($headers['Content-Type']);

            // Use standard endpoint
            $endpoint = $this->baseUrl . '/contacts/' . $ghlContactId;

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
}
