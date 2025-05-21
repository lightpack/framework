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
        $this->expectSchema = $schema;
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
                // Optionally prepend a system message if set via .system()
                array_unshift($params['messages'], ['role' => 'system', 'content' => $this->system]);
            }
        } else {
            // Fallback: build prompt as single user message
            $finalPrompt = $this->prompt;
            if ($this->expectSchema) {
                $keys = implode("', '", array_keys($this->expectSchema));
                $finalPrompt .= " Respond ONLY as a JSON object with keys: '$keys'.";
            }
            if ($this->expectArrayKey) {
                $finalPrompt .= " Respond ONLY as a JSON array of $this->expectArrayKey objects.";
            }
            if ($this->example) {
                $finalPrompt .= " Example: " . json_encode($this->example);
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

        // Try to decode as JSON
        $data = json_decode($this->rawResponse, true);
        $success = false;
        if ($this->expectSchema && is_array($data)) {
            // Ensure all keys exist, coerce types
            foreach ($this->expectSchema as $key => $type) {
                if (!array_key_exists($key, $data)) {
                    $data[$key] = null;
                }
                settype($data[$key], $type);
            }
            $success = true;
        }
        if ($this->expectArrayKey && is_array($data)) {
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
}
