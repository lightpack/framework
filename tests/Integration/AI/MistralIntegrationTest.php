<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\Providers\Mistral;
use Lightpack\Http\Http;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;

class MistralIntegrationTest extends TestCase
{
    private $mistral;
    private $apiKey;

    protected function setUp(): void
    {
        $this->apiKey = getenv('MISTRAL_API_KEY');
        
        if (!$this->apiKey) {
            $this->markTestSkipped('MISTRAL_API_KEY environment variable not set');
        }

        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function($key, $default = null) {
            $map = [
                'ai.providers.mistral.key' => $this->apiKey,
                'ai.providers.mistral.model' => 'mistral-small-latest',
                'ai.providers.mistral.endpoint' => 'https://api.mistral.ai/v1/chat/completions',
                'ai.http_timeout' => 30,
                'ai.temperature' => 0.7,
                'ai.max_tokens' => 100,
                'ai.cache_ttl' => 3600,
            ];
            return $map[$key] ?? $default;
        });

        $this->mistral = new Mistral(
            new Http(),
            $this->createMock(Cache::class),
            $config
        );
    }

    public function testBasicCompletion()
    {
        $result = $this->mistral->generate([
            'prompt' => 'Say "Hello, World!" and nothing else.',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('text', $result);
        $this->assertNotEmpty($result['text']);
        $this->assertStringContainsStringIgnoringCase('hello', $result['text']);
    }

    public function testAskMethod()
    {
        $answer = $this->mistral->ask('What is 2+2? Answer with just the number.');
        
        $this->assertIsString($answer);
        $this->assertNotEmpty($answer);
    }

    public function testTaskBuilderWithSchema()
    {
        $result = $this->mistral->task()
            ->prompt('Extract: Emma Wilson, age 27')
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

    public function testArrayResponse()
    {
        $result = $this->mistral->task()
            ->prompt('List exactly 3 countries as a JSON array')
            ->expect(['name' => 'string'])
            ->expectArray('country')
            ->run();

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertGreaterThanOrEqual(1, count($result['data']), 'Should return at least 1 item');
    }

    public function testTemperatureControl()
    {
        $result = $this->mistral->generate([
            'prompt' => 'Say hello',
            'temperature' => 0.1,
            'max_tokens' => 50,
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['text']);
    }

    public function testMessageHistory()
    {
        $result = $this->mistral->task()
            ->message('user', 'My name is Frank')
            ->message('assistant', 'Nice to meet you, Frank!')
            ->message('user', 'What is my name?')
            ->run();

        $this->assertStringContainsStringIgnoringCase('frank', strtolower($result['raw']));
    }

    public function testSystemPrompt()
    {
        $result = $this->mistral->task()
            ->system('You are a helpful assistant that always responds in JSON format.')
            ->prompt('Say hello')
            ->expect(['message' => 'string'])
            ->run();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result['data']);
    }
}
