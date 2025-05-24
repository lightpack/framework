<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\TaskBuilder;

class FakeProvider {
    public function generate($params) {
        return ['text' => '{"name":"Alice","age":30}'];
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
            public function generate($params) {
                return ['text' => '{"name":null}'];
            }
        });
        $result = $builder
            ->prompt('What is your name and age?')
            ->expect([
                'name' => 'string',
                'age'  => 'int',
            ])
            ->run();

        $this->assertTrue($result['success']);
    }
}
