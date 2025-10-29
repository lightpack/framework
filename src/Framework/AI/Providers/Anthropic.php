<?php
namespace Lightpack\AI\Providers;

use Lightpack\AI\AI;
use Lightpack\Http\Response;

class Anthropic extends AI
{
    public function generate(array $params)
    {
        $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        $useCache = $params['cache'] ?? true;
        $cacheTtl = $params['cache_ttl'] ?? $this->config->get('ai.cache_ttl');
        $cacheKey = $this->generateCacheKey($params);

        // Check cache first (unless bypassed)
        if ($useCache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $result = $this->makeApiRequest(
            $this->config->get('ai.providers.anthropic.endpoint'),
            $this->prepareRequestBody($params),
            $this->prepareHeaders(),
            $this->config->get('ai.http_timeout', 10)
        );
        $output = $this->parseOutput($result);

        if ($useCache) {
            $this->cache->set($cacheKey, $output, $cacheTtl);
        }

        return $output;
    }

    /**
     * Prepare the request body for Anthropic's API.
     * - 'system' is a top-level key.
     * - 'messages' is an array of {role: 'user'|'assistant', content: string}.
     * - 'content' is a string, not an array.
     */
    protected function prepareRequestBody(array $params): array
    {
        $messages = $params['messages'];

        // Remove any 'system' role messages (Anthropic only accepts 'user' and 'assistant')
        $messages = array_filter($messages, function($msg) {
            return $msg['role'] !== 'system';
        });
        $messages = array_values($messages); // reindex

        $messages = array_map(function($msg) {
            return [
                'role' => $msg['role'],
                'content' => is_array($msg['content']) ? implode("\n", $msg['content']) : $msg['content'],
            ];
        }, $messages);

        return [
            'system' => $params['system'] ?? '',
            'messages' => $messages,
            'model' => $params['model'] ?? $this->config->get('ai.providers.anthropic.model'),
            'temperature' => $params['temperature'] ?? $this->config->get('ai.temperature'),
            'max_tokens' => (int) ($params['max_tokens'] ?? $this->config->get('ai.max_tokens')),
        ];
    }

    /**
     * Prepare headers for Anthropic API.
     * Uses x-api-key, not Authorization.
     */
    protected function prepareHeaders(): array
    {
        return [
            'anthropic-version' => $this->config->get('ai.providers.anthropic.version'),
            'x-api-key' => $this->config->get('ai.providers.anthropic.key'),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Parse the Anthropic API response.
     * - Main text is in content[0]['text']
     * - Stop reason and usage are top-level keys
     */
    protected function parseOutput(array $result): array
    {
        $content = '';
        if (isset($result['content']) && is_array($result['content']) && isset($result['content'][0]['text'])) {
            $content = $result['content'][0]['text'];
        }
        return [
            'text' => $content,
            'finish_reason' => $result['stop_reason'] ?? '',
            'usage' => $result['usage'] ?? [],
            'raw' => $result,
        ];
    }

    /**
     * Generate streaming completion using Server-Sent Events.
     * Streams tokens as they are generated for real-time display.
     * 
     * @param array $params Generation parameters
     * @return Response Response configured for SSE streaming
     */
    public function generateStream(array $params): Response
    {
        $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        
        $response = app('response');
        $response->setType('text/event-stream');
        $response->setHeader('Cache-Control', 'no-cache');
        $response->setHeader('Connection', 'keep-alive');
        $response->setHeader('X-Accel-Buffering', 'no');
        
        $endpoint = $this->config->get('ai.providers.anthropic.endpoint');
        $body = $this->prepareRequestBody($params);
        $body['stream'] = true; // Enable streaming
        $headers = $this->prepareHeaders();
        $timeout = $this->config->get('ai.http_timeout', 30);
        
        $response->stream(function() use ($endpoint, $body, $headers, $timeout) {
            // Disable output buffering for real-time streaming
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            $ch = curl_init();
            $buffer = '';
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_HTTPHEADER => array_merge(
                    array_map(fn($k, $v) => "$k: $v", array_keys($headers), $headers),
                    ['Accept: text/event-stream']
                ),
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_WRITEFUNCTION => function($curl, $data) use (&$buffer) {
                    $buffer .= $data;
                    $lines = explode("\n", $buffer);
                    
                    // Keep the last incomplete line in buffer
                    $buffer = array_pop($lines);
                    
                    foreach ($lines as $line) {
                        $line = trim($line);
                        
                        if (empty($line)) {
                            continue;
                        }
                        
                        // Anthropic uses different event types
                        if (strpos($line, 'event: ') === 0) {
                            continue; // Skip event type lines
                        }
                        
                        if (strpos($line, 'data: ') === 0) {
                            $json = substr($line, 6);
                            $chunk = json_decode($json, true);
                            
                            // Handle content_block_delta events
                            if (isset($chunk['type']) && $chunk['type'] === 'content_block_delta') {
                                if (isset($chunk['delta']['text'])) {
                                    $content = $chunk['delta']['text'];
                                    // Send as SSE format
                                    echo "data: " . json_encode(['content' => $content]) . "\n\n";
                                    flush();
                                }
                            }
                            
                            // Handle message_stop event
                            if (isset($chunk['type']) && $chunk['type'] === 'message_stop') {
                                // Stream completed
                                break;
                            }
                        }
                    }
                    
                    return strlen($data);
                },
            ]);
            
            curl_exec($ch);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                $this->logger->error('Anthropic streaming error: ' . $error);
                echo "data: " . json_encode(['error' => $error]) . "\n\n";
                flush();
            }
            
            curl_close($ch);
            
            // Send completion signal
            echo "data: [DONE]\n\n";
            flush();
        });
        
        return $response;
    }
}
