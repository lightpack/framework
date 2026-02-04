<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\Providers\Gemini;
use Lightpack\Http\Http;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;

class GeminiIntegrationTest extends TestCase
{
    private $gemini;
    private $apiKey;

    protected function setUp(): void
    {
        $this->apiKey = getenv('GEMINI_API_KEY');
        
        if (!$this->apiKey) {
            $this->markTestSkipped('GEMINI_API_KEY environment variable not set');
        }

        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function($key, $default = null) {
            $map = [
                'ai.providers.gemini.key' => $this->apiKey,
                'ai.providers.gemini.model' => 'gemini-2.0-flash',
                'ai.providers.gemini.endpoint' => 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions',
                'ai.http_timeout' => 30,
                'ai.temperature' => 0.7,
                'ai.max_tokens' => 100,
                'ai.cache_ttl' => 3600,
            ];
            return $map[$key] ?? $default;
        });

        $this->gemini = new Gemini(
            new Http(),
            $this->createMock(Cache::class),
            $config
        );
    }

    public function testBasicCompletion()
    {
        $result = $this->gemini->generate([
            'prompt' => 'Say "Hello, World!" and nothing else.',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('finish_reason', $result);
        $this->assertArrayHasKey('usage', $result);
        $this->assertNotEmpty($result['text']);
        $this->assertStringContainsStringIgnoringCase('hello', $result['text']);
    }

    public function testAskMethod()
    {
        $answer = $this->gemini->ask('What is 2+2? Answer with just the number.');
        
        $this->assertIsString($answer);
        $this->assertNotEmpty($answer);
    }

    public function testTaskBuilderWithSchema()
    {
        $result = $this->gemini->task()
            ->prompt('Extract: Sarah Johnson, age 28')
            ->expect([
                'name' => 'string',
                'age' => 'int',
            ])
            ->run();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('name', $result['data']);
        $this->assertArrayHasKey('age', $result['data']);
        $this->assertIsString($result['data']['name']);
        $this->assertIsInt($result['data']['age']);
    }

    public function testSystemPrompt()
    {
        $result = $this->gemini->task()
            ->system('You are a helpful assistant that always responds in JSON format.')
            ->prompt('Say hello')
            ->expect(['message' => 'string'])
            ->run();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result['data']);
    }

    public function testArrayResponse()
    {
        $result = $this->gemini->task()
            ->prompt('List exactly 3 fruits as a JSON array')
            ->expect(['name' => 'string'])
            ->expectArray('fruit')
            ->run();

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertGreaterThanOrEqual(1, count($result['data']), 'Should return at least 1 item');
    }

    public function testTemperatureControl()
    {
        $result = $this->gemini->generate([
            'prompt' => 'Say hello',
            'temperature' => 0.1,
            'max_tokens' => 50,
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['text']);
    }

    public function testMessageHistory()
    {
        $result = $this->gemini->task()
            ->message('user', 'My name is Charlie')
            ->message('assistant', 'Nice to meet you, Charlie!')
            ->message('user', 'What is my name?')
            ->run();

        $this->assertStringContainsStringIgnoringCase('charlie', strtolower($result['raw']));
    }

    public function testMultipleModels()
    {
        // Test with different model
        $result = $this->gemini->generate([
            'prompt' => 'Say hello',
            'model' => 'gemini-2.5-flash',
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['text']);
    }

    public function testOpenAICompatibility()
    {
        // Verify OpenAI-compatible response structure
        $result = $this->gemini->generate([
            'prompt' => 'Test',
        ]);

        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('finish_reason', $result);
        $this->assertArrayHasKey('usage', $result);
        $this->assertArrayHasKey('raw', $result);
        
        // Verify raw response has OpenAI structure
        $raw = $result['raw'];
        $this->assertArrayHasKey('choices', $raw);
        $this->assertArrayHasKey('model', $raw);
    }
}
