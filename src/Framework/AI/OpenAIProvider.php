<?php
namespace Lightpack\AI;

class OpenAIProvider extends BaseProvider
{
    public function generate(array $params)
    {
        $apiKey = $this->config->get('ai.providers.openai.key');
        $model = $params['model'] ?? $this->config->get('ai.providers.openai.model', 'gpt-3.5-turbo');
        $system = $params['system'] ?? '';
        $messages = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        $temperature = $params['temperature'] ?? $this->config->get('ai.providers.openai.temperature', 0.7);
        $maxTokens = $params['max_tokens'] ?? $this->config->get('ai.providers.openai.max_tokens', 256);
        $cacheKey = md5($model . json_encode($messages) . $temperature . $maxTokens);
        $useCache = $params['cache'] ?? true;
        $cacheTtl = $params['cache_ttl'] ?? $this->config->get('ai.cache_ttl', 3600);

        // Check cache first (unless bypassed)
        if ($useCache) {
            if ($this->cache->has($cacheKey)) {
                return $this->cache->get($cacheKey);
            }
        }

        $body = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        if ($system) {
            $body['system'] = $system;
        }

        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ];

        // Use endpoint from config, allow override
        $endpoint = $this->config->get('ai.providers.openai.endpoint_chat', 'https://api.openai.com/v1/chat/completions');

        try {
            $response = $this->http
                ->headers($headers)
                ->timeout($this->config->get('ai.http_timeout', 10))
                ->post($endpoint, $body);

            if ($response->failed()) {
                $errorMsg = $response->error() ?: 'HTTP error ' . $response->status();
                $this->logger->error('OpenAI API error: ' . $errorMsg);
                throw new \Exception('OpenAI API error: ' . $errorMsg);
            }

            $result = json_decode($response->body(), true);
            $choice = $result['choices'][0] ?? [];
            $output = [
                'text' => $choice['message']['content'] ?? '',
                'finish_reason' => $choice['finish_reason'] ?? '',
                'usage' => $result['usage'] ?? [],
                'raw' => $result,
            ];
            // Write to cache unless bypassed
            if ($useCache) {
                $this->cache->set($cacheKey, $output, $cacheTtl);
            }
            return $output;
        } catch (\Exception $e) {
            $this->logger->error('OpenAI API error: ' . $e->getMessage());
            throw $e;
        }
    }
}
