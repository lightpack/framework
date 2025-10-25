<?php

use Lightpack\Container\Container;
use Lightpack\Database\Query\Query;
use PHPUnit\Framework\TestCase;

final class DateTimeQueryTest extends TestCase
{
    private $db;
    private $query;

    public function setUp(): void
    {
        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        
        // Create test table with datetime columns
        $sql = "
            DROP TABLE IF EXISTS events;
            CREATE TABLE events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255),
                event_date DATE,
                event_datetime DATETIME,
                event_time TIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ";
        $this->db->query($sql);
        
        // Insert test data
        $this->db->query("INSERT INTO events (name, event_date, event_datetime, event_time, created_at) VALUES 
            ('Event 2023', '2023-06-15', '2023-06-15 10:30:00', '10:30:00', '2023-06-15 10:30:00'),
            ('Event 2024 Jan', '2024-01-10', '2024-01-10 14:00:00', '14:00:00', '2024-01-10 14:00:00'),
            ('Event 2024 Feb', '2024-02-20', '2024-02-20 09:15:00', '09:15:00', '2024-02-20 09:15:00'),
            ('Event 2024 Dec', '2024-12-25', '2024-12-25 18:45:00', '18:45:00', '2024-12-25 18:45:00'),
            ('Event 2025', '2025-03-05', '2025-03-05 16:20:00', '16:20:00', '2025-03-05 16:20:00')
        ");
        
        $this->query = new Query('events', $this->db);
        
        // Configure container
        $container = Container::getInstance();
        $container->register('db', function () {
            return $this->db;
        });
    }

    public function tearDown(): void
    {
        $this->db->query("DROP TABLE IF EXISTS events");
        $this->db = null;
    }

    public function testWhereDateWithExactMatch()
    {
        // Test exact date match
        $sql = "SELECT * FROM `events` WHERE DATE(`event_date`) = ?";
        $this->query->whereDate('event_date', '2024-01-10');
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals(['2024-01-10'], $this->query->bindings);
        
        // Execute and verify
        $results = $this->query->all();
        $this->assertCount(1, $results);
        $this->assertEquals('Event 2024 Jan', $results[0]->name);
        $this->query->resetQuery();
    }

    public function testWhereDateWithOperator()
    {
        // Test date with >= operator
        $sql = "SELECT * FROM `events` WHERE DATE(`event_date`) >= ?";
        $this->query->whereDate('event_date', '>=', '2024-12-01');
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals(['2024-12-01'], $this->query->bindings);
        
        // Execute and verify
        $results = $this->query->all();
        $this->assertCount(2, $results); // Dec 2024 and Mar 2025
        $this->query->resetQuery();
    }

    public function testWhereDateWithLessThan()
    {
        // Test date with < operator
        $results = $this->query->whereDate('event_date', '<', '2024-01-01')->all();
        $this->assertCount(1, $results);
        $this->assertEquals('Event 2023', $results[0]->name);
        $this->query->resetQuery();
    }

    public function testWhereYearWithExactMatch()
    {
        // Test exact year match
        $sql = "SELECT * FROM `events` WHERE YEAR(`created_at`) = ?";
        $this->query->whereYear('created_at', 2024);
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals([2024], $this->query->bindings);
        
        // Execute and verify
        $results = $this->query->all();
        $this->assertCount(3, $results); // Jan, Feb, Dec 2024
        $this->query->resetQuery();
    }

    public function testWhereYearWithOperator()
    {
        // Test year with > operator
        $sql = "SELECT * FROM `events` WHERE YEAR(`created_at`) > ?";
        $this->query->whereYear('created_at', '>', 2023);
        $this->assertEquals($sql, $this->query->toSql());
        
        // Execute and verify
        $results = $this->query->all();
        $this->assertCount(4, $results); // All 2024 and 2025 events
        $this->query->resetQuery();
    }

    public function testWhereMonthWithExactMatch()
    {
        // Test exact month match
        $sql = "SELECT * FROM `events` WHERE MONTH(`created_at`) = ?";
        $this->query->whereMonth('created_at', 12);
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals([12], $this->query->bindings);
        
        // Execute and verify
        $results = $this->query->all();
        $this->assertCount(1, $results);
        $this->assertEquals('Event 2024 Dec', $results[0]->name);
        $this->query->resetQuery();
    }

    public function testWhereMonthWithOperator()
    {
        // Test month with >= operator
        $results = $this->query->whereMonth('created_at', '>=', 6)->all();
        $this->assertCount(2, $results); // June 2023 and December 2024
        $this->query->resetQuery();
    }

    public function testWhereMonthWithMonthNames()
    {
        // Test with short month name
        $sql = "SELECT * FROM `events` WHERE MONTH(`created_at`) = ?";
        $this->query->whereMonth('created_at', 'dec');
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals([12], $this->query->bindings);
        
        // Execute and verify
        $results = $this->query->all();
        $this->assertCount(1, $results);
        $this->assertEquals('Event 2024 Dec', $results[0]->name);
        $this->query->resetQuery();
        
        // Test with full month name
        $this->query->whereMonth('created_at', 'december');
        $results = $this->query->all();
        $this->assertCount(1, $results);
        $this->assertEquals('Event 2024 Dec', $results[0]->name);
        $this->query->resetQuery();
        
        // Test with different month names
        $this->query->whereMonth('created_at', 'jan');
        $results = $this->query->all();
        $this->assertCount(1, $results);
        $this->assertEquals('Event 2024 Jan', $results[0]->name);
        $this->query->resetQuery();
        
        // Test case insensitivity
        $this->query->whereMonth('created_at', 'JAN');
        $results = $this->query->all();
        $this->assertCount(1, $results);
        $this->query->resetQuery();
        
        $this->query->whereMonth('created_at', 'January');
        $results = $this->query->all();
        $this->assertCount(1, $results);
        $this->query->resetQuery();
        
        // Test all month names
        $monthTests = [
            'feb' => 2, 'february' => 2,
            'mar' => 3, 'march' => 3,
            'apr' => 4, 'april' => 4,
            'may' => 5,
            'jun' => 6, 'june' => 6,
            'jul' => 7, 'july' => 7,
            'aug' => 8, 'august' => 8,
            'sep' => 9, 'september' => 9,
            'oct' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11,
        ];
        
        foreach ($monthTests as $monthName => $monthNumber) {
            $this->query->whereMonth('created_at', $monthName);
            $this->assertEquals([$monthNumber], $this->query->bindings);
            $this->query->resetQuery();
        }
    }

