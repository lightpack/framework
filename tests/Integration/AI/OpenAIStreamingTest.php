<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\Providers\OpenAI;
use Lightpack\Http\Http;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;

class OpenAIStreamingTest extends TestCase
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
                'ai.providers.openai.model' => 'gpt-4o-mini',
                'ai.providers.openai.endpoint' => 'https://api.openai.com/v1/chat/completions',
                'ai.http_timeout' => 30,
                'ai.temperature' => 0.7,
                'ai.max_tokens' => 150,
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

    public function testBasicStreaming()
    {
        $chunks = [];
        $fullText = '';
        
        $this->openai->task()
            ->prompt('Count from 1 to 5, one number per line.')
            ->stream(function($chunk) use (&$chunks, &$fullText) {
                $chunks[] = $chunk;
                $fullText .= $chunk;
            });
        
        // Verify we received multiple chunks
        $this->assertGreaterThan(1, count($chunks), 'Should receive multiple chunks');
        
        // Verify full text is not empty
        $this->assertNotEmpty($fullText, 'Should receive text content');
        
        // Verify we got numbers (basic content check)
        $this->assertMatchesRegularExpression('/[1-5]/', $fullText, 'Should contain numbers');
        
        echo "\n\n=== Streaming Test Results ===\n";
        echo "Total chunks received: " . count($chunks) . "\n";
        echo "Full text length: " . strlen($fullText) . " characters\n";
        echo "Full text:\n" . $fullText . "\n";
        echo "==============================\n\n";
    }

    public function testStreamingWithSystemPrompt()
    {
        $chunks = [];
        $fullText = '';
        
        $this->openai->task()
            ->system('You are a helpful assistant. Be concise.')
            ->prompt('What is PHP?')
            ->stream(function($chunk) use (&$chunks, &$fullText) {
                $chunks[] = $chunk;
                $fullText .= $chunk;
            });
        
        $this->assertGreaterThan(1, count($chunks));
        $this->assertNotEmpty($fullText);
        $this->assertStringContainsStringIgnoringCase('php', $fullText);
    }

    public function testStreamingWithTemperature()
    {
        $chunks = [];
        
        $this->openai->task()
            ->prompt('Say hello')
            ->temperature(0.1)
            ->stream(function($chunk) use (&$chunks) {
                $chunks[] = $chunk;
            });
        
        $this->assertGreaterThan(0, count($chunks));
    }

    public function testStreamingRejectsAgentMode()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Streaming is not supported in agent mode');
        
        $this->openai->task()
            ->prompt('Test')
            ->loop(3)
            ->stream(fn($chunk) => null);
    }

    public function testStreamingRejectsTools()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Streaming is not supported with tools');
        
        $this->openai->task()
            ->prompt('Test')
            ->tool('test_tool', fn($p) => ['result' => 'data'])
            ->stream(fn($chunk) => null);
    }

    public function testStreamingRejectsSchemaExtraction()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Streaming is not supported with schema extraction');
        
        $this->openai->task()
            ->prompt('Test')
            ->expect(['name' => 'string'])
            ->stream(fn($chunk) => null);
    }

    public function testStreamingRejectsArrayExtraction()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Streaming is not supported with schema extraction');
        
        $this->openai->task()
            ->prompt('Test')
            ->expectArray('item')
            ->stream(fn($chunk) => null);
    }

    public function testStreamingProgressiveOutput()
    {
        $receivedAt = [];
        $startTime = microtime(true);
        
        $this->openai->task()
            ->prompt('Write a short 3-sentence story about a cat.')
            ->stream(function($chunk) use (&$receivedAt, $startTime) {
                $receivedAt[] = microtime(true) - $startTime;
            });
        
        // Verify chunks arrived over time (not all at once)
        $this->assertGreaterThan(2, count($receivedAt), 'Should receive multiple chunks');
        
        // Verify there's time difference between first and last chunk
        if (count($receivedAt) > 1) {
            $timeDiff = end($receivedAt) - $receivedAt[0];
            $this->assertGreaterThan(0, $timeDiff, 'Chunks should arrive progressively');
        }
    }
}
