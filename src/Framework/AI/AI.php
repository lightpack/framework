<?php

namespace Lightpack\AI;

use Lightpack\Http\Http;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;
use Lightpack\Logger\Logger;

abstract class AI
{
    public function __construct(
        protected Http $http,
        protected Cache $cache,
        protected Config $config,
        protected Logger $logger,
    ) {}

    /**
     * Generate a completion or response from the provider.
     * 
     * @param array $params Supported keys (provider may use subset):
     *   - prompt: string (single prompt for simple completion)
     *   - system: string (system prompt/persona)
     *   - messages: array (conversation history, each with ['role' => ..., 'content' => ...])
     *   - temperature: float
     *   - max_tokens: int
     *   - model: string (model name)
     *   - timeout: int (seconds)
     *   - ... (provider-specific keys)
     *
     * @return array {
     *   @type string $text           The generated text/completion.
     *   @type string $finish_reason  Why the generation stopped (if available).
     *   @type array  $usage          Token usage stats (if available).
     *   @type mixed  $raw            The full raw provider response.
     * }
     *
     * @param array $params See interface docblock for supported keys.
     * @return array See interface docblock for return structure.
     */
    abstract public function generate(array $params);

    /**
     * Start a fluent AI task builder for this provider.
     */
    public function task()
    {
        return new AiTaskBuilder($this);
    }

    /**
     * Simple Q&A: Ask a question and get a plain answer string.
     */
    public function ask(string $question): string
    {
        $result = $this->task()->prompt($question)->run();
        return $result['raw'];
    }

    protected function makeApiRequest(string $endpoint, array $body, array $headers = [], int $timeout = 10)
    {
        try {
            $response = $this->http
                ->headers($headers)
                ->timeout($timeout)
                ->post($endpoint, $body);

            if ($response->failed()) {
                $errorMsg = $response->error() ?: 'HTTP error ' . $response->status();
                $this->logger->error(static::class . ' API response: ' . $response->body());
                throw new \Exception(static::class . ' API error: ' . $errorMsg);
            }

            return json_decode($response->body(), true);
        } catch (\Exception $e) {
            $this->logger->error(static::class . ' API error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate a robust, order-independent cache key from selected params.
     * @param array $params The input parameters to consider
     * @param array $fields The list of keys to include in the cache key
     * @return string
     */
    protected function generateCacheKey(array $params): string
    {
        $data = [];
        $fields = ['model', 'messages', 'temperature', 'max_tokens', 'system'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                $data[$field] = $params[$field];
            }
        }
        ksort($data);
        return md5(json_encode($data));
    }
}
