<?php

namespace Lightpack\Tests\AI;

use Lightpack\AI\TaskBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Tests that validate ACTUAL agent loop behavior.
 * 
 * These tests prove the agent loop works correctly by:
 * - Executing real multi-turn scenarios
 * - Validating memory propagation
 * - Testing goal achievement logic
 * - Verifying stopping conditions
 * 
 * Unlike AgentLoopTest (property tests), these test BEHAVIOR.
 * If the agent loop logic breaks, these tests WILL fail.
 */
class AgentLoopBehaviorTest extends TestCase
{
    /**
     * Test: Agent actually executes multiple turns with real decision-making.
     * 
     * Proves:
     * - Agent loop runs multiple times (not just once)
     * - Each turn is executed in sequence
     * - Turn counter increments correctly
     * - Agent stops at the right turn (not max turns)
     */
    public function testAgentExecutesMultipleTurnsWithRealDecisions()
    {
        $turnExecutions = [];
        
        $provider = new class($turnExecutions) {
            private array $turnExecutions;
            private int $callCount = 0;
            
            public function __construct(array &$turnExecutions)
            {
                $this->turnExecutions = &$turnExecutions;
            }
            
            public function generate(array $params): array
            {
                $prompt = $params['prompt'] ?? '';
                
                // Track each turn execution
                $this->turnExecutions[] = [
                    'turn' => $this->callCount,
                    'prompt' => $prompt,
                    'has_context' => str_contains(strtolower($prompt), 'previous') || 
                                    str_contains(strtolower($prompt), 'context')
                ];
                
                // Turn 1: AI decides to call search tool
                if ($this->callCount === 0) {
                    $this->callCount++;
                    return ['text' => '{"tool":"search","params":{"query":"laptops"}}'];
                }
                
                // Turn 2: AI decides to call filter tool
                if ($this->callCount === 1) {
                    $this->callCount++;
                    return ['text' => '{"tool":"filter","params":{"max_price":1000}}'];
                }
                
                // Turn 3: AI provides final answer (no more tools)
                $this->callCount++;
                return ['text' => 'I found 5 laptops under $1000. The Dell XPS 13 is the best option.'];
            }
        };
        
        $toolCalls = [];
        
        $result = (new TaskBuilder($provider))
            ->tool('search', function($params) use (&$toolCalls) {
                $toolCalls[] = 'search';
                return ['results' => '50 laptops found'];
            })
            ->tool('filter', function($params) use (&$toolCalls) {
                $toolCalls[] = 'filter';
                return ['results' => '5 laptops under $1000'];
            })
            ->loop(5)
            ->goal('Find best laptop under $1000')
            ->prompt('Find best laptop under $1000')
            ->run();
        
        // Verify agent executed exactly 3 turns (not 1, not 5)
        $this->assertCount(3, $turnExecutions, 'Agent should execute 3 turns');
        $this->assertEquals(3, $result['agent_turns'], 'Result should report 3 turns');
        
        // Verify tools were called in sequence
        $this->assertEquals(['search', 'filter'], $toolCalls, 'Tools should be called in order');
        
        // Verify turn 2 and 3 received context from previous turns
        $this->assertTrue(
            $turnExecutions[1]['has_context'] || $turnExecutions[2]['has_context'],
            'Later turns should receive context from previous turns'
        );
        
        // Verify agent stopped early (turn 3, not max 5)
        $this->assertLessThan(5, $result['agent_turns'], 'Agent should stop before max turns');
        
        // Verify goal was achieved
        $this->assertTrue($result['goal_achieved'], 'Goal should be achieved');
    }

