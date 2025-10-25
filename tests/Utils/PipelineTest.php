<?php

namespace Lightpack\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Lightpack\Utils\Pipeline;

class PipelineTest extends TestCase
{
    public function testPipelinePassesThroughSinglePipe()
    {
        $result = (new Pipeline(1))
            ->through([
                fn($value) => $value + 1,
            ])
            ->run();

        $this->assertEquals(2, $result);
    }

    public function testPipelinePassesThroughMultiplePipes()
    {
        $result = (new Pipeline(1))
            ->through([
                fn($value) => $value + 1,  // 1 + 1 = 2
                fn($value) => $value * 2,  // 2 * 2 = 4
                fn($value) => $value - 1,  // 4 - 1 = 3
            ])
            ->run();

        $this->assertEquals(3, $result);
    }

    public function testPipelineWithArrayData()
    {
        $result = (new Pipeline(['name' => 'john']))
            ->through([
                fn($data) => array_merge($data, ['age' => 25]),
                fn($data) => array_merge($data, ['email' => 'john@example.com']),
            ])
            ->run();

        $this->assertEquals([
            'name' => 'john',
            'age' => 25,
            'email' => 'john@example.com',
        ], $result);
    }

    public function testPipelineWithObjects()
    {
        $user = new \stdClass();
        $user->name = 'John';

        $result = (new Pipeline($user))
            ->through([
                function($user) {
                    $user->age = 25;
                    return $user;
                },
                function($user) {
                    $user->email = 'john@example.com';
                    return $user;
                },
            ])
            ->run();

        $this->assertEquals('John', $result->name);
        $this->assertEquals(25, $result->age);
        $this->assertEquals('john@example.com', $result->email);
    }

    public function testPipelineWithCallableClasses()
    {
        $result = (new Pipeline(10))
            ->through([
                new class {
                    public function __invoke($value) {
                        return $value * 2;
                    }
                },
                new class {
                    public function __invoke($value) {
                        return $value + 5;
                    }
                },
            ])
            ->run();

        $this->assertEquals(25, $result); // (10 * 2) + 5
    }

    public function testPipelineWithEmptyPipes()
    {
        $result = (new Pipeline(42))
            ->through([])
            ->run();

        $this->assertEquals(42, $result);
    }

    public function testPipelineWithStringTransformation()
    {
        $result = (new Pipeline('hello'))
            ->through([
                fn($str) => strtoupper($str),
                fn($str) => $str . ' WORLD',
                fn($str) => str_replace(' ', '_', $str),
            ])
            ->run();

        $this->assertEquals('HELLO_WORLD', $result);
    }

    public function testPipelineHelperFunction()
    {
        $result = pipeline(5)
            ->through([
                fn($n) => $n * 2,
                fn($n) => $n + 3,
            ])
            ->run();

        $this->assertEquals(13, $result); // (5 * 2) + 3
    }

    public function testPipelineWithComplexDataTransformation()
    {
        $data = [
            'first_name' => 'john',
            'last_name' => 'doe',
            'age' => '25',
        ];

        $result = pipeline($data)
            ->through([
                // Capitalize names
                function($data) {
                    $data['first_name'] = ucfirst($data['first_name']);
                    $data['last_name'] = ucfirst($data['last_name']);
                    return $data;
                },
                // Cast age to int
                function($data) {
                    $data['age'] = (int) $data['age'];
                    return $data;
                },
                // Add full name
                function($data) {
                    $data['full_name'] = $data['first_name'] . ' ' . $data['last_name'];
                    return $data;
                },
            ])
            ->run();

        $this->assertEquals('John', $result['first_name']);
        $this->assertEquals('Doe', $result['last_name']);
        $this->assertEquals(25, $result['age']);
        $this->assertEquals('John Doe', $result['full_name']);
    }

    public function testPipelinePreservesOrder()
    {
        $log = [];

        pipeline('start')
            ->through([
                function($value) use (&$log) {
                    $log[] = 'pipe1';
                    return $value;
                },
                function($value) use (&$log) {
                    $log[] = 'pipe2';
                    return $value;
                },
                function($value) use (&$log) {
                    $log[] = 'pipe3';
                    return $value;
                },
            ])
            ->run();

        $this->assertEquals(['pipe1', 'pipe2', 'pipe3'], $log);
    }

