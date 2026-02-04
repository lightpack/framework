<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\Providers\Anthropic;
use Lightpack\Http\Http;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;

class AnthropicIntegrationTest extends TestCase
{
    private $anthropic;
    private $apiKey;

    protected function setUp(): void
    {
        $this->apiKey = getenv('ANTHROPIC_API_KEY');
        
        if (!$this->apiKey) {
            $this->markTestSkipped('ANTHROPIC_API_KEY environment variable not set');
        }
        
        // Avoid rate limiting - wait 5 seconds between tests
        sleep(5);

        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function($key, $default = null) {
            $map = [
                'ai.providers.anthropic.key' => $this->apiKey,
                'ai.providers.anthropic.model' => 'claude-sonnet-4-5',
                'ai.providers.anthropic.endpoint' => 'https://api.anthropic.com/v1/messages',
                'ai.providers.anthropic.version' => '2023-06-01',
                'ai.http_timeout' => 30,
                'ai.temperature' => 0.7,
                'ai.max_tokens' => 100,
                'ai.cache_ttl' => 3600,
            ];
            return $map[$key] ?? $default;
        });

        $this->anthropic = new Anthropic(
            new Http(),
            $this->createMock(Cache::class),
            $config
        );
    }

    public function testBasicCompletion()
    {
        $result = $this->anthropic->generate([
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
        $answer = $this->anthropic->ask('What is 2+2? Answer with just the number.');
        
        $this->assertIsString($answer);
        $this->assertNotEmpty($answer);
    }

    public function testTaskBuilderWithSchema()
    {
        $result = $this->anthropic->task()
            ->prompt('Extract: Jane Smith, age 25')
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
        $result = $this->anthropic->task()
            ->system('You are a helpful assistant that always responds in JSON format.')
            ->prompt('Say hello')
            ->expect(['message' => 'string'])
            ->run();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result['data']);
    }

    public function testArrayResponse()
    {
        $result = $this->anthropic->task()
            ->prompt('List exactly 3 colors as a JSON array')
            ->expect(['name' => 'string'])
            ->expectArray('color')
            ->run();

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertGreaterThanOrEqual(1, count($result['data']), 'Should return at least 1 item');
    }

    public function testTemperatureControl()
    {
        $result = $this->anthropic->generate([
            'prompt' => 'Say hello',
            'temperature' => 0.1,
            'max_tokens' => 50,
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['text']);
    }

    public function testMessageHistory()
    {
        $result = $this->anthropic->task()
            ->message('user', 'My name is Bob')
            ->message('assistant', 'Nice to meet you, Bob!')
            ->message('user', 'What is my name?')
            ->run();

        $this->assertStringContainsStringIgnoringCase('bob', strtolower($result['raw']));
    }
}