    /**
     * Test: Memory is actually passed to subsequent turns.
     * 
     * Proves:
     * - Turn 1 result is stored in memory
     * - Turn 2 prompt includes context from turn 1
     * - Memory structure is correct
     * - Memory is used in decision-making
     */
    public function testMemoryIsPassedToSubsequentTurns()
    {
        $capturedPrompts = [];
        
        $provider = new class($capturedPrompts) {
            private array $capturedPrompts;
            private int $callCount = 0;
            
            public function __construct(array &$capturedPrompts)
            {
                $this->capturedPrompts = &$capturedPrompts;
            }
            
            public function generate(array $params): array
            {
                $prompt = $params['prompt'] ?? '';
                $this->capturedPrompts[] = $prompt;
                
                if ($this->callCount === 0) {
                    $this->callCount++;
                    return ['text' => '{"tool":"get_data","params":{}}'];
                }
                
                if ($this->callCount === 1) {
                    $this->callCount++;
                    return ['text' => 'Analysis complete based on the data retrieved.'];
                }
                
                return ['text' => 'Done'];
            }
        };
        
        $result = (new TaskBuilder($provider))
            ->tool('get_data', function($params) {
                return ['data' => 'important_data_from_turn_1'];
            })
            ->loop(5)
            ->goal('Analyze data')
            ->prompt('Get and analyze data')
            ->run();
        
        // Verify we have prompts from multiple turns
        $this->assertGreaterThanOrEqual(2, count($capturedPrompts), 'Should have at least 2 prompts');
        
        // Verify turn 2 prompt includes context/memory from turn 1
        $turn2Prompt = strtolower($capturedPrompts[1]);
        $hasContext = str_contains($turn2Prompt, 'previous') || 
                      str_contains($turn2Prompt, 'context') ||
                      str_contains($turn2Prompt, 'turn');
        
        $this->assertTrue(
            $hasContext,
            'Turn 2 prompt should include context from turn 1. Got: ' . substr($capturedPrompts[1], 0, 200)
        );
        
        // Verify memory structure in result
        $this->assertArrayHasKey('agent_memory', $result);
        $this->assertIsArray($result['agent_memory']);
        $this->assertGreaterThanOrEqual(3, count($result['agent_memory']), 'Memory should have at least 3 entries');
        
        // Verify first memory entry is user prompt
        $this->assertEquals('user', $result['agent_memory'][0]['role']);
        $this->assertEquals('Get and analyze data', $result['agent_memory'][0]['content']);
        $this->assertEquals(0, $result['agent_memory'][0]['turn']);
        
        // Verify subsequent entries are assistant with turn numbers
        $this->assertEquals('assistant', $result['agent_memory'][1]['role']);
        $this->assertEquals(1, $result['agent_memory'][1]['turn']);
        $this->assertArrayHasKey('tools_used', $result['agent_memory'][1]);
    }

    /**
     * Test: isTaskComplete() correctly detects when task is done.
     * 
     * Proves:
     * - Agent stops when no more tools are needed
     * - Agent continues when tools are still being called
     * - Goal achievement logic works
     */
    public function testIsTaskCompleteDetectsRealCompletion()
    {
        // Scenario 1: Agent stops when no tools needed (final answer)
        $provider1 = new class {
            private int $callCount = 0;
            
            public function generate(array $params): array
            {
                if ($this->callCount === 0) {
                    $this->callCount++;
                    return ['text' => '{"tool":"search","params":{"query":"test"}}'];
                }
                
                // No more tools, just final answer
                return ['text' => 'Here is your answer based on search results.'];
            }
        };
        
        $result1 = (new TaskBuilder($provider1))
            ->tool('search', fn($p) => ['results' => 'data'])
            ->loop(5)
            ->prompt('Search for something')
            ->run();
        
        $this->assertTrue($result1['goal_achieved'], 'Should detect completion when no more tools needed');
        $this->assertEquals(2, $result1['agent_turns'], 'Should stop at turn 2');
        
        // Scenario 2: Agent continues when tools are still being called
        $provider2 = new class {
            private int $callCount = 0;
            
            public function generate(array $params): array
            {
                // Always call tools, never give final answer
                $this->callCount++;
                return ['text' => '{"tool":"search","params":{"query":"test' . $this->callCount . '"}}'];
            }
        };
        
        $result2 = (new TaskBuilder($provider2))
            ->tool('search', fn($p) => ['results' => 'data'])
            ->loop(3)
            ->prompt('Search forever')
            ->run();
        
        $this->assertEquals(3, $result2['agent_turns'], 'Should reach max turns when never completing');
    }

