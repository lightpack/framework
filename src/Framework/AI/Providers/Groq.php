<?php
namespace Lightpack\AI\Providers;

use Lightpack\AI\AI;
use Lightpack\Http\Response;

class Groq extends AI
{
    /**
     * Generate a response from the Groq API.
     */
    public function generate(array $params): array
    {
        $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        $useCache = $params['cache'] ?? true;
        $cacheTtl = $params['cache_ttl'] ?? $this->config->get('ai.cache_ttl');
        $cacheKey = $this->generateCacheKey($params);

        // Check cache first
        if ($useCache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $result = $this->makeApiRequest(
            $this->config->get('ai.providers.groq.endpoint'),
            $this->prepareRequestBody($params),
            $this->prepareHeaders(),
            $this->config->get('ai.http_timeout')
        );
        $output = $this->parseOutput($result);

        // Write to cache
        if ($useCache) {
            $this->cache->set($cacheKey, $output, $cacheTtl);
        }

        return $output;
    }

    /**
     * Prepare the request body for Groq's API.
     */
    protected function prepareRequestBody(array $params): array
    {
        $messages = $params['messages'];
        $messages = array_map(function($msg) {
            return [
                'role' => $msg['role'],
                'content' => is_array($msg['content']) ? implode("\n", $msg['content']) : $msg['content'],
            ];
        }, $messages);

        return [
            'model' => $params['model'] ?? $this->config->get('ai.providers.groq.model'),
            'messages' => $messages,
            'temperature' => $params['temperature'] ?? $this->config->get('ai.temperature'),
            'max_tokens' => $params['max_tokens'] ?? $this->config->get('ai.max_tokens'),
        ];
    }

    /**
     * Prepare headers for Groq API.
     */
    protected function prepareHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->get('ai.providers.groq.key'),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Parse the output from Groq API response.
     */
    protected function parseOutput($result): array
    {
        $text = '';
        if (isset($result['choices'][0]['message']['content'])) {
            $text = $result['choices'][0]['message']['content'];
        }
        return [
            'text' => $text,
            'raw' => $result,
        ];
    }

    /**
     * Generate streaming completion using Server-Sent Events.
     * Groq uses OpenAI-compatible streaming format.
     */
    public function generateStream(array $params): Response
    {
        $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        
        $response = app('response');
        $response->setType('text/event-stream');
        $response->setHeader('Cache-Control', 'no-cache');
        $response->setHeader('Connection', 'keep-alive');
        $response->setHeader('X-Accel-Buffering', 'no');
        
        $endpoint = $this->config->get('ai.providers.groq.endpoint');
        $body = $this->prepareRequestBody($params);
        $body['stream'] = true;
        $headers = $this->prepareHeaders();
        $timeout = $this->config->get('ai.http_timeout', 30);
        
        $response->stream(function() use ($endpoint, $body, $headers, $timeout) {
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
                $this->logger->error('Groq streaming error: ' . $error);
                echo "data: " . json_encode(['error' => $error]) . "\n\n";
                flush();
            }
            
            curl_close($ch);
            echo "data: [DONE]\n\n";
            flush();
        });
        
        return $response;
    }
}
