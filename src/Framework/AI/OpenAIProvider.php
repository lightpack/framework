<?php
namespace Lightpack\AI;

class OpenAIProvider extends BaseProvider
{
    public function generate(array $params)
    {
        $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        $useCache = $params['cache'] ?? true;
        $cacheTtl = $params['cache_ttl'] ?? $this->config->get('ai.cache_ttl', 3600);
        $cacheKey = md5($params['model'] . json_encode($params['messages']) . $params['temperature'] . $params['max_tokens']);

        // Check cache first (unless bypassed)
        if ($useCache) {
            if ($this->cache->has($cacheKey)) {
                return $this->cache->get($cacheKey);
            }
        }

        $result = $this->makeApiRequest(
            $this->config->get('ai.providers.openai.endpoint_chat'),
            $this->prepareRequestBody($params), 
            $this->prepareHeaders(), 
            $this->config->get('ai.http_timeout')
        );
        $output = $this->parseOutput($result);
        
        // Write to cache unless bypassed
        if ($useCache) {
            $this->cache->set($cacheKey, $output, $cacheTtl);
        }

        return $output;
    }

    protected function parseOutput(array $result): array
    {
        $choice = $result['choices'][0] ?? [];

        return [
            'text' => $choice['message']['content'] ?? '',
            'finish_reason' => $choice['finish_reason'] ?? '',
            'usage' => $result['usage'] ?? [],
            'raw' => $result,
        ];
    }

    protected function prepareRequestBody(array $params): array
    {
        $model = $params['model'] ?? $this->config->get('ai.providers.openai.model', 'gpt-3.5-turbo');
        $system = $params['system'] ?? '';
        $messages = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        $temperature = $params['temperature'] ?? $this->config->get('ai.providers.openai.temperature', 0.7);
        $maxTokens = $params['max_tokens'] ?? $this->config->get('ai.providers.openai.max_tokens', 256);
        $body = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        if ($system) {
            $body['system'] = $system;
        }

        return $body;
    }

    protected function prepareHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->get('ai.providers.openai.key'),
            'Content-Type' => 'application/json',
        ];
    }
}
