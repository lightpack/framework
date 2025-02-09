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

    public function testGroupByEmptyKey()
    {
        $items = [
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane', 'age' => 30]
        ];

        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Key or keys array cannot be empty');
        (new Arr)->groupBy('', $items);
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

    public function testGroupBySingleKey()
    {
        $items = [
            ['name' => 'John', 'age' => 25, 'country' => 'USA'],
            ['name' => 'Jane', 'age' => 30, 'country' => 'Canada'],
            ['name' => 'Bob', 'age' => 22, 'country' => 'USA'],
            ['name' => 'Alice', 'age' => 28, 'country' => 'Canada'],
        ];
        
        // Test with string key
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

        // Test with single key in array
        $result2 = (new Arr)->groupBy(['country'], $items);
        $this->assertEquals($expectedResult, $result2);
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

    public function testGroupByMultipleKeys()
    {
        // Test basic multi-level grouping
        $items = [
            ['country' => 'USA', 'state' => 'CA', 'city' => 'LA', 'name' => 'John'],
            ['country' => 'USA', 'state' => 'CA', 'city' => 'SF', 'name' => 'Jane'],
            ['country' => 'USA', 'state' => 'NY', 'city' => 'NYC', 'name' => 'Bob'],
            ['country' => 'Canada', 'state' => 'ON', 'city' => 'Toronto', 'name' => 'Alice']
        ];

        $result = (new Arr)->groupBy(['country', 'state'], $items);

        // Assertions for structure
        $this->assertCount(2, $result); // USA and Canada
        $this->assertCount(2, $result['USA']); // CA and NY
        $this->assertCount(2, $result['USA']['CA']); // LA and SF

        // Test missing intermediate keys
        $items = [
            ['a' => 1, 'b' => 2, 'c' => 3],
            ['a' => 1, 'c' => 4], // missing 'b'
            ['a' => 2, 'b' => 3, 'c' => 5]
        ];
        $result = (new Arr)->groupBy(['a', 'b'], $items);
        
        $this->assertCount(2, $result); // 1 and 2
        $this->assertCount(1, $result[1][2] ?? []); // One item with a=1, b=2
    }

    public function testSet()
    {
        $array = ['a' => ['b' => []]];
        
        // Test basic set
        $this->assertEquals(
            ['a' => ['b' => ['c' => 'value']]],
            (new Arr)->set('a.b.c', 'value', $array)
        );

        // Test setting deep nested value
        $this->assertEquals(
            ['a' => ['b' => ['c' => ['d' => 'deep']]]],
            (new Arr)->set('a.b.c.d', 'deep', $array)
        );

        // Test setting value in non-existent path
        $array = ['x' => 1];
        $this->assertEquals(
            ['x' => 1, 'new' => ['path' => 'value']],
            (new Arr)->set('new.path', 'value', $array)
        );

        // Test empty key
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Key cannot be empty');
        (new Arr)->set('', 'value', $array);
    }

    public function testMerge()
    {
        // Test basic merge
        $array1 = ['a' => 1, 'b' => 2];
        $array2 = ['b' => 3, 'c' => 4];
        $expected = ['a' => 1, 'b' => 3, 'c' => 4];
        $this->assertEquals($expected, (new Arr)->merge($array1, $array2));

        // Test nested merge
        $array1 = ['a' => ['b' => 2], 'c' => 3];
        $array2 = ['a' => ['b' => 4, 'd' => 5]];
        $expected = ['a' => ['b' => 4, 'd' => 5], 'c' => 3];
        $this->assertEquals($expected, (new Arr)->merge($array1, $array2));

        // Test deep nested merge
        $array1 = ['a' => ['b' => ['c' => 1]]];
        $array2 = ['a' => ['b' => ['c' => 2, 'd' => 3]]];
        $expected = ['a' => ['b' => ['c' => 2, 'd' => 3]]];
        $this->assertEquals($expected, (new Arr)->merge($array1, $array2));
    }

    public function testPluck()
    {
        // Test plucking from array of arrays
        $items = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
            ['id' => 3, 'name' => 'Bob']
        ];
        $this->assertEquals(['John', 'Jane', 'Bob'], (new Arr)->pluck('name', $items));

        // Test plucking with key by
        $this->assertEquals(
            [1 => 'John', 2 => 'Jane', 3 => 'Bob'],
            (new Arr)->pluck('name', $items, 'id')
        );

        // Test plucking from array of objects
        $objects = array_map(function($item) {
            return (object)$item;
        }, $items);
        $this->assertEquals(['John', 'Jane', 'Bob'], (new Arr)->pluck('name', $objects));
        $this->assertEquals(
            [1 => 'John', 2 => 'Jane', 3 => 'Bob'],
            (new Arr)->pluck('name', $objects, 'id')
        );

        // Test plucking non-existent key
        $this->assertEquals([], (new Arr)->pluck('invalid', $items));

        // Test empty key
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Key cannot be empty');
        (new Arr)->pluck('', $items);
    }

    public function testShuffle()
    {
        $items = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5];
        
        // Test basic shuffle
        $shuffled = (new Arr)->shuffle($items);
        $this->assertCount(count($items), $shuffled);
        $this->assertEquals(array_sum($items), array_sum($shuffled));
        $this->assertNotEquals(array_values($items), array_values($shuffled));

        // Test shuffle with preserved keys
        $shuffled = (new Arr)->shuffle($items, true);
        $this->assertCount(count($items), $shuffled);
        $this->assertEquals(array_sum($items), array_sum($shuffled));
        $this->assertEquals(array_keys($items), array_keys($shuffled));
        $this->assertNotEquals(array_values($items), array_values($shuffled));

        // Test empty array
        $this->assertEquals([], (new Arr)->shuffle([]));
        $this->assertEquals([], (new Arr)->shuffle([], true));
    }

    public function testChunk()
    {
        $items = range(1, 10);

        // Test basic chunking
        $chunks = (new Arr)->chunk($items, 3);
        $this->assertCount(4, $chunks);
        $this->assertEquals([1, 2, 3], $chunks[0]);
        $this->assertEquals([10], $chunks[3]);

        // Test chunking with preserved keys
        $items = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        $chunks = (new Arr)->chunk($items, 2, true);
        $this->assertCount(2, $chunks);
        $this->assertEquals(['a' => 1, 'b' => 2], $chunks[0]);
        $this->assertEquals(['c' => 3, 'd' => 4], $chunks[1]);

        // Test empty array
        $this->assertEquals([], (new Arr)->chunk([], 5));

        // Test invalid chunk size
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Chunk size must be greater than 0');
        (new Arr)->chunk($items, 0);
    }

    public function testDiffSimpleArrays()
    {
        $array1 = ['a' => 1, 'b' => 2, 'c' => 3];
        $array2 = ['b' => 2, 'c' => 4, 'd' => 5];

        $diff = (new Arr)->diff($array1, $array2);

        $this->assertEquals(['d' => 5], $diff['added']);
        $this->assertEquals(['a' => 1], $diff['removed']);
        $this->assertEquals(['c' => ['old' => 3, 'new' => 4]], $diff['modified']);
    }

    public function testDiffNestedArrays()
    {
        $array1 = [
            'a' => ['x' => 1, 'y' => 2],
            'b' => ['p' => 3, 'q' => 4]
        ];
        $array2 = [
            'a' => ['x' => 1, 'y' => 3],
            'c' => ['m' => 5, 'n' => 6]
        ];

        $diff = (new Arr)->diff($array1, $array2);

        $this->assertEquals(['c' => ['m' => 5, 'n' => 6]], $diff['added']);
        $this->assertEquals(['b' => ['p' => 3, 'q' => 4]], $diff['removed']);
        $this->assertEquals(['a' => ['old' => ['x' => 1, 'y' => 2], 'new' => ['x' => 1, 'y' => 3]]], $diff['modified']);
    }

    public function testDiffWithKey()
    {
        $users1 = [
            ['id' => 1, 'name' => 'John', 'age' => 30],
            ['id' => 2, 'name' => 'Jane', 'age' => 25],
            ['id' => 3, 'name' => 'Bob', 'age' => 35]
        ];

        $users2 = [
            ['id' => 1, 'name' => 'John', 'age' => 31],
            ['id' => 3, 'name' => 'Bob', 'age' => 35],
            ['id' => 4, 'name' => 'Alice', 'age' => 28]
        ];

        $diff = (new Arr)->diff($users1, $users2, 'id');

        // Test additions
        $this->assertCount(1, $diff['added']);
        $this->assertEquals(4, $diff['added'][4]['id']);
        $this->assertEquals('Alice', $diff['added'][4]['name']);

        // Test removals
        $this->assertCount(1, $diff['removed']);
        $this->assertEquals(2, $diff['removed'][2]['id']);
        $this->assertEquals('Jane', $diff['removed'][2]['name']);

        // Test modifications
        $this->assertCount(1, $diff['modified']);
        $this->assertEquals(30, $diff['modified'][1]['old']['age']);
        $this->assertEquals(31, $diff['modified'][1]['new']['age']);
    }

    public function testDiffEmptyArrays()
    {
        // Both empty
        $diff1 = (new Arr)->diff([], []);
        $this->assertEmpty($diff1['added']);
        $this->assertEmpty($diff1['removed']);
        $this->assertEmpty($diff1['modified']);

        // First empty
        $diff2 = (new Arr)->diff([], ['a' => 1]);
        $this->assertEquals(['a' => 1], $diff2['added']);
        $this->assertEmpty($diff2['removed']);
        $this->assertEmpty($diff2['modified']);

        // Second empty
        $diff3 = (new Arr)->diff(['a' => 1], []);
        $this->assertEmpty($diff3['added']);
        $this->assertEquals(['a' => 1], $diff3['removed']);
        $this->assertEmpty($diff3['modified']);
    }

    public function testDiffWithObjects()
    {
        $obj1 = (object)['name' => 'John'];
        $obj2 = (object)['name' => 'Jane'];
        
        $array1 = ['a' => $obj1];
        $array2 = ['a' => $obj2];

        $diff = (new Arr)->diff($array1, $array2);

        $this->assertEmpty($diff['added']);
        $this->assertEmpty($diff['removed']);
        $this->assertEquals(['a' => ['old' => $obj1, 'new' => $obj2]], $diff['modified']);
    }
}
