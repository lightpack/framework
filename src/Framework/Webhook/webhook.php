<?php
return [
    // Example: Webhook secrets and settings per provider
    'stripe' => [
        'secret' => get_env('STRIPE_WEBHOOK_SECRET'),
        'log_events' => true,
    ],
    'github' => [
        'secret' => get_env('GITHUB_WEBHOOK_SECRET'),
        'log_events' => true,
    ],
    // Add more providers as needed
];
