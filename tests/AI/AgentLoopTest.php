<?php

namespace Lightpack\Tests\AI;

use Lightpack\AI\TaskBuilder;
use PHPUnit\Framework\TestCase;

class AgentLoopTest extends TestCase
{
    private function createMockProvider(array $responses = []): object
    {
        return new class($responses) {
            private array $responses;
            private int $callCount = 0;

            public function __construct(array $responses)
            {
                $this->responses = $responses;
            }

            public function generate(array $params): array
            {
                $response = $this->responses[$this->callCount] ?? ['text' => 'default response'];
                $this->callCount++;
                return $response;
            }
        };
    }

    public function testLoopMethodSetsMaxTurns()
    {
        $provider = $this->createMockProvider();
        $task = new TaskBuilder($provider);
        
        $task->loop(5);
        
        $reflection = new \ReflectionClass($task);
        $property = $reflection->getProperty('maxTurns');
        $property->setAccessible(true);
        
        $this->assertEquals(5, $property->getValue($task));
    }

    public function testLoopDefaultsToTenTurns()
    {
        $provider = $this->createMockProvider();
        $task = new TaskBuilder($provider);
        
        $task->loop();
        
        $reflection = new \ReflectionClass($task);
        $property = $reflection->getProperty('maxTurns');
        $property->setAccessible(true);
        
        $this->assertEquals(10, $property->getValue($task));
    }

    public function testGoalMethodSetsAgentGoal()
    {
        $provider = $this->createMockProvider();
        $task = new TaskBuilder($provider);
        
        $task->goal('Find the best option');
        
        $reflection = new \ReflectionClass($task);
        $property = $reflection->getProperty('agentGoal');
        $property->setAccessible(true);
        
        $this->assertEquals('Find the best option', $property->getValue($task));
    }

    public function testAgentModeStopsWhenGoalAchieved()
    {
        $provider = $this->createMockProvider([
            ['text' => '{"tool":"search","params":{"query":"test"}}'],
            ['text' => 'Task completed successfully']
        ]);
        
        $task = new TaskBuilder($provider);
        $callCount = 0;
        
        $result = $task
            ->tool('search', function($params) use (&$callCount) {
                $callCount++;
                return ['results' => 'found'];
            })
            ->loop(5)
            ->goal('Search for test')
            ->prompt('Search for test')
            ->run();
        
        $this->assertTrue($result['goal_achieved']);
        $this->assertArrayHasKey('agent_turns', $result);
        $this->assertArrayHasKey('agent_memory', $result);
        $this->assertLessThan(5, $result['agent_turns']);
    }

    public function testAgentModeReachesMaxTurns()
    {
        // Provider always returns tool calls, never gives final answer
        $provider = $this->createMockProvider([
            ['text' => '{"tool":"search","params":{"query":"test"}}'],
            ['text' => '{"tool":"search","params":{"query":"test2"}}'],
            ['text' => '{"tool":"search","params":{"query":"test3"}}'],
            ['text' => '{"tool":"search","params":{"query":"test4"}}'],
        ]);
        
        $task = new TaskBuilder($provider);
        
        $result = $task
            ->tool('search', function($params) {
                return ['results' => 'found'];
            })
            ->loop(3)
            ->goal('Never-ending search')
            ->prompt('Search forever')
            ->run();
        
        // Should reach max turns since it never stops calling tools
        $this->assertEquals(3, $result['agent_turns']);
        $this->assertArrayHasKey('agent_memory', $result);
    }

    public function testAgentMemoryStoresAllTurns()
    {
        $provider = $this->createMockProvider([
            ['text' => '{"tool":"search","params":{"query":"test"}}'],
            ['text' => 'Final answer']
        ]);
        
        $task = new TaskBuilder($provider);
        
        $result = $task
            ->tool('search', function($params) {
                return ['results' => 'data'];
            })
            ->loop(5)
            ->prompt('Find something')
            ->run();
        
        $this->assertArrayHasKey('agent_memory', $result);
        $this->assertIsArray($result['agent_memory']);
        
        // Should have: user prompt + turn 1 + turn 2
        $this->assertGreaterThanOrEqual(3, count($result['agent_memory']));
        
        // First entry should be user
        $this->assertEquals('user', $result['agent_memory'][0]['role']);
        $this->assertEquals('Find something', $result['agent_memory'][0]['content']);
    }

    public function testSingleTurnModeStillWorksWithoutLoop()
    {
        $provider = $this->createMockProvider([
            ['text' => 'Simple response']
        ]);
        
        $task = new TaskBuilder($provider);
        
        $result = $task
            ->prompt('Hello')
            ->run();
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Simple response', $result['raw']);
        $this->assertArrayNotHasKey('agent_turns', $result);
        $this->assertArrayNotHasKey('agent_memory', $result);
    }

    public function testAgentModeWithToolsIntegration()
    {
        $provider = $this->createMockProvider([
            ['text' => '{"tool":"get_data","params":{}}'],
            ['text' => 'Data retrieved successfully']
        ]);
        
        $task = new TaskBuilder($provider);
        $toolCalls = [];
        
        $result = $task
            ->tool('get_data', function($params) use (&$toolCalls) {
                $toolCalls[] = 'get_data';
                return ['data' => 'raw'];
            })
            ->tool('process_data', function($params) use (&$toolCalls) {
                $toolCalls[] = 'process_data';
                return ['data' => 'processed'];
            })
            ->loop(5)
            ->goal('Get data')
            ->prompt('Get data')
            ->run();
        
        $this->assertTrue($result['goal_achieved']);
        $this->assertGreaterThanOrEqual(1, count($toolCalls));
        $this->assertContains('get_data', $toolCalls);
    }
}
