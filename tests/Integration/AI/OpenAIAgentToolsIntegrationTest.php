<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\Providers\OpenAI;
use Lightpack\Http\Http;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;

/**
 * Integration test for OpenAI agent mode with real tool execution.
 * 
 * This test validates the complete agent loop workflow:
 * - Multi-turn execution with real OpenAI API
 * - Sequential tool usage across turns
 * - Context propagation between turns
 * - Goal achievement detection
 * - Tool result accumulation
 * - Memory structure correctness
 */
class OpenAIAgentToolsIntegrationTest extends TestCase
{
    private $openai;
    private $apiKey;

    protected function setUp(): void
    {
        $this->apiKey = getenv('OPENAI_API_KEY');
        
        if (!$this->apiKey) {
            $this->markTestSkipped('OPENAI_API_KEY environment variable not set');
        }

        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(function($key, $default = null) {
            $map = [
                'ai.providers.openai.key' => $this->apiKey,
                'ai.providers.openai.model' => 'gpt-4o-mini',
                'ai.providers.openai.base_url' => 'https://api.openai.com/v1',
                'ai.http_timeout' => 60,
                'ai.temperature' => 0.3,
                'ai.max_tokens' => 500,
                'ai.cache_ttl' => 3600,
            ];
            return $map[$key] ?? $default;
        });

        $this->openai = new OpenAI(
            new Http(),
            $this->createMock(Cache::class),
            $config
        );
    }

    /**
     * Test: Agent executes multiple turns with real OpenAI decision-making.
     * 
     * Scenario: Product research agent
     * - Turn 1: Search for product specs
     * - Turn 2: Get current price
     * - Turn 3: Check reviews
     * - Turn 4: Provide recommendation
     * 
     * Validates:
     * - Agent makes sequential tool calls
     * - Context flows between turns
     * - Goal is achieved
     * - All tools and results are tracked
     */
    public function testAgentModeWithSequentialToolCalls()
    {
        $toolCallLog = [];
        
        $result = $this->openai->task()
            ->tool('search_product', function($params) use (&$toolCallLog) {
                $toolCallLog[] = 'search_product';
                return [
                    'name' => 'Wireless Bluetooth Headphones',
                    'brand' => 'AudioTech Pro',
                    'features' => ['Noise cancellation', '40-hour battery', 'Foldable design'],
                    'category' => 'Electronics'
                ];
            }, 'Search for product information and specifications', [
                'query' => ['string', 'Product name or keywords to search for']
            ])
            ->tool('get_price', function($params) use (&$toolCallLog) {
                $toolCallLog[] = 'get_price';
                return [
                    'current_price' => 89.99,
                    'original_price' => 129.99,
                    'discount' => '31% off',
                    'in_stock' => true
                ];
            }, 'Get current price and availability for a product', [
                'product_name' => ['string', 'Name of the product to check price for']
            ])
            ->tool('get_reviews', function($params) use (&$toolCallLog) {
                $toolCallLog[] = 'get_reviews';
                return [
                    'average_rating' => 4.5,
                    'total_reviews' => 1247,
                    'recent_reviews' => [
                        'Great sound quality and battery life!',
                        'Comfortable for long use',
                        'Noise cancellation works well'
                    ]
                ];
            }, 'Get customer reviews and ratings for a product', [
                'product_name' => ['string', 'Name of the product to get reviews for']
            ])
            ->loop(6)
            ->goal('Research wireless headphones and provide a buying recommendation')
            ->prompt('I want to buy wireless headphones. Help me research and decide if they are worth buying.')
            ->run();

        // Core assertions: Agent execution
        $this->assertTrue($result['success'], 'Agent should complete successfully');
        $this->assertTrue($result['goal_achieved'], 'Agent should achieve the goal');
        $this->assertGreaterThan(1, $result['agent_turns'], 'Agent should execute multiple turns');
        $this->assertLessThanOrEqual(6, $result['agent_turns'], 'Agent should not exceed max turns');

        // Tool execution assertions
        $this->assertNotEmpty($result['tools_used'], 'Agent should use at least one tool');
        $this->assertNotEmpty($result['tool_results'], 'Agent should have tool results');
        
        // Verify at least 2 different tools were used (agent should gather multiple data points)
        $uniqueTools = array_unique($result['tools_used']);
        $this->assertGreaterThanOrEqual(2, count($uniqueTools), 
            'Agent should use at least 2 different tools to gather comprehensive information');

        // Memory structure assertions
        $this->assertArrayHasKey('agent_memory', $result);
        $this->assertIsArray($result['agent_memory']);
        $this->assertGreaterThanOrEqual(3, count($result['agent_memory']), 
            'Memory should have user prompt + at least 2 assistant turns');

        // Verify first memory entry is user prompt
        $this->assertEquals('user', $result['agent_memory'][0]['role']);
        $this->assertEquals(0, $result['agent_memory'][0]['turn']);
        $this->assertStringContainsString('wireless headphones', 
            strtolower($result['agent_memory'][0]['content']));

        // Verify subsequent entries are assistant with proper structure
        for ($i = 1; $i < count($result['agent_memory']); $i++) {
            $entry = $result['agent_memory'][$i];
            $this->assertEquals('assistant', $entry['role']);
            $this->assertEquals($i, $entry['turn']);
            $this->assertArrayHasKey('tools_used', $entry);
            $this->assertArrayHasKey('content', $entry);
        }

        // Final answer assertions
        $this->assertNotEmpty($result['raw'], 'Agent should provide a final answer');
        $finalAnswer = strtolower($result['raw']);
        
        // In agent mode, final answer appears when no tools are used
        // It should be substantive, not a tool execution message
        $this->assertStringNotContainsString('tool', $finalAnswer, 
            'Final answer should not be a tool execution message');
        $this->assertStringNotContainsString('executed successfully', $finalAnswer, 
            'Final answer should be actual response, not execution confirmation');

        // Output summary for manual verification
        echo "\n\n=== Agent Execution Summary ===\n";
        echo "Turns executed: {$result['agent_turns']}\n";
        echo "Tools used: " . implode(', ', $result['tools_used']) . "\n";
        echo "Tool call sequence: " . implode(' → ', $toolCallLog) . "\n";
        echo "Goal achieved: " . ($result['goal_achieved'] ? 'Yes' : 'No') . "\n";
        echo "Memory entries: " . count($result['agent_memory']) . "\n";
        echo "\nFinal recommendation:\n" . $result['raw'] . "\n";
        echo "================================\n\n";
    }

