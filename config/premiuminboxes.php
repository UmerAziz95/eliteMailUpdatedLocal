<?php

return [
    'api_url' => env('PREMIUMINBOXES_API_URL', 'https://api.piwhitelabel.dev/api/v1'),
    'api_key' => env('PREMIUMINBOXES_API_KEY', ''),
    'webhook_secret' => env('PREMIUMINBOXES_WEBHOOK_SECRET', ''),
    'timeout' => env('PREMIUMINBOXES_TIMEOUT', 30),
];
