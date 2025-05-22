<?php
namespace Lightpack\AI;

class AiTaskBuilder
{
    protected array $messages = [];
    protected $provider;
    protected ?string $prompt = null;
    protected ?array $expectSchema = null;
    protected ?string $expectArrayKey = null;
    protected ?array $example = null;
    protected ?string $model = null;
    protected ?float $temperature = null;
    protected ?int $maxTokens = null;
    protected ?string $system = null;
    protected ?string $rawResponse = null;

    public function __construct($provider)
    {
        $this->provider = $provider;
    }

    /**
     * Add a message to the chat history (role: user, system, assistant).
     */
    public function message(string $role, string $content)
    {
        $this->messages[] = ['role' => $role, 'content' => $content];
        return $this;
    }

    public function prompt(string $prompt)
    {
        $this->prompt = $prompt;
        return $this;
    }

    public function expect(array $schema)
    {
        // If $schema is a list of keys (numeric), default all types to 'string'
        $normalized = [];
        foreach ($schema as $key => $type) {
            if (is_int($key)) {
                $normalized[$type] = 'string';
            } else {
                $normalized[$key] = $type;
            }
        }
        $this->expectSchema = $normalized;
        return $this;
    }

    public function expectArray(string $key = 'item')
    {
        $this->expectArrayKey = $key;
        return $this;
    }

    public function example(array $example)
    {
        $this->example = $example;
        return $this;
    }

    public function model(string $model)
    {
        $this->model = $model;
        return $this;
    }

    public function temperature(float $temperature)
    {
        $this->temperature = $temperature;
        return $this;
    }

    public function maxTokens(int $maxTokens)
    {
        $this->maxTokens = $maxTokens;
        return $this;
    }

    public function system(string $system)
    {
        $this->system = $system;
        return $this;
    }

    public function run(): array
    {
        $params = $this->buildParams();
        $result = $this->provider->generate($params);
        $this->rawResponse = $result['text'] ?? '';

        $data = $this->extractAndDecodeJson($this->rawResponse);
        $success = false;

        if ($this->expectArrayKey && is_array($data)) {
            $data = $this->coerceSchemaOnArray($data);
            $success = true;
        } elseif ($this->expectSchema && is_array($data)) {
            $data = $this->coerceSchemaOnObject($data);
            $success = true;
        }

        return [
            'success' => $success,
            'data' => $success ? $data : null,
            'raw' => $this->rawResponse,
        ];
    }

    /**
     * Build request parameters for the provider.
     */
    protected function buildParams(): array
    {
        $params = [];
        if (!empty($this->messages)) {
            $params['messages'] = $this->messages;
            if ($this->system) {
                array_unshift($params['messages'], ['role' => 'system', 'content' => $this->system]);
            }
            if (($this->expectSchema || $this->expectArrayKey)) {
                $schemaInstruction = $this->buildSchemaInstruction();
                array_unshift($params['messages'], ['role' => 'system', 'content' => $schemaInstruction]);
            }
        } else {
            $finalPrompt = $this->prompt;
            if (($this->expectSchema || $this->expectArrayKey)) {
                $finalPrompt .= ' ' . $this->buildSchemaInstruction();
            }
            $params['prompt'] = $finalPrompt;
            if ($this->system) {
                $params['system'] = $this->system;
            }
        }
        if ($this->model) $params['model'] = $this->model;
        if ($this->temperature) $params['temperature'] = $this->temperature;
        if ($this->maxTokens) $params['max_tokens'] = $this->maxTokens;
        return $params;
    }

    /**
     * Extract and decode JSON from the raw LLM response.
     */
    protected function extractAndDecodeJson(string $text)
    {
        $json = $this->extractJson($text) ?? $text;
        return json_decode($json, true);
    }

    /**
     * Coerce schema on an array of objects.
     */
    protected function coerceSchemaOnArray(array $data): array
    {
        foreach ($data as &$item) {
            if (is_array($item) && $this->expectSchema) {
                foreach ($this->expectSchema as $key => $type) {
                    if (!array_key_exists($key, $item)) {
                        $item[$key] = null;
                    }
                    settype($item[$key], $type);
                }
            }
        }
        unset($item);
        return $data;
    }

    /**
     * Coerce schema on a single object.
     */
    protected function coerceSchemaOnObject(array $data): array
    {
        foreach ($this->expectSchema as $key => $type) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = null;
            }
            settype($data[$key], $type);
        }
        return $data;
    }

    public function raw(): string
    {
        return $this->rawResponse;
    }

    protected function buildSchemaInstruction(): string
    {
        if ($this->expectSchema) {
            $keys = implode('", "', array_keys($this->expectSchema));
            $example = $this->example ?? $this->autoExampleFromSchema();
            return 'Respond ONLY as a JSON object with keys: "' . $keys . '". No markdown, no extra text. Example: ' . json_encode($example);
        }
        if ($this->expectArrayKey) {
            return 'Respond ONLY as a JSON array of ' . $this->expectArrayKey . ' objects. No markdown, no extra text.';
        }
        return '';
    }

    protected function autoExampleFromSchema(): array
    {
        $example = [];
        foreach ($this->expectSchema as $key => $type) {
            $example[$key] = $type === 'string' ? 'example' : ($type === 'int' ? 0 : null);
        }
        return $example;
    }

    /**
     * Extract JSON array, multi-object, or object from a string (for messy LLM outputs).
     */
    protected function extractJson(string $text): ?string
    {
        // Try to extract a JSON array first
        if (preg_match('/(\[.*\])/s', $text, $matches)) {
            return $matches[0];
        }
        // If multiple JSON objects separated by newlines or not wrapped, wrap them as an array
        if (preg_match_all('/\{.*?\}/s', $text, $matches) && count($matches[0]) > 1) {
            return '[' . implode(',', $matches[0]) . ']';
        }
        // Fallback: single JSON object
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            return $matches[0];
        }
        return null;
    }
}


