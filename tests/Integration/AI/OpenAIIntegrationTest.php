<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\Providers\OpenAI;
use Lightpack\Http\Http;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;

class OpenAIIntegrationTest extends TestCase
{
    private $openai;
    private $apiKey;

    protected function setUp(): void
    {
        $this->apiKey = getenv('OPENAI_API_KEY');
        
        if (!$this->apiKey) {
            $this->markTestSkipped('OPENAI_API_KEY environment variable not set');
        }

        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function($key, $default = null) {
            $map = [
                'ai.providers.openai.key' => $this->apiKey,
                'ai.providers.openai.model' => 'gpt-3.5-turbo',
                'ai.providers.openai.endpoint' => 'https://api.openai.com/v1/chat/completions',
                'ai.http_timeout' => 30,
                'ai.temperature' => 0.7,
                'ai.max_tokens' => 100,
                'ai.cache_ttl' => 3600,
            ];
            return $map[$key] ?? $default;
        });

        $this->openai = new OpenAI(
            new Http(),
            $this->createMock(Cache::class),
            $config
        );
    }

    public function testBasicCompletion()
    {
        $result = $this->openai->generate([
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
        $answer = $this->openai->ask('What is 2+2? Answer with just the number.');
        
        $this->assertIsString($answer);
        $this->assertNotEmpty($answer);
    }

    public function testTaskBuilderWithSchema()
    {
        $result = $this->openai->task()
            ->prompt('Extract: John Doe, age 30')
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
        $result = $this->openai->task()
            ->system('You are a helpful assistant that always responds in JSON format.')
            ->prompt('Say hello')
            ->expect(['message' => 'string'])
            ->run();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result['data']);
    }

    public function testArrayResponse()
    {
        $result = $this->openai->task()
            ->prompt('List exactly 3 programming languages as a JSON array')
            ->expect(['name' => 'string'])
            ->expectArray('language')
            ->run();

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertGreaterThanOrEqual(1, count($result['data']), 'Should return at least 1 item');
    }

    public function testTemperatureControl()
    {
        $result = $this->openai->generate([
            'prompt' => 'Say hello',
            'temperature' => 0.1,
            'max_tokens' => 50,
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['text']);
    }

    public function testMessageHistory()
    {
        $result = $this->openai->task()
            ->message('user', 'My name is Alice')
            ->message('assistant', 'Nice to meet you, Alice!')
            ->message('user', 'What is my name?')
            ->run();

        $this->assertStringContainsStringIgnoringCase('alice', strtolower($result['raw']));
    }

    public function testRequiredFieldValidation()
    {
        $result = $this->openai->task()
            ->prompt('Say hello')
            ->expect(['greeting' => 'string', 'name' => 'string'])
            ->required('greeting', 'name')
            ->run();

        // May succeed or fail depending on if model includes both fields
        $this->assertIsBool($result['success']);
        if (!$result['success']) {
            $this->assertNotEmpty($result['errors']);
        }
    }
}
