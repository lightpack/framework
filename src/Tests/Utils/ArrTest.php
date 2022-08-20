<?php

declare(strict_types=1);

use Lightpack\Utils\Arr;
use PHPUnit\Framework\TestCase;

final class ArrTest extends TestCase
{
    public function testArrayHasKey()
    {
        $array = ['a' => ['b' => ['c' => 'd']]];

        $this->assertTrue(Arr::has('a', $array));
        $this->assertTrue(Arr::has('a.b', $array));
        $this->assertTrue(Arr::has('a.b.c', $array));
    }

    public function testArrayGetByKey()
    {
        $array = ['a' => ['b' => ['c' => 'd']]];

        $this->assertEquals('d', Arr::get('a.b.c', $array));
        $this->assertEquals(['c' => 'd'], Arr::get('a.b', $array));
        $this->assertEquals('default', Arr::get('a.b.c.d', $array, 'default'));
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

        $flattenedArray = Arr::flatten($array);

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

        $tree = Arr::tree($categories);

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

        $tree = Arr::tree($categories, null, 'category_id', 'category_parent_id');

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

        $tree = Arr::tree($categories);

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

        $tree = Arr::tree($categories, null, 'category_id', 'category_parent_id');

        // Assertions
        $this->assertIsArray($tree);
        $this->assertCount(2, $tree);
        $this->assertObjectHasAttribute('children', $tree[0]);
        $this->assertCount(2, $tree[0]->children);
        $this->assertCount(1, $tree[0]->children[1]->children);
    }
}
