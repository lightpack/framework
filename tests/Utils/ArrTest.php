<?php

declare(strict_types=1);

use Lightpack\Utils\Arr;
use PHPUnit\Framework\TestCase;

final class ArrTest extends TestCase
{
    public function testArrayHasKey()
    {
        $array = ['a' => ['b' => ['c' => 'd']]];

        $this->assertTrue((new Arr)->has('a', $array));
        $this->assertTrue((new Arr)->has('a.b', $array));
        $this->assertTrue((new Arr)->has('a.b.c', $array));
    }

    public function testArrayGetByKey()
    {
        $array = ['a' => ['b' => ['c' => 'd']]];

        $this->assertEquals('d', (new Arr)->get('a.b.c', $array));
        $this->assertEquals(['c' => 'd'], (new Arr)->get('a.b', $array));
        $this->assertEquals('default', (new Arr)->get('a.b.c.d', $array, 'default'));
    }

    public function testArrayFlatten()
    {
        $array = [
            'a' => 'A',
            'b' => 'B',
            'ab' => ['AB', 'BA'],
            'abc' => ['c' => 'ABC'],
            'd' => ['e' => ['f' => 'D']]
        ];

        $flattenedArray = (new Arr)->flatten($array);

        // Assertions
        $this->assertIsArray($flattenedArray);
        $this->assertEquals(['A', 'B', 'AB', 'BA', 'ABC', 'D'], $flattenedArray);
    }

    public function testArrayTreeFromArrays()
    {
        // Test 1
        $categories = [
            ['id' => 1, 'parent_id' => 0, 'name' => 'Category 1'],
            ['id' => 2, 'parent_id' => 1, 'name' => 'Category 2'],
            ['id' => 3, 'parent_id' => 1, 'name' => 'Category 3'],
            ['id' => 4, 'parent_id' => 2, 'name' => 'Category 4'],
            ['id' => 5, 'parent_id' => 0, 'name' => 'Category 5'],
        ];

        $tree = (new Arr)->tree($categories);

        // Assertions
        $this->assertIsArray($tree);
        $this->assertCount(2, $tree);
        $this->assertArrayHasKey('children', $tree[0]);
        $this->assertCount(2, $tree[0]['children']);
        $this->assertCount(1, $tree[0]['children'][1]['children']);

        // Test 2
        $categories = [
            ['category_id' => 1, 'category_parent_id' => null, 'name' => 'Category 1'],
            ['category_id' => 2, 'category_parent_id' => 1, 'name' => 'Category 2'],
            ['category_id' => 3, 'category_parent_id' => 1, 'name' => 'Category 3'],
            ['category_id' => 4, 'category_parent_id' => 2, 'name' => 'Category 4'],
            ['category_id' => 5, 'category_parent_id' => null, 'name' => 'Category 5'],
        ];

        $tree = (new Arr)->tree($categories, null, 'category_id', 'category_parent_id');

        // Assertions
        $this->assertIsArray($tree);
        $this->assertCount(2, $tree);
        $this->assertArrayHasKey('children', $tree[0]);
        $this->assertCount(2, $tree[0]['children']);
        $this->assertCount(1, $tree[0]['children'][1]['children']);
    }

    public function testArrayTreeFromObjects()
    {
        // Test 1
        $categories = [
            (object) ['id' => 1, 'parent_id' => null, 'name' => 'Category 1'],
            (object) ['id' => 2, 'parent_id' => 1, 'name' => 'Category 2'],
            (object) ['id' => 3, 'parent_id' => 1, 'name' => 'Category 3'],
            (object) ['id' => 4, 'parent_id' => 2, 'name' => 'Category 4'],
            (object) ['id' => 5, 'parent_id' => null, 'name' => 'Category 5'],
        ];

        $tree = (new Arr)->tree($categories);

        // Assertions
        $this->assertIsArray($tree);
        $this->assertCount(2, $tree);
        $this->assertObjectHasAttribute('children', $tree[0]);
        $this->assertCount(2, $tree[0]->children);
        $this->assertCount(1, $tree[0]->children[1]->children);

        // Test 2
        $categories = [
            (object) ['category_id' => 1, 'category_parent_id' => null, 'name' => 'Category 1'],
            (object) ['category_id' => 2, 'category_parent_id' => 1, 'name' => 'Category 2'],
            (object) ['category_id' => 3, 'category_parent_id' => 1, 'name' => 'Category 3'],
            (object) ['category_id' => 4, 'category_parent_id' => 2, 'name' => 'Category 4'],
            (object) ['category_id' => 5, 'category_parent_id' => null, 'name' => 'Category 5'],
        ];

        $tree = (new Arr)->tree($categories, null, 'category_id', 'category_parent_id');

        // Assertions
        $this->assertIsArray($tree);
        $this->assertCount(2, $tree);
        $this->assertObjectHasAttribute('children', $tree[0]);
        $this->assertCount(2, $tree[0]->children);
        $this->assertCount(1, $tree[0]->children[1]->children);
    }

