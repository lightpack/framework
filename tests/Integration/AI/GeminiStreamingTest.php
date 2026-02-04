<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\Providers\Gemini;
use Lightpack\Http\Http;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;

class GeminiStreamingTest extends TestCase
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
                'ai.max_tokens' => 150,
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

    public function testBasicStreaming()
    {
        $chunks = [];
        $fullText = '';
        
        $this->gemini->task()
            ->prompt('Count from 1 to 5, one number per line.')
            ->stream(function($chunk) use (&$chunks, &$fullText) {
                $chunks[] = $chunk;
                $fullText .= $chunk;
            });
        
        $this->assertGreaterThan(1, count($chunks), 'Should receive multiple chunks');
        $this->assertNotEmpty($fullText, 'Should receive text content');
        $this->assertMatchesRegularExpression('/[1-5]/', $fullText, 'Should contain numbers');
        
        echo "\n\n=== Gemini Streaming Test ===\n";
        echo "Total chunks: " . count($chunks) . "\n";
        echo "Full text:\n" . $fullText . "\n";
        echo "=============================\n\n";
    }

    public function testStreamingWithSystemPrompt()
    {
        $chunks = [];
        $fullText = '';
        
        $this->gemini->task()
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
        
        $this->gemini->task()
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
