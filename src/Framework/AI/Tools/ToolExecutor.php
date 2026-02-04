<?php

namespace Lightpack\AI\Tools;

use Lightpack\AI\Support\SchemaValidator;

/**
 * Handles tool execution logic including decision-making, validation, and invocation.
 * Extracted from TaskBuilder to separate concerns and enable reusability.
 */
class ToolExecutor
{
    private array $tools = [];

    public function __construct(array $tools = [])
    {
        $this->tools = $tools;
    }

    /**
     * Decide which tool to use based on user query.
     * 
     * @param string $userQuery The user's question/request
     * @param callable $aiGenerator Function to call AI provider: fn(string $prompt, float $temp) => string
     * @return array ['tool' => string, 'params' => array, 'raw' => string]
     */
    public function decideToolToUse(string $userQuery, callable $aiGenerator): array
    {
        $decisionPrompt = $this->buildToolDecisionPrompt($userQuery);
        $decisionText = $aiGenerator($decisionPrompt, 0.0);
        
        $decision = $this->decodeJsonObject($decisionText);

        if (!is_array($decision)) {
            return [
                'tool' => null,
                'params' => null,
                'raw' => $decisionText,
                'error' => 'Failed to parse tool decision JSON'
            ];
        }

        $toolName = $decision['tool'] ?? null;
        $toolParams = $decision['params'] ?? null;

        if (!is_string($toolName) || $toolName === '') {
            return [
                'tool' => null,
                'params' => null,
                'raw' => $decisionText,
                'error' => 'Tool decision missing "tool"'
            ];
        }

        return [
            'tool' => $toolName,
            'params' => $toolParams,
            'raw' => $decisionText,
            'error' => null
        ];
    }

    /**
     * Validate tool parameters against schema.
     * 
     * @param string $toolName Name of the tool
     * @param mixed $params Parameters to validate
     * @return array ['valid' => bool, 'params' => array|null, 'errors' => array]
     */
    public function validateToolParams(string $toolName, mixed $params): array
    {
        if (!isset($this->tools[$toolName])) {
            return [
                'valid' => false,
                'params' => null,
                'errors' => ["Unknown tool: {$toolName}"]
            ];
        }

        if (!is_array($params)) {
            return [
                'valid' => false,
                'params' => null,
                'errors' => ['Tool decision missing "params" object']
            ];
        }

        $toolDef = $this->tools[$toolName];
        $validator = new SchemaValidator();
        $validatedParams = $validator->validate($params, $toolDef['params'] ?? []);

        if ($validatedParams === null) {
            return [
                'valid' => false,
                'params' => null,
                'errors' => $validator->errors()
            ];
        }

        return [
            'valid' => true,
            'params' => $validatedParams,
            'errors' => []
        ];
    }

    /**
     * Invoke a tool with validated parameters.
     * 
     * @param string $toolName Name of the tool to invoke
     * @param array $params Validated parameters
     * @return array ['success' => bool, 'result' => mixed, 'error' => string|null]
     */
    public function invokeTool(string $toolName, array $params): array
    {
        if (!isset($this->tools[$toolName])) {
            return [
                'success' => false,
                'result' => null,
                'error' => "Unknown tool: {$toolName}"
            ];
        }

        $toolDef = $this->tools[$toolName];

        try {
            $result = ToolInvoker::invoke($toolDef['fn'], $params);
            return [
                'success' => true,
                'result' => $result,
                'error' => null
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'result' => null,
                'error' => 'Tool execution failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Execute complete tool workflow: decide -> validate -> invoke.
     * 
     * @param string $userQuery User's question
     * @param callable $aiGenerator AI provider function
     * @return array Complete execution result
     */
    public function executeToolWorkflow(string $userQuery, callable $aiGenerator): array
    {
        // Step 1: Decide which tool to use
        $decision = $this->decideToolToUse($userQuery, $aiGenerator);
        
        if ($decision['error'] !== null) {
            return [
                'success' => false,
                'tool_name' => null,
                'tool_result' => null,
                'decision_raw' => $decision['raw'],
                'error' => $decision['error']
            ];
        }

        $toolName = $decision['tool'];

        // Special case: AI decided no tool needed
        if ($toolName === 'none') {
            return [
                'success' => true,
                'tool_name' => 'none',
                'tool_result' => null,
                'decision_raw' => $decision['raw'],
                'error' => null
            ];
        }

        // Step 2: Validate parameters
        $validation = $this->validateToolParams($toolName, $decision['params']);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'tool_name' => $toolName,
                'tool_result' => null,
                'decision_raw' => $decision['raw'],
                'error' => implode(', ', $validation['errors'])
            ];
        }

        // Step 3: Invoke tool
        $invocation = $this->invokeTool($toolName, $validation['params']);
        
        return [
            'success' => $invocation['success'],
            'tool_name' => $toolName,
            'tool_result' => $invocation['result'],
            'decision_raw' => $decision['raw'],
            'error' => $invocation['error']
        ];
    }

    /**
     * Build prompt for AI to decide which tool to use.
     */
    protected function buildToolDecisionPrompt(string $userQuery): string
    {
        $toolLines = [];
        foreach ($this->tools as $name => $tool) {
            $toolLines[] = $this->describeToolForPrompt($name, $tool);
        }

        $toolList = implode("\n", $toolLines);

        return "Decide if you should call ONE tool to help answer the user.\n\n"
            . "User: {$userQuery}\n\n"
            . "Available Tools:\n{$toolList}\n\n"
            . "Rules:\n"
            . "- Return ONLY a JSON object. No markdown, no extra text.\n"
            . "- Choose tool=\"none\" if no tool is needed or if required parameters are missing.\n"
            . "- If you choose a tool, include a JSON object params with all required parameters.\n\n"
            . "Response format:\n"
            . '{"tool":"tool_name_or_none","params":{}}';
    }

    /**
     * Describe a tool for inclusion in the decision prompt.
     */
    protected function describeToolForPrompt(string $name, array $tool): string
    {
        $desc = $tool['description'] ?? 'No description';
        $params = $tool['params'] ?? [];

        $paramLines = [];
        foreach ($params as $paramName => $paramInfo) {
            $type = is_array($paramInfo) ? ($paramInfo[0] ?? 'any') : 'any';
            $description = is_array($paramInfo) ? ($paramInfo[1] ?? '') : '';
            $paramLines[] = "  - {$paramName} ({$type}): {$description}";
        }

        $paramBlock = empty($paramLines) ? '  (no parameters)' : implode("\n", $paramLines);

        return "Tool: {$name}\nDescription: {$desc}\nParameters:\n{$paramBlock}";
    }

    /**
     * Decode JSON from AI response.
     */
    protected function decodeJsonObject(string $text): mixed
    {
        $text = trim($text);
        
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $matches)) {
            $text = $matches[1];
        }
        
        $decoded = json_decode($text, true);
        
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
    }
}
