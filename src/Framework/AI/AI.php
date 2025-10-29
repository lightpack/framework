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
        return new TaskBuilder($this);
    }

    /**
     * Simple Q&A: Ask a question and get a plain answer string.
     */
    public function ask(string $question): string
    {
        $result = $this->task()->prompt($question)->run();
        return $result['raw'];
    }

    /**
     * Stream a question response in real-time using Server-Sent Events.
     * Returns a Response object configured for SSE streaming.
     * 
     * @param string $question The question to ask
     * @param array $options Optional parameters (model, temperature, etc.)
     * @return \Lightpack\Http\Response Response configured for SSE streaming
     */
    public function askStream(string $question, array $options = [])
    {
        $params = array_merge(['prompt' => $question], $options);
        return $this->generateStream($params);
    }

    /**
     * Generate streaming completion using Server-Sent Events.
     * Must be implemented by each provider to support streaming.
     * 
     * @param array $params Generation parameters
     * @return \Lightpack\Http\Response Response configured for SSE streaming
     */
    abstract public function generateStream(array $params);

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

    /**
     * Make a streaming API request using Server-Sent Events.
     * Yields chunks of data as they arrive from the provider.
     * 
     * @param string $endpoint API endpoint URL
     * @param array $body Request body
     * @param array $headers Request headers
     * @param int $timeout Request timeout in seconds
     * @return \Generator Yields SSE data chunks
     */
    protected function makeStreamingRequest(string $endpoint, array $body, array $headers = [], int $timeout = 30)
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => array_merge(
                array_map(fn($k, $v) => "$k: $v", array_keys($headers), $headers),
                ['Accept: text/event-stream']
            ),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_WRITEFUNCTION => function($curl, $data) {
                echo $data;
                flush();
                return strlen($data);
            },
        ]);
        
        curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            $this->logger->error(static::class . ' streaming error: ' . $error);
            throw new \Exception(static::class . ' streaming error: ' . $error);
        }
        
        curl_close($ch);
    }
}
