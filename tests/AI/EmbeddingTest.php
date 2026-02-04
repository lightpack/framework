<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\Providers\Gemini;
use Lightpack\Http\Http;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;

class EmbeddingTest extends TestCase
{
    private $ai;

    protected function setUp(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function($key, $default = null) {
            $map = [
                'ai.providers.gemini.key' => 'test-key',
                'ai.providers.gemini.embedding_model' => 'text-embedding-004',
                'ai.http_timeout' => 15,
            ];
            return $map[$key] ?? $default;
        });

        // Use anonymous class to mock embedding responses
        $this->ai = new class(
            new Http(),
            $this->createMock(Cache::class),
            $config
        ) extends Gemini {
            protected function generateEmbedding(string|array $input, array $options = []): array
            {
                // Single text
                if (is_string($input)) {
                    return array_fill(0, 768, 0.5);
                }
                
                // Batch
                return array_map(fn($text) => array_fill(0, 768, 0.5), $input);
            }
        };
    }

    public function testEmbedSingleText()
    {
        $embedding = $this->ai->embed('Hello world');
        
        $this->assertIsArray($embedding);
        $this->assertEquals(768, count($embedding));
        $this->assertIsFloat($embedding[0]);
    }

    public function testEmbedBatch()
    {
        $embeddings = $this->ai->embed([
            'First text',
            'Second text',
            'Third text'
        ]);
        
        $this->assertIsArray($embeddings);
        $this->assertCount(3, $embeddings);
        $this->assertEquals(768, count($embeddings[0]));
    }

    public function testCosineSimilarity()
    {
        $vec1 = [1.0, 0.0, 0.0];
        $vec2 = [1.0, 0.0, 0.0];
        $vec3 = [0.0, 1.0, 0.0];
        
        // Identical vectors
        $similarity = $this->ai->cosineSimilarity($vec1, $vec2);
        $this->assertEquals(1.0, $similarity);
        
        // Orthogonal vectors
        $similarity = $this->ai->cosineSimilarity($vec1, $vec3);
        $this->assertEquals(0.0, $similarity);
    }

    public function testCosineSimilarityThrowsOnDifferentDimensions()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vectors must have same dimensions');
        
        $vec1 = [1.0, 0.0];
        $vec2 = [1.0, 0.0, 0.0];
        
        $this->ai->cosineSimilarity($vec1, $vec2);
    }

    public function testFindSimilar()
    {
        $query = [1.0, 0.0, 0.0];
        
        $items = [
            'doc1' => ['embedding' => [1.0, 0.0, 0.0]],  // Identical
            'doc2' => ['embedding' => [0.9, 0.1, 0.0]],  // Similar
            'doc3' => ['embedding' => [0.0, 1.0, 0.0]],  // Different
        ];
        
        $results = $this->ai->similar($query, $items, limit: 2);
        
        $this->assertCount(2, $results);
        $this->assertEquals('doc1', $results[0]['id']);
        $this->assertGreaterThan(0.9, $results[0]['similarity']);
        $this->assertEquals('doc2', $results[1]['id']);
    }

    public function testFindSimilarWithThreshold()
    {
        $query = [1.0, 0.0, 0.0];
        
        $items = [
            'doc1' => ['embedding' => [1.0, 0.0, 0.0]],   // similarity = 1.0
            'doc2' => ['embedding' => [0.5, 0.5, 0.0]],   // similarity < 0.9
            'doc3' => ['embedding' => [0.0, 1.0, 0.0]],   // similarity = 0.0
        ];
        
        $results = $this->ai->similar($query, $items, limit: 10, threshold: 0.9);
        
        // Only doc1 should match
        $this->assertCount(1, $results);
        $this->assertEquals('doc1', $results[0]['id']);
    }

    public function testFindSimilarWithDirectVectors()
    {
        $query = [1.0, 0.0, 0.0];
        
        // Items can be just vectors (no 'embedding' key)
        $items = [
            'vec1' => [1.0, 0.0, 0.0],
            'vec2' => [0.0, 1.0, 0.0],
        ];
        
        $results = $this->ai->similar($query, $items, limit: 1);
        
        $this->assertCount(1, $results);
        $this->assertEquals('vec1', $results[0]['id']);
    }

    public function testSemanticSearch()
    {
        // Real-world example: semantic search
        $documents = [
            'doc1' => [
                'text' => 'Lightpack is a PHP framework',
                'embedding' => $this->ai->embed('Lightpack is a PHP framework')
            ],
            'doc2' => [
                'text' => 'Python is a programming language',
                'embedding' => $this->ai->embed('Python is a programming language')
            ],
            'doc3' => [
                'text' => 'PHP web development framework',
                'embedding' => $this->ai->embed('PHP web development framework')
            ],
        ];
        
        $query = 'What is Lightpack?';
        $queryEmbedding = $this->ai->embed($query);
        
        $results = $this->ai->similar($queryEmbedding, $documents, limit: 2);
        
        $this->assertCount(2, $results);
        $this->assertArrayHasKey('similarity', $results[0]);
        $this->assertArrayHasKey('item', $results[0]);
    }
}
