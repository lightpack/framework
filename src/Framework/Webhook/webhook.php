<?php
return [
    // Example: Webhook secrets and settings per provider
    'stripe' => [
        'secret' => get_env('STRIPE_WEBHOOK_SECRET'),
        'algo' => 'hmac',
        'id' => 'id',
    ],
    'github' => [
        'secret' => get_env('GITHUB_WEBHOOK_SECRET'),
        'algo' => 'hmac',
        'id' => 'delivery',
    ],
    // Add more providers as needed
];
