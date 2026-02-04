<?php
namespace Lightpack\AI\Providers;

use Lightpack\AI\AI;

class OpenAI extends AI
{
    public function generate(array $params): array
    {
        return $this->executeWithCache($params, function() use ($params) {
            $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
            $endpoint = $params['endpoint'] ?? $this->config->get('ai.providers.openai.endpoint');
            
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

    protected function generateEmbedding(string|array $input, array $options = []): array
    {
        $model = $options['model'] ?? $this->config->get('ai.providers.openai.embedding_model', 'text-embedding-3-small');
        $endpoint = 'https://api.openai.com/v1/embeddings';
        
        $result = $this->makeApiRequest(
            $endpoint,
            [
                'model' => $model,
                'input' => $input,  // OpenAI accepts both string and array
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

    public function generateStream(array $params, callable $onChunk): void
    {
        $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
        $endpoint = $params['endpoint'] ?? $this->config->get('ai.providers.openai.endpoint');
        
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
                    
                    // Parse SSE data line
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
                        
                        // Extract content from delta
                        $content = $json['choices'][0]['delta']['content'] ?? '';
                        
                        if ($content !== '') {
                            $onChunk($content);
                        }
                    }
                }
            });
    }
}
