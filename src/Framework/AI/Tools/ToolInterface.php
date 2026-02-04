<?php
namespace Lightpack\AI\Tools;

interface ToolInterface
{
    /**
     * Execute the tool with validated parameters.
     */
    public function __invoke(array $params): mixed;
    
    /**
     * Describe what this tool does (used by AI for decision-making).
     */
    public static function description(): string;
    
    /**
     * Define the parameter schema for this tool.
     * 
     * Format: ['param_name' => 'type'] or ['param_name' => ['type', 'description']]
     * Types: 'string', 'int', 'number', 'bool', 'array'
     */
    public static function params(): array;
}
