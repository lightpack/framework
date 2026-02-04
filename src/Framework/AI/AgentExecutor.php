<?php

namespace Lightpack\AI;

/**
 * Handles multi-turn agent execution logic.
 * Extracted from TaskBuilder to separate agent concerns from single-turn task execution.
 */
class AgentExecutor
{
    private int $maxTurns;
    private ?string $goal;
    private TurnHistory $history;
    private $taskExecutor;

    public function __construct(
        int $maxTurns,
        ?string $goal,
        callable $taskExecutor
    ) {
        $this->maxTurns = $maxTurns;
        $this->goal = $goal;
        $this->taskExecutor = $taskExecutor;
        $this->history = new TurnHistory();
    }

    /**
     * Run the agent loop until goal is achieved or max turns reached.
     * 
     * @param string $originalPrompt The initial user prompt
     * @return array Result with agent_turns, agent_memory, goal_achieved
     */
    public function run(string $originalPrompt): array
    {
        $currentTurn = 0;
        $allToolsUsed = [];
        $allToolResults = [];
        
        // Initialize history with user's request
        if ($originalPrompt) {
            $this->history->addTurn('user', $originalPrompt, 0);
        }
        
        while ($currentTurn < $this->maxTurns) {
            // Execute single turn
            $result = ($this->taskExecutor)($this);
            
            // Accumulate tools used across all turns
            if (!empty($result['tools_used'])) {
                $allToolsUsed = array_merge($allToolsUsed, $result['tools_used']);
            }
            if (!empty($result['tool_results'])) {
                $allToolResults = array_merge($allToolResults, $result['tool_results']);
            }
            
            // Store turn result in history with tool results
            $this->history->addTurn(
                'assistant',
                $result['raw'] ?? '',
                $currentTurn + 1,
                $result['tools_used'] ?? [],
                $result['tool_results'] ?? []
            );
            
            // Check if goal achieved or task complete
            if ($this->isTaskComplete($result)) {
                return array_merge($result, [
                    'agent_turns' => $currentTurn + 1,
                    'agent_memory' => $this->history->getAllTurns(),
                    'goal_achieved' => true,
                    'tools_used' => $allToolsUsed,
                    'tool_results' => $allToolResults,
                ]);
            }
            
            $currentTurn++;
        }
        
        // Max turns reached without completion
        $recentTurns = $this->history->getRecentTurns(1);
        $lastTurn = !empty($recentTurns) ? $recentTurns[0] : [];
        
        return [
            'success' => false,
            'data' => null,
            'raw' => $lastTurn['content'] ?? '',
            'errors' => ['Agent reached maximum turns without achieving goal'],
            'agent_turns' => $currentTurn,
            'agent_memory' => $this->history->getAllTurns(),
            'goal_achieved' => false,
            'tools_used' => $allToolsUsed,
            'tool_results' => $allToolResults,
        ];
    }

    /**
     * Check if the task is complete.
     */
    protected function isTaskComplete(array $result): bool
    {
        // If no tools were used and we got a successful response, task is complete
        if (empty($result['tools_used']) && $result['success']) {
            return true;
        }
        
        // If we have a substantive response with no more tool calls needed, we're done
        if (!empty($result['raw']) && empty($result['tools_used'])) {
            return true;
        }
        
        return false;
    }

    /**
     * Get the current turn history.
     */
    public function getHistory(): array
    {
        return $this->history->getAllTurns();
    }

    /**
     * Build context string from turn history for next turn.
     */
    public function buildHistoryContext(): string
    {
        return $this->history->formatForPrompt();
    }

    /**
     * Prepare prompt for next turn with context and goal.
     */
    public function prepareNextTurnPrompt(): string
    {
        $historyContext = $this->buildHistoryContext();
        
        if ($this->goal) {
            return "Goal: {$this->goal}\n\n"
                . "Previous Context:\n{$historyContext}\n\n"
                . "Continue working towards the goal. What should you do next?";
        }
        
        return "Previous Context:\n{$historyContext}\n\n"
            . "Continue with the task. What should you do next?";
    }
}
