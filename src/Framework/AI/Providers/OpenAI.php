<?php
namespace Lightpack\AI\Providers;

use Lightpack\AI\AI;
use Lightpack\AI\Traits\StreamsResponses;
use Lightpack\Http\Response;

class OpenAI extends AI
{
    use StreamsResponses;
    public function generate(array $params)
    {
        $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        $useCache = $params['cache'] ?? true;
        $cacheTtl = $params['cache_ttl'] ?? $this->config->get('ai.cache_ttl', 3600);
        $cacheKey = $this->generateCacheKey($params);

        // Check cache first (unless bypassed)
        if ($useCache) {
            if ($this->cache->has($cacheKey)) {
                return $this->cache->get($cacheKey);
            }
        }

        $result = $this->makeApiRequest(
            $this->config->get('ai.providers.openai.endpoint'),
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
        $messages = $params['messages'];
        
        if (!empty($params['system'])) {
            array_unshift($messages, ['role' => 'system', 'content' => $params['system']]);
        }

        $messages = array_map(function($msg) {
            return [
                'role' => $msg['role'],
                'content' => is_array($msg['content']) ? implode("\n", $msg['content']) : $msg['content'],
            ];
        }, $messages);
        
        return [
            'messages' => $messages,
            'model' => $params['model'] ?? $this->config->get('ai.providers.openai.model'),
            'temperature' => $params['temperature'] ?? $this->config->get('ai.temperature'),
            'max_tokens' => $params['max_tokens'] ?? $this->config->get('ai.max_tokens'),
        ];
    }

    protected function prepareHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->get('ai.providers.openai.key'),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Generate streaming completion using Server-Sent Events.
     * Streams tokens as they are generated for real-time display.
     * 
     * @param array $params Generation parameters
     * @return Response Response configured for SSE streaming
     */
    public function generateStream(array $params): Response
    {
        $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        
        return $this->createStreamingResponse(
            $this->config->get('ai.providers.openai.endpoint'),
            $this->prepareRequestBody($params),
            $this->prepareHeaders(),
            $this->config->get('ai.http_timeout', 30),
            fn($line) => $this->parseOpenAIStreamLine($line)
        );
    }
}
