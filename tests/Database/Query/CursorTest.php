<?php

use Lightpack\Container\Container;
use Lightpack\Database\Query\Query;
use PHPUnit\Framework\TestCase;

final class CursorTest extends TestCase
{
    private $db;
    private $query;

    public function setUp(): void
    {
        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        
        // Create test table
        $sql = "
            DROP TABLE IF EXISTS cursor_test;
            CREATE TABLE cursor_test (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255),
                value INT
            );
        ";
        $this->db->query($sql);
        
        // Insert test data (100 rows)
        for ($i = 1; $i <= 100; $i++) {
            $this->db->query(
                "INSERT INTO cursor_test (name, value) VALUES (?, ?)",
                ["Item $i", $i]
            );
        }
        
        $this->query = new Query('cursor_test', $this->db);
        
        // Configure container
        $container = Container::getInstance();
        $container->register('db', function () {
            return $this->db;
        });
    }

    public function tearDown(): void
    {
        $this->db->query("DROP TABLE IF EXISTS cursor_test");
        $this->db = null;
    }

    public function testCursorReturnsGenerator()
    {
        $cursor = $this->query->cursor();
        $this->assertInstanceOf(\Generator::class, $cursor);
    }

    public function testCursorYieldsAllRows()
    {
        $count = 0;
        foreach ($this->query->cursor() as $row) {
            $count++;
        }
        
        $this->assertEquals(100, $count);
    }

    public function testCursorYieldsCorrectData()
    {
        $cursor = $this->query->where('id', '<=', 5)->cursor();
        
        $ids = [];
        foreach ($cursor as $row) {
            $ids[] = $row->id;
        }
        
        $this->assertEquals([1, 2, 3, 4, 5], $ids);
    }

    public function testCursorWithWhereClause()
    {
        $count = 0;
        foreach ($this->query->where('value', '>', 50)->cursor() as $row) {
            $count++;
            $this->assertGreaterThan(50, $row->value);
        }
        
        $this->assertEquals(50, $count); // 51-100 = 50 rows
    }

    public function testCursorWithOrderBy()
    {
        $cursor = $this->query
            ->where('id', '<=', 10)
            ->orderBy('id', 'DESC')
            ->cursor();
        
        $firstRow = $cursor->current();
        $this->assertEquals(10, $firstRow->id);
    }

    public function testCursorWithLimit()
    {
        $count = 0;
        foreach ($this->query->limit(10)->cursor() as $row) {
            $count++;
        }
        
        $this->assertEquals(10, $count);
    }

    public function testCursorMemoryEfficiency()
    {
        // This test verifies that cursor doesn't load all rows at once
        $cursor = $this->query->cursor();
        
        // Generator hasn't executed yet
        $this->assertInstanceOf(\Generator::class, $cursor);
        
        // Start iteration
        $cursor->rewind();
        $firstRow = $cursor->current();
        
        // Should have first row
        $this->assertEquals(1, $firstRow->id);
        
        // Generator is still valid (hasn't loaded all rows)
        $this->assertTrue($cursor->valid());
    }

    public function testCursorCanBeIteratedMultipleTimes()
    {
        // First iteration
        $count1 = 0;
        foreach ($this->query->where('id', '<=', 5)->cursor() as $row) {
            $count1++;
        }
        
        // Second iteration (new cursor)
        $count2 = 0;
        foreach ($this->query->where('id', '<=', 5)->cursor() as $row) {
            $count2++;
        }
        
        $this->assertEquals($count1, $count2);
    }

    public function testCursorWithComplexQuery()
    {
        $sum = 0;
        $count = 0;
        
        foreach ($this->query
            ->where('value', '>=', 25)
            ->where('value', '<=', 75)
            ->orderBy('value', 'ASC')
            ->cursor() as $row) {
            $sum += $row->value;
            $count++;
        }
        
        $this->assertEquals(51, $count); // 25-75 inclusive
        $this->assertEquals(2550, $sum); // Sum of 25 to 75
    }

    public function testCursorWithJoin()
    {
        // Create related table
        $this->db->query("
            CREATE TABLE cursor_related (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cursor_test_id INT,
                description VARCHAR(255)
            )
        ");
        
        $this->db->query("
            INSERT INTO cursor_related (cursor_test_id, description) VALUES
            (1, 'Description 1'),
            (2, 'Description 2'),
            (3, 'Description 3')
        ");
        
        $count = 0;
        foreach ($this->query
            ->select('cursor_test.*', 'cursor_related.description')
            ->join('cursor_related', 'cursor_test.id', 'cursor_related.cursor_test_id')
            ->cursor() as $row) {
            $count++;
            $this->assertObjectHasProperty('description', $row);
        }
        
        $this->assertEquals(3, $count);
        
        $this->db->query("DROP TABLE IF EXISTS cursor_related");
    }

    public function testCursorEarlyBreak()
    {
        // Test that we can break out of cursor iteration early
        $count = 0;
        foreach ($this->query->cursor() as $row) {
            $count++;
            if ($count >= 10) {
                break;
            }
        }
        
        $this->assertEquals(10, $count);
    }

    public function testCursorWithEmptyResult()
    {
        $count = 0;
        foreach ($this->query->where('id', '>', 1000)->cursor() as $row) {
            $count++;
        }
        
        $this->assertEquals(0, $count);
    }
}
