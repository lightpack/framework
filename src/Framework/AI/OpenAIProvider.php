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
        $prompt = $params['prompt'] ?? '';
        $temperature = $params['temperature'] ?? $this->config->get('ai.providers.openai.temperature', 0.7);
        $maxTokens = $params['max_tokens'] ?? $this->config->get('ai.providers.openai.max_tokens', 256);
        $cacheKey = md5($model . $prompt . $temperature . $maxTokens);

        // Check cache first
        if ($this->cache) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ];

        try {
            $response = $this->http
                ->headers($headers)
                ->timeout($this->config->get('ai.providers.openai.timeout', 15))
                ->post('https://api.openai.com/v1/chat/completions', $body);

            if ($response->failed()) {
                $errorMsg = $response->error() ?: 'HTTP error ' . $response->status();
                if ($this->logger) {
                    $this->logger->error('OpenAI API error: ' . $errorMsg);
                }
                throw new \Exception('OpenAI API error: ' . $errorMsg);
            }

            $result = json_decode($response->body(), true);
            $output = $result['choices'][0]['message']['content'] ?? '';
            if ($this->cache) {
                $this->cache->set($cacheKey, $output, 3600);
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
