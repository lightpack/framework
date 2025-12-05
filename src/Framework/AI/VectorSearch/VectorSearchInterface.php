<?php

namespace Lightpack\AI\VectorSearch;

/**
 * Interface for vector search implementations.
 * 
 * Allows pluggable vector search backends (in-memory, Qdrant, Meilisearch, etc.)
 * while maintaining a consistent API.
 */
interface VectorSearchInterface
{
    /**
     * Search for similar items using vector similarity.
     * 
     * @param array $queryEmbedding The query vector to search with
     * @param mixed $target For in-memory: array of items. For vector DBs: collection name
     * @param int $limit Maximum number of results to return
     * @param array $options Implementation-specific options (threshold, filters, etc.)
     * @return array Array of results with 'id', 'similarity', and 'item' keys
     */
    public function search(array $queryEmbedding, mixed $target, int $limit = 5, array $options = []): array;
}
