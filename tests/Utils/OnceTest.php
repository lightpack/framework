<?php

namespace Lightpack\Tests\Utils;

use PHPUnit\Framework\TestCase;

class OnceTest extends TestCase
{
    public function testOnceExecutesCallbackOnlyOnce()
    {
        $counter = 0;
        
        $result1 = once(function() use (&$counter) {
            $counter++;
            return 'executed';
        });
        
        $result2 = once(function() use (&$counter) {
            $counter++;
            return 'executed';
        });
        
        $result3 = once(function() use (&$counter) {
            $counter++;
            return 'executed';
        });
        
        // Callback executed 3 times (different closures)
        $this->assertEquals(3, $counter);
        $this->assertEquals('executed', $result1);
        $this->assertEquals('executed', $result2);
        $this->assertEquals('executed', $result3);
    }
    
    public function testOnceCachesResultPerClosure()
    {
        $counter = 0;
        
        $callback = function() use (&$counter) {
            $counter++;
            return $counter;
        };
        
        $result1 = once($callback);
        $result2 = once($callback);
        $result3 = once($callback);
        
        // Same closure, executed only once
        $this->assertEquals(1, $counter);
        $this->assertEquals(1, $result1);
        $this->assertEquals(1, $result2);
        $this->assertEquals(1, $result3);
    }
    
    public function testOnceWithExpensiveOperation()
    {
        $executionCount = 0;
        
        $expensiveOperation = function() use (&$executionCount) {
            $executionCount++;
            // Simulate expensive operation
            usleep(1000);
            return 'expensive result';
        };
        
        $start = microtime(true);
        
        $result1 = once($expensiveOperation);
        $result2 = once($expensiveOperation);
        $result3 = once($expensiveOperation);
        
        $duration = microtime(true) - $start;
        
        // Should only execute once
        $this->assertEquals(1, $executionCount);
        $this->assertEquals('expensive result', $result1);
        $this->assertEquals('expensive result', $result2);
        $this->assertEquals('expensive result', $result3);
        
        // Should be fast (only one execution)
        $this->assertLessThan(0.01, $duration);
    }
    
    public function testOnceWithDifferentReturnTypes()
    {
        // Test with array
        $arrayResult = once(fn() => [1, 2, 3]);
        $this->assertEquals([1, 2, 3], $arrayResult);
        
        // Test with object
        $obj = new \stdClass();
        $obj->name = 'test';
        $objectResult = once(fn() => $obj);
        $this->assertEquals('test', $objectResult->name);
        
        // Test with null
        $nullResult = once(fn() => null);
        $this->assertNull($nullResult);
        
        // Test with boolean
        $boolResult = once(fn() => true);
        $this->assertTrue($boolResult);
        
        // Test with number
        $numberResult = once(fn() => 42);
        $this->assertEquals(42, $numberResult);
    }
    
    public function testOnceInClassMethod()
    {
        $obj = new class {
            private $counter = 0;
            private $callback;
            
            public function __construct()
            {
                // Store callback as property to reuse same instance
                $this->callback = function() {
                    $this->counter++;
                    return 'data-' . $this->counter;
                };
            }
            
            public function getExpensiveData()
            {
                return once($this->callback);
            }
            
            public function getCounter()
            {
                return $this->counter;
            }
        };
        
        $result1 = $obj->getExpensiveData();
        $result2 = $obj->getExpensiveData();
        $result3 = $obj->getExpensiveData();
        
        // Should execute only once (same callback instance)
        $this->assertEquals(1, $obj->getCounter());
        $this->assertEquals('data-1', $result1);
        $this->assertEquals('data-1', $result2);
        $this->assertEquals('data-1', $result3);
    }
    
    public function testOnceWithDatabaseQuery()
    {
        $queryCount = 0;
        
        $fetchUsers = function() use (&$queryCount) {
            $queryCount++;
            // Simulate database query
            return ['user1', 'user2', 'user3'];
        };
        
        // First call - executes query
        $users1 = once($fetchUsers);
        $this->assertEquals(1, $queryCount);
        $this->assertCount(3, $users1);
        
        // Subsequent calls - returns cached
        $users2 = once($fetchUsers);
        $users3 = once($fetchUsers);
        $this->assertEquals(1, $queryCount);
        $this->assertEquals($users1, $users2);
        $this->assertEquals($users1, $users3);
    }
    
    public function testOnceWithClosureCapturingVariables()
    {
        $multiplier = 5;
        $executionCount = 0;
        
        $calculate = function() use ($multiplier, &$executionCount) {
            $executionCount++;
            return 10 * $multiplier;
        };
        
        $result1 = once($calculate);
        $result2 = once($calculate);
        
        $this->assertEquals(1, $executionCount);
        $this->assertEquals(50, $result1);
        $this->assertEquals(50, $result2);
    }
}
