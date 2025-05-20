<?php
return [
    'default' => 'openai',
    'providers' => [
        'openai' => [
            'key' => get_env('OPENAI_KEY'),
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 256,
            'timeout' => 15,
        ],
        // Add more providers here
    ],
];
