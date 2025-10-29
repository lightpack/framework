<?php
namespace Lightpack\AI\Providers;

use Lightpack\AI\AI;
use Lightpack\Http\Response;

class OpenAI extends AI
{
    public function generate(array $params)
    {
        $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        $useCache = $params['cache'] ?? true;
        $cacheTtl = $params['cache_ttl'] ?? $this->config->get('ai.cache_ttl', 3600);
        $cacheKey = $this->generateCacheKey($params);

        // Check cache first (unless bypassed)
        if ($useCache) {
            if ($this->cache->has($cacheKey)) {
                return $this->cache->get($cacheKey);
            }
        }

        $result = $this->makeApiRequest(
            $this->config->get('ai.providers.openai.endpoint'),
            $this->prepareRequestBody($params), 
            $this->prepareHeaders(), 
            $this->config->get('ai.http_timeout')
        );
        $output = $this->parseOutput($result);
        
        // Write to cache unless bypassed
        if ($useCache) {
            $this->cache->set($cacheKey, $output, $cacheTtl);
        }

        return $output;
    }

    protected function parseOutput(array $result): array
    {
        $choice = $result['choices'][0] ?? [];

        return [
            'text' => $choice['message']['content'] ?? '',
            'finish_reason' => $choice['finish_reason'] ?? '',
            'usage' => $result['usage'] ?? [],
            'raw' => $result,
        ];
    }

    protected function prepareRequestBody(array $params): array
    {
        $messages = $params['messages'];
        
        if (!empty($params['system'])) {
            array_unshift($messages, ['role' => 'system', 'content' => $params['system']]);
        }

        $messages = array_map(function($msg) {
            return [
                'role' => $msg['role'],
                'content' => is_array($msg['content']) ? implode("\n", $msg['content']) : $msg['content'],
            ];
        }, $messages);
        
        return [
            'messages' => $messages,
            'model' => $params['model'] ?? $this->config->get('ai.providers.openai.model'),
            'temperature' => $params['temperature'] ?? $this->config->get('ai.temperature'),
            'max_tokens' => $params['max_tokens'] ?? $this->config->get('ai.max_tokens'),
        ];
    }

    protected function prepareHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->get('ai.providers.openai.key'),
            'Content-Type' => 'application/json',
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
        
        $endpoint = $this->config->get('ai.providers.openai.endpoint');
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
                        
                        if (empty($line) || $line === 'data: [DONE]') {
                            continue;
                        }
                        
                        if (strpos($line, 'data: ') === 0) {
                            $json = substr($line, 6);
                            $chunk = json_decode($json, true);
                            
                            if (isset($chunk['choices'][0]['delta']['content'])) {
                                $content = $chunk['choices'][0]['delta']['content'];
                                // Send as SSE format
                                echo "data: " . json_encode(['content' => $content]) . "\n\n";
                                flush();
                            }
                        }
                    }
                    
                    return strlen($data);
                },
            ]);
            
            curl_exec($ch);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                $this->logger->error('OpenAI streaming error: ' . $error);
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