    public function testWhereDayWithExactMatch()
    {
        // Test exact day match
        $sql = "SELECT * FROM `events` WHERE DAY(`created_at`) = ?";
        $this->query->whereDay('created_at', 25);
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals([25], $this->query->bindings);
        
        // Execute and verify
        $results = $this->query->all();
        $this->assertCount(1, $results);
        $this->assertEquals('Event 2024 Dec', $results[0]->name);
        $this->query->resetQuery();
    }

    public function testWhereDayWithOperator()
    {
        // Test day with <= operator
        $results = $this->query->whereDay('created_at', '<=', 15)->all();
        $this->assertCount(3, $results); // 15th, 10th, and 5th
        $this->query->resetQuery();
    }

    public function testWhereTimeWithExactMatch()
    {
        // Test exact time match
        $sql = "SELECT * FROM `events` WHERE TIME(`event_time`) = ?";
        $this->query->whereTime('event_time', '14:00:00');
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals(['14:00:00'], $this->query->bindings);
        
        // Execute and verify
        $results = $this->query->all();
        $this->assertCount(1, $results);
        $this->assertEquals('Event 2024 Jan', $results[0]->name);
        $this->query->resetQuery();
    }

    public function testWhereTimeWithOperator()
    {
        // Test time with >= operator
        $results = $this->query->whereTime('event_time', '>=', '14:00:00')->all();
        $this->assertCount(3, $results); // 14:00, 16:20, 18:45
        $this->query->resetQuery();
    }

    public function testWhereTimeRange()
    {
        // Test time range (business hours)
        $results = $this->query
            ->whereTime('event_time', '>=', '09:00:00')
            ->whereTime('event_time', '<=', '17:00:00')
            ->all();
        $this->assertCount(4, $results); // All except 18:45
        $this->query->resetQuery();
    }

    public function testCombinedDateTimeFilters()
    {
        // Test combining year and month
        $results = $this->query
            ->whereYear('created_at', 2024)
            ->whereMonth('created_at', '>=', 6)
            ->all();
        $this->assertCount(1, $results); // Only December 2024
        $this->assertEquals('Event 2024 Dec', $results[0]->name);
        $this->query->resetQuery();
    }

    public function testDateTimeWithOtherConditions()
    {
        // Test date filters with regular WHERE
        $results = $this->query
            ->whereYear('created_at', 2024)
            ->where('name', 'LIKE', '%Jan%')
            ->all();
        $this->assertCount(1, $results);
        $this->assertEquals('Event 2024 Jan', $results[0]->name);
        $this->query->resetQuery();
    }

    public function testWhereDateOnDatetimeColumn()
    {
        // Test DATE() on DATETIME column
        $results = $this->query
            ->whereDate('event_datetime', '2024-02-20')
            ->all();
        $this->assertCount(1, $results);
        $this->assertEquals('Event 2024 Feb', $results[0]->name);
        $this->query->resetQuery();
    }

    public function testWhereTimeOnDatetimeColumn()
    {
        // Test TIME() on DATETIME column
        $results = $this->query
            ->whereTime('event_datetime', '<', '12:00:00')
            ->all();
        $this->assertCount(2, $results); // 10:30 and 09:15
        $this->query->resetQuery();
    }

    public function testMultipleDateConditionsWithOr()
    {
        // Test OR conditions with dates
        $results = $this->query
            ->where(function($q) {
                $q->whereYear('created_at', 2023)
                  ->orWhereYear('created_at', 2025);
            })
            ->all();
        $this->assertCount(2, $results); // 2023 and 2025 events
        $this->query->resetQuery();
    }

    public function testCurrentDateComparison()
    {
        // Test with current date
        $today = date('Y-m-d');
        $currentYear = date('Y');
        
        $sql = "SELECT * FROM `events` WHERE DATE(`event_date`) = ?";
        $this->query->whereDate('event_date', $today);
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals([$today], $this->query->bindings);
        $this->query->resetQuery();
        
        // Test current year
        $sql = "SELECT * FROM `events` WHERE YEAR(`created_at`) = ?";
        $this->query->whereYear('created_at', $currentYear);
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals([(int)$currentYear], $this->query->bindings);
        $this->query->resetQuery();
    }

    public function testDateFilterWithOrderAndLimit()
    {
        // Test date filter with ordering and limit
        $results = $this->query
            ->whereYear('created_at', 2024)
            ->orderBy('event_date', 'ASC')
            ->limit(2)
            ->all();
        
        $this->assertCount(2, $results);
        $this->assertEquals('Event 2024 Jan', $results[0]->name);
        $this->assertEquals('Event 2024 Feb', $results[1]->name);
        $this->query->resetQuery();
    }

    public function testCountWithDateFilters()
    {
        // Test count with date filters
        $count = $this->query->whereYear('created_at', 2024)->count();
        $this->assertEquals(3, $count);
    }
}
