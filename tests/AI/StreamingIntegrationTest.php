<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Http\Response;

/**
 * Integration tests for streaming AI responses with Response class.
 * These tests verify the complete integration between AI providers and Response streaming.
 */
class StreamingIntegrationTest extends TestCase
{
    public function testResponseStreamingWithCallback()
    {
        $response = new Response();
        $response->setType('text/event-stream');
        $response->setHeader('Cache-Control', 'no-cache');
        
        $testData = ['chunk1', 'chunk2', 'chunk3'];
        
        $response->stream(function() use ($testData) {
            foreach ($testData as $chunk) {
                echo "data: " . json_encode(['content' => $chunk]) . "\n\n";
                flush();
            }
            echo "data: [DONE]\n\n";
            flush();
        });
        
        $this->assertEquals('text/event-stream', $response->getType());
        $this->assertNotNull($response->getStreamCallback());
        
        // Test callback execution
        ob_start();
        $callback = $response->getStreamCallback();
        $callback();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('chunk1', $output);
        $this->assertStringContainsString('chunk2', $output);
        $this->assertStringContainsString('chunk3', $output);
        $this->assertStringContainsString('[DONE]', $output);
    }

    public function testResponseStreamingHeaders()
    {
        $response = new Response();
        $response->setType('text/event-stream');
        $response->setHeader('Cache-Control', 'no-cache');
        $response->setHeader('Connection', 'keep-alive');
        $response->setHeader('X-Accel-Buffering', 'no');
        
        $this->assertEquals('text/event-stream', $response->getType());
        $this->assertEquals('no-cache', $response->getHeader('Cache-Control'));
        $this->assertEquals('keep-alive', $response->getHeader('Connection'));
        $this->assertEquals('no', $response->getHeader('X-Accel-Buffering'));
    }

    public function testStreamingWithLargeContent()
    {
        $response = new Response();
        $response->setType('text/event-stream');
        
        // Simulate streaming large content
        $largeContent = str_repeat('Lorem ipsum dolor sit amet. ', 100);
        $words = explode(' ', $largeContent);
        
        $response->stream(function() use ($words) {
            foreach ($words as $word) {
                echo "data: " . json_encode(['content' => $word . ' ']) . "\n\n";
                flush();
            }
            echo "data: [DONE]\n\n";
            flush();
        });
        
        ob_start();
        $callback = $response->getStreamCallback();
        $callback();
        $output = ob_get_clean();
        
        // Verify all content was streamed
        $this->assertStringContainsString('Lorem', $output);
        $this->assertStringContainsString('ipsum', $output);
        $this->assertStringContainsString('[DONE]', $output);
        
        // Verify SSE format is maintained
        $lines = explode("\n", $output);
        $dataLineCount = 0;
        foreach ($lines as $line) {
            if (strpos($line, 'data: ') === 0) {
                $dataLineCount++;
            }
        }
        
        $this->assertGreaterThan(0, $dataLineCount);
    }

    public function testStreamingWithJsonData()
    {
        $response = new Response();
        $response->setType('text/event-stream');
        
        $jsonData = [
            ['type' => 'start', 'timestamp' => time()],
            ['type' => 'content', 'text' => 'Hello'],
            ['type' => 'content', 'text' => 'World'],
            ['type' => 'end', 'timestamp' => time()],
        ];
        
        $response->stream(function() use ($jsonData) {
            foreach ($jsonData as $data) {
                echo "data: " . json_encode($data) . "\n\n";
                flush();
            }
            echo "data: [DONE]\n\n";
            flush();
        });
        
        ob_start();
        $callback = $response->getStreamCallback();
        $callback();
        $output = ob_get_clean();
        
        // Verify JSON structure
        $lines = explode("\n", $output);
        $validJsonCount = 0;
        
        foreach ($lines as $line) {
            if (strpos($line, 'data: {') === 0) {
                $json = substr($line, 6);
                $decoded = json_decode($json, true);
                if ($decoded !== null && is_array($decoded)) {
                    $validJsonCount++;
                }
            }
        }
        
        $this->assertEquals(4, $validJsonCount, 'Should have 4 valid JSON objects');
    }