    public function testPipelinePassesNullThrough()
    {
        $result = pipeline(10)
            ->through([
                fn($n) => $n * 2,              // 10 * 2 = 20
                fn($n) => $n > 15 ? null : $n, // 20 > 15, returns null
                fn($n) => $n ?? 100,           // null ?? 100 = 100
            ])
            ->run();

        $this->assertEquals(100, $result);
    }

    public function testPipelineWithValidationExample()
    {
        $data = ['email' => 'test@example.com', 'age' => 25];

        $result = pipeline($data)
            ->through([
                // Validate email
                function($data) {
                    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        throw new \Exception('Invalid email');
                    }
                    return $data;
                },
                // Validate age
                function($data) {
                    if ($data['age'] < 18) {
                        throw new \Exception('Must be 18+');
                    }
                    return $data;
                },
                // Add validated flag
                function($data) {
                    $data['validated'] = true;
                    return $data;
                },
            ])
            ->run();

        $this->assertTrue($result['validated']);
    }

    public function testPipelineResolvesClassNamesFromContainer()
    {
        // Arrange: Register a service in container
        app()->register('test_service', function() {
            return new class {
                public function getValue() {
                    return 'injected';
                }
            };
        });

        // Act: Use class name string in pipeline
        $result = pipeline(10)
            ->through([
                PipelineTestPipeWithDependency::class,
            ])
            ->run();

        // Assert: Dependency was injected
        $this->assertEquals(20, $result); // Pipe doubles the value
    }

    public function testPipelineWithMultipleClassNames()
    {
        $result = pipeline(5)
            ->through([
                PipelineTestDoublePipe::class,    // 5 * 2 = 10
                PipelineTestAddFivePipe::class,   // 10 + 5 = 15
                PipelineTestSquarePipe::class,    // 15 * 15 = 225
            ])
            ->run();

        $this->assertEquals(225, $result);
    }

    public function testPipelineMixesClassNamesAndClosures()
    {
        $result = pipeline(10)
            ->through([
                PipelineTestDoublePipe::class,           // 10 * 2 = 20
                fn($n) => $n + 5,                        // 20 + 5 = 25
                PipelineTestSquarePipe::class,           // 25 * 25 = 625
                fn($n) => $n / 5,                        // 625 / 5 = 125
            ])
            ->run();

        $this->assertEquals(125, $result);
    }

    public function testPipelineMixesClassNamesAndInstances()
    {
        $result = pipeline(10)
            ->through([
                PipelineTestDoublePipe::class,           // Class name
                new PipelineTestAddFivePipe(),           // Instance
                fn($n) => $n - 3,                        // Closure
            ])
            ->run();

        $this->assertEquals(22, $result); // (10 * 2) + 5 - 3
    }

    public function testPipelineWithComplexDependencyInjection()
    {
        // Register multiple services as objects
        app()->register('multiplier', function() {
            return new class {
                public function getValue() { return 3; }
            };
        });

        app()->register('adder', function() {
            return new class {
                public function getValue() { return 7; }
            };
        });

        $result = pipeline(10)
            ->through([
                PipelineTestComplexDependencyPipe::class,
            ])
            ->run();

        // (10 * 3) + 7 = 37
        $this->assertEquals(37, $result);
    }

    public function testPipelineThrowsExceptionForInvalidPipe()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid pipe');

        pipeline(10)
            ->through([
                'NonExistentClass',
            ])
            ->run();
    }
}

// Test pipe classes

class PipelineTestDoublePipe
{
    public function __invoke($value)
    {
        return $value * 2;
    }
}

class PipelineTestAddFivePipe
{
    public function __invoke($value)
    {
        return $value + 5;
    }
}

class PipelineTestSquarePipe
{
    public function __invoke($value)
    {
        return $value * $value;
    }
}

class PipelineTestPipeWithDependency
{
    protected $service;

    public function __construct($service = null)
    {
        $this->service = $service ?? app('test_service');
    }

    public function __invoke($value)
    {
        // Use injected service
        $this->service->getValue(); // Proves DI worked
        return $value * 2;
    }
}

class PipelineTestComplexDependencyPipe
{
    protected $multiplier;
    protected $adder;

    public function __construct()
    {
        $this->multiplier = app('multiplier');
        $this->adder = app('adder');
    }

    public function __invoke($value)
    {
        return ($value * $this->multiplier->getValue()) + $this->adder->getValue();
    }
}
