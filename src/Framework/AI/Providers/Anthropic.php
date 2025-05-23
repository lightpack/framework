<?php
namespace Lightpack\AI\Providers;

use Lightpack\AI\AI;

class Anthropic extends AI
{
    public function generate(array $params)
    {
        $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        $useCache = $params['cache'] ?? true;
        $cacheTtl = $params['cache_ttl'] ?? $this->config->get('ai.cache_ttl');
        $cacheKey = $this->generateCacheKey($params);

        // Check cache first (unless bypassed)
        if ($useCache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $result = $this->makeApiRequest(
            $this->config->get('ai.providers.anthropic.endpoint'),
            $this->prepareRequestBody($params),
            $this->prepareHeaders(),
            $this->config->get('ai.http_timeout', 10)
        );
        $output = $this->parseOutput($result);

        if ($useCache) {
            $this->cache->set($cacheKey, $output, $cacheTtl);
        }

        return $output;
    }

    /**
     * Prepare the request body for Anthropic's API.
     * - 'system' is a top-level key.
     * - 'messages' is an array of {role: 'user'|'assistant', content: string}.
     * - 'content' is a string, not an array.
     */
    protected function prepareRequestBody(array $params): array
    {
        $messages = $params['messages'];

        // Remove any 'system' role messages (Anthropic only accepts 'user' and 'assistant')
        $messages = array_filter($messages, function($msg) {
            return $msg['role'] !== 'system';
        });
        $messages = array_values($messages); // reindex

        $messages = array_map(function($msg) {
            return [
                'role' => $msg['role'],
                'content' => is_array($msg['content']) ? implode("\n", $msg['content']) : $msg['content'],
            ];
        }, $messages);

        return [
            'system' => $params['system'] ?? '',
            'messages' => $messages,
            'model' => $params['model'] ?? $this->config->get('ai.providers.anthropic.model'),
            'temperature' => $params['temperature'] ?? $this->config->get('ai.providers.anthropic.temperature'),
            'max_tokens' => $params['max_tokens'] ?? $this->config->get('ai.providers.anthropic.max_tokens'),
        ];
    }

    /**
     * Prepare headers for Anthropic API.
     * Uses x-api-key, not Authorization.
     */
    protected function prepareHeaders(): array
    {
        return [
            'anthropic-version' => $this->config->get('ai.providers.anthropic.version'),
            'x-api-key' => $this->config->get('ai.providers.anthropic.key'),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Parse the Anthropic API response.
     * - Main text is in content[0]['text']
     * - Stop reason and usage are top-level keys
     */
    protected function parseOutput(array $result): array
    {
        $content = '';
        if (isset($result['content']) && is_array($result['content']) && isset($result['content'][0]['text'])) {
            $content = $result['content'][0]['text'];
        }
        return [
            'text' => $content,
            'finish_reason' => $result['stop_reason'] ?? '',
            'usage' => $result['usage'] ?? [],
            'raw' => $result,
        ];
    }
}
