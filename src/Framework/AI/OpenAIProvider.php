<?php
namespace Lightpack\AI;

use Lightpack\AI\ProviderInterface;
use Lightpack\Config\Config;
use Lightpack\Http\Http;
use Lightpack\Logger\Logger;
use Lightpack\Cache\Cache;

class OpenAIProvider implements ProviderInterface
{
    protected $config;
    protected $http;
    protected $logger;
    protected $cache;

    public function __construct(Config $config, Http $http, Logger $logger, Cache $cache)
    {
        $this->config = $config;
        $this->http = $http;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    public function generate(array $params)
    {
        $apiKey = $this->config->get('ai.providers.openai.key');
        $model = $params['model'] ?? $this->config->get('ai.providers.openai.model', 'gpt-3.5-turbo');
        $system = $params['system'] ?? '';
        $messages = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        $temperature = $params['temperature'] ?? $this->config->get('ai.providers.openai.temperature', 0.7);
        $maxTokens = $params['max_tokens'] ?? $this->config->get('ai.providers.openai.max_tokens', 256);
        $options = $params['options'] ?? [];
        $cacheKey = md5($model . json_encode($messages) . $temperature . $maxTokens);
        $useCache = $params['cache'] ?? true;
        // Use config default for cache_ttl, allow per-call override
        $cacheTtl = $params['cache_ttl'] ?? $this->config->get('ai.cache_ttl', 3600);

        // Check cache first (unless bypassed)
        if ($useCache && $this->cache) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
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

        // Use config default for http_timeout, allow per-call override
        $httpTimeout = $params['timeout'] ?? $this->config->get('ai.http_timeout', $this->config->get('ai.providers.openai.timeout', 15));
        $httpOptions = array_merge([
            'timeout' => $httpTimeout,
        ], $options);

        try {
            $response = $this->http
                ->headers($headers)
                ->options($httpOptions)
                ->post('https://api.openai.com/v1/chat/completions', $body);

            if ($response->failed()) {
                $errorMsg = $response->error() ?: 'HTTP error ' . $response->status();
                if ($this->logger) {
                    $this->logger->error('OpenAI API error: ' . $errorMsg);
                }
                throw new \Exception('OpenAI API error: ' . $errorMsg);
            }

            $result = json_decode($response->body(), true);
            $output = [
                'text' => $result['choices'][0]['message']['content'] ?? '',
                'finish_reason' => $result['choices'][0]['finish_reason'] ?? '',
                'usage' => $result['usage'] ?? [],
                'raw' => $result,
            ];

            // Write to cache unless bypassed
            if ($useCache && $this->cache) {
                $this->cache->set($cacheKey, $output, $cacheTtl);
            }
            return $output;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('OpenAI API error: ' . $e->getMessage());
            }
            throw $e;
        }
    }
}
