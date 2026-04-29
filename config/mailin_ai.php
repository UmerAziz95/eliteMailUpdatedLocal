<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mailin.ai API Configuration
    |--------------------------------------------------------------------------
    |
    | Core configuration for Mailin.ai API integration
    |
    */

    // Base URL for Mailin.ai API
    'base_url' => env('MAILIN_BASE_URL', 'https://api.mailin.ai/api/v1/public'),

    /*
    |--------------------------------------------------------------------------
    | Authentication Credentials
    |--------------------------------------------------------------------------
    */
    'email' => env('MAILIN_EMAIL'),
    'password' => env('MAILIN_PASSWORD'),
    'device_name' => env('MAILIN_DEVICE_NAME', 'project inbox'),

    // Optional API key (if Mailin.ai supports it)
    'api_key' => env('MAILIN_AI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Request Settings
    |--------------------------------------------------------------------------
    */
    'timeout' => env('MAILIN_AI_TIMEOUT', 120), // total request timeout (seconds)
    'connect_timeout' => env('MAILIN_AI_CONNECT_TIMEOUT', 15), // connection timeout

    /*
    |--------------------------------------------------------------------------
    | Retry (Non-429 errors)
    |--------------------------------------------------------------------------
    */
    'retry_attempts' => env('MAILIN_AI_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('MAILIN_AI_RETRY_DELAY', 5), // seconds

    /*
    |--------------------------------------------------------------------------
    | Proactive Throttling (VERY IMPORTANT)
    |--------------------------------------------------------------------------
    |
    | This delays requests BEFORE sending them to avoid hitting rate limits.
    |
    | Example:
    | 1000 = 1 second
    | 2500 = 2.5 seconds
    | 3000 = 3 seconds (recommended if still hitting 429)
    |
    */
    'request_throttle_ms' => env('MAILIN_AI_REQUEST_THROTTLE_MS', 2500),

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Handling (429 Recovery)
    |--------------------------------------------------------------------------
    |
    | These apply AFTER Mailin.ai returns 429
    |
    | delay = base_delay * (2 ^ retry_attempt)
    | capped at delay_cap
    |
    */
    'rate_limit_max_retries' => env('MAILIN_AI_RATE_LIMIT_MAX_RETRIES', 6),
    'rate_limit_base_delay' => env('MAILIN_AI_RATE_LIMIT_BASE_DELAY', 15), // seconds
    'rate_limit_delay_cap' => env('MAILIN_AI_RATE_LIMIT_DELAY_CAP', 180), // seconds

    /*
    |--------------------------------------------------------------------------
    | Mailbox Creation Settings
    |--------------------------------------------------------------------------
    */
    'mailbox_creation_delay_between_domains' => env('MAILIN_AI_MAILBOX_DELAY_BETWEEN_DOMAINS', 3), // seconds

    /*
    |--------------------------------------------------------------------------
    | Domain Transfer Rate Limiting
    |--------------------------------------------------------------------------
    */
    'domain_transfer_delay' => env('MAILIN_AI_DOMAIN_TRANSFER_DELAY', 2), // seconds between each domain
    'domain_transfer_batch_size' => env('MAILIN_AI_DOMAIN_TRANSFER_BATCH_SIZE', 10),
    'domain_transfer_batch_delay' => env('MAILIN_AI_DOMAIN_TRANSFER_BATCH_DELAY', 10), // seconds between batches

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */
    'webhook_secret' => env('MAILIN_AI_WEBHOOK_SECRET'),
    'webhook_url' => env('MAILIN_AI_WEBHOOK_URL', '/webhook/mailin-ai'),

    /*
    |--------------------------------------------------------------------------
    | Polling (Fallback if webhook not available)
    |--------------------------------------------------------------------------
    */
    'polling_enabled' => env('MAILIN_AI_POLLING_ENABLED', true),
    'polling_interval' => env('MAILIN_AI_POLLING_INTERVAL', 300), // seconds

    /*
    |--------------------------------------------------------------------------
    | Automation Toggle
    |--------------------------------------------------------------------------
    */
    'automation_enabled' => env('MAILIN_AI_AUTOMATION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'order_delay_notification_hours' => env('MAILIN_AI_ORDER_DELAY_NOTIFICATION_HOURS', 24),

];