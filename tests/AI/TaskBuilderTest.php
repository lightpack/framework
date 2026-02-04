<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\TaskBuilder;
use Lightpack\AI\Tools\ToolInterface;

class FakeProvider
{
    public function generate($params)
    {
        return ['text' => '{"name":"Alice","age":30}'];
    }
}

class TestTool implements ToolInterface
{
    public static $invoked = false;
    public static $receivedParams = [];

    public function __invoke(array $params): mixed
    {
        self::$invoked = true;
        self::$receivedParams = $params;
        
        return ['result' => 'success', 'product_id' => $params['product_id']];
    }

    public static function description(): string
    {
        return 'Test tool for unit testing';
    }

    public static function params(): array
    {
        return [
            'product_id' => ['string', 'Product identifier'],
            'quantity' => ['int', 'Quantity to order']
        ];
    }

    public static function reset(): void
    {
        self::$invoked = false;
        self::$receivedParams = [];
    }
}

class TaskBuilderTest extends TestCase
{
    public function testPromptWithSchema()
    {
        $builder = new TaskBuilder(new FakeProvider());
        $result = $builder
            ->prompt('What is your name and age?')
            ->expect([
                'name' => 'string',
                'age'  => 'int',
            ])
            ->run();

        $this->assertTrue($result['success']);
        $this->assertEquals('Alice', $result['data']['name']);
        $this->assertEquals(30, $result['data']['age']);
    }

    public function testPromptWithMissingRequiredField()
    {
        $builder = new TaskBuilder(new class {
            public function generate($params)
            {
                return ['text' => '{"name":null}'];
            }
        });
        $result = $builder
            ->prompt('What is your name and age?')
            ->expect([
                'name' => 'string',
                'age'  => 'int',
            ])
            ->required('name', 'age')
            ->run();

        $this->assertFalse($result['success']);
        $this->assertContains('Missing required field: name', $result['errors']);
        $this->assertContains('Missing required field: age', $result['errors']);
    }

    public function testRequiredFieldsInArrayOfObjects()
    {
        $builder = new TaskBuilder(new class {
            public function generate($params)
            {
                return ['text' => '[{"title":"Inception","rating":10},{"title":"Matrix"}]'];
            }
        });
        $result = $builder
            ->expect(['title' => 'string', 'rating' => 'int', 'summary' => 'string'])
            ->required('title', 'rating', 'summary')
            ->expectArray('movie')
            ->run();

        $this->assertFalse($result['success']);
        $this->assertContains('Item 1: Missing required field: summary', $result['errors']);
        $this->assertContains('Item 2: Missing required field: rating', $result['errors']);
        $this->assertContains('Item 2: Missing required field: summary', $result['errors']);
    }

    public function testAllItemsValid()
    {
        $builder = new TaskBuilder(new class {
            public function generate($params)
            {
                return ['text' => '[{"title":"Inception","rating":10,"summary":"A"},{"title":"Matrix","rating":9,"summary":"B"}]'];
            }
        });
        $result = $builder
            ->expect(['title' => 'string', 'rating' => 'int', 'summary' => 'string'])
            ->required('title', 'rating', 'summary')
            ->expectArray('movie')
            ->run();

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errors']);
    }

    public function testTemperatureZeroIsPassedToProvider()
    {
        $provider = new class {
            public array $lastParams = [];

            public function generate($params)
            {
                $this->lastParams = $params;
                return ['text' => 'ok'];
            }
        };

        $builder = new TaskBuilder($provider);
        $result = $builder
            ->prompt('Say ok')
            ->temperature(0.0)
            ->run();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('temperature', $provider->lastParams);
        $this->assertSame(0.0, $provider->lastParams['temperature']);
    }

    public function testToolAwareRunWithToolNone()
    {
        $provider = new class {
            public function generate($params)
            {
                $prompt = $params['prompt'] ?? '';

                if (str_contains($prompt, 'Decide if you should call ONE tool')) {
                    return ['text' => '{"tool":"none","params":{}}'];
                }

                return ['text' => 'No tool needed answer'];
            }
        };

        $result = (new TaskBuilder($provider))
            ->tool('dummy', fn($p) => ['ok' => true], 'Dummy tool', ['id' => ['string', 'Id']])
            ->prompt('hello')
            ->run();

        $this->assertTrue($result['success']);
        $this->assertSame('No tool needed answer', $result['raw']);
        $this->assertSame([], $result['tools_used']);
    }

