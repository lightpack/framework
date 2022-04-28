<?php

require_once 'Product.php';
require_once 'Owner.php';
require_once 'Product.php';
require_once 'Option.php';
require_once 'Project.php';
require_once 'Task.php';

use Lightpack\Container\Container;
use Lightpack\Database\Lucid\Pagination;
use Lightpack\Http\Request;
use PHPUnit\Framework\TestCase;

// Initalize container
$container = new Container();

final class PaginationTest extends TestCase
{
    public function setUp(): void
    {
        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();
        $this->productsCollection = $this->db->model(Product::class)->query()->all();

        // Configure container
        global $container;
        $container->register('db', function () {
            return $this->db;
        });
        $container->register('request', function () {
            return new Request();
        });

        // Set Request URI
        $_SERVER['REQUEST_URI'] = '/lightpack';
    }

    public function tearDown(): void
    {
        $sql = "DROP TABLE products, options, owners, users, roles, role_user, permissions, permission_role, projects, tasks, comments, articles, managers";
        $this->db->query($sql);
        $this->db = null;
    }

    public function testContructor()
    {
        $pagination = Product::query()->paginate();

        $this->assertEquals($this->productsCollection->count(), $pagination->count());
        $this->assertInstanceOf(Traversable::class, $pagination->items());
        $this->assertInstanceOf(JsonSerializable::class, $pagination);
        $this->assertInstanceOf(Countable::class, $pagination);
        $this->assertInstanceOf(IteratorAggregate::class, $pagination);
        $this->assertInstanceOf(Traversable::class, $pagination->getIterator());
    }

    public function testIsJsonSerializable()
    {
        $pagination = Product::query()->paginate();
        $json = $pagination->jsonSerialize();

        $this->assertArrayHasKey('total', $json);
        $this->assertArrayHasKey('per_page', $json);
        $this->assertArrayHasKey('current_page', $json);
        $this->assertArrayHasKey('last_page', $json);
        $this->assertArrayHasKey('path', $json);
        $this->assertArrayHasKey('links', $json);
        $this->assertArrayHasKey('items', $json);

        $this->assertEquals($this->productsCollection->count(), $json['total']);
        $this->assertEquals(10, $json['per_page']);
        $this->assertEquals(1, $json['current_page']);
        $this->assertEquals(1, $json['last_page']);
        $this->assertEquals('/lightpack', $json['path']);
        $this->assertEquals(['next' => null, 'prev' => null], $json['links']);
        $this->assertEquals($this->productsCollection->count(), count($json['items']));
    }

    public function testLoadMethod()
    {
        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);

        // bulk insert tasks
        $this->db->table('tasks')->bulkInsert([
            ['name' => 'Task 1', 'project_id' => 1],
            ['name' => 'Task 2', 'project_id' => 1],
            ['name' => 'Task 3', 'project_id' => 2],
        ]);

        $projectModel = $this->db->model(Project::class);
        $paginatedProducts = $projectModel->query()->paginate();
        $paginatedProducts->load('tasks');
        $paginatedProducts->loadCount('tasks');
        $projects = $paginatedProducts->items();

        // Assertions
        $this->assertEquals(3, $projects->count());
        $this->assertEquals(2, $projects[0]->tasks_count);
        $this->assertEquals(1, $projects[1]->tasks_count);
        $this->assertEquals(0, $projects[2]->tasks_count);
    }
}
