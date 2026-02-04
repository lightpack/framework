<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\Providers\Anthropic;
use Lightpack\Http\Http;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;

class AnthropicStreamingTest extends TestCase
{
    private $anthropic;
    private $apiKey;

    protected function setUp(): void
    {
        $this->apiKey = getenv('ANTHROPIC_API_KEY');
        
        if (!$this->apiKey) {
            $this->markTestSkipped('ANTHROPIC_API_KEY environment variable not set');
        }

        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function($key, $default = null) {
            $map = [
                'ai.providers.anthropic.key' => $this->apiKey,
                'ai.providers.anthropic.model' => 'claude-3-haiku-20240307',
                'ai.providers.anthropic.version' => '2023-06-01',
                'ai.providers.anthropic.endpoint' => 'https://api.anthropic.com/v1/messages',
                'ai.http_timeout' => 30,
                'ai.temperature' => 0.7,
                'ai.max_tokens' => 150,
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

    public function testBasicStreaming()
    {
        $chunks = [];
        $fullText = '';
        
        $this->anthropic->task()
            ->prompt('Count from 1 to 5, one number per line.')
            ->stream(function($chunk) use (&$chunks, &$fullText) {
                $chunks[] = $chunk;
                $fullText .= $chunk;
            });
        
        $this->assertGreaterThan(1, count($chunks), 'Should receive multiple chunks');
        $this->assertNotEmpty($fullText, 'Should receive text content');
        $this->assertMatchesRegularExpression('/[1-5]/', $fullText, 'Should contain numbers');
        
        echo "\n\n=== Anthropic Streaming Test ===\n";
        echo "Total chunks: " . count($chunks) . "\n";
        echo "Full text:\n" . $fullText . "\n";
        echo "================================\n\n";
    }

    public function testStreamingWithSystemPrompt()
    {
        $chunks = [];
        $fullText = '';
        
        $this->anthropic->task()
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

    public function testStreamingProgressiveOutput()
    {
        $receivedAt = [];
        $startTime = microtime(true);
        
        $this->anthropic->task()
            ->prompt('Write a short 3-sentence story.')
            ->stream(function($chunk) use (&$receivedAt, $startTime) {
                $receivedAt[] = microtime(true) - $startTime;
            });
        
        $this->assertGreaterThan(2, count($receivedAt), 'Should receive multiple chunks');
        
        if (count($receivedAt) > 1) {
            $timeDiff = end($receivedAt) - $receivedAt[0];
            $this->assertGreaterThan(0, $timeDiff, 'Chunks should arrive progressively');
        }
    }
}