    public function testArrayRandomMethod()
    {
        $items = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E', 'f' => 'F'];

        // Test 1
        $randomItem = (new Arr)->random($items);

        // Assertions
        $this->assertIsNotArray($randomItem);
        $this->assertTrue(in_array($randomItem, $items));

        // Test 2
        $randomItems = (new Arr)->random($items, 2);

        // Assertions
        $this->assertIsArray($randomItems);
        $this->assertCount(2, $randomItems);

        // Test 3
        try {
            $randomItems = (new Arr)->random($items, 10);
        } catch (\Error $e) {
            $this->assertInstanceOf(\ValueError::class, $e);
            $this->assertEquals('You cannot request more than 6 items.', $e->getMessage());
        }

        // Test 4
        try {
            $randomItems = (new Arr)->random([]);
        } catch (\Error $e) {
            $this->assertInstanceOf(\ValueError::class, $e);
            $this->assertEquals('You cannot pass an empty array of items.', $e->getMessage());
        }

        // Test 5
        try {
            $randomItems = (new Arr)->random($items, 0);
        } catch (\Error $e) {
            $this->assertInstanceOf(\ValueError::class, $e);
            $this->assertEquals('You cannot request less than 1 item.', $e->getMessage());
        }

        // Test 6
        $randomItems = (new Arr)->random($items, 3, true);
        
        $this->assertIsArray($randomItems);
        $this->assertCount(3, $randomItems);
        $this->assertIsString(array_rand($randomItems));
        $this->assertIsNotNumeric(array_rand($randomItems));

        // Test 6
        $randomItems = (new Arr)->random($items, 3);

        $this->assertIsNumeric(array_rand($randomItems));
        $this->assertIsNotString(array_rand($randomItems));
    }

    public function testGroupByEmptyArray()
    {
        $items = [];
        $result = (new Arr)->groupBy('key', $items);

        $this->assertEquals([], $result);
    }

    public function testGroupByMissingKey()
    {
        $items = [
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane', 'age' => 30],
            ['name' => 'Bob'],
            ['name' => 'Alice', 'age' => 28],
        ];
        $result = (new Arr)->groupBy('country', $items);

        $this->assertEquals([], $result);
    }

    public function testGroupBy()
    {
        $items = [
            ['name' => 'John', 'age' => 25, 'country' => 'USA'],
            ['name' => 'Jane', 'age' => 30, 'country' => 'Canada'],
            ['name' => 'Bob', 'age' => 22, 'country' => 'USA'],
            ['name' => 'Alice', 'age' => 28, 'country' => 'Canada'],
        ];
        $result = (new Arr)->groupBy('country', $items);

        $expectedResult = [
            'USA' => [
                ['name' => 'John', 'age' => 25, 'country' => 'USA'],
                ['name' => 'Bob', 'age' => 22, 'country' => 'USA'],
            ],
            'Canada' => [
                ['name' => 'Jane', 'age' => 30, 'country' => 'Canada'],
                ['name' => 'Alice', 'age' => 28, 'country' => 'Canada'],
            ],
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testGroupByPreserveKeys()
    {
        $items = [
            'A' => ['id' => 1, 'name' => 'John'],
            'B' => ['id' => 2, 'name' => 'Jane'],
            'C' => ['id' => 3, 'name' => 'Bob'],
        ];
        $result = (new Arr)->groupBy('name', $items, true);

        $expectedResult = [
            'John' => [
                'A' => ['id' => 1, 'name' => 'John'],
            ],
            'Jane' => [
                'B' => ['id' => 2, 'name' => 'Jane'],
            ],
            'Bob' => [
                'C' => ['id' => 3, 'name' => 'Bob'],
            ],
        ];
        
        $this->assertEquals($expectedResult, $result);
    }
}