    public function testToolAwareRunExecutesSelectedTool()
    {
        $provider = new class {
            public function generate($params)
            {
                $prompt = $params['prompt'] ?? '';

                if (str_contains($prompt, 'Decide if you should call ONE tool')) {
                    return ['text' => '{"tool":"get_status","params":{"order_id":"123"}}'];
                }

                if (str_contains($prompt, 'Tool Used: get_status')) {
                    return ['text' => 'Order 123 is shipped'];
                }

                return ['text' => 'fallback'];
            }
        };

        $toolCalled = false;

        $result = (new TaskBuilder($provider))
            ->tool('get_status', function ($params) use (&$toolCalled) {
                $toolCalled = true;
                return ['order_id' => $params['order_id'], 'status' => 'shipped'];
            }, 'Get order status', ['order_id' => ['string', 'Order id']])
            ->prompt('status of my order')
            ->run();

        $this->assertTrue($toolCalled);
        $this->assertTrue($result['success']);
        $this->assertSame('Order 123 is shipped', $result['raw']);
        $this->assertSame(['get_status'], $result['tools_used']);
        $this->assertArrayHasKey('get_status', $result['tool_results']);
    }

    public function testToolAwareRunFailsOnInvalidParamsNoRetry()
    {
        $provider = new class {
            public function generate($params)
            {
                $prompt = $params['prompt'] ?? '';

                if (str_contains($prompt, 'Decide if you should call ONE tool')) {
                    return ['text' => '{"tool":"get_status","params":{}}'];
                }

                return ['text' => 'fallback'];
            }
        };

        $result = (new TaskBuilder($provider))
            ->tool('get_status', fn($params) => ['status' => 'shipped'], 'Get order status', ['order_id' => ['string', 'Order id']])
            ->prompt('status of my order')
            ->run();

        $this->assertFalse($result['success']);
        $this->assertContains('Missing required parameter: order_id', $result['errors']);
        $this->assertSame(['get_status'], $result['tools_used']);
    }

    public function testToolClassWithAutoExtractedMetadata()
    {
        TestTool::reset();

        $provider = new class {
            public function generate($params)
            {
                $prompt = $params['prompt'] ?? '';

                if (str_contains($prompt, 'Decide if you should call ONE tool')) {
                    return ['text' => '{"tool":"test_tool","params":{"product_id":"ABC123","quantity":5}}'];
                }

                if (str_contains($prompt, 'Tool Used: test_tool')) {
                    return ['text' => 'Product ABC123 ordered successfully'];
                }

                return ['text' => 'fallback'];
            }
        };

        $result = (new TaskBuilder($provider))
            ->tool('test_tool', TestTool::class)
            ->prompt('order product ABC123')
            ->run();

        $this->assertTrue(TestTool::$invoked);
        $this->assertTrue($result['success']);
        $this->assertEquals('ABC123', TestTool::$receivedParams['product_id']);
        $this->assertEquals(5, TestTool::$receivedParams['quantity']);
    }

    public function testToolClassWithMetadata()
    {
        TestTool::reset();

        $provider = new class {
            public function generate($params)
            {
                $prompt = $params['prompt'] ?? '';

                if (str_contains($prompt, 'Decide if you should call ONE tool')) {
                    return ['text' => '{"tool":"test_tool","params":{"product_id":"XYZ789","quantity":3}}'];
                }

                if (str_contains($prompt, 'Tool Used: test_tool')) {
                    return ['text' => 'Order placed for user 42'];
                }

                return ['text' => 'fallback'];
            }
        };

        $result = (new TaskBuilder($provider))
            ->tool('test_tool', TestTool::class)
            ->prompt('order product')
            ->run();

        $this->assertTrue(TestTool::$invoked);
        $this->assertTrue($result['success']);
    }

    public function testToolClassInstance()
    {
        TestTool::reset();

        $provider = new class {
            public function generate($params)
            {
                $prompt = $params['prompt'] ?? '';

                if (str_contains($prompt, 'Decide if you should call ONE tool')) {
                    return ['text' => '{"tool":"test_tool","params":{"product_id":"INST001","quantity":1}}'];
                }

                if (str_contains($prompt, 'Tool Used: test_tool')) {
                    return ['text' => 'Instance tool executed'];
                }

                return ['text' => 'fallback'];
            }
        };

        $toolInstance = new TestTool();

        $result = (new TaskBuilder($provider))
            ->tool('test_tool', $toolInstance)
            ->prompt('test instance')
            ->run();

        $this->assertTrue(TestTool::$invoked);
        $this->assertTrue($result['success']);
        $this->assertEquals('INST001', TestTool::$receivedParams['product_id']);
    }
}
