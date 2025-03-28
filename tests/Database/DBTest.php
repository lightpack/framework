<?php

use Lightpack\Database\Lucid\Model;
use PHPUnit\Framework\TestCase;

final class DBTest extends TestCase
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
        $config = [
            'host' => 'invalid-host',
            'port' => 3306,
            'username' => 'invalid-user',
            'password' => 'invalid-pass',
            'database' => 'invalid-db',
            'options' => null,
        ];
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
        $this->db->table('projects')->insert([
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

    public function testNestedTransactions()
    {
        $initialCount = $this->db->query('SELECT COUNT(*) FROM products')->fetchColumn();

        // Start outer transaction
        $this->db->begin();
        $this->db->query('INSERT INTO products (name, color) VALUES (?, ?)', ['Outer1', 'Red']);

        // Start first nested transaction
        $this->db->begin();
        $this->db->query('INSERT INTO products (name, color) VALUES (?, ?)', ['Inner1', 'Blue']);
        $this->db->commit(); // Just decrements counter

        // Start second nested transaction
        $this->db->begin();
        $this->db->query('INSERT INTO products (name, color) VALUES (?, ?)', ['Inner2', 'Green']);
        $this->db->rollback(); // Just decrements counter

        // Commit outer transaction - this should persist all changes
        $this->db->commit();

        $finalCount = $this->db->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $this->assertEquals($initialCount + 3, $finalCount, 'All records should persist since nested transactions are logical');

        // Verify all records exist
        $products = $this->db->query('SELECT name FROM products WHERE name IN (?, ?, ?)', ['Outer1', 'Inner1', 'Inner2'])
            ->fetchAll(\PDO::FETCH_COLUMN);
        
        $this->assertContains('Outer1', $products, 'Outer transaction record should exist');
        $this->assertContains('Inner1', $products, 'First nested transaction record should exist');
        $this->assertContains('Inner2', $products, 'Second nested transaction record should exist');
    }

    public function testNestedTransactionRollback()
    {
        $initialCount = $this->db->query('SELECT COUNT(*) FROM products')->fetchColumn();

        // Start outer transaction
        $this->db->begin();
        $this->db->query('INSERT INTO products (name, color) VALUES (?, ?)', ['Outer1', 'Red']);

        // Start nested transaction
        $this->db->begin();
        $this->db->query('INSERT INTO products (name, color) VALUES (?, ?)', ['Inner1', 'Blue']);
        $this->db->commit(); // Just decrements counter

        // Rollback outer transaction - this should remove all changes
        $this->db->rollback();

        $finalCount = $this->db->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $this->assertEquals($initialCount, $finalCount, 'Outer rollback should remove all changes');

        // Verify no records were inserted
        $products = $this->db->query('SELECT name FROM products WHERE name IN (?, ?)', ['Outer1', 'Inner1'])
            ->fetchAll(\PDO::FETCH_COLUMN);
        
        $this->assertEmpty($products, 'No records should exist after outer transaction rollback');
    }

    public function testTransactionLevel()
    {
        $this->assertEquals(0, $this->db->getTransactionLevel(), 'Initial level should be 0');

        $this->db->begin();
        $this->assertEquals(1, $this->db->getTransactionLevel(), 'First begin() should set level to 1');

        $this->db->begin();
        $this->assertEquals(2, $this->db->getTransactionLevel(), 'Nested begin() should increment level');

        $this->db->commit();
        $this->assertEquals(1, $this->db->getTransactionLevel(), 'Nested commit() should decrement level');

        $this->db->rollback();
        $this->assertEquals(0, $this->db->getTransactionLevel(), 'Final rollback() should reset level to 0');
    }

    /**
     * Test closure-based transaction API
     */
    public function testClosureBasedTransaction()
    {
        // Test successful transaction with return value
        $result = $this->db->transaction(function() {
            $this->db->query("INSERT INTO users (name) VALUES ('test')");
            return 'success';
        });
        $this->assertEquals('success', $result);
        
        // Verify data was committed
        $user = $this->db->query("SELECT * FROM users WHERE name = 'test'")->fetch();
        $this->assertNotNull($user);

        // Test nested transactions using closure
        $result = $this->db->transaction(function() {
            $this->db->query("INSERT INTO users (name) VALUES ('outer')");
            
            // Inner transaction - should just increment counter
            $this->db->transaction(function() {
                $this->db->query("INSERT INTO users (name) VALUES ('inner')");
            });
            
            return 'nested';
        });
        $this->assertEquals('nested', $result);
        
        // Verify both outer and inner were committed
        $count = $this->db->query("SELECT COUNT(*) as count FROM users WHERE name IN ('outer', 'inner')")->fetch();
        $this->assertEquals(2, $count['count']);
    }
}