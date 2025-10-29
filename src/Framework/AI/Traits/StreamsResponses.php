<?php

namespace Lightpack\AI\Traits;

use Lightpack\Http\Response;

/**
 * Trait for AI providers that support streaming responses.
 * Eliminates code duplication across providers by providing shared streaming logic.
 */
trait StreamsResponses
{
    /**
     * Create a streaming response using the HTTP client.
     * This method handles the common streaming logic for all providers.
     * 
     * @param string $endpoint API endpoint URL
     * @param array $body Request body (will have 'stream' => true added)
     * @param array $headers Request headers
     * @param int $timeout Request timeout in seconds
     * @param callable $chunkParser Function to parse provider-specific chunk format
     * @return Response
     */
    protected function createStreamingResponse(
        string $endpoint,
        array $body,
        array $headers,
        int $timeout,
        callable $chunkParser
    ): Response {
        $response = app('response');
        $response->setType('text/event-stream');
        $response->setHeader('Cache-Control', 'no-cache');
        $response->setHeader('Connection', 'keep-alive');
        $response->setHeader('X-Accel-Buffering', 'no');
        
        // Enable streaming in request body
        $body['stream'] = true;
        
        $response->stream(function() use ($endpoint, $body, $headers, $timeout, $chunkParser) {
            // Disable output buffering for real-time streaming
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            $buffer = '';
            
            // Use HTTP client for streaming
            $this->http
                ->headers($headers)
                ->timeout($timeout)
                ->stream($endpoint, $body, function($chunk) use (&$buffer, $chunkParser) {
                    $buffer .= $chunk;
                    $lines = explode("\n", $buffer);
                    
                    // Keep the last incomplete line in buffer
                    $buffer = array_pop($lines);
                    
                    foreach ($lines as $line) {
                        $line = trim($line);
                        
                        if (empty($line)) {
                            continue;
                        }
                        
                        // Let provider-specific parser handle the line
                        $content = $chunkParser($line);
                        
                        if ($content !== null) {
                            // Send as SSE format
                            echo "data: " . json_encode(['content' => $content]) . "\n\n";
                            flush();
                        }
                    }
                });
            
            // Check for HTTP errors
            if ($this->http->failed()) {
                $error = $this->http->error() ?: 'HTTP error ' . $this->http->status();
                $this->logger->error(static::class . ' streaming error: ' . $error);
                echo "data: " . json_encode(['error' => $error]) . "\n\n";
                flush();
            }
            
            // Send completion signal
            echo "data: [DONE]\n\n";
            flush();
        });
        
        return $response;
    }

    /**
     * Parse OpenAI-compatible streaming format.
     * Used by OpenAI, Groq, and Mistral providers.
     * 
     * @param string $line SSE line to parse
     * @return string|null Content if found, null otherwise
     */
    protected function parseOpenAIStreamLine(string $line): ?string
    {
        if ($line === 'data: [DONE]') {
            return null;
        }
        
        if (strpos($line, 'data: ') === 0) {
            $json = substr($line, 6);
            $chunk = json_decode($json, true);
            
            if (isset($chunk['choices'][0]['delta']['content'])) {
                return $chunk['choices'][0]['delta']['content'];
            }
        }
        
        return null;
    }

    /**
     * Parse Anthropic (Claude) streaming format.
     * 
     * @param string $line SSE line to parse
     * @return string|null Content if found, null otherwise
     */
    protected function parseAnthropicStreamLine(string $line): ?string
    {
        // Skip event type lines
        if (strpos($line, 'event: ') === 0) {
            return null;
        }
        
        if (strpos($line, 'data: ') === 0) {
            $json = substr($line, 6);
            $chunk = json_decode($json, true);
            
            // Handle content_block_delta events
            if (isset($chunk['type']) && $chunk['type'] === 'content_block_delta') {
                if (isset($chunk['delta']['text'])) {
                    return $chunk['delta']['text'];
                }
            }
            
            // Stop on message_stop event
            if (isset($chunk['type']) && $chunk['type'] === 'message_stop') {
                return null;
            }
        }
        
        return null;
    }
}
