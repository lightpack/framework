<?php
namespace Lightpack\AI\Providers;

use Lightpack\AI\AI;

class Anthropic extends AI
{
    public function generate(array $params): array
    {
        return $this->executeWithCache($params, function() use ($params) {
            $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
            $baseUrl = $this->config->get('ai.providers.anthropic.base_url');
            $endpoint = $params['endpoint'] ?? $baseUrl . '/messages';
            
            $result = $this->makeApiRequest(
                $endpoint,
                $this->prepareRequestBody($params),
                $this->prepareHeaders(),
                $this->config->get('ai.http_timeout', 10)
            );
            
            return $this->parseOutput($result);
        });
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
                'content' => $this->normalizeContent($msg['content']),
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

    protected function normalizeContent($content): string|array
    {
        if (is_string($content)) {
            return $content;
        }
        
        if (is_array($content)) {
            $normalized = [];
            foreach ($content as $item) {
                $type = $item['type'] ?? null;
                
                if ($type === 'text') {
                    $normalized[] = ['type' => 'text', 'text' => $item['text']];
                } elseif ($type === 'image_url') {
                    // Convert framework's image_url format to Anthropic format
                    $imageUrl = $item['image_url']['url'] ?? $item['image_url'];
                    $parsed = $this->parseDataUrl($imageUrl);
                    if ($parsed) {
                        $normalized[] = [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $parsed['mime_type'],
                                'data' => $parsed['data']
                            ]
                        ];
                    }
                } elseif ($type === 'document') {
                    // Convert generic document to Anthropic format
                    $normalized[] = [
                        'type' => 'document',
                        'source' => [
                            'type' => 'base64',
                            'media_type' => $item['mime_type'],
                            'data' => $item['data']
                        ]
                    ];
                }
            }
            return $normalized;
        }
        
        return $content;
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

    public function generateStream(array $params, callable $onChunk): void
    {
        $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        $baseUrl = $this->config->get('ai.providers.anthropic.base_url');
        $endpoint = $params['endpoint'] ?? $baseUrl . '/messages';
        
        $body = $this->prepareRequestBody($params);
        $body['stream'] = true;
        
        $buffer = '';
        
        $this->http
            ->headers($this->prepareHeaders())
            ->timeout($this->config->get('ai.http_timeout', 10))
            ->stream('POST', $endpoint, $body, function($chunk) use (&$buffer, $onChunk) {
                $buffer .= $chunk;
                
                // Process complete lines (Server-Sent Events format)
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);
                    
                    // Skip empty lines
                    if (trim($line) === '') {
                        continue;
                    }
                    
                    // Parse SSE event line
                    if (str_starts_with($line, 'event: ')) {
                        // Anthropic sends event type, we can ignore for now
                        continue;
                    }
                    
                    // Parse SSE data line
                    if (str_starts_with($line, 'data: ')) {
                        $data = substr($line, 6);
                        
                        // Parse JSON chunk
                        $json = json_decode($data, true);
                        if (!$json) {
                            continue;
                        }
                        
                        // Anthropic streaming format:
                        // - content_block_delta events contain the text
                        // - delta.text contains the actual content
                        if (isset($json['type']) && $json['type'] === 'content_block_delta') {
                            $content = $json['delta']['text'] ?? '';
                            
                            if ($content !== '') {
                                $onChunk($content);
                            }
                        }
                        
                        // Check for stream end
                        if (isset($json['type']) && $json['type'] === 'message_stop') {
                            return;
                        }
                    }
                }
            });
    }
}
