<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\Providers\Gemini;
use Lightpack\AI\Providers\OpenAI;
use Lightpack\AI\Providers\Mistral;
use Lightpack\Http\Http;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;

class EmbeddingIntegrationTest extends TestCase
{
    public function testGeminiEmbedding()
    {
        $apiKey = getenv('GEMINI_API_KEY');
        
        if (!$apiKey) {
            $this->markTestSkipped('GEMINI_API_KEY environment variable not set');
        }

        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function($key, $default = null) use ($apiKey) {
            $map = [
                'ai.providers.gemini.key' => $apiKey,
                'ai.providers.gemini.embedding_model' => 'text-embedding-004',
                'ai.http_timeout' => 30,
            ];
            return $map[$key] ?? $default;
        });

        $gemini = new Gemini(
            new Http(),
            $this->createMock(Cache::class),
            $config
        );

        // Test single embedding
        $embedding = $gemini->embed('Hello world');
        
        $this->assertIsArray($embedding);
        $this->assertGreaterThan(700, count($embedding)); // Gemini uses 768 dimensions
        $this->assertLessThan(800, count($embedding));
        $this->assertIsFloat($embedding[0]);
    }

    public function testGeminiBatchEmbedding()
    {
        $apiKey = getenv('GEMINI_API_KEY');
        
        if (!$apiKey) {
            $this->markTestSkipped('GEMINI_API_KEY environment variable not set');
        }

        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function($key, $default = null) use ($apiKey) {
            $map = [
                'ai.providers.gemini.key' => $apiKey,
                'ai.providers.gemini.embedding_model' => 'text-embedding-004',
                'ai.http_timeout' => 30,
            ];
            return $map[$key] ?? $default;
        });

        $gemini = new Gemini(
            new Http(),
            $this->createMock(Cache::class),
            $config
        );

        // Test batch embedding
        $embeddings = $gemini->embed([
            'The cat sat on the mat',
            'A feline rested on the rug',
            'Python is a programming language'
        ]);
        
        $this->assertIsArray($embeddings);
        $this->assertCount(3, $embeddings);
        
        // Similar sentences should have high similarity
        $similarity = $gemini->cosineSimilarity($embeddings[0], $embeddings[1]);
        $this->assertGreaterThan(0.7, $similarity, 'Similar sentences should have high similarity');
        
        // Different sentences should have lower similarity
        $similarity2 = $gemini->cosineSimilarity($embeddings[0], $embeddings[2]);
        $this->assertLessThan($similarity, $similarity2, 'Different topics should have lower similarity');
    }

    public function testSemanticSearch()
    {
        $apiKey = getenv('GEMINI_API_KEY');
        
        if (!$apiKey) {
            $this->markTestSkipped('GEMINI_API_KEY environment variable not set');
        }

        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function($key, $default = null) use ($apiKey) {
            $map = [
                'ai.providers.gemini.key' => $apiKey,
                'ai.providers.gemini.embedding_model' => 'text-embedding-004',
                'ai.http_timeout' => 30,
            ];
            return $map[$key] ?? $default;
        });

        $gemini = new Gemini(
            new Http(),
            $this->createMock(Cache::class),
            $config
        );

        // Create knowledge base
        $documents = [
            'doc1' => [
                'title' => 'Lightpack Framework',
                'content' => 'Lightpack is a PHP framework for web development',
                'embedding' => $gemini->embed('Lightpack is a PHP framework for web development')
            ],
            'doc2' => [
                'title' => 'Python Guide',
                'content' => 'Python is a high-level programming language',
                'embedding' => $gemini->embed('Python is a high-level programming language')
            ],
            'doc3' => [
                'title' => 'PHP Basics',
                'content' => 'PHP is a server-side scripting language for web',
                'embedding' => $gemini->embed('PHP is a server-side scripting language for web')
            ],
        ];

        // Search query
        $query = 'What is Lightpack?';
        $queryEmbedding = $gemini->embed($query);
        
        $results = $gemini->similar($queryEmbedding, $documents, limit: 2);
        
        $this->assertCount(2, $results);
        
        // First result should be about Lightpack
        $this->assertEquals('doc1', $results[0]['id']);
        $this->assertGreaterThan(0.5, $results[0]['similarity']);
        
        // Second result should be about PHP (related)
        $this->assertEquals('doc3', $results[1]['id']);
    }

    public function testOpenAIEmbedding()
    {
        $apiKey = getenv('OPENAI_API_KEY');
        
        if (!$apiKey) {
            $this->markTestSkipped('OPENAI_API_KEY environment variable not set');
        }

        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function($key, $default = null) use ($apiKey) {
            $map = [
                'ai.providers.openai.key' => $apiKey,
                'ai.providers.openai.embedding_model' => 'text-embedding-3-small',
                'ai.http_timeout' => 30,
            ];
            return $map[$key] ?? $default;
        });

        $openai = new OpenAI(
            new Http(),
            $this->createMock(Cache::class),
            $config
        );

        $embedding = $openai->embed('Hello world');
        
        $this->assertIsArray($embedding);
        $this->assertEquals(1536, count($embedding)); // OpenAI text-embedding-3-small uses 1536 dimensions
        $this->assertIsFloat($embedding[0]);
    }

    public function testMistralEmbedding()
    {
        $apiKey = getenv('MISTRAL_API_KEY');
        
        if (!$apiKey) {
            $this->markTestSkipped('MISTRAL_API_KEY environment variable not set');
        }

        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function($key, $default = null) use ($apiKey) {
            $map = [
                'ai.providers.mistral.key' => $apiKey,
                'ai.providers.mistral.embedding_model' => 'mistral-embed',
                'ai.http_timeout' => 30,
            ];
            return $map[$key] ?? $default;
        });

        $mistral = new Mistral(
            new Http(),
            $this->createMock(Cache::class),
            $config
        );

        $embedding = $mistral->embed('Hello world');
        
        $this->assertIsArray($embedding);
        $this->assertEquals(1024, count($embedding)); // Mistral uses 1024 dimensions
        $this->assertIsFloat($embedding[0]);
    }
}
