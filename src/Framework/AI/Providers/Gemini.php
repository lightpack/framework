<?php
namespace Lightpack\AI\Providers;

use Lightpack\AI\AI;

class Gemini extends AI
{
    public function generate(array $params): array
    {
        return $this->executeWithCache($params, function() use ($params) {
            $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
            $endpoint = $params['endpoint'] ?? $this->config->get('ai.providers.gemini.endpoint');
            
            $result = $this->makeApiRequest(
                $endpoint,
                $this->prepareRequestBody($params), 
                $this->prepareHeaders(), 
                $this->config->get('ai.http_timeout')
            );
            
            return $this->parseOutput($result);
        });
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

    public function generateStream(array $params, callable $onChunk): void
    {
        $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        $endpoint = $params['endpoint'] ?? $this->config->get('ai.providers.gemini.endpoint');
        
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
                    
                    // Parse SSE data line (Gemini uses OpenAI-compatible format)
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
            'model' => $params['model'] ?? $this->config->get('ai.providers.gemini.model'),
            'temperature' => $params['temperature'] ?? $this->config->get('ai.temperature'),
            'max_tokens' => $params['max_tokens'] ?? $this->config->get('ai.max_tokens'),
        ];
    }

    protected function prepareHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->get('ai.providers.gemini.key'),
            'Content-Type' => 'application/json',
        ];
    }

    protected function generateEmbedding(string|array $input, array $options = []): array
    {
        $model = $options['model'] ?? $this->config->get('ai.providers.gemini.embedding_model', 'text-embedding-004');
        $apiKey = $this->config->get('ai.providers.gemini.key');
        
        // Single text
        if (is_string($input)) {
            $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':embedContent?key=' . $apiKey;
            
            $result = $this->makeApiRequest(
                $endpoint,
                ['content' => ['parts' => [['text' => $input]]]],
                ['Content-Type' => 'application/json'],
                $this->config->get('ai.http_timeout', 15)
            );
            
            return $result['embedding']['values'] ?? [];
        }
        
        // Batch - use efficient batch endpoint
        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':batchEmbedContents?key=' . $apiKey;
        
        $requests = array_map(fn($text) => [
            'model' => 'models/' . $model,
            'content' => ['parts' => [['text' => $text]]]
        ], $input);
        
        $result = $this->makeApiRequest(
            $endpoint,
            ['requests' => $requests],
            ['Content-Type' => 'application/json'],
            $this->config->get('ai.http_timeout', 15)
        );
        
        return array_map(fn($item) => $item['values'] ?? [], $result['embeddings'] ?? []);
    }
}
