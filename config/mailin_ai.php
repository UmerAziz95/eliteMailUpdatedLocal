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
    'timeout' => env('MAILIN_AI_TIMEOUT', 120), // seconds
    'retry_attempts' => env('MAILIN_AI_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('MAILIN_AI_RETRY_DELAY', 5), // seconds

    // Rate limit (429) handling for all API calls
    'rate_limit_max_retries' => env('MAILIN_AI_RATE_LIMIT_MAX_RETRIES', 6),
    'rate_limit_base_delay' => env('MAILIN_AI_RATE_LIMIT_BASE_DELAY', 10), // seconds; delay = base_delay * (2 ^ retry)
    'rate_limit_delay_cap' => env('MAILIN_AI_RATE_LIMIT_DELAY_CAP', 120), // max seconds to wait before retry

    // Mailbox creation: delay between processing each domain to avoid hitting rate limits
    'mailbox_creation_delay_between_domains' => env('MAILIN_AI_MAILBOX_DELAY_BETWEEN_DOMAINS', 3), // seconds

    // Domain transfer rate limiting settings
    'domain_transfer_delay' => env('MAILIN_AI_DOMAIN_TRANSFER_DELAY', 2), // seconds between individual transfers
    'domain_transfer_batch_size' => env('MAILIN_AI_DOMAIN_TRANSFER_BATCH_SIZE', 10), // domains per batch
    'domain_transfer_batch_delay' => env('MAILIN_AI_DOMAIN_TRANSFER_BATCH_DELAY', 10), // seconds between batches
    'rate_limit_delay' => env('MAILIN_AI_RATE_LIMIT_DELAY', 30), // seconds to wait after hitting rate limit

    // Webhook configuration (if available)
    'webhook_secret' => env('MAILIN_AI_WEBHOOK_SECRET'),
    'webhook_url' => env('MAILIN_AI_WEBHOOK_URL', '/webhook/mailin-ai'),

    // Polling configuration (fallback if webhooks not available)
    'polling_enabled' => env('MAILIN_AI_POLLING_ENABLED', true),
    'polling_interval' => env('MAILIN_AI_POLLING_INTERVAL', 300), // seconds (5 minutes)

    // Enable/disable automation
    'automation_enabled' => env('MAILIN_AI_AUTOMATION_ENABLED', false),

    // Order delay notification settings
    'order_delay_notification_hours' => env('MAILIN_AI_ORDER_DELAY_NOTIFICATION_HOURS', 24), // Hours after which to send notification for in-progress orders
];
