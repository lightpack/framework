<?php

declare(strict_types=1);

use Lightpack\Utils\Arr;
use PHPUnit\Framework\TestCase;

class ArrTest extends TestCase
{
    public function testArrayHasKey()
    {
        $data = [
            'user' => [
                'profile' => [
                    'name' => 'John',
                ],
            ],
        ];

        $arr = new Arr;

        $this->assertTrue($arr->has('user.profile.name', $data));
        $this->assertFalse($arr->has('user.profile.age', $data));
        $this->assertFalse($arr->has('user.settings', $data));
    }

    public function testArrayGetByKey()
    {
        $data = [
            'user' => [
                'profile' => [
                    'name' => 'John',
                    'email' => 'john@example.com',
                ],
                'settings' => [
                    'theme' => 'dark',
                ],
            ],
        ];

        $arr = new Arr;

        // Test nested array access
        $this->assertEquals('John', $arr->get('user.profile.name', $data));
        $this->assertEquals('john@example.com', $arr->get('user.profile.email', $data));
        $this->assertEquals('dark', $arr->get('user.settings.theme', $data));

        // Test missing keys
        $this->assertNull($arr->get('user.profile.age', $data));
        $this->assertEquals(25, $arr->get('user.profile.age', $data, 25));

        // Test invalid paths
        $this->assertNull($arr->get('invalid.path', $data));
        $this->assertEquals('default', $arr->get('invalid.path', $data, 'default'));
    }

    public function testArrayGetWithWildcardAndArrayAccess()
    {
        $data = [
            'users' => [
                ['name' => 'John', 'profile' => ['age' => 30]],
                ['name' => 'Jane', 'profile' => ['age' => 25]],
                ['name' => 'Bob', 'profile' => ['age' => 35]],
            ],
        ];

        $arr = new Arr;

        // Test wildcard access
        $this->assertEquals(
            ['John', 'Jane', 'Bob'],
            $arr->get('users.*.name', $data)
        );

        $this->assertEquals(
            [30, 25, 35],
            $arr->get('users.*.profile.age', $data)
        );
    }

    public function testArrayGetWithMultipleWildcards()
    {
        $data = [
            'departments' => [
                'engineering' => [
                    'teams' => [
                        ['name' => 'frontend', 'members' => [
                            ['name' => 'John', 'skills' => ['js', 'react']],
                            ['name' => 'Jane', 'skills' => ['vue', 'angular']],
                        ]],
                        ['name' => 'backend', 'members' => [
                            ['name' => 'Bob', 'skills' => ['php', 'mysql']],
                            ['name' => 'Alice', 'skills' => ['python', 'postgres']],
                        ]],
                    ],
                ],
                'design' => [
                    'teams' => [
                        ['name' => 'ui', 'members' => [
                            ['name' => 'Mike', 'skills' => ['figma', 'sketch']],
                            ['name' => 'Sara', 'skills' => ['photoshop', 'illustrator']],
                        ]],
                        ['name' => 'ux', 'members' => [
                            ['name' => 'Tom', 'skills' => ['research', 'prototyping']],
                            ['name' => 'Emma', 'skills' => ['wireframing', 'testing']],
                        ]],
                    ],
                ],
            ],
        ];

        $arr = new Arr;

        // Test multiple wildcards at different levels
        $names = $arr->get('departments.*.teams.*.members.*.name', $data);
        $this->assertEquals(['John', 'Jane', 'Bob', 'Alice', 'Mike', 'Sara', 'Tom', 'Emma'], $names);

        // Test wildcards with specific array access
        $names = $arr->get('departments.engineering.teams.0.members.*.name', $data);
        sort($names); // Sort for consistent comparison
        $this->assertEquals(
            ['Jane', 'John'],
            $names
        );

        // Test wildcards with empty results
        $this->assertEquals(
            null,
            $arr->get('departments.*.teams.*.members.*.nonexistent', $data)
        );

        // Test wildcards with default value
        $this->assertEquals(
            ['default'],
            $arr->get('departments.nonexistent.*.members.*.name', $data, ['default'])
        );

        // Test wildcards with numeric array access
        $firstTeamNames = $arr->get('departments.*.teams.0.name', $data);
        sort($firstTeamNames); // Sort for consistent comparison
        $this->assertEquals(
            ['frontend', 'ui'],
            $firstTeamNames
        );

        // Test wildcards with non-array values
        $invalidData = ['test' => 'value'];
        $this->assertNull($arr->get('test.*.value', $invalidData));
    }

