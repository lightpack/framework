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
     * Generate embedding vector(s) for text.
     * 
     * @param string|array $input Single text or array of texts
     * @param array $options Optional parameters (model, etc.)
     * @return array Single embedding vector or array of vectors
     */
    public function embed(string|array $input, array $options = []): array
    {
        return $this->generateEmbedding($input, $options);
    }

    /**
     * Find similar items to a query embedding.
     * 
     * @param array $queryEmbedding The query vector
     * @param array $items Array of items with 'embedding' key
     * @param int $limit Number of results to return
     * @param float $threshold Minimum similarity score (0-1)
     * @return array Sorted array of items with similarity scores
     */
    public function similar(array $queryEmbedding, array $items, int $limit = 5, float $threshold = 0.0): array
    {
        $similarities = [];
        
        foreach ($items as $id => $item) {
            $embedding = $item['embedding'] ?? $item;
            $score = $this->cosineSimilarity($queryEmbedding, $embedding);
            
            if ($score >= $threshold) {
                $similarities[$id] = [
                    'id' => $id,
                    'similarity' => $score,
                    'item' => $item,
                ];
            }
        }
        
        // Sort by similarity (highest first)
        usort($similarities, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        
        return array_slice($similarities, 0, $limit);
    }

    /**
     * Calculate cosine similarity between two vectors.
     * Returns value between 0 (completely different) and 1 (identical).
     * 
     * @param array $a First vector
     * @param array $b Second vector
     * @return float Similarity score (0-1)
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new \InvalidArgumentException('Vectors must have same dimensions');
        }

        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;
        
        for ($i = 0; $i < count($a); $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $magnitudeA += $a[$i] * $a[$i];
            $magnitudeB += $b[$i] * $b[$i];
        }
        
        $magnitude = sqrt($magnitudeA) * sqrt($magnitudeB);
        
        return $magnitude > 0 ? $dotProduct / $magnitude : 0;
    }

    /**
     * Provider-specific embedding implementation.
     * Override in provider classes that support embeddings.
     * 
     * @param string|array $input Single text or array of texts
     * @param array $options Optional parameters
     * @return array Single embedding vector or array of vectors
     */
    protected function generateEmbedding(string|array $input, array $options = []): array
    {
        throw new \Exception(static::class . ' does not support embeddings');
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

    /**
     * Execute API call with optional caching.
     * Handles cache check, API execution, and cache storage.
     * 
     * @param array $params Request parameters (must include cache settings)
     * @param callable $apiCall The actual API call to execute
     * @return array The API response
     */
    protected function executeWithCache(array $params, callable $apiCall): array
    {
        $useCache = $params['cache'] ?? false;
        $cacheTtl = $params['cache_ttl'] ?? $this->config->get('ai.cache_ttl', 3600);
        $cacheKey = $this->generateCacheKey($params);

        // Check cache first
        if ($useCache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        // Execute API call
        $output = $apiCall();

        // Store in cache
        if ($useCache) {
            $this->cache->set($cacheKey, $output, $cacheTtl);
        }

        return $output;
    }
}
