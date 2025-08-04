<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\GhlSetting;

class UpdateGHLCustomFields
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
     * Update custom fields for a contact in GHL
     *
     * @param string $contactId GHL Contact ID
     * @param array $customFields Array of custom fields to update
     * @return array|null
     */
    public function updateContactCustomFields(string $contactId, array $customFields): ?array
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

            if (empty($contactId)) {
                Log::error('GHL Contact ID is required for updating custom fields');
                return null;
            }

            if (empty($customFields)) {
                Log::warning('No custom fields provided for update');
                return ['success' => false, 'message' => 'No custom fields provided'];
            }

            $headers = $this->getAuthHeaders();
            $endpoint = $this->baseUrl . '/contacts/' . $contactId;

            // Prepare the update data
            $updateData = [
                'customFields' => $this->formatCustomFields($customFields)
            ];

            Log::info('Updating GHL contact custom fields', [
                'contact_id' => $contactId,
                'custom_fields' => $customFields,
                'endpoint' => $endpoint
            ]);

            $response = Http::withHeaders($headers)->put($endpoint, $updateData);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('GHL contact custom fields updated successfully', [
                    'contact_id' => $contactId,
                    'updated_fields' => array_keys($customFields),
                    'response' => $responseData
                ]);

                return [
                    'success' => true,
                    'message' => 'Custom fields updated successfully',
                    'data' => $responseData,
                    'contact_id' => $contactId
                ];
            } else {
                Log::error('Failed to update GHL contact custom fields', [
                    'contact_id' => $contactId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'custom_fields' => $customFields
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to update custom fields',
                    'error' => $response->body(),
                    'status_code' => $response->status()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Exception during GHL custom fields update', [
                'contact_id' => $contactId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'custom_fields' => $customFields ?? []
            ]);

            return [
                'success' => false,
                'message' => 'Exception occurred during update',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update custom fields for a contact using email lookup
     *
     * @param string $email Contact email to lookup
     * @param array $customFields Array of custom fields to update
     * @return array|null
     */
    public function updateContactCustomFieldsByEmail(string $email, array $customFields): ?array
    {
        try {
            $contactId = $this->getContactIdByEmail($email);
            
            if (!$contactId) {
                Log::warning('Contact not found for email', ['email' => $email]);
                return [
                    'success' => false,
                    'message' => 'Contact not found for the provided email',
                    'email' => $email
                ];
            }

            return $this->updateContactCustomFields($contactId, $customFields);

        } catch (\Exception $e) {
            Log::error('Exception during GHL custom fields update by email', [
                'email' => $email,
                'error' => $e->getMessage(),
                'custom_fields' => $customFields
            ]);

            return [
                'success' => false,
                'message' => 'Exception occurred during update by email',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get contact ID by email
     *
     * @param string $email
     * @return string|null
     */
    private function getContactIdByEmail(string $email): ?string
    {
        try {
            if (!$this->isEnabled()) {
                return null;
            }

            $headers = $this->getAuthHeaders();
            $endpoint = $this->baseUrl . '/contacts/search';

            $response = Http::withHeaders($headers)->get($endpoint, [
                'query' => $email
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!empty($data['contacts'])) {
                    foreach ($data['contacts'] as $contact) {
                        if (isset($contact['email']) && strtolower($contact['email']) === strtolower($email)) {
                            return $contact['id'];
                        }
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Exception during GHL contact lookup by email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Format custom fields for GHL API
     *
     * @param array $customFields
     * @return array
     */
    private function formatCustomFields(array $customFields): array
    {
        $formatted = [];

        foreach ($customFields as $key => $value) {
            $formatted[] = [
                'key' => $key,
                'field_value' => $value
            ];
        }

        return $formatted;
    }

    /**
     * Get all custom fields for a contact
     *
     * @param string $contactId
     * @return array|null
     */
    public function getContactCustomFields(string $contactId): ?array
    {
        try {
            if (!$this->isEnabled()) {
                Log::info('GHL integration is disabled');
                return null;
            }

            $headers = $this->getAuthHeaders();
            $endpoint = $this->baseUrl . '/contacts/' . $contactId;

            $response = Http::withHeaders($headers)->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                return $data['contact']['customFields'] ?? [];
            }

            Log::error('Failed to get GHL contact custom fields', [
                'contact_id' => $contactId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Exception during GHL contact custom fields retrieval', [
                'contact_id' => $contactId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Add or update a single custom field
     *
     * @param string $contactId
     * @param string $fieldKey
     * @param mixed $fieldValue
     * @return array|null
     */
    public function updateSingleCustomField(string $contactId, string $fieldKey, $fieldValue): ?array
    {
        return $this->updateContactCustomFields($contactId, [$fieldKey => $fieldValue]);
    }

    /**
     * Remove custom fields from a contact
     *
     * @param string $contactId
     * @param array $fieldKeys Array of field keys to remove
     * @return array|null
     */
    public function removeCustomFields(string $contactId, array $fieldKeys): ?array
    {
        $customFields = [];
        
        // Set field values to empty string to remove them
        foreach ($fieldKeys as $key) {
            $customFields[$key] = '';
        }

        return $this->updateContactCustomFields($contactId, $customFields);
    }

    /**
     * Validate custom field data
     *
     * @param array $customFields
     * @return array
     */
    public function validateCustomFields(array $customFields): array
    {
        $errors = [];
        $valid = [];

        foreach ($customFields as $key => $value) {
            if (empty($key) || !is_string($key)) {
                $errors[] = "Invalid field key: {$key}";
                continue;
            }

            // Check for valid field key format (alphanumeric, underscore, hyphen)
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $key)) {
                $errors[] = "Invalid field key format: {$key}. Only alphanumeric characters, underscores, and hyphens are allowed.";
                continue;
            }

            $valid[$key] = $value;
        }

        return [
            'valid' => $valid,
            'errors' => $errors,
            'is_valid' => empty($errors)
        ];
    }

}