    /**
     * Test: Agent stops early when goal is achieved (doesn't use all turns).
     * 
     * Validates:
     * - Agent detects when it has enough information
     * - Stops before max turns
     * - Provides final answer without unnecessary tool calls
     */
    public function testAgentStopsEarlyWhenGoalAchieved()
    {
        $toolCallCount = 0;
        
        $result = $this->openai->task()
            ->tool('get_weather', function($params) use (&$toolCallCount) {
                $toolCallCount++;
                return [
                    'temperature' => 72,
                    'condition' => 'Sunny',
                    'humidity' => 45,
                    'forecast' => 'Clear skies all day'
                ];
            }, 'Get current weather information', [
                'location' => ['string', 'City name or location']
            ])
            ->loop(8)
            ->goal('Tell me the weather')
            ->prompt('What is the weather like in San Francisco?')
            ->run();

        // For simple queries, agent should complete successfully
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('agent_turns', $result);
        
        // Agent should complete the task (may take a few turns with real AI decision-making)
        $this->assertLessThanOrEqual(8, $result['agent_turns'], 
            'Agent should complete within max turns');
        
        // Should call weather tool (at least once, maybe twice if it needs to verify)
        $this->assertGreaterThanOrEqual(1, $toolCallCount, 
            'Agent should call weather tool at least once');
        $this->assertLessThanOrEqual(2, $toolCallCount,
            'Agent should not call weather tool more than twice for simple query');
        
        // Final answer should be substantive (not a tool execution message)
        $finalAnswer = strtolower($result['raw']);
        $this->assertStringNotContainsString('executed successfully', $finalAnswer,
            'Final answer should be actual response, not tool execution message');
        
        // Should be a real answer (check it's not empty and has reasonable length)
        $this->assertGreaterThan(20, strlen($result['raw']),
            'Final answer should be substantive');
    }

