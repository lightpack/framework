<?php

namespace Lightpack\AI;

use Lightpack\Http\Http;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;
use Lightpack\AI\VectorSearch\VectorSearchInterface;
use Lightpack\AI\VectorSearch\InMemoryVectorSearch;

abstract class AI
{
    private ?VectorSearchInterface $vectorSearch = null;

    public function __construct(
        protected Http $http,
        protected Cache $cache,
        protected Config $config,
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
     */
    abstract public function generate(array $params): array;

    /**
     * Generate a streaming response from the provider.
     * 
     * Calls the provided callback for each chunk of text as it arrives.
     * This method does not return the complete response - use generate() for that.
     * 
     * @param array $params Same parameters as generate()
     * @param callable $onChunk Callback function: fn(string $textChunk) => void
     * @return void
     * @throws \Exception If streaming is not supported by the provider
     */
    abstract public function generateStream(array $params, callable $onChunk): void;

    /**
     * Start a fluent AI task builder for this provider.
     */
    public function task(): TaskBuilder
    {
        return new TaskBuilder($this);
    }

    /**
     * Simple Q&A: Ask a question and get a plain answer string.
     */
    public function ask(string $question): string
    {
        $result = $this->task()->prompt($question)->run();
        return $result['raw'] ?? '';
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
     * Find similar items using vector similarity search.
     * 
     * Uses the configured VectorSearchInterface implementation (defaults to in-memory).
     * For custom implementations (Qdrant, Meilisearch), use setVectorSearch().
     * 
     * @param array $queryEmbedding The query vector
     * @param mixed $target For in-memory: array of items. For vector DBs: collection name
     * @param int $limit Number of results to return
     * @param float $threshold Minimum similarity score (0-1)
     * @return array Sorted array of items with similarity scores
     */
    public function similar(array $queryEmbedding, mixed $target, int $limit = 5, float $threshold = 0.0): array
    {
        $search = $this->getVectorSearch();
        return $search->search($queryEmbedding, $target, $limit, ['threshold' => $threshold]);
    }

    /**
     * Set a custom vector search implementation.
     * Allows using external vector databases (Qdrant, Meilisearch, etc.)
     * 
     * @param VectorSearchInterface $search Custom search implementation
     * @return self For method chaining
     */
    public function setVectorSearch(VectorSearchInterface $search): self
    {
        $this->vectorSearch = $search;
        return $this;
    }

    /**
     * Get the vector search implementation (creates default if not set).
     * 
     * @return VectorSearchInterface
     */
    protected function getVectorSearch(): VectorSearchInterface
    {
        if ($this->vectorSearch === null) {
            $this->vectorSearch = new InMemoryVectorSearch();
        }
        return $this->vectorSearch;
    }

    /**
     * Calculate cosine similarity between two vectors.
     * Returns value between 0 (completely different) and 1 (identical).
     * 
     * Note: This is a utility method that delegates to
     * InMemoryVectorSearch. If you're using a custom VectorSearchInterface,
     * this method will throw an exception since vector databases
     * handle similarity calculations server-side.
     * 
     * @internal
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        $search = $this->getVectorSearch();
        
        if ($search instanceof InMemoryVectorSearch) {
            return $search->cosineSimilarity($a, $b);
        }
        
        throw new \BadMethodCallException(
            'cosineSimilarity() is only available with InMemoryVectorSearch. ' .
            'Vector databases calculate similarity server-side.'
        );
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

    protected function makeApiRequest(string $endpoint, array $body, array $headers = [], int $timeout = 10): array
    {
        $response = $this->http
            ->headers($headers)
            ->timeout($timeout)
            ->post($endpoint, $body);

        if ($response->failed()) {
            $errorMsg = $response->error() ?: 'HTTP error ' . $response->status();
            throw new \Exception(static::class . ' API error: ' . $errorMsg);
        }

        return json_decode($response->body(), true);
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
        $fields = ['model', 'messages', 'prompt', 'temperature', 'max_tokens', 'system'];
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

    /**
     * Detect if content is multimodal (structured with type keys).
     * 
     * Multimodal: [['type' => 'text', ...], ['type' => 'image_url', ...]]
     * Legacy array: ['line1', 'line2']
     * 
     * @param mixed $content The content to check
     * @return bool True if multimodal format detected
     */
    protected function isMultimodalContent(mixed $content): bool
    {
        return is_array($content) 
            && !empty($content) 
            && is_array($first = reset($content)) 
            && isset($first['type']);
    }

    /**
     * Normalize message content for API requests.
     * 
     * - Multimodal arrays (with 'type' keys) pass through unchanged
     * - Legacy string arrays get joined with newlines
     * - Strings pass through unchanged
     * 
     * @param mixed $content The content to normalize
     * @return mixed Normalized content
     */
    protected function normalizeContent(mixed $content): mixed
    {
        if (is_array($content) && !$this->isMultimodalContent($content)) {
            return implode("\n", $content);
        }
        
        return $content;
    }

    /**
     * Parse a data URL to extract MIME type and base64 data.
     * 
     * @param string $dataUrl The data URL (e.g., 'data:image/jpeg;base64,/9j/4AAQ...')
     * @return array|null Array with 'mime_type' and 'data' keys, or null if invalid
     */
    protected function parseDataUrl(string $dataUrl): ?array
    {
        if (!str_starts_with($dataUrl, 'data:')) {
            return null;
        }
        
        preg_match('/data:([^;]+);base64,(.+)/', $dataUrl, $matches);
        
        return $matches ? [
            'mime_type' => $matches[1],
            'data' => $matches[2]
        ] : null;
    }

    /**
     * Build a data URL from MIME type and base64 data.
     * 
     * @param string $mimeType The MIME type (e.g., 'image/jpeg')
     * @param string $base64Data The base64-encoded data
     * @return string The data URL
     */
    protected function buildDataUrl(string $mimeType, string $base64Data): string
    {
        return "data:{$mimeType};base64,{$base64Data}";
    }
}
