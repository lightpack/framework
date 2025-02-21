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
                ['name' => 'Bob', 'profile' => ['age' => 35]]
            ]
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
                            ['name' => 'Jane', 'skills' => ['vue', 'angular']]
                        ]],
                        ['name' => 'backend', 'members' => [
                            ['name' => 'Bob', 'skills' => ['php', 'mysql']],
                            ['name' => 'Alice', 'skills' => ['python', 'postgres']]
                        ]]
                    ]
                ],
                'design' => [
                    'teams' => [
                        ['name' => 'ui', 'members' => [
                            ['name' => 'Mike', 'skills' => ['figma', 'sketch']],
                            ['name' => 'Sara', 'skills' => ['photoshop', 'illustrator']]
                        ]],
                        ['name' => 'ux', 'members' => [
                            ['name' => 'Tom', 'skills' => ['research', 'prototyping']],
                            ['name' => 'Emma', 'skills' => ['wireframing', 'testing']]
                        ]]
                    ]
                ]
            ]
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
                    'email' => 'john@example.com'
                ]
            ]
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
        $arr = new Arr();
        
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
                                'name' => 'Category 4'
                            ]
                        ]
                    ],
                    [
                        'id' => 3,
                        'parent_id' => 1,
                        'name' => 'Category 3'
                    ]
                ]
            ],
            [
                'id' => 5,
                'parent_id' => 0,
                'name' => 'Category 5'
            ]
        ];

        $tree = $arr->tree($items);
        $this->assertEquals($expected, $tree);

        // Test with custom keys
        $items = [
            ['item_id' => 1, 'pid' => null, 'title' => 'Root'],
            ['item_id' => 2, 'pid' => 1, 'title' => 'Child 1'],
            ['item_id' => 3, 'pid' => 1, 'title' => 'Child 2']
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
                        'title' => 'Child 1'
                    ],
                    [
                        'item_id' => 3,
                        'pid' => 1,
                        'title' => 'Child 2'
                    ]
                ]
            ]
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
            ['id' => 'b1', 'parent_id' => 'a1', 'name' => 'B1']
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
                                'name' => 'B1'
                            ]
                        ]
                    ],
                    [
                        'id' => 'a2',
                        'parent_id' => 'root',
                        'name' => 'A2'
                    ]
                ]
            ]
        ];

        $tree = $arr->tree($items, '');
        $this->assertEquals($expected, $tree);
    }

    public function testTranspose()
    {
        $arr = new Arr();

        // Test basic transposition
        $data = [
            'name' => ['John', 'Jane'],
            'age' => [25, 30]
        ];

        $expected = [
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane', 'age' => 30]
        ];

        $this->assertEquals($expected, $arr->transpose($data));

        // Test with $_FILES like structure
        $files = [
            'name' => ['photo1.jpg', 'photo2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => ['/tmp/php123', '/tmp/php456'],
            'error' => [0, 0],
            'size' => [1024, 2048]
        ];

        $expected = [
            [
                'name' => 'photo1.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/php123',
                'error' => 0,
                'size' => 1024
            ],
            [
                'name' => 'photo2.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/php456',
                'error' => 0,
                'size' => 2048
            ]
        ];

        $this->assertEquals($expected, $arr->transpose($files));

        // Test with specific keys
        $this->assertEquals(
            [
                ['name' => 'photo1.jpg', 'size' => 1024],
                ['name' => 'photo2.jpg', 'size' => 2048]
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
            'age' => [25] // Missing second age
        ];

        $expected = [
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane'] // No age for Jane
        ];

        $this->assertEquals($expected, $arr->transpose($data));
    }
}
