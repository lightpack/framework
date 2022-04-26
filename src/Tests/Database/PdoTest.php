<?php

use Lightpack\Database\Lucid\Model;
use PHPUnit\Framework\TestCase;

final class PdoTest extends TestCase
{
    private $db;

    public function setUp(): void
    {
        $config = require __DIR__ . '/tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);   
        $sql = file_get_contents(__DIR__ . '/tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();
    }

    public function tearDown(): void
    {
        $sql = "DROP TABLE `products`, `options`, `owners`;";
        $this->db->query($sql);
        $this->db = null;
    }

    public function testContructor()
    {
        $config = require __DIR__ . '/tmp/mysql.config.php';
        $db = new \Lightpack\Database\Adapters\Mysql($config);
        $this->assertInstanceOf(\Lightpack\Database\Adapters\Mysql::class, $db);
    }

    public function testConstructorThrowsException()
    {
        $this->expectException(\Exception::class);
        $config = require __DIR__ . '/tmp/pgsql.config.php';
        $db = new \Lightpack\Database\Adapters\Mysql($config);
    }

    public function testTableMethod()
    {
        $query = $this->db->table('products');
        $this->assertInstanceOf(\Lightpack\Database\Query\Query::class, $query);
    }

    public function testQueryMethod()
    {
        // Test
        $query = $this->db->query('SELECT * FROM products');
        $this->assertInstanceOf(PDOStatement::class, $query);

        // Test
        $query = $this->db->query('SELECT * FROM products WHERE color = ?', ['Red']);
        $this->assertInstanceOf(PDOStatement::class, $query);
    }

    public function testModel()
    {
        $model = $this->db->model(Model::class);
        $this->assertInstanceOf(Model::class, $model);
    }

    public function testGetQueryLogs()
    {
        $this->db->clearQueryLogs();

        // Because no query logging happens when not in debug mode
        set_env('APP_DEBUG', true);

        // bulk insert projects
        $this->db->table('projects')->bulkInsert([
            ['name' => 'Project 1'],
            ['name' => 'Project 2'],
            ['name' => 'Project 3'],
        ]);
        $this->db->query('SELECT * FROM projects');
        $this->db->query('SELECT * FROM projects WHERE name = ?', ['Project 1']);

        $logs = $this->db->getQueryLogs();

        // Assertions
        $this->assertIsArray($logs);
        $this->assertCount(2, $logs);
        $this->assertArrayHasKey('queries', $logs);
        $this->assertArrayHasKey('bindings', $logs);
        $this->assertCount(3, $logs['queries']);
        $this->assertCount(3, $logs['bindings']);
    }

    public function testTransaction()
    {
        $productsCountBeforeTransaction = $this->db->query('SELECT COUNT(*) FROM products')->fetchColumn();

        $this->db->begin();
        $this->db->query('INSERT INTO products (name, color) VALUES (?, ?)', ['Red', 'Red']);
        $this->db->query('INSERT INTO products (name, color) VALUES (?, ?)', ['Blue', 'Blue']);
        $this->db->commit();

        $productsCountAfterTransaction = $this->db->query('SELECT COUNT(*) FROM products')->fetchColumn();

        $this->assertEquals($productsCountAfterTransaction, 2 + $productsCountBeforeTransaction);
    }

    public function testRollback()
    {
        $productsCountBeforeTransaction = $this->db->query('SELECT COUNT(*) FROM products')->fetchColumn();

        $this->db->begin();
        $this->db->query('INSERT INTO products (name, color) VALUES (?, ?)', ['Red', 'Red']);
        $this->db->query('INSERT INTO products (name, color) VALUES (?, ?)', ['Blue', 'Blue']);
        $this->db->rollback();

        $productsCountAfterTransaction = $this->db->query('SELECT COUNT(*) FROM products')->fetchColumn();

        $this->assertEquals($productsCountAfterTransaction, $productsCountBeforeTransaction);
    }

    public function testGetConnection()
    {
        $this->assertInstanceOf(\PDO::class, $this->db->getConnection());
    }

    public function testGetQuery()
    {
        $query = $this->db->query('SELECT * FROM products');
        $this->assertInstanceOf(PDOStatement::class, $query);
    }

    public function testDriver()
    {
        $this->assertEquals('mysql', $this->db->getDriver());
    }

    public function testLastInsertId()
    {
        $this->db->query('INSERT INTO products (name, color) VALUES (?, ?)', ['Red', 'Red']);
        $this->assertIsNumeric($this->db->lastInsertId());
        $this->assertGreaterThan(0, $this->db->lastInsertId());
    }
}