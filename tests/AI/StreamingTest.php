<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\AI;
use Lightpack\Http\Response;
use Lightpack\Http\Http;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;
use Lightpack\Logger\Logger;

/**
 * Mock provider for testing streaming functionality
 */
class MockStreamingProvider extends AI
{
    private $streamContent = "Hello world from AI streaming!";
    private $shouldError = false;

    public function setStreamContent(string $content): void
    {
        $this->streamContent = $content;
    }

    public function setShouldError(bool $error): void
    {
        $this->shouldError = $error;
    }

    public function generate(array $params)
    {
        return ['text' => $this->streamContent];
    }

    public function generateStream(array $params): Response
    {
        $response = new Response();
        $response->setType('text/event-stream');
        $response->setHeader('Cache-Control', 'no-cache');
        $response->setHeader('Connection', 'keep-alive');
        
        $content = $this->streamContent;
        $shouldError = $this->shouldError;
        
        $response->stream(function() use ($content, $shouldError) {
            if ($shouldError) {
                echo "data: " . json_encode(['error' => 'Test error']) . "\n\n";
                flush();
                return;
            }
            
            // Simulate streaming by sending content word by word
            $words = explode(' ', $content);
            foreach ($words as $word) {
                echo "data: " . json_encode(['content' => $word . ' ']) . "\n\n";
                flush();
            }
            echo "data: [DONE]\n\n";
            flush();
        });
        
        return $response;
    }
}

class StreamingTest extends TestCase
{
    private $provider;
    private $http;
    private $cache;
    private $config;
    private $logger;

    protected function setUp(): void
    {
        $this->http = $this->createMock(Http::class);
        $this->cache = $this->createMock(Cache::class);
        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(Logger::class);
        
        $this->provider = new MockStreamingProvider(
            $this->http,
            $this->cache,
            $this->config,
            $this->logger
        );
    }

    public function testAskStreamReturnsResponse()
    {
        $response = $this->provider->askStream('What is AI?');
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('text/event-stream', $response->getType());
        $this->assertEquals('no-cache', $response->getHeader('Cache-Control'));
        $this->assertEquals('keep-alive', $response->getHeader('Connection'));
    }

    public function testGenerateStreamReturnsResponse()
    {
        $response = $this->provider->generateStream(['prompt' => 'Test prompt']);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('text/event-stream', $response->getType());
    }

    public function testStreamCallbackIsSet()
    {
        $response = $this->provider->generateStream(['prompt' => 'Test']);
        
        $this->assertNotNull($response->getStreamCallback());
        $this->assertIsCallable($response->getStreamCallback());
    }

    public function testStreamOutputFormat()
    {
        $this->provider->setStreamContent('Test message');
        $response = $this->provider->generateStream(['prompt' => 'Test']);
        
        // Capture output
        ob_start();
        $callback = $response->getStreamCallback();
        $callback();
        $output = ob_get_clean();
        
        // Verify SSE format
        $this->assertStringContainsString('data: ', $output);
        $this->assertStringContainsString('[DONE]', $output);
        $this->assertStringContainsString('Test', $output);
    }

    public function testStreamOutputContainsContent()
    {
        $testContent = 'Hello streaming world';
        $this->provider->setStreamContent($testContent);
        $response = $this->provider->generateStream(['prompt' => 'Test']);
        
        ob_start();
        $callback = $response->getStreamCallback();
        $callback();
        $output = ob_get_clean();
        
        // Each word should be in the output
        $words = explode(' ', $testContent);
        foreach ($words as $word) {
            $this->assertStringContainsString($word, $output);
        }
    }

    public function testStreamErrorHandling()
    {
        $this->provider->setShouldError(true);
        $response = $this->provider->generateStream(['prompt' => 'Test']);
        
        ob_start();
        $callback = $response->getStreamCallback();
        $callback();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('error', $output);
        $this->assertStringContainsString('Test error', $output);
    }

    public function testStreamJsonFormat()
    {
        $this->provider->setStreamContent('Single word');
        $response = $this->provider->generateStream(['prompt' => 'Test']);
        
        ob_start();
        $callback = $response->getStreamCallback();
        $callback();
        $output = ob_get_clean();
        
        // Extract data lines
        $lines = explode("\n", $output);
        $dataLines = array_filter($lines, fn($line) => strpos($line, 'data: ') === 0);
        
        // Each data line should contain valid JSON
        foreach ($dataLines as $line) {
            if (trim($line) === 'data: [DONE]') {
                continue;
            }
            
            $json = substr($line, 6); // Remove "data: " prefix
            $decoded = json_decode($json, true);
            
            $this->assertNotNull($decoded, "Line should contain valid JSON: $line");
            $this->assertIsArray($decoded);
        }
    }

    public function testTaskBuilderStreamMethod()
    {
        $task = $this->provider->task();
        $this->assertTrue(method_exists($task, 'stream'));
        
        $response = $task->prompt('Test question')->stream();
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testStreamWithCustomModel()
    {
        $response = $this->provider->generateStream([
            'prompt' => 'Test',
            'model' => 'gpt-4',
            'temperature' => 0.7
        ]);
        
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testStreamResponseHeaders()
    {
        $response = $this->provider->generateStream(['prompt' => 'Test']);
        
        // Verify all required SSE headers are set
        $this->assertEquals('text/event-stream', $response->getType());
        $this->assertTrue($response->hasHeader('Cache-Control'));
        $this->assertTrue($response->hasHeader('Connection'));
        $this->assertEquals('no-cache', $response->getHeader('Cache-Control'));
        $this->assertEquals('keep-alive', $response->getHeader('Connection'));
    }

    public function testMultipleStreamCalls()
    {
        // First stream
        $response1 = $this->provider->generateStream(['prompt' => 'First']);
        ob_start();
        $callback1 = $response1->getStreamCallback();
        $callback1();
        $output1 = ob_get_clean();
        
        // Second stream
        $this->provider->setStreamContent('Different content');
        $response2 = $this->provider->generateStream(['prompt' => 'Second']);
        ob_start();
        $callback2 = $response2->getStreamCallback();
        $callback2();
        $output2 = ob_get_clean();
        
        // Both should work independently
        $this->assertStringContainsString('[DONE]', $output1);
        $this->assertStringContainsString('[DONE]', $output2);
        $this->assertStringContainsString('Different', $output2);
    }

    public function testStreamWithEmptyContent()
    {
        $this->provider->setStreamContent('');
        $response = $this->provider->generateStream(['prompt' => 'Test']);
        
        ob_start();
        $callback = $response->getStreamCallback();
        $callback();
        $output = ob_get_clean();
        
        // Should still send completion signal
        $this->assertStringContainsString('[DONE]', $output);
    }

    public function testStreamWithSpecialCharacters()
    {
        $specialContent = 'Test with "quotes" and \'apostrophes\' & symbols!';
        $this->provider->setStreamContent($specialContent);
        $response = $this->provider->generateStream(['prompt' => 'Test']);
        
        ob_start();
        $callback = $response->getStreamCallback();
        $callback();
        $output = ob_get_clean();
        
        // Content should be properly JSON encoded
        $this->assertStringContainsString('data: ', $output);
        
        // Extract and verify JSON is valid
        $lines = explode("\n", $output);
        $hasValidJson = false;
        foreach ($lines as $line) {
            if (strpos($line, 'data: {') === 0) {
                $json = substr($line, 6);
                $decoded = json_decode($json, true);
                if ($decoded !== null) {
                    $hasValidJson = true;
                    break;
                }
            }
        }
        
        $this->assertTrue($hasValidJson, 'Should contain valid JSON with special characters');
    }
}
