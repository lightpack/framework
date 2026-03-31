<?php

namespace Lightpack\AI;

use Lightpack\AI\Tools\ToolInvoker;
use Lightpack\AI\Tools\ToolExecutor;
use Lightpack\AI\AgentExecutor;
use Lightpack\AI\Support\JsonExtractor;

class TaskBuilder
{
    private const MAX_PROMPT_CHARS = 12000;
    private const MAX_NESTING_LEVEL = 5;
    private const MAX_ITEMS = 50;
    private const MAX_STRING_LENGTH = 800;

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
    protected ?bool $useCache = null;
    protected ?int $cacheTtl = null;
    protected array $tools = [];
    protected array $contentParts = [];

    // Agent loop properties
    protected int $maxTurns = 1;
    protected ?string $agentGoal = null;

    public function __construct($provider)
    {
        $this->provider = $provider;
    }

    /**
     * Add a message to the chat history (role: user, system, assistant).
     * Content can be a string or multimodal array.
     */
    public function message(string $role, mixed $content): self
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
                // Handle both 'key' => 'type' and 'key' => ['type', 'description']
                if (is_array($type)) {
                    $normalized[$key] = $type[0]; // Extract just the type
                } else {
                    $normalized[$key] = $type;
                }
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

    /**
     * Enable multi-turn agent mode with maximum number of turns.
     * 
     * @param int $maxTurns Maximum number of thinking-and-action cycles (default: 10)
     * @return self
     */
    public function loop(int $maxTurns = 10): self
    {
        $this->maxTurns = $maxTurns;
        return $this;
    }

    /**
     * Set the goal for agent mode.
     * Agent will work toward this goal across multiple turns.
     * 
     * @param string $goal The objective to achieve
     * @return self
     */
    public function goal(string $goal): self
    {
        $this->agentGoal = $goal;
        return $this;
    }

    public function tool(string $name, mixed $fn, ?string $description = null, array $params = []): self
    {
        $meta = ToolInvoker::extractMeta($fn);

        $this->tools[$name] = [
            'fn' => $fn,
            'description' => $description ?? $meta['description'] ?? "Tool: {$name}",
            'params' => !empty($params) ? $params : $meta['params'],
        ];

        return $this;
    }

    /**
     * Add text content to the multimodal message.
     * 
     * @param string $text The text content
     * @return self
     */
    public function text(string $text): self
    {
        $this->contentParts[] = ['type' => 'text', 'text' => $text];
        return $this;
    }

    /**
     * Add an image from base64 data to the multimodal message.
     * 
     * @param string $base64Data Base64-encoded image data
     * @param string $mimeType MIME type (e.g., 'image/jpeg', 'image/png')
     * @return self
     */
    public function image(string $base64Data, string $mimeType = 'image/jpeg'): self
    {
        $this->contentParts[] = [
            'type' => 'image_url',
            'image_url' => ['url' => "data:{$mimeType};base64,{$base64Data}"]
        ];
        return $this;
    }

    /**
     * Add an image from a URL to the multimodal message.
     * 
     * @param string $url Image URL (can be http/https or data URL)
     * @return self
     */
    public function imageUrl(string $url): self
    {
        $this->contentParts[] = [
            'type' => 'image_url',
            'image_url' => ['url' => $url]
        ];
        return $this;
    }

    /**
     * Attach an image file to the multimodal message.
     * Reads the file, encodes it to base64, and adds it to the content.
     * 
     * @param string $filePath Path to the image file
     * @return self
     * @throws \Exception If file doesn't exist or can't be read
     */
    public function attachImage(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new \Exception("Image file not found: {$filePath}");
        }
        
        $data = base64_encode(file_get_contents($filePath));
        $mimeType = mime_content_type($filePath);
        
