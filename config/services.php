<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'chargebee' => [
        'site' => env('CHARGEBEE_SITE'),
        'api_key' => env('CHARGEBEE_API_KEY'),
        'item_family_id' => env('CHARGEBEE_ITEM_FAMILY_ID', 'cbdemo_omnisupport-solutions'),
    ],

    'ghl' => [
        'enabled' => env('GHL_ENABLED', false),
        'base_url' => env('GHL_BASE_URL', 'https://rest.gohighlevel.com/v1'),
        'api_token' => env('GHL_API_TOKEN'),
        'location_id' => env('GHL_LOCATION_ID'),
        'auth_type' => env('GHL_AUTH_TYPE', 'bearer'), // bearer, jwt, api_key
        'api_version' => env('GHL_API_VERSION', '2021-07-28'),
    ],

    'mailin' => [
        'base_url' => env('MAILIN_BASE_URL'),
        'email' => env('MAILIN_EMAIL'),
        'password' => env('MAILIN_PASSWORD'),
        'timeout' => env('MAILIN_TIMEOUT', 30),
        'verify_ssl' => env('MAILIN_VERIFY_SSL', true),
        'auth_endpoint' => env('MAILIN_AUTH_ENDPOINT', 'auth/login'),
        'buy_domains_endpoint' => env('MAILIN_BUY_DOMAINS_ENDPOINT', 'domains/buy'),
        'domain_status_endpoint' => env('MAILIN_DOMAIN_STATUS_ENDPOINT', 'domains/jobs'),
        'list_domains_endpoint' => env('MAILIN_LIST_DOMAINS_ENDPOINT', 'domains'),
        'create_mailboxes_endpoint' => env('MAILIN_CREATE_MAILBOXES_ENDPOINT', 'mailboxes/bulk'),
        'mailbox_status_endpoint' => env('MAILIN_MAILBOX_STATUS_ENDPOINT', 'mailboxes/jobs'),
        'list_mailboxes_endpoint' => env('MAILIN_LIST_MAILBOXES_ENDPOINT', 'mailboxes'),
        'mailbox_chunk_size' => env('MAILIN_MAILBOX_CHUNK_SIZE', 50),
        'auto_domain_tld' => env('MAILIN_AUTO_DOMAIN_TLD', 'mailin.ai'),
        'auto_domain_prefix' => env('MAILIN_AUTO_DOMAIN_PREFIX', 'order-mailin'),
    ],

];