    public function testWildcardEdgeCases()
    {
        $arr = new Arr;

        // Test empty array
        $this->assertNull($arr->get('*.test', []));

        // Test invalid path with multiple wildcards
        $data = ['key' => 'value'];
        $this->assertNull($arr->get('*.*.*.test', $data));

        // Test wildcard at start
        $data = ['users' => [['name' => 'John'], ['name' => 'Jane']]];
        $this->assertEquals(
            ['John', 'Jane'],
            $arr->get('users.*.name', $data)
        );
    }

    public function testSet()
    {
        $arr = new Arr;
        $data = [];

        // Test setting simple value
        $arr->set('name', 'John', $data);
        $this->assertEquals(['name' => 'John'], $data);

        // Test setting nested value
        $arr->set('user.profile.email', 'john@example.com', $data);
        $this->assertEquals('john@example.com', $data['user']['profile']['email']);

        // Test overwriting existing value
        $arr->set('name', 'Jane', $data);
        $this->assertEquals('Jane', $data['name']);

        // Test setting value in non-array
        $data = ['key' => 'value'];
        $arr->set('key.nested', 'test',  $data);
        $this->assertEquals(['nested' => 'test'], $data['key']);
    }

    public function testDeleteRemovesNestedKey()
    {
        $arr = new Arr;
        $data = [
            'user' => [
                'profile' => [
                    'name' => 'John',
                    'email' => 'john@example.com',
                ],
            ],
        ];

        $arr->delete('user.profile.email', $data);
        $this->assertArrayNotHasKey('email', $data['user']['profile']);
        $this->assertEquals('John', $data['user']['profile']['name']);
    }

    public function testDeleteWithNonExistentKeyDoesNothing()
    {
        $arr = new Arr;
        $data = ['key' => 'value'];
        $original = $data;

        $arr->delete('nonexistent.key', $data);
        $this->assertEquals($original, $data);
    }

    public function testDeleteWithScalarValueDoesNothing()
    {
        $arr = new Arr;
        $data = ['key' => 'value'];
        $original = $data;

        $arr->delete('key.nested', $data);
        $this->assertEquals($original, $data);
    }

