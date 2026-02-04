<?php
namespace Lightpack\AI\Providers;

use Lightpack\AI\AI;

class Groq extends AI
{
    public function generate(array $params): array
    {
        return $this->executeWithCache($params, function() use ($params) {
            $params['messages'] = $params['messages'] ?? [['role' => 'user', 'content' => $params['prompt'] ?? '']];
            $endpoint = $params['endpoint'] ?? $this->config->get('ai.providers.groq.endpoint');
            
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
     * Prepare the request body for Groq's API.
     */
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
        $endpoint = $params['endpoint'] ?? $this->config->get('ai.providers.groq.endpoint');
        
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
                    
                    // Parse SSE data line (Groq uses OpenAI-compatible format)
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
}
