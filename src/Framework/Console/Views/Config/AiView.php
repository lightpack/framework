<?php

namespace Lightpack\Console\Views\Config;

class AiView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'ai' => [
        'driver' => get_env('AI_PROVIDER'), // openai, anthropic, mistral, groq, gemini
        'cache_ttl' => 3600, // Cache TTL in seconds (when caching is enabled)
        'http_timeout' => 30, // 30 seconds HTTP timeout by default
        'temperature' => 0.7,
        'max_tokens' => 256,

        'providers' => [
            'openai' => [
                'key' => get_env('OPENAI_API_KEY'),
                'model' => 'gpt-3.5-turbo',
                'base_url' => 'https://api.openai.com/v1',
            ],
            'anthropic' => [
                'key' => get_env('ANTHROPIC_API_KEY'),
                'model' => 'claude-sonnet-4-5',
                'base_url' => 'https://api.anthropic.com/v1',
                'version' => get_env('ANTHROPIC_VERSION', '2023-06-01'),
            ],
            'mistral' => [
                'key' => get_env('MISTRAL_API_KEY'),
                'model' => 'mistral-small-latest', // Or 'mistral-small', 'mistral-large'
                'base_url' => 'https://api.mistral.ai/v1',
            ],
            'groq' => [
                'key' => get_env('GROQ_API_KEY'),
                'model' => 'llama-3.1-8b-instant', // Or 'llama3-8b-8192', 'mixtral-8x7b-32768', etc.
                'base_url' => 'https://api.groq.com/openai/v1',
            ],
            'gemini' => [
                'key' => get_env('GEMINI_API_KEY'),
                'model' => 'gemini-2.0-flash', // Or 'gemini-2.5-flash', 'gemini-pro', etc.
                'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            ],
        ],
    ],
];
PHP;
    }
}
