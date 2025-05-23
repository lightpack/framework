<?php
namespace Lightpack\AI\Providers;

use Lightpack\AI\BaseProvider;

class Mistral extends BaseProvider
{
    /**
     * Generate a response from the Mistral API.
     */
    public function generate(array $params): array
    {
        $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        $useCache = $params['cache'] ?? true;
        $cacheTtl = $params['cache_ttl'] ?? $this->config->get('ai.cache_ttl',);
        $cacheKey = $this->generateCacheKey($params);

        // Check cache first
        if ($useCache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $result = $this->makeApiRequest(
            $this->config->get('ai.providers.mistral.endpoint'),
            $this->prepareRequestBody($params),
            $this->prepareHeaders(),
            $this->config->get('ai.http_timeout')
        );
        $output = $this->parseOutput($result);

        // Write to cache
        if ($useCache) {
            $this->cache->set($cacheKey, $output, $cacheTtl);
        }

        return $output;
    }

    /**
     * Prepare the request body for Mistral's API.
     */
    protected function prepareRequestBody(array $params): array
    {
        $messages = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        $messages = array_map(function($msg) {
            return [
                'role' => $msg['role'],
                'content' => is_array($msg['content']) ? implode("\n", $msg['content']) : $msg['content'],
            ];
        }, $messages);

        return [
            'model' => $params['model'] ?? $this->config->get('ai.providers.mistral.model', 'mistral-medium'),
            'messages' => $messages,
            'temperature' => $params['temperature'] ?? $this->config->get('ai.providers.mistral.temperature', 0.7),
            'max_tokens' => $params['max_tokens'] ?? $this->config->get('ai.providers.mistral.max_tokens', 256),
        ];
    }

    /**
     * Prepare headers for Mistral API.
     */
    protected function prepareHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->get('ai.providers.mistral.key'),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Parse the output from Mistral API response.
     */
    protected function parseOutput($result): array
    {
        $text = '';
        if (isset($result['choices'][0]['message']['content'])) {
            $text = $result['choices'][0]['message']['content'];
        }
        return [
            'text' => $text,
            'raw' => $result,
        ];
    }
}
