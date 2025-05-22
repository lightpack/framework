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
        $params = [];
        // If messages are present, use chat mode
        if (!empty($this->messages)) {
            $params['messages'] = $this->messages;
            if ($this->system) {
                array_unshift($params['messages'], ['role' => 'system', 'content' => $this->system]);
            }
            // Auto-inject strong schema instructions as a system message if needed
            if (($this->expectSchema || $this->expectArrayKey) && !$this->hasStrongSchemaInstruction($params['messages'])) {
                $schemaInstruction = $this->buildSchemaInstruction();
                array_unshift($params['messages'], ['role' => 'system', 'content' => $schemaInstruction]);
            }
        } else {
            // Fallback: build prompt as single user message
            $finalPrompt = $this->prompt;
            if (($this->expectSchema || $this->expectArrayKey) && !$this->hasStrongSchemaInstruction([$finalPrompt])) {
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

        $result = $this->provider->generate($params);
        $this->rawResponse = $result['text'] ?? '';

        // Try to extract and decode JSON (handles messy LLM outputs)
        $json = $this->extractJson($this->rawResponse) ?? $this->rawResponse;
        $data = json_decode($json, true);
        $success = false;
        if ($this->expectArrayKey && is_array($data)) {
            // If it's an array of objects, coerce schema for each item
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
            $success = true;
        } elseif ($this->expectSchema && is_array($data)) {
            // Single object
            foreach ($this->expectSchema as $key => $type) {
                if (!array_key_exists($key, $data)) {
                    $data[$key] = null;
                }
                settype($data[$key], $type);
            }
            $success = true;
        }
        return [
            'success' => $success,
            'data' => $success ? $data : null,
            'raw' => $this->rawResponse,
        ];
    }

    public function raw(): string
    {
        return $this->rawResponse;
    }

    // --- Helpers for schema instruction injection ---
    protected function hasStrongSchemaInstruction($messagesOrPrompt): bool
    {
        // Very basic check—customize as needed
        $text = is_array($messagesOrPrompt)
            ? implode(' ', array_map(fn($m) => is_array($m) ? ($m['content'] ?? '') : $m, $messagesOrPrompt))
            : $messagesOrPrompt;
        return stripos($text, 'respond only as a json') !== false;
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
     * Extract the first JSON object or array from a string (for messy LLM outputs).
     */
    protected function extractJson(string $text): ?string
    {
        // Try to extract the first JSON array or object from the text
        if (preg_match('/(\{.*\}|\[.*\])/s', $text, $matches)) {
            return $matches[0];
        }
        return null;
    }
}


