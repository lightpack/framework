<?php

namespace Lightpack\AI;

/**
 * Stores turn-by-turn history for agent execution.
 * Handles storing, retrieving, and formatting conversation turns.
 */
class TurnHistory
{
    private array $turns = [];

    /**
     * Add a turn to history.
     * 
     * @param string $role 'user' or 'assistant'
     * @param string $content The message content
     * @param int $turn Turn number
     * @param array $toolsUsed Tools used in this turn (for assistant only)
     * @param array $toolResults Actual tool results data (for assistant only)
     */
    public function addTurn(string $role, string $content, int $turn, array $toolsUsed = [], array $toolResults = []): void
    {
        $entry = [
            'role' => $role,
            'content' => $content,
            'turn' => $turn
        ];

        if ($role === 'assistant') {
            $entry['tools_used'] = $toolsUsed;
            $entry['tool_results'] = $toolResults;
        }

        $this->turns[] = $entry;
    }

    /**
     * Get all turns.
     */
    public function getAllTurns(): array
    {
        return $this->turns;
    }

    /**
     * Get recent N turns.
     */
    public function getRecentTurns(int $n): array
    {
        return array_slice($this->turns, -$n);
    }

    /**
     * Build context string from turn history for prompts.
     * 
     * @param int|null $recentOnly If set, only include last N turns
     * @return string Formatted context string
     */
    public function formatForPrompt(?int $recentOnly = null): string
    {
        $turns = $recentOnly ? $this->getRecentTurns($recentOnly) : $this->turns;

        if (empty($turns)) {
            return '';
        }

        $contextParts = [];
        
        foreach ($turns as $entry) {
            $role = $entry['role'] ?? 'unknown';
            $content = $entry['content'] ?? '';
            $turn = $entry['turn'] ?? 0;
            $toolsUsed = $entry['tools_used'] ?? [];
            $toolResults = $entry['tool_results'] ?? [];
            
            if ($role === 'user') {
                $contextParts[] = "User (Turn {$turn}): {$content}";
            } else {
                // Include tool results in context if tools were used
                if (!empty($toolResults)) {
                    $toolDataParts = [];
                    foreach ($toolResults as $toolName => $toolData) {
                        $formattedData = $this->formatToolResultForPrompt($toolData);
                        $toolDataParts[] = "Tool '{$toolName}' returned: {$formattedData}";
                    }
                    $toolResultsText = implode("\n", $toolDataParts);
                    $contextParts[] = "Assistant (Turn {$turn}):\n{$toolResultsText}";
                } else {
                    // No tools used, just regular content
                    $contextParts[] = "Assistant (Turn {$turn}): {$content}";
                }
            }
        }
        
        return implode("\n\n", $contextParts);
    }

    /**
     * Format tool result data for inclusion in prompts.
     * Handles arrays, objects, and scalar values with size limits.
     */
    private function formatToolResultForPrompt(mixed $data): string
    {
        if (is_array($data) || is_object($data)) {
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            
            // Truncate if too large (max 2000 chars per tool result)
            if (strlen($json) > 2000) {
                $json = substr($json, 0, 2000) . "\n... (truncated)";
            }
            
            return $json;
        }
        
        $str = (string)$data;
        
        // Truncate long strings
        if (strlen($str) > 2000) {
            return substr($str, 0, 2000) . '... (truncated)';
        }
        
        return $str;
    }
}
