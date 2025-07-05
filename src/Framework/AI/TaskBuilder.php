<?php
namespace Lightpack\AI;

class TaskBuilder
{
    protected array $messages = [];
    protected $provider;
    protected ?string $prompt = null;
    protected ?array $expectSchema = null;
    protected ?string $expectArrayKey = null;
    protected array $requiredFields = [];
    protected array $errors = [];
    protected ?array $example = null;
    protected ?string $model = null;
    protected ?float $temperature = null;
    protected ?int $maxTokens = null;
    protected ?string $system = null;
    protected ?string $rawResponse = null;
    protected ?bool $useCache = null;
    protected ?int $cacheTtl = null;
    
    public function __construct($provider)
    {
        $this->provider = $provider;
    }

    /**
     * Add a message to the chat history (role: user, system, assistant).
     */
    public function message(string $role, string $content): self
    {
        $this->messages[] = ['role' => $role, 'content' => $content];
        return $this;
    }

    public function prompt(string $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    public function expect(array $schema): self
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

    /**
     * Specify required fields for the result.
     */
    public function required(string ...$fields): self
    {
        $this->requiredFields = $fields;
        return $this;
    }

    public function expectArray(string $key = 'item'): self
    {
        $this->expectArrayKey = $key;
        return $this;
    }

    public function example(array $example): self
    {
        $this->example = $example;
        return $this;
    }

    public function model(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;
        return $this;
    }

    public function maxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;
        return $this;
    }

    public function system(string $system): self
    {
        $this->system = $system;
        return $this;
    }

    public function cache(bool $useCache): self
    {
        $this->useCache = $useCache;
        return $this;
    }

    public function cacheTtl(int $ttl): self
    {
        $this->cacheTtl = $ttl;
        return $this;
    }

    public function run(): array
    {
        $params = $this->buildParams();
        if ($this->useCache !== null) {
            $params['cache'] = $this->useCache;
        }
        if ($this->cacheTtl !== null) {
            $params['cache_ttl'] = $this->cacheTtl;
        }
        $result = $this->provider->generate($params);
        $this->rawResponse = $result['text'] ?? '';

        $data = $this->extractAndDecodeJson($this->rawResponse);
        $success = false;
        $this->errors = [];

        // Store original data for required field check
        $originalData = is_array($data) ? $data : [];

        if ($this->expectArrayKey && is_array($data)) {
            $data = $this->coerceSchemaOnArray($data);
            $success = true;
        } elseif ($this->expectSchema && is_array($data)) {
            $data = $this->coerceSchemaOnObject($data);
            $success = true;
        }

        // Check required fields BEFORE coercion
        if ($success && !empty($this->requiredFields)) {
            if ($this->expectArrayKey && is_array($originalData)) {
                $this->validateRequiredFieldsInArray($originalData);
            } else {
                $this->validateRequiredFieldsInObject($originalData);
            }
            if (!empty($this->errors)) {
                $success = false;
            }
        }


        return [
            'success' => $success,
            'data' => $success ? $data : null,
            'raw' => $this->rawResponse,
            'errors' => $this->errors,
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

    /**
     * Validate required fields in a single object.
     */
    private function validateRequiredFieldsInObject(array $data): void
    {
        foreach ($this->requiredFields as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null) {
                $this->errors[] = "Missing required field: $field";
            }
        }
    }

    /**
     * Validate required fields in an array of objects.
     */
    private function validateRequiredFieldsInArray(array $items): void
    {
        foreach ($items as $i => $item) {
            foreach ($this->requiredFields as $field) {
                if (!array_key_exists($field, $item) || $item[$field] === null) {
                    $this->errors[] = "Item " . ($i + 1) . ": Missing required field: $field";
                }
            }
        }
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
    /**
     * Attempt to extract valid JSON (array or object) from messy LLM output.
     * Handles the most common LLM output quirks:
     *
     *   1. JSON array:
     *      - Example input: 'Here is your data: [{"a":1},{"b":2}]'
     *      - Extracted:      '[{"a":1},{"b":2}]'
     *
     *   2. Multiple JSON objects (newline or space separated):
     *      - Example input: '{"a":1}\n{"b":2}\n{"c":3}'
     *      - Extracted:      '[{"a":1},{"b":2},{"c":3}]'
     *
     *   3. Single JSON object:
     *      - Example input: 'Result: {"a":1, "b":2}'
     *      - Extracted:      '{"a":1, "b":2}'
     *
     *   4. If nothing found, returns null.
     *
     * This makes the builder robust to unpredictable LLM output formatting.
     */
    protected function extractJson(string $text): ?string
    {
        // 1. Try to extract a JSON array (most robust, preferred format)
        if (preg_match('/(\[.*\])/s', $text, $matches)) {
            return $matches[0];
        }
        // 2. If multiple JSON objects (e.g., separated by newlines), wrap as array
        if (preg_match_all('/\{.*?\}/s', $text, $matches) && count($matches[0]) > 1) {
            // Join all found objects into a valid JSON array
            return '[' . implode(',', $matches[0]) . ']';
        }
        // 3. Fallback: extract a single JSON object
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            return $matches[0];
        }
        // 4. Nothing found: return null
        return null;
    }
}