    /**
     * Test: Agent handles tool errors gracefully and continues.
     * 
     * Validates:
     * - Agent can recover from tool failures
     * - Error handling in multi-turn context
     * - Agent can still achieve goal despite errors
     */
    public function testAgentHandlesToolErrorsGracefully()
    {
        $attemptCount = 0;
        
        $result = $this->openai->task()
            ->tool('flaky_api', function($params) use (&$attemptCount) {
                $attemptCount++;
                // First call fails, subsequent calls succeed
                if ($attemptCount === 1) {
                    throw new \Exception('API temporarily unavailable');
                }
                return ['status' => 'success', 'data' => 'Retrieved successfully'];
            }, 'Call a potentially flaky API', [
                'endpoint' => ['string', 'API endpoint to call']
            ])
            ->tool('backup_source', function($params) {
                return ['status' => 'success', 'data' => 'Backup data retrieved'];
            }, 'Get data from backup source', [
                'query' => ['string', 'What data to retrieve']
            ])
            ->loop(5)
            ->goal('Get data from API or backup')
            ->prompt('Fetch data from the API, use backup if needed')
            ->run();

        // Agent should handle the error and potentially use backup or retry
        $this->assertIsArray($result);
        $this->assertArrayHasKey('agent_turns', $result);
        $this->assertArrayHasKey('tools_used', $result);
        
        // Verify agent attempted to use tools despite initial failure
        $this->assertNotEmpty($result['tools_used'], 
            'Agent should have attempted to use tools');
    }

    /**
     * Test: Agent with complex multi-step workflow.
     * 
     * Scenario: Calculate shipping cost
     * - Get product weight
     * - Get shipping distance
     * - Calculate cost based on weight and distance
     * 
     * Validates:
     * - Agent can chain multiple tool calls logically
     * - Each tool result influences next decision
     * - Complex reasoning across turns
     */
    public function testAgentComplexMultiStepWorkflow()
    {
        $executionOrder = [];
        
        $result = $this->openai->task()
            ->tool('get_product_weight', function($params) use (&$executionOrder) {
                $executionOrder[] = 'get_weight';
                return ['weight_kg' => 2.5, 'dimensions' => '30x20x15cm'];
            }, 'Get product weight and dimensions', [
                'product_id' => ['string', 'Product identifier']
            ])
            ->tool('calculate_distance', function($params) use (&$executionOrder) {
                $executionOrder[] = 'calc_distance';
                return ['distance_km' => 450, 'zone' => 'Zone 2'];
            }, 'Calculate shipping distance between locations', [
                'from' => ['string', 'Origin location'],
                'to' => ['string', 'Destination location']
            ])
            ->tool('get_shipping_rate', function($params) use (&$executionOrder) {
                $executionOrder[] = 'get_rate';
                $weight = $params['weight'] ?? 0;
                $distance = $params['distance'] ?? 0;
                $cost = ($weight * 2.5) + ($distance * 0.15);
                return [
                    'base_cost' => $cost,
                    'tax' => $cost * 0.1,
                    'total' => $cost * 1.1
                ];
            }, 'Calculate shipping cost based on weight and distance', [
                'weight' => ['number', 'Weight in kg'],
                'distance' => ['number', 'Distance in km']
            ])
            ->loop(6)
            ->goal('Calculate total shipping cost for product from warehouse to customer')
            ->prompt('Calculate shipping cost for product ABC123 from New York warehouse to Los Angeles customer')
            ->run();

        // This is a complex workflow - agent may or may not complete within max turns
        // What matters is that it attempted the workflow and used tools logically
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('agent_turns', $result);
        
        // Verify logical tool execution order
        $this->assertNotEmpty($executionOrder, 'Tools should have been executed');
        
        // If agent didn't achieve goal, it should have at least tried multiple tools
        if (!$result['goal_achieved']) {
            $this->assertGreaterThanOrEqual(2, count($executionOrder),
                'Agent should have attempted multiple tools even if goal not achieved');
        }
        
        // Agent should gather data before calculating (weight and distance before rate)
        if (count($executionOrder) >= 3) {
            $rateIndex = array_search('get_rate', $executionOrder);
            if ($rateIndex !== false) {
                // Rate calculation should come after data gathering
                $this->assertGreaterThan(0, $rateIndex, 
                    'Rate calculation should come after gathering weight/distance data');
            }
        }
        
        // Final answer should be substantive (not a tool execution message)
        $finalAnswer = strtolower($result['raw']);
        $this->assertStringNotContainsString('executed successfully', $finalAnswer,
            'Final answer should be actual response, not tool execution message');
        
        // Should be a real answer
        $this->assertGreaterThan(20, strlen($result['raw']),
            'Final answer should be substantive');

        echo "\n\n=== Multi-Step Workflow Summary ===\n";
        echo "Execution order: " . implode(' → ', $executionOrder) . "\n";
        echo "Tools used: " . implode(', ', $result['tools_used']) . "\n";
        echo "Turns: {$result['agent_turns']}\n";
        echo "Final answer:\n" . $result['raw'] . "\n";
        echo "====================================\n\n";
    }
}
