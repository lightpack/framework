<?php

namespace Lightpack\AI\VectorSearch;

/**
 * In-memory vector search implementation using brute-force cosine similarity.
 * 
 * This implementation works great for small to moderate datasets (up to 3-5K documents). 
 * For larger datasets or high traffic, consider using a vector database (Qdrant, Meilisearch).
 */ 
class InMemoryVectorSearch implements VectorSearchInterface
{
    /**
     * Search for similar items using cosine similarity.
     * 
     * NOTE: This is an O(n) brute-force in-memory search.
     * 
     * @param array $queryEmbedding The query vector
     * @param mixed $target Array of items with 'embedding' key
     * @param int $limit Number of results to return
     * @param array $options Options: 'threshold' (float, default 0.0)
     * @return array Sorted array of items with similarity scores
     */
    public function search(array $queryEmbedding, mixed $target, int $limit = 5, array $options = []): array
    {
        $items = $target;
        $threshold = $options['threshold'] ?? 0.0;
        $similarities = [];
        
        foreach ($items as $id => $item) {
            $embedding = $item['embedding'] ?? $item;
            $score = $this->cosineSimilarity($queryEmbedding, $embedding);
            
            if ($score >= $threshold) {
                $similarities[] = [
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
}