    public function testTree()
    {
        $arr = new Arr;

        // Test basic tree structure
        $items = [
            ['id' => 1, 'parent_id' => 0, 'name' => 'Category 1'],
            ['id' => 2, 'parent_id' => 1, 'name' => 'Category 2'],
            ['id' => 3, 'parent_id' => 1, 'name' => 'Category 3'],
            ['id' => 4, 'parent_id' => 2, 'name' => 'Category 4'],
            ['id' => 5, 'parent_id' => 0, 'name' => 'Category 5'],
        ];

        $expected = [
            [
                'id' => 1,
                'parent_id' => 0,
                'name' => 'Category 1',
                'children' => [
                    [
                        'id' => 2,
                        'parent_id' => 1,
                        'name' => 'Category 2',
                        'children' => [
                            [
                                'id' => 4,
                                'parent_id' => 2,
                                'name' => 'Category 4',
                            ],
                        ],
                    ],
                    [
                        'id' => 3,
                        'parent_id' => 1,
                        'name' => 'Category 3',
                    ],
                ],
            ],
            [
                'id' => 5,
                'parent_id' => 0,
                'name' => 'Category 5',
            ],
        ];

        $tree = $arr->tree($items);
        $this->assertEquals($expected, $tree);

        // Test with custom keys
        $items = [
            ['item_id' => 1, 'pid' => null, 'title' => 'Root'],
            ['item_id' => 2, 'pid' => 1, 'title' => 'Child 1'],
            ['item_id' => 3, 'pid' => 1, 'title' => 'Child 2'],
        ];

        $expected = [
            [
                'item_id' => 1,
                'pid' => null,
                'title' => 'Root',
                'children' => [
                    [
                        'item_id' => 2,
                        'pid' => 1,
                        'title' => 'Child 1',
                    ],
                    [
                        'item_id' => 3,
                        'pid' => 1,
                        'title' => 'Child 2',
                    ],
                ],
            ],
        ];

        $tree = $arr->tree($items, null, 'item_id', 'pid');
        $this->assertEquals($expected, $tree);

        // Test empty array
        $this->assertEquals([], $arr->tree([]));

        // Test with string IDs
        $items = [
            ['id' => 'root', 'parent_id' => '', 'name' => 'Root'],
            ['id' => 'a1', 'parent_id' => 'root', 'name' => 'A1'],
            ['id' => 'a2', 'parent_id' => 'root', 'name' => 'A2'],
            ['id' => 'b1', 'parent_id' => 'a1', 'name' => 'B1'],
        ];

        $expected = [
            [
                'id' => 'root',
                'parent_id' => '',
                'name' => 'Root',
                'children' => [
                    [
                        'id' => 'a1',
                        'parent_id' => 'root',
                        'name' => 'A1',
                        'children' => [
                            [
                                'id' => 'b1',
                                'parent_id' => 'a1',
                                'name' => 'B1',
                            ],
                        ],
                    ],
                    [
                        'id' => 'a2',
                        'parent_id' => 'root',
                        'name' => 'A2',
                    ],
                ],
            ],
        ];

        $tree = $arr->tree($items, '');
        $this->assertEquals($expected, $tree);
    }

