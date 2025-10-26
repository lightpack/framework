<?php

use Lightpack\Container\Container;
use Lightpack\Database\Query\Query;
use PHPUnit\Framework\TestCase;

final class RelativeDateQueryTest extends TestCase
{
    private $db;
    private $query;

    public function setUp(): void
    {
        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        
        // Create test table
        $sql = "
            DROP TABLE IF EXISTS activities;
            CREATE TABLE activities (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255),
                created_at DATETIME
            );
        ";
        $this->db->query($sql);
        
        // Insert test data with various dates
        $dates = [
            date('Y-m-d H:i:s'), // Today
            date('Y-m-d H:i:s', strtotime('-1 day')), // Yesterday
            date('Y-m-d H:i:s', strtotime('-3 days')), // 3 days ago
            date('Y-m-d H:i:s', strtotime('-7 days')), // 1 week ago
            date('Y-m-d H:i:s', strtotime('-15 days')), // 15 days ago
            date('Y-m-d H:i:s', strtotime('-1 month')), // 1 month ago
            date('Y-m-d H:i:s', strtotime('-3 months')), // 3 months ago
            date('Y-m-d H:i:s', strtotime('-1 year')), // 1 year ago
            date('Y-m-d H:i:s', strtotime('monday this week')), // This week
            date('Y-m-d H:i:s', strtotime('monday last week')), // Last week
        ];
        
        foreach ($dates as $i => $date) {
            $this->db->query("INSERT INTO activities (name, created_at) VALUES (?, ?)", ["Activity $i", $date]);
        }
        
        $this->query = new Query('activities', $this->db);
        