        return $this->image($data, $mimeType);
    }

    /**
     * Add a document from base64 data to the multimodal message.
     * 
     * @param string $base64Data Base64-encoded document data
     * @param string $mimeType MIME type (e.g., 'application/pdf')
     * @return self
     */
    public function document(string $base64Data, string $mimeType = 'application/pdf'): self
    {
        $this->contentParts[] = [
            'type' => 'document',
            'data' => $base64Data,
            'mime_type' => $mimeType
        ];
        return $this;
    }

    /**
     * Attach a document file to the multimodal message.
     * Reads the file, encodes it to base64, and adds it to the content.
     * 
     * @param string $filePath Path to the document file
     * @return self
     * @throws \Exception If file doesn't exist or can't be read
     */
    public function attachDocument(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new \Exception("Document file not found: {$filePath}");
        }
        
        $data = base64_encode(file_get_contents($filePath));
        $mimeType = mime_content_type($filePath);
        
        return $this->document($data, $mimeType);
    }

    /**
     * Stream the AI response as Server-Sent Events (SSE).
     * 
     * If callback provided: streams chunks directly to callback (for testing/CLI).
     * If no callback: returns Response configured for SSE (for HTTP controllers).
     * 
     * Examples:
     * ```php
     * // HTTP controller - returns Response
     * return ai()->task()->prompt('Write an essay')->stream();
     * 
     * // Testing/CLI - callback receives chunks
     * ai()->task()->prompt('Count')->stream(function($chunk) {
     *     echo $chunk;
     * });
     * ```
     * 
     * @param callable|null $callback Optional callback to receive chunks directly
     * @return \Lightpack\Http\Response|void Returns Response if no callback, void if callback provided
     * @throws \Exception If streaming is incompatible with current configuration
     */
    public function stream(?callable $callback = null)
    {
        // Validate streaming compatibility
        if ($this->maxTurns > 1) {
            throw new \Exception('Streaming is not supported in agent mode (loop). Use run() instead.');
        }
        
        if (!empty($this->tools)) {
            throw new \Exception('Streaming is not supported with tools. Use run() instead.');
        }
        
        if ($this->expectSchema || $this->expectArrayKey) {
            throw new \Exception('Streaming is not supported with schema extraction (expect/expectArray). Use run() instead.');
        }
        
        // If callback provided, stream directly to it (for testing/CLI)
        if ($callback !== null) {
            $params = $this->buildParams();
            $this->provider->generateStream($params, $callback);
            return;
        }
        
        // Otherwise, return SSE Response (for HTTP controllers)
        return response()->sse(function($stream) {
            $params = $this->buildParams();
            $this->provider->generateStream($params, function($chunk) use ($stream) {
                $stream->push('chunk', ['text' => $chunk]);
            });
            $stream->push('done');
        });
    }

    public function run(): array
    {
        // Multi-turn agent mode
        if ($this->maxTurns > 1) {
            return $this->runAgentMode();
        }

        // Single-turn mode (existing behavior)
        if (!empty($this->tools) && ($this->prompt !== null || !empty($this->messages))) {
            return $this->runWithTools();
        }

        $params = $this->buildParams();
        if ($this->useCache !== null) {
            $params['cache'] = $this->useCache;
        }
        if ($this->cacheTtl !== null) {
            $params['cache_ttl'] = $this->cacheTtl;
        }
        $result = $this->provider->generate($params);
        $rawResponse = $result['text'] ?? '';

        $data = $this->extractAndDecodeJson($rawResponse);
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
        } elseif (!$this->expectSchema && !$this->expectArrayKey) {
            // Plain text mode - no schema expected, always success if we got a response
            $success = !empty($rawResponse);
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
            'raw' => $rawResponse,
            'errors' => $this->errors,
        ];
    }

    protected function runWithTools(): array
    {
        $this->errors = [];

        // Tool-first API requires explicit ->prompt() call. 
        // We don't extract from ->messages() to avoid losing conversation context 
        // in tool decisions (e.g., "show me the cheapest one" needs prior context).
        // For multi-turn tool conversations, use ->prompt() on each turn.
        $userQuery = $this->prompt ?? '';

        if (empty($userQuery)) {
            return [
                'success' => false,
                'data' => null,
                'raw' => null,
                'errors' => ['No user prompt provided'],
                'tools_used' => [],
                'tool_results' => [],
            ];
        }

        // Use ToolExecutor for tool workflow
        $executor = new ToolExecutor($this->tools);
        $aiGenerator = fn($prompt, $temp) => $this->generateRawText($prompt, temperature: $temp);

        $result = $executor->executeToolWorkflow($userQuery, $aiGenerator);

        // Handle tool execution result
        if (!$result['success']) {
            return [
                'success' => false,
                'data' => null,
                'raw' => $result['decision_raw'],
                'errors' => [$result['error']],
                'tools_used' => $result['tool_name'] ? [$result['tool_name']] : [],
                'tool_results' => [],
            ];
        }

        // If AI decided no tool needed, generate answer directly
        if ($result['tool_name'] === 'none') {
            $prompt = "User: {$userQuery}\n\n"
                . "Provide a helpful answer. If you need more details, ask a clarifying question.";

            $answer = $this->generateRawText($prompt, temperature: $this->temperature ?? 0.3);

            return [
                'success' => true,
                'data' => null,
                'raw' => $answer,
                'errors' => [],
                'tools_used' => [],
                'tool_results' => [],
            ];
        }

        // Tool was executed successfully, generate final answer
        $toolResultText = $this->formatForPrompt($result['tool_result']);
        $finalPrompt = "User: {$userQuery}\n\n"
            . "Tool Used: {$result['tool_name']}\n\n"
            . "Tool Result:\n{$toolResultText}\n\n"
            . "Rules:\n"
            . "- Use ONLY the Tool Result for facts.\n"
            . "- If the Tool Result does not contain enough information, say so explicitly.\n"
            . "- Do not invent details.\n\n"
            . "Answer:";

        $answer = $this->generateRawText($finalPrompt, temperature: $this->temperature ?? 0.3);

        return [
            'success' => true,
            'data' => null,
            'raw' => $answer,
            'errors' => [],
            'tools_used' => [$result['tool_name']],
            'tool_results' => [$result['tool_name'] => $result['tool_result']],
        ];
    }


    protected function generateRawText(string $prompt, float $temperature = 0.3): string
    {
        $params = $this->buildParams();
        $params['prompt'] = $prompt;
        $params['temperature'] = $temperature;

        $result = $this->provider->generate($params);
        return (string)($result['text'] ?? '');
    }

    protected function decodeJsonObject(string $text): ?array
    {
        return JsonExtractor::decode($text);
    }


    protected function formatForPrompt(mixed $value): string
    {
        if (is_string($value)) {
            return $this->truncateString($value, self::MAX_PROMPT_CHARS);
        }

        if (is_scalar($value) || $value === null) {
            return $this->truncateString((string)$value, self::MAX_PROMPT_CHARS);
        }

        $normalized = $this->normalizeForPrompt($value, self::MAX_NESTING_LEVEL, self::MAX_ITEMS, self::MAX_STRING_LENGTH);
        $json = json_encode($normalized, JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if (!is_string($json)) {
            $json = '[unserializable tool result]';
        }

        return $this->truncateString($json, self::MAX_PROMPT_CHARS);
    }

    protected function normalizeForPrompt(mixed $value, int $maxDepth, int $maxItems, int $maxStringLen): mixed
    {
        if ($maxDepth <= 0) {
            return '[max depth reached]';
        }

        if (is_string($value)) {
            return $this->truncateString($value, $maxStringLen);
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if (is_array($value)) {
            $out = [];
            $count = 0;

            foreach ($value as $k => $v) {
                if ($count >= $maxItems) {
                    $out['__truncated__'] = true;
                    $out['__truncated_reason__'] = 'max items reached';
                    break;
                }

                $key = is_string($k) ? $this->truncateString($k, 120) : $k;
                $out[$key] = $this->normalizeForPrompt($v, $maxDepth - 1, $maxItems, $maxStringLen);
                $count++;
            }

            return $out;
        }

        if (is_object($value)) {
            return $this->normalizeForPrompt(get_object_vars($value), $maxDepth - 1, $maxItems, $maxStringLen);
        }

        return '[unsupported tool result type]';
    }

    protected function truncateString(string $value, int $maxLen): string
    {
        if (strlen($value) <= $maxLen) {
            return $value;
        }

        return substr($value, 0, $maxLen) . '...';
    }

    /**
     * Build request parameters for the provider.
     */
    protected function buildParams(): array
    {
        $params = [];
        
        // If contentParts exist, build a multimodal message
        if (!empty($this->contentParts)) {
            $params['messages'] = [['role' => 'user', 'content' => $this->contentParts]];
            if ($this->system !== null) {
                array_unshift($params['messages'], ['role' => 'system', 'content' => $this->system]);
            }
        } elseif (!empty($this->messages)) {
            $params['messages'] = $this->messages;
            if ($this->system !== null) {
                array_unshift($params['messages'], ['role' => 'system', 'content' => $this->system]);
            }
        }

        if ($this->prompt) {
            $schemaInstruction = $this->buildSchemaInstruction();
            $params['prompt'] = $schemaInstruction
                ? $this->prompt . "\n\n" . $schemaInstruction
                : $this->prompt;
        }

        if ($this->temperature !== null) {
            $params['temperature'] = $this->temperature;
        }

        if ($this->maxTokens !== null) {
            $params['max_tokens'] = $this->maxTokens;
        }

        if ($this->model) {
            $params['model'] = $this->model;
        }

        if ($this->example) {
            $params['example'] = $this->example;
        }
        return $params;
    }

    /**
     * Extract and decode JSON from the raw LLM response.
     */
    protected function extractAndDecodeJson(string $text)
    {
        $json = JsonExtractor::extract($text) ?? $text;
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

            // Don't coerce null values - preserve them
            if ($data[$key] !== null) {
                // Map 'number' to 'float' for settype compatibility
                $phpType = $type === 'number' ? 'float' : $type;
                settype($data[$key], $phpType);
            }
        }
        return $data;
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
     * Run in multi-turn agent mode.
     * Agent will execute multiple turns until goal is achieved or max turns reached.
     */
    protected function runAgentMode(): array
    {
        $originalPrompt = $this->prompt;

        // Create agent executor with task executor callback
        $agent = new AgentExecutor(
            maxTurns: $this->maxTurns,
            goal: $this->agentGoal,
            taskExecutor: function (AgentExecutor $agent) {
                // Prepare prompt for next turn
                $this->prompt = $agent->prepareNextTurnPrompt();

                // Execute single turn
                return $this->executeSingleTurn();
            }
        );

        // Run agent loop
        return $agent->run($originalPrompt);
    }

    /**
     * Execute a single turn (either with tools or plain generation).
     */
    protected function executeSingleTurn(): array
    {
        // In agent mode with tools, we need different behavior than single-turn mode
        if (!empty($this->tools) && ($this->prompt !== null || !empty($this->messages))) {
            return $this->executeAgentTurnWithTools();
        }

        $params = $this->buildParams();
        if ($this->useCache !== null) {
            $params['cache'] = $this->useCache;
        }
        if ($this->cacheTtl !== null) {
            $params['cache_ttl'] = $this->cacheTtl;
        }
        $result = $this->provider->generate($params);
        $rawResponse = $result['text'] ?? '';

        $data = $this->extractAndDecodeJson($rawResponse);
        $success = false;
        $this->errors = [];

        $originalData = is_array($data) ? $data : [];

        if ($this->expectArrayKey && is_array($data)) {
            $data = $this->coerceSchemaOnArray($data);
            $success = true;
        } elseif ($this->expectSchema && is_array($data)) {
            $data = $this->coerceSchemaOnObject($data);
            $success = true;
        } elseif (!$this->expectSchema && !$this->expectArrayKey) {
            $success = !empty($rawResponse);
        }

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
            'raw' => $rawResponse,
            'errors' => $this->errors,
        ];
    }

    /**
     * Execute a single agent turn with tools.
     * Unlike runWithTools(), this doesn't generate final answer - just executes tool and returns.
     */
    protected function executeAgentTurnWithTools(): array
    {
        $this->errors = [];
        $userQuery = $this->prompt ?? '';

        if (empty($userQuery)) {
            return [
                'success' => false,
                'data' => null,
                'raw' => null,
                'errors' => ['No user prompt provided'],
                'tools_used' => [],
                'tool_results' => [],
            ];
        }

        // Use ToolExecutor for tool workflow
        $executor = new ToolExecutor($this->tools);
        $aiGenerator = fn($prompt, $temp) => $this->generateRawText($prompt, temperature: $temp);

        $result = $executor->executeToolWorkflow($userQuery, $aiGenerator);

        // Handle tool execution result
        if (!$result['success']) {
            return [
                'success' => false,
                'data' => null,
                'raw' => $result['decision_raw'],
                'errors' => [$result['error']],
                'tools_used' => $result['tool_name'] ? [$result['tool_name']] : [],
                'tool_results' => [],
            ];
        }

        // If AI decided no tool needed, generate final answer
        if ($result['tool_name'] === 'none') {
            // Generate natural language answer instead of returning JSON decision
            $answer = $this->generateRawText($userQuery, temperature: $this->temperature ?? 0.3);
            
            return [
                'success' => true,
                'data' => null,
                'raw' => $answer,
                'errors' => [],
                'tools_used' => [],
                'tool_results' => [],
            ];
        }

        // In agent mode, return tool result without generating final answer
        // Agent will decide in next turn whether to call another tool or finish
        return [
            'success' => true,
            'data' => null,
            'raw' => "Tool {$result['tool_name']} executed successfully",
            'errors' => [],
            'tools_used' => [$result['tool_name']],
            'tool_results' => [$result['tool_name'] => $result['tool_result']],
        ];
    }
}