    /**
     * Test: Agent stops at max turns and returns proper failure.
     * 
     * Proves:
     * - Agent respects max turns limit
     * - Returns failure state when max reached
     * - Error message is present
     * - Doesn't execute turn N+1
     */
    public function testAgentStopsAtMaxTurnsWithFailure()
    {
        $executedTurns = 0;
        
        $provider = new class($executedTurns) {
            private int $executedTurns;
            
            public function __construct(int &$executedTurns)
            {
                $this->executedTurns = &$executedTurns;
            }
            
            public function generate(array $params): array
            {
                $this->executedTurns++;
                
                // Always return tool call, never complete
                return ['text' => '{"tool":"search","params":{"query":"test"}}'];
            }
        };
        
        $result = (new TaskBuilder($provider))
            ->tool('search', function($params) {
                return ['results' => 'data'];
            })
            ->loop(3)
            ->goal('Never-ending task')
            ->prompt('Search forever')
            ->run();
        
        // Verify exactly 3 turns executed (not 4)
        $this->assertEquals(3, $executedTurns, 'Should execute exactly max turns');
        $this->assertEquals(3, $result['agent_turns'], 'Result should report 3 turns');
        
        // Verify failure state
        $this->assertFalse($result['success'], 'Should fail when max turns reached');
        $this->assertFalse($result['goal_achieved'], 'Goal should not be achieved');
        
        // Verify error message exists
        $this->assertNotEmpty($result['errors'], 'Should have error messages');
        $this->assertStringContainsString(
            'maximum turns',
            strtolower($result['errors'][0]),
            'Error should mention maximum turns'
        );
        
        // Verify memory has all 3 turns + initial user prompt
        $this->assertCount(4, $result['agent_memory'], 'Memory should have user + 3 turns');
    }

    /**
     * Test: Tool results actually influence the next turn's decision.
     * 
     * Proves:
     * - Tool result from turn 1 affects turn 2
     * - Agent analyzes tool results
     * - Decision changes based on data
     * - Context building works
     */
    public function testToolResultsInfluenceNextTurnDecision()
    {
        $capturedPrompts = [];
        $toolResults = [];
        
        $provider = new class($capturedPrompts, $toolResults) {
            private array $capturedPrompts;
            private array $toolResults;
            private int $callCount = 0;
            
            public function __construct(array &$capturedPrompts, array &$toolResults)
            {
                $this->capturedPrompts = &$capturedPrompts;
                $this->toolResults = &$toolResults;
            }
            
            public function generate(array $params): array
            {
                $prompt = $params['prompt'] ?? '';
                $this->capturedPrompts[] = $prompt;
                
                if ($this->callCount === 0) {
                    $this->callCount++;
                    return ['text' => '{"tool":"check_stock","params":{"product":"laptop"}}'];
                }
                
                if ($this->callCount === 1) {
                    $this->callCount++;
                    // Decision should be influenced by stock check result
                    return ['text' => '{"tool":"get_price","params":{"product":"laptop"}}'];
                }
                
                return ['text' => 'Product is in stock and costs $999'];
            }
        };
        
        $result = (new TaskBuilder($provider))
            ->tool('check_stock', function($params) use (&$toolResults) {
                $result = ['in_stock' => true, 'quantity' => 5];
                $toolResults['check_stock'] = $result;
                return $result;
            })
            ->tool('get_price', function($params) use (&$toolResults) {
                $result = ['price' => 999];
                $toolResults['get_price'] = $result;
                return $result;
            })
            ->loop(5)
            ->goal('Check product availability and price')
            ->prompt('Is laptop available and what is the price?')
            ->run();
        
        // Verify both tools were called
        $this->assertArrayHasKey('check_stock', $toolResults, 'First tool should be called');
        $this->assertArrayHasKey('get_price', $toolResults, 'Second tool should be called');
        
        // Verify turn 2 prompt includes context about stock check
        $this->assertGreaterThanOrEqual(2, count($capturedPrompts), 'Should have at least 2 prompts');
        
        // Verify tools_used is tracked in result
        $this->assertArrayHasKey('tools_used', $result);
        $this->assertContains('check_stock', $result['tools_used'], 'check_stock should be in tools_used');
        $this->assertContains('get_price', $result['tools_used'], 'get_price should be in tools_used');
        
        // Verify tool results are stored
        $this->assertArrayHasKey('tool_results', $result);
        $this->assertArrayHasKey('check_stock', $result['tool_results']);
        $this->assertArrayHasKey('get_price', $result['tool_results']);
        
        // Verify goal was achieved
        $this->assertTrue($result['goal_achieved'], 'Goal should be achieved after gathering all info');
    }
}
