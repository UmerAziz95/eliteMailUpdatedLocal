<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mailin.ai API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Mailin.ai API integration
    |
    */

    // Base URL for Mailin.ai API
    'base_url' => env('MAILIN_BASE_URL', 'https://api.mailin.ai/api/v1/public'),

    // Authentication credentials
    'email' => env('MAILIN_EMAIL'),
    'password' => env('MAILIN_PASSWORD'),
    'device_name' => env('MAILIN_DEVICE_NAME', 'project inbox'),

    // Optional: API key if available
    'api_key' => env('MAILIN_AI_API_KEY'),

    // API request settings
    'timeout' => env('MAILIN_AI_TIMEOUT', 30), // seconds
    'retry_attempts' => env('MAILIN_AI_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('MAILIN_AI_RETRY_DELAY', 5), // seconds

    // Webhook configuration (if available)
    'webhook_secret' => env('MAILIN_AI_WEBHOOK_SECRET'),
    'webhook_url' => env('MAILIN_AI_WEBHOOK_URL', '/webhook/mailin-ai'),

    // Polling configuration (fallback if webhooks not available)
    'polling_enabled' => env('MAILIN_AI_POLLING_ENABLED', true),
    'polling_interval' => env('MAILIN_AI_POLLING_INTERVAL', 300), // seconds (5 minutes)

    // Enable/disable automation
    'automation_enabled' => env('MAILIN_AI_AUTOMATION_ENABLED', false),
];
