<?php

use PHPUnit\Framework\TestCase;
use Lightpack\AI\TaskBuilder;

class FakeProvider
{
    public function generate($params)
    {
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
        $this->assertContains('Item 0: Missing required field: summary', $result['errors']);
        $this->assertContains('Item 1: Missing required field: rating', $result['errors']);
        $this->assertContains('Item 1: Missing required field: summary', $result['errors']);
    }
}