    public function testTranspose()
    {
        $arr = new Arr;

        // Test basic transposition
        $data = [
            'name' => ['John', 'Jane'],
            'age' => [25, 30],
        ];

        $expected = [
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane', 'age' => 30],
        ];

        $this->assertEquals($expected, $arr->transpose($data));

        // Test with $_FILES like structure
        $files = [
            'name' => ['photo1.jpg', 'photo2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => ['/tmp/php123', '/tmp/php456'],
            'error' => [0, 0],
            'size' => [1024, 2048],
        ];

        $expected = [
            [
                'name' => 'photo1.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/php123',
                'error' => 0,
                'size' => 1024,
            ],
            [
                'name' => 'photo2.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/php456',
                'error' => 0,
                'size' => 2048,
            ],
        ];

        $this->assertEquals($expected, $arr->transpose($files));

        // Test with specific keys
        $this->assertEquals(
            [
                ['name' => 'photo1.jpg', 'size' => 1024],
                ['name' => 'photo2.jpg', 'size' => 2048],
            ],
            $arr->transpose($files, ['name', 'size'])
        );

        // Test empty array
        $this->assertEquals([], $arr->transpose([]));

        // Test non-array values
        $this->assertEquals(
            [['key' => 'value']],
            $arr->transpose(['key' => 'value'])
        );

        // Test with missing values
        $data = [
            'name' => ['John', 'Jane'],
            'age' => [25], // Missing second age
        ];

        $expected = [
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane'], // No age for Jane
        ];

        $this->assertEquals($expected, $arr->transpose($data));
    }

    public function testFlatten()
    {
        $arr = new Arr;

        // Basic two-level nesting
        $data = ['db' => ['host' => 'localhost', 'port' => 3306]];
        $this->assertEquals(['db.host' => 'localhost', 'db.port' => 3306], $arr->flatten($data));

        // Three-level deep chain
        $data = ['a' => ['b' => ['c' => 'deep']]];
        $this->assertEquals(['a.b.c' => 'deep'], $arr->flatten($data));

        // Mix of flat and nested sibling keys
        $data = ['name' => 'John', 'address' => ['city' => 'London', 'zip' => 'EC1A']];
        $this->assertEquals(
            ['name' => 'John', 'address.city' => 'London', 'address.zip' => 'EC1A'],
            $arr->flatten($data)
        );

        // Multiple sibling branches at same depth
        $data = [
            'db' => ['host' => 'localhost', 'port' => 3306],
            'mail' => ['host' => 'smtp.test', 'port' => 587],
        ];
        $this->assertEquals(
            ['db.host' => 'localhost', 'db.port' => 3306, 'mail.host' => 'smtp.test', 'mail.port' => 587],
            $arr->flatten($data)
        );

        // Numeric-indexed nested arrays are flattened with numeric dot keys
        $data = ['tags' => ['php', 'mysql']];
        $this->assertEquals(['tags.0' => 'php', 'tags.1' => 'mysql'], $arr->flatten($data));

        // Falsy scalar leaves are preserved (null, false, 0, '')
        $data = ['flag' => false, 'count' => 0, 'label' => null, 'text' => ''];
        $this->assertEquals(['flag' => false, 'count' => 0, 'label' => null, 'text' => ''], $arr->flatten($data));

        // Empty nested array is treated as a leaf (not recursed into)
        $data = ['empty' => [], 'name' => 'test'];
        $this->assertEquals(['empty' => [], 'name' => 'test'], $arr->flatten($data));

        // Empty input
        $this->assertEquals([], $arr->flatten([]));

        // Result is round-trippable: get() on flattened key returns the original value
        $data = ['config' => ['db' => ['host' => 'localhost']]];
        $flat = $arr->flatten($data);
        $this->assertEquals('localhost', $arr->get('config.db.host', $data));
        $this->assertArrayHasKey('config.db.host', $flat);
        $this->assertEquals('localhost', $flat['config.db.host']);
    }

    public function testGroupBy()
    {
        $arr = new Arr;

        // Basic grouping preserves item order within each group
        $orders = [
            ['id' => 1, 'status' => 'pending'],
            ['id' => 2, 'status' => 'shipped'],
            ['id' => 3, 'status' => 'pending'],
            ['id' => 4, 'status' => 'shipped'],
        ];
        $grouped = $arr->groupBy('status', $orders);
        $this->assertCount(2, $grouped['pending']);
        $this->assertCount(2, $grouped['shipped']);
        $this->assertEquals(1, $grouped['pending'][0]['id']);
        $this->assertEquals(3, $grouped['pending'][1]['id']);
        $this->assertEquals(2, $grouped['shipped'][0]['id']);
        $this->assertEquals(4, $grouped['shipped'][1]['id']);

        // All items in the same group
        $items = [['id' => 1, 'type' => 'A'], ['id' => 2, 'type' => 'A']];
        $grouped = $arr->groupBy('type', $items);
        $this->assertCount(1, $grouped);
        $this->assertCount(2, $grouped['A']);

        // Each item in its own group
        $items = [['id' => 1, 'type' => 'A'], ['id' => 2, 'type' => 'B'], ['id' => 3, 'type' => 'C']];
        $grouped = $arr->groupBy('type', $items);
        $this->assertCount(3, $grouped);

        // Dot-notation key groups by a nested value
        $items = [
            ['id' => 1, 'meta' => ['region' => 'EU']],
            ['id' => 2, 'meta' => ['region' => 'US']],
            ['id' => 3, 'meta' => ['region' => 'EU']],
        ];
        $grouped = $arr->groupBy('meta.region', $items);
        $this->assertArrayHasKey('EU', $grouped);
        $this->assertArrayHasKey('US', $grouped);
        $this->assertCount(2, $grouped['EU']);
        $this->assertEquals(1, $grouped['EU'][0]['id']);
        $this->assertEquals(3, $grouped['EU'][1]['id']);

        // Missing key is grouped under null
        $items = [['id' => 1, 'category' => null], ['id' => 2, 'category' => 'A']];
        $grouped = $arr->groupBy('category', $items);
        $this->assertTrue(array_key_exists(null, $grouped));
        $this->assertArrayHasKey('A', $grouped);

        // Empty array
        $this->assertEquals([], $arr->groupBy('status', []));

        // Works with objects (direct property, not dot-notation)
        $obj1 = (object) ['type' => 'x'];
        $obj2 = (object) ['type' => 'y'];
        $obj3 = (object) ['type' => 'x'];
        $grouped = $arr->groupBy('type', [$obj1, $obj2, $obj3]);
        $this->assertCount(2, $grouped['x']);
        $this->assertCount(1, $grouped['y']);
    }

    public function testSort()
    {
        $arr = new Arr;

        $items = [
            ['name' => 'Charlie', 'price' => 30],
            ['name' => 'Alice',   'price' => 10],
            ['name' => 'Bob',     'price' => 20],
        ];

        // Ascending by simple string key
        $sorted = $arr->sort('name', $items);
        $this->assertEquals(['Alice', 'Bob', 'Charlie'], array_column($sorted, 'name'));

        // Descending by simple numeric key
        $sorted = $arr->sort('price', $items, 'desc');
        $this->assertEquals([30, 20, 10], array_column($sorted, 'price'));

        // Two-level dot-notation key ascending
        $users = [
            ['name' => 'Charlie', 'address' => ['city' => 'Paris']],
            ['name' => 'Alice',   'address' => ['city' => 'London']],
            ['name' => 'Bob',     'address' => ['city' => 'Berlin']],
        ];
        $sorted = $arr->sort('address.city', $users);
        $this->assertEquals(
            ['Berlin', 'London', 'Paris'],
            array_column(array_column($sorted, 'address'), 'city')
        );

        // Two-level dot-notation key descending
        $sorted = $arr->sort('address.city', $users, 'desc');
        $this->assertEquals(
            ['Paris', 'London', 'Berlin'],
            array_column(array_column($sorted, 'address'), 'city')
        );

        // Three-level dot-notation key
        $records = [
            ['id' => 1, 'meta' => ['geo' => ['country' => 'US']]],
            ['id' => 2, 'meta' => ['geo' => ['country' => 'AU']]],
            ['id' => 3, 'meta' => ['geo' => ['country' => 'GB']]],
        ];
        $sorted = $arr->sort('meta.geo.country', $records);
        $this->assertEquals([2, 3, 1], array_column($sorted, 'id'));

        // Missing key on some items sorts nulls to the front (null <=> value = -1)
        $items = [
            ['id' => 1, 'score' => 50],
            ['id' => 2],                // no 'score'
            ['id' => 3, 'score' => 10],
        ];
        $sorted = $arr->sort('score', $items);
        $this->assertNull($arr->get('score', $sorted[0])); // null sorts first
        $this->assertEquals(10, $sorted[1]['score']);
        $this->assertEquals(50, $sorted[2]['score']);

        // Equal values preserve relative order (stable)
        $items = [
            ['id' => 1, 'score' => 5],
            ['id' => 2, 'score' => 5],
            ['id' => 3, 'score' => 5],
        ];
        $sorted = $arr->sort('score', $items);
        $this->assertEquals([1, 2, 3], array_column($sorted, 'id'));

        // Does not mutate the original
        $items = [['name' => 'B'], ['name' => 'A']];
        $arr->sort('name', $items);
        $this->assertEquals('B', $items[0]['name']);

        // Empty array
        $this->assertEquals([], $arr->sort('name', []));

        // Works with objects
        $a = (object) ['score' => 3];
        $b = (object) ['score' => 1];
        $c = (object) ['score' => 2];
        $sorted = $arr->sort('score', [$a, $b, $c]);
        $this->assertEquals([1, 2, 3], array_map(fn ($o) => $o->score, $sorted));
    }
}
