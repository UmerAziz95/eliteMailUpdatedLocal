<?php

return [

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'get-contact-detail',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://go.startyouraiagency.com',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
