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
}