        // Configure container
        $container = Container::getInstance();
        $container->register('db', function () {
            return $this->db;
        });
    }

    public function tearDown(): void
    {
        $this->db->query("DROP TABLE IF EXISTS activities");
        $this->db = null;
    }

    public function testToday()
    {
        $results = $this->query->today()->all();
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Verify all results are from today
        foreach ($results as $result) {
            $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($result->created_at)));
        }
    }

    public function testYesterday()
    {
        $results = $this->query->yesterday()->all();
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Verify all results are from yesterday
        foreach ($results as $result) {
            $this->assertEquals(
                date('Y-m-d', strtotime('-1 day')), 
                date('Y-m-d', strtotime($result->created_at))
            );
        }
    }

    public function testThisWeek()
    {
        $results = $this->query->thisWeek()->all();
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Verify all results are from this week
        // Get current day of week (1=Monday, 7=Sunday)
        $dayOfWeek = (int) date('N');
        
        if ($dayOfWeek === 7) {
            // If today is Sunday, week is from last Monday to today
            $startOfWeek = strtotime('monday last week');
            $endOfWeek = strtotime('today 23:59:59');
        } else {
            // Otherwise, week is from this Monday to next Sunday
            $startOfWeek = strtotime('monday this week');
            $endOfWeek = strtotime('sunday this week 23:59:59');
        }
        
        foreach ($results as $result) {
            $timestamp = strtotime($result->created_at);
            $this->assertGreaterThanOrEqual($startOfWeek, $timestamp);
            $this->assertLessThanOrEqual($endOfWeek, $timestamp);
        }
    }

    public function testLastWeek()
    {
        $results = $this->query->lastWeek()->all();
        $this->assertGreaterThanOrEqual(1, count($results));
    }

    public function testThisMonth()
    {
        $results = $this->query->thisMonth()->all();
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Verify all results are from this month
        foreach ($results as $result) {
            $this->assertEquals(date('Y-m'), date('Y-m', strtotime($result->created_at)));
        }
    }

    public function testLastMonth()
    {
        $results = $this->query->lastMonth()->all();
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Verify all results are from last month
        $lastMonth = date('Y-m', strtotime('-1 month'));
        foreach ($results as $result) {
            $this->assertEquals($lastMonth, date('Y-m', strtotime($result->created_at)));
        }
    }

    public function testThisYear()
    {
        $results = $this->query->thisYear()->all();
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Verify all results are from this year
        foreach ($results as $result) {
            $this->assertEquals(date('Y'), date('Y', strtotime($result->created_at)));
        }
    }

    public function testLastYear()
    {
        $results = $this->query->lastYear()->all();
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Verify all results are from last year
        foreach ($results as $result) {
            $this->assertEquals(date('Y') - 1, date('Y', strtotime($result->created_at)));
        }
    }

    public function testLastDays()
    {
        // Test last 7 days
        $results = $this->query->lastDays(7)->all();
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Verify all results are within last 7 days
        $sevenDaysAgo = strtotime('-7 days');
        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual($sevenDaysAgo, strtotime($result->created_at));
        }
        
        $this->query->resetQuery();
        
        // Test last 30 days
        $results = $this->query->lastDays(30)->all();
        $this->assertGreaterThanOrEqual(1, count($results));
    }

    public function testLastWeeks()
    {
        $results = $this->query->lastWeeks(2)->all();
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Verify all results are within last 2 weeks
        $twoWeeksAgo = strtotime('-2 weeks');
        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual($twoWeeksAgo, strtotime($result->created_at));
        }
    }

    public function testLastMonths()
    {
        // Test last 3 months (quarterly)
        $results = $this->query->lastMonths(3)->all();
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Verify all results are within last 3 months
        $threeMonthsAgo = strtotime('-3 months');
        foreach ($results as $result) {
            $this->assertGreaterThanOrEqual($threeMonthsAgo, strtotime($result->created_at));
        }
    }

    public function testOlderThan()
    {
        // Test older than 7 days
        $results = $this->query->olderThan(7, 'days')->all();
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Verify all results are older than 7 days
        $sevenDaysAgo = strtotime('-7 days');
        foreach ($results as $result) {
            $this->assertLessThan($sevenDaysAgo, strtotime($result->created_at));
        }
        
        $this->query->resetQuery();
        
        // Test older than 1 month
        $results = $this->query->olderThan(1, 'month')->all();
        $this->assertGreaterThanOrEqual(1, count($results));
    }

    public function testNewerThan()
    {
        // Test newer than 30 days
        $results = $this->query->newerThan(30, 'days')->all();
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Verify all results are newer than 30 days
        $thirtyDaysAgo = strtotime('-30 days');
        foreach ($results as $result) {
            $this->assertGreaterThan($thirtyDaysAgo, strtotime($result->created_at));
        }
    }

    public function testBefore()
    {
        $beforeDate = date('Y-m-d', strtotime('-10 days'));
        $results = $this->query->before($beforeDate)->all();
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Verify all results are before the date
        foreach ($results as $result) {
            $this->assertLessThan(
                strtotime($beforeDate), 
                strtotime(date('Y-m-d', strtotime($result->created_at)))
            );
        }
    }

    public function testAfter()
    {
        $afterDate = date('Y-m-d', strtotime('-10 days'));
        $results = $this->query->after($afterDate)->all();
        $this->assertGreaterThanOrEqual(1, count($results));
        
        // Verify all results are after the date
        foreach ($results as $result) {
            $this->assertGreaterThan(
                strtotime($afterDate), 
                strtotime(date('Y-m-d', strtotime($result->created_at)))
            );
        }
    }

    public function testWeekdays()
    {
        $sql = "SELECT * FROM `activities` WHERE DAYOFWEEK(`created_at`) BETWEEN 2 AND 6";
        $this->query->weekdays();
        $this->assertEquals($sql, $this->query->toSql());
    }

    public function testWeekends()
    {
        $sql = "SELECT * FROM `activities` WHERE DAYOFWEEK(`created_at`) IN (1, 7)";
        $this->query->weekends();
        $this->assertEquals($sql, $this->query->toSql());
    }

    public function testCombinedRelativeFilters()
    {
        // Real-world: "Active users this month who logged in last 7 days"
        $results = $this->query
            ->thisMonth()
            ->lastDays(7)
            ->all();
        
        // Should have results (both conditions overlap)
        $this->assertIsArray($results);
    }

    public function testRelativeFiltersWithCustomColumn()
    {
        // Test with custom column name
        $results = $this->query->today('created_at')->all();
        $this->assertGreaterThanOrEqual(1, count($results));
        
        $this->query->resetQuery();
        
        $results = $this->query->lastDays(7, 'created_at')->all();
        $this->assertGreaterThanOrEqual(1, count($results));
    }

    public function testRelativeFiltersWithOtherConditions()
    {
        // Combine relative date with WHERE
        $results = $this->query
            ->where('name', 'LIKE', '%Activity%')
            ->lastDays(30)
            ->all();
        
        $this->assertGreaterThanOrEqual(1, count($results));
    }

    public function testRelativeFiltersWithAggregates()
    {
        // Count today's activities
        $count = $this->query->today()->count();
        $this->assertGreaterThanOrEqual(1, $count);
        
        // Count last 7 days
        $count = $this->query->lastDays(7)->count();
        $this->assertGreaterThanOrEqual(1, $count);
    }
}
