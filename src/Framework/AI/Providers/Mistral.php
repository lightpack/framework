<?php
namespace Lightpack\AI\Providers;

use Lightpack\AI\AI;

class Mistral extends AI
{
    public function generate(array $params): array
    {
        return $this->executeWithCache($params, function() use ($params) {
            $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
            $baseUrl = $this->config->get('ai.providers.mistral.base_url');
            $endpoint = $params['endpoint'] ?? $baseUrl . '/chat/completions';
            
            $result = $this->makeApiRequest(
                $endpoint,
                $this->prepareRequestBody($params),
                $this->prepareHeaders(),
                $this->config->get('ai.http_timeout')
            );
            
            return $this->parseOutput($result);
        });
    }

    /**
     * Prepare the request body for Mistral's API.
     */
    protected function prepareRequestBody(array $params): array
    {
        $messages = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        
        if (!empty($params['system'])) {
            array_unshift($messages, ['role' => 'system', 'content' => $params['system']]);
        }
        
        $messages = array_map(function($msg) {
            return [
                'role' => $msg['role'],
                'content' => $this->normalizeContent($msg['content']),
            ];
        }, $messages);

        return [
            'model' => $params['model'] ?? $this->config->get('ai.providers.mistral.model', 'mistral-medium'),
            'messages' => $messages,
            'temperature' => $params['temperature'] ?? $this->config->get('ai.temperature', 0.7),
            'max_tokens' => $params['max_tokens'] ?? $this->config->get('ai.max_tokens', 256),
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
                    $normalized[] = $item;
                } elseif ($type === 'document') {
                    continue;
                }
            }
            return $normalized;
        }
        
        return $content;
    }

    /**
     * Prepare headers for Mistral API.
     */
    protected function prepareHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->get('ai.providers.mistral.key'),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Parse the output from Mistral API response.
     */
    protected function parseOutput($result): array
    {
        $choice = $result['choices'][0] ?? [];

        return [
            'text' => $choice['message']['content'] ?? '',
            'finish_reason' => $choice['finish_reason'] ?? '',
            'usage' => $result['usage'] ?? [],
            'raw' => $result,
        ];
    }

    public function generateStream(array $params, callable $onChunk): void
    {
        $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        $baseUrl = $this->config->get('ai.providers.mistral.base_url');
        $endpoint = $params['endpoint'] ?? $baseUrl . '/chat/completions';
        
        $body = $this->prepareRequestBody($params);
        $body['stream'] = true;
        
        $buffer = '';
        
        $this->http
            ->headers($this->prepareHeaders())
            ->timeout($this->config->get('ai.http_timeout'))
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
                    
                    // Parse SSE data line (Mistral uses OpenAI-compatible format)
                    if (str_starts_with($line, 'data: ')) {
                        $data = substr($line, 6);
                        
                        // Check for stream end
                        if ($data === '[DONE]') {
                            return;
                        }
                        
                        // Parse JSON chunk
                        $json = json_decode($data, true);
                        if (!$json) {
                            continue;
                        }
                        
                        // Extract content from delta (same as OpenAI)
                        $content = $json['choices'][0]['delta']['content'] ?? '';
                        
                        if ($content !== '') {
                            $onChunk($content);
                        }
                    }
                }
            });
    }

    protected function generateEmbedding(string|array $input, array $options = []): array
    {
        $model = $options['model'] ?? $this->config->get('ai.providers.mistral.embedding_model', 'mistral-embed');
        $endpoint = 'https://api.mistral.ai/v1/embeddings';
        
        // Mistral API expects array, so wrap string
        $apiInput = is_string($input) ? [$input] : $input;
        
        $result = $this->makeApiRequest(
            $endpoint,
            [
                'model' => $model,
                'input' => $apiInput,
            ],
            $this->prepareHeaders(),
            $this->config->get('ai.http_timeout', 15)
        );
        
        // Single text returns single embedding
        if (is_string($input)) {
            return $result['data'][0]['embedding'] ?? [];
        }
        
        // Batch returns array of embeddings
        return array_map(fn($item) => $item['embedding'] ?? [], $result['data'] ?? []);
    }
}
