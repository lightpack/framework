<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\Providers\Groq;
use Lightpack\Http\Http;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;

class GroqIntegrationTest extends TestCase
{
    private $groq;
    private $apiKey;

    protected function setUp(): void
    {
        $this->apiKey = getenv('GROQ_API_KEY');
        
        if (!$this->apiKey) {
            $this->markTestSkipped('GROQ_API_KEY environment variable not set');
        }

        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function($key, $default = null) {
            $map = [
                'ai.providers.groq.key' => $this->apiKey,
                'ai.providers.groq.model' => 'llama-3.1-8b-instant',
                'ai.providers.groq.endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
                'ai.http_timeout' => 30,
                'ai.temperature' => 0.7,
                'ai.max_tokens' => 100,
                'ai.cache_ttl' => 3600,
            ];
            return $map[$key] ?? $default;
        });

        $this->groq = new Groq(
            new Http(),
            $this->createMock(Cache::class),
            $config
        );
    }

    public function testBasicCompletion()
    {
        $result = $this->groq->generate([
            'prompt' => 'Say "Hello, World!" and nothing else.',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('text', $result);
        $this->assertNotEmpty($result['text']);
        $this->assertStringContainsStringIgnoringCase('hello', $result['text']);
    }

    public function testAskMethod()
    {
        $answer = $this->groq->ask('What is 2+2? Answer with just the number.');
        
        $this->assertIsString($answer);
        $this->assertNotEmpty($answer);
    }

    public function testTaskBuilderWithSchema()
    {
        $result = $this->groq->task()
            ->prompt('Extract: Mike Brown, age 35')
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
        $result = $this->groq->task()
            ->system('You are a helpful assistant that always responds in JSON format.')
            ->prompt('Say hello')
            ->expect(['message' => 'string'])
            ->run();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result['data']);
    }

    public function testArrayResponse()
    {
        $result = $this->groq->task()
            ->prompt('List exactly 3 animals as a JSON array')
            ->expect(['name' => 'string'])
            ->expectArray('animal')
            ->run();

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertGreaterThanOrEqual(1, count($result['data']), 'Should return at least 1 item');
    }

    public function testTemperatureControl()
    {
        $result = $this->groq->generate([
            'prompt' => 'Say hello',
            'temperature' => 0.1,
            'max_tokens' => 50,
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['text']);
    }

    public function testMessageHistory()
    {
        $result = $this->groq->task()
            ->message('user', 'My name is David')
            ->message('assistant', 'Nice to meet you, David!')
            ->message('user', 'What is my name?')
            ->run();

        $this->assertStringContainsStringIgnoringCase('david', strtolower($result['raw']));
    }

    public function testFastInference()
    {
        // Groq is known for fast inference
        $start = microtime(true);
        
        $result = $this->groq->generate([
            'prompt' => 'Say hello',
        ]);
        
        $duration = microtime(true) - $start;
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result['text']);
        // Groq should typically respond in under 5 seconds
        $this->assertLessThan(5, $duration, 'Groq inference should be fast');
    }
}