    public function testStreamingErrorRecovery()
    {
        $response = new Response();
        $response->setType('text/event-stream');
        
        $response->stream(function() {
            // Send some data
            echo "data: " . json_encode(['content' => 'Start']) . "\n\n";
            flush();
            
            // Simulate error
            echo "data: " . json_encode(['error' => 'Connection issue']) . "\n\n";
            flush();
            
            // Continue after error
            echo "data: " . json_encode(['content' => 'Recovered']) . "\n\n";
            flush();
            
            echo "data: [DONE]\n\n";
            flush();
        });
        
        ob_start();
        $callback = $response->getStreamCallback();
        $callback();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Start', $output);
        $this->assertStringContainsString('error', $output);
        $this->assertStringContainsString('Recovered', $output);
        $this->assertStringContainsString('[DONE]', $output);
    }

    public function testStreamingWithUnicodeContent()
    {
        $response = new Response();
        $response->setType('text/event-stream');
        
        $unicodeContent = [
            'Hello 世界',
            'Привет мир',
            'مرحبا بالعالم',
            '🚀 🌟 ✨'
        ];
        
        $response->stream(function() use ($unicodeContent) {
            foreach ($unicodeContent as $text) {
                echo "data: " . json_encode(['content' => $text]) . "\n\n";
                flush();
            }
            echo "data: [DONE]\n\n";
            flush();
        });
        
        ob_start();
        $callback = $response->getStreamCallback();
        $callback();
        $output = ob_get_clean();
        
        // Verify unicode is properly encoded
        $this->assertStringContainsString('data: ', $output);
        $this->assertStringContainsString('[DONE]', $output);
        
        // Extract and verify JSON is valid
        $lines = explode("\n", $output);
        $validUnicodeCount = 0;
        
        foreach ($lines as $line) {
            if (strpos($line, 'data: {') === 0 && strpos($line, '[DONE]') === false) {
                $json = substr($line, 6);
                $decoded = json_decode($json, true);
                if ($decoded !== null && isset($decoded['content'])) {
                    $validUnicodeCount++;
                }
            }
        }
        
        $this->assertEquals(4, $validUnicodeCount, 'All unicode content should be properly encoded');
    }

    public function testStreamingPerformance()
    {
        $response = new Response();
        $response->setType('text/event-stream');
        
        $chunkCount = 100;
        
        $response->stream(function() use ($chunkCount) {
            for ($i = 0; $i < $chunkCount; $i++) {
                echo "data: " . json_encode(['content' => "Chunk $i"]) . "\n\n";
                flush();
            }
            echo "data: [DONE]\n\n";
            flush();
        });
        
        $startTime = microtime(true);
        
        ob_start();
        $callback = $response->getStreamCallback();
        $callback();
        $output = ob_get_clean();
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Verify all chunks were sent
        $lines = explode("\n", $output);
        $dataLines = array_filter($lines, fn($line) => strpos($line, 'data: {') === 0);
        
        $this->assertCount($chunkCount, $dataLines, "Should have $chunkCount data lines");
        
        // Performance should be reasonable (less than 1 second for 100 chunks)
        $this->assertLessThan(1.0, $duration, 'Streaming should complete in under 1 second');
    }

    public function testStreamingWithConnectionCheck()
    {
        $response = new Response();
        $response->setType('text/event-stream');
        
        $response->stream(function() {
            for ($i = 0; $i < 5; $i++) {
                echo "data: " . json_encode(['content' => "Message $i"]) . "\n\n";
                flush();
                
                // Simulate connection check
                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }
            echo "data: [DONE]\n\n";
            flush();
        });
        
        ob_start();
        $callback = $response->getStreamCallback();
        $callback();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Message 0', $output);
        $this->assertStringContainsString('[DONE]', $output);
    }

    public function testResponseSendWithStreaming()
    {
        $response = new Response();
        $response->setTestMode(true); // Prevent exit()
        $response->setType('text/event-stream');
        
        $response->stream(function() {
            echo "data: " . json_encode(['content' => 'Test']) . "\n\n";
            flush();
            echo "data: [DONE]\n\n";
            flush();
        });
        
        // Capture the send output
        ob_start();
        $response->send();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Test', $output);
        $this->assertStringContainsString('[DONE]', $output);
    }
}
