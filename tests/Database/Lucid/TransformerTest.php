<?php

namespace Tests\Database\Lucid;

require_once 'Project.php';
require_once 'Task.php';
require_once 'Comment.php';
require_once 'TaskTransformer.php';
require_once 'CommentTransformer.php';
require_once 'ProjectTransformer.php';

use Project;
use PHPUnit\Framework\TestCase;
use Lightpack\Container\Container;
use Lightpack\Database\Lucid\Collection;
use Lightpack\Http\Request;

class TransformerTest extends TestCase
{
    /** @var \Lightpack\Database\DB */
    private $db;

    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();

        // Configure container
        $container = Container::getInstance();

        $container->register('db', function () {
            return $this->db;
        });

        $container->register('logger', function () {
            return new class {
                public function error($message, $context = []) {}
                public function critical($message, $context = []) {}
            };
        });

        // required for pagination transformer test
        $container->register('request', function () {
            return new Request();
        });

        $_SERVER['REQUEST_URI'] = '';

        $this->createTestData();
    }

    public function tearDown(): void
    {
        $sql = "DROP TABLE products, options, owners, users, roles, role_user, permissions, permission_role, projects, tasks, comments, articles, managers, cast_models, cast_model_relations";
        $this->db->query($sql);
        $this->db = null;

        parent::tearDown();
    }

    public function testBasicTransform()
    {
        $project = new Project(1);
        $transformer = new \ProjectTransformer();

        $result = $transformer->transform($project);

        $this->assertSame([
            'id' => 1,
            'name' => 'Project 1',
        ], $result);
    }

    public function testTransformWithFieldFiltering()
    {
        $project = new Project(1);
        $transformer = new \ProjectTransformer();

        $result = $transformer
            ->fields(['self' => ['name']])
            ->transform($project);

        $this->assertSame([
            'name' => 'Project 1',
        ], $result);
    }

    public function testTransformWithRelations()
    {
        $project = new Project(1);
        $transformer = new \ProjectTransformer();

        $result = $transformer
            ->including('tasks')
            ->fields([
                'self' => ['name'],
                'tasks' => ['id', 'name'],
                'tasks.comments' => ['id', 'name'],
            ])
            ->transform($project);

        $this->assertSame([
            'name' => 'Project 1',
            'tasks' => [
                [
                    'id' => 1,
                    'name' => 'Task 1',
                ]
            ]
        ], $result);
    }

    public function testTransformWithNestedRelations()
    {
        $project = new Project(1);
        $transformer = new \ProjectTransformer();

        $result = $transformer
            ->including('tasks.comments')
            ->fields([
                'self' => ['name'],
                'tasks' => ['name'],
                'tasks.comments' => ['content']
            ])
            ->transform($project);

        $this->assertSame([
            'name' => 'Project 1',
            'tasks' => [
                [
                    'name' => 'Task 1',
                    'comments' => [
                        [
                            'content' => 'Comment 1'
                        ]
                    ]
                ]
            ]
        ], $result);
    }

    public function testTransformWithMultipleNestedPaths()
    {
        $project = new Project(2);
        $transformer = new \ProjectTransformer();

        $result = $transformer
            ->including(['tasks', 'tasks.comments'])
            ->fields([
                'self' => ['id', 'name'],
                'tasks' => ['name'],
                'tasks.comments' => ['content']
            ])
            ->transform($project);

        $this->assertSame([
            'id' => 2,
            'name' => 'Project 2',
            'tasks' => [
                [
                    'name' => 'Task 2',
                    'comments' => [
                        [
                            'content' => 'Comment 2'
                        ],
                        [
                            'content' => 'Comment 3'
                        ]
                    ]
                ],
                [
                    'name' => 'Task 3',
                    'comments' => []
                ]
            ]
        ], $result);
    }

    public function testTransformWithMissingRelation()
    {
        $project = Project::query()->one(1);
        $project = new Project(1);
        $transformer = new \ProjectTransformer();

        $result = $transformer
            ->including(['tasks', 'nonexistent'])
            ->fields([
                'self' => ['name'],
                'tasks' => ['name']
            ])
            ->transform($project);

        $this->assertSame([
            'name' => 'Project 1',
            'tasks' => [
                [
                    'name' => 'Task 1'
                ]
            ]
        ], $result);
    }

    public function testTransformWithNullRelation()
    {
        $project = new Project();
        $project->id = 999;
        $project->name = 'New Project';
        
        $transformer = new \ProjectTransformer();

        $result = $transformer
            ->including(['tasks'])
            ->fields([
                'self' => ['name'],
                'tasks' => ['name']
            ])
            ->transform($project);

        $this->assertSame([
            'name' => 'New Project',
            'tasks' => []
        ], $result);
    }

    public function testTransformCollection()
    {
        $projects = Project::query()->all();  // Get all projects
        $transformer = new \ProjectTransformer();

        $result = $transformer
            ->including(['tasks'])
            ->fields([
                'self' => ['name'],
                'tasks' => ['name']
            ])
            ->transform($projects);

        $this->assertSame([
            [
                'name' => 'Project 1',
                'tasks' => [
                    [
                        'name' => 'Task 1'
                    ]
                ]
            ],
            [
                'name' => 'Project 2',
                'tasks' => [
                    [
                        'name' => 'Task 2'
                    ],
                    [
                        'name' => 'Task 3'
                    ]
                ]
            ],
            [
                'name' => 'Project 3',
                'tasks' => []
            ]
        ], $result);
    }

    public function testModelTransform()
    {
        $project = new Project(1);

        $result = $project->transform([
            'self' => ['name'],
            'tasks' => ['name'],
            'tasks.comments' => ['content']
        ], ['tasks.comments']);

        $this->assertSame([
            'name' => 'Project 1',
            'tasks' => [
                [
                    'name' => 'Task 1',
                    'comments' => [
                        [
                            'content' => 'Comment 1'
                        ]
                    ]
                ]
            ]
        ], $result);
    }

    public function testCollectionTransform()
    {
        $projects = new Collection([
            new Project(1),
            new Project(2)
        ]);

        $result = $projects->transform([
            'self' => ['name'],
            'tasks' => ['name']
        ], ['tasks']);

        $this->assertSame([
            [
                'name' => 'Project 1',
                'tasks' => [
                    [
                        'name' => 'Task 1'
                    ]
                ]
            ],
            [
                'name' => 'Project 2',
                'tasks' => [
                    [
                        'name' => 'Task 2'
                    ],
                    [
                        'name' => 'Task 3'
                    ]
                ]
            ]
        ], $result);
    }

    public function testPaginationTransform()
    {
        // Create a collection with first page items
        $collection = new Collection([
            new Project(1),
            new Project(2)
        ]);

        // Create pagination with 2 items per page (3 total items = 2 pages)
        $pagination = new \Lightpack\Database\Lucid\Pagination(
            $collection, 
            3,  // total records
            2,  // per page
            1   // current page
        );

        $result = $pagination->transform([
            'self' => ['name'],
            'tasks' => ['name']
        ], ['tasks']);

        $this->assertSame([
            'data' => [
                [
                    'name' => 'Project 1',
                    'tasks' => [
                        [
                            'name' => 'Task 1'
                        ]
                    ]
                ],
                [
                    'name' => 'Project 2',
                    'tasks' => [
                        [
                            'name' => 'Task 2'
                        ],
                        [
                            'name' => 'Task 3'
                        ]
                    ]
                ]
            ],
            'meta' => [
                'current_page' => 1,
                'per_page' => 2,
                'total' => 3,
                'total_pages' => 2
            ],
            'links' => [
                'first' => '?page=1',
                'last' => '?page=2',
                'prev' => null,  // null because we're on first page
                'next' => '?page=2'
            ]
        ], $result);

        // Test second page pagination
        $collection = new Collection([
            new Project(3)
        ]);

        $pagination = new \Lightpack\Database\Lucid\Pagination(
            $collection, 
            3,  // total records
            2,  // per page
            2   // current page (second page)
        );

        $result = $pagination->transform([
            'self' => ['name']
        ], []);

        $this->assertSame([
            'data' => [
                [
                    'name' => 'Project 3'
                ]
            ],
            'meta' => [
                'current_page' => 2,
                'per_page' => 2,
                'total' => 3,
                'total_pages' => 2
            ],
            'links' => [
                'first' => '?page=1',
                'last' => '?page=2',
                'prev' => '?page=1',
                'next' => null  // null because we're on last page
            ]
        ], $result);
    }

    public function testTransformWithDifferentIncludePaths()
    {
        $project = new Project(2);

        // Test comma notation (direct relations)
        $result = (new \ProjectTransformer)
            ->including(['tasks', 'comments'])
            ->transform($project);

        $this->assertArrayHasKey('tasks', $result);
        $this->assertArrayHasKey('comments', $result);
        $this->assertCount(2, $result['tasks']);
        $this->assertCount(2, $result['comments']);
        
        // Verify tasks are loaded directly
        $this->assertEquals('Task 2', $result['tasks'][0]['name']);
        $this->assertEquals('Task 3', $result['tasks'][1]['name']);
        
        // Verify comments are loaded through hasManyThrough
        $this->assertEquals('Comment 2', $result['comments'][0]['content']);
        $this->assertEquals('Comment 3', $result['comments'][1]['content']);

        // Test dot notation (nested relations)
        $result = (new \ProjectTransformer)
            ->including('tasks.comments')
            ->transform($project);
            
        $this->assertArrayHasKey('tasks', $result);
        $this->assertCount(2, $result['tasks']);
        
        // Verify comments are nested under tasks
        $this->assertArrayHasKey('comments', $result['tasks'][0]);
        $this->assertCount(2, $result['tasks'][0]['comments']);
        $this->assertEquals('Comment 2', $result['tasks'][0]['comments'][0]['content']);
    }

    private function createTestData()
    {
        // projects, tasks, and comments table will be used for tests of hasmanyThrough relation
        $this->db->table('projects')->insert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);
        $this->db->table('tasks')->insert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 2],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);
        $this->db->table('comments')->insert([
            ['task_id' => 1, 'content' => 'Comment 1'],
            ['task_id' => 2, 'content' => 'Comment 2'],
            ['task_id' => 2, 'content' => 'Comment 3'],
        ]);
    }
}
