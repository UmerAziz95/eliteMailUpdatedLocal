<?php

namespace App\Contracts\Providers;

/**
 * Interface for provider credentials
 */
interface ProviderCredentialsInterface
{
    /**
     * Get base URL for API calls
     * @return string
     */
    public function getBaseUrl(): string;

    /**
     * Get authentication email/username
     * @return string
     */
    public function getEmail(): string;

    /**
     * Get authentication password/API key
     * @return string
     */
    public function getPassword(): string;

    /**
     * Get all credentials as array
     * @return array
     */
    public function toArray(): array;
}
