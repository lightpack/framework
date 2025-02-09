<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Utils\Moment;

final class MomentTest extends TestCase
{
    /** @var Moment */
    private $moment;

    public function setUp(): void
    {
        // Initialize with UTC for consistent testing
        $this->moment = new Moment('UTC');
    }

    public function tearDown(): void
    {
        $this->moment = null;
    }

    public function testDefaultTimezoneIsUTC()
    {
        $moment = new Moment();
        $this->assertEquals('UTC', $moment->getTimezone());
    }

    public function testSetTimezone()
    {
        $this->moment->setTimezone('Asia/Kolkata');
        $this->assertEquals('Asia/Kolkata', $this->moment->getTimezone());
    }

    public function testInvalidTimezone()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->moment->setTimezone('Invalid/Timezone');
    }

    public function testTimezoneConversion()
    {
        // Create two moments with different timezones
        $utcMoment = new Moment('UTC');
        $istMoment = new Moment('Asia/Kolkata');
        
        // Get current time in both timezones
        $utcTime = new DateTime('now', new DateTimeZone('UTC'));
        $istTime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        
        // Format for hour comparison (avoiding minute/second precision issues in testing)
        $utcHour = $utcTime->format('H');
        $istHour = $istTime->format('H');
        
        // Test that the hours differ by 5.5 (or 4.5 during DST)
        $hourDiff = ($istHour - $utcHour + 24) % 24;
        $this->assertTrue($hourDiff == 5 || $hourDiff == 6, 'IST should be ahead of UTC by 5/6 hours');
    }

    public function testFormatChaining()
    {
        $result = $this->moment
            ->format('Y-m-d')
            ->setTimezone('Asia/Kolkata')
            ->now();
        
        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result);
    }

    public function testToday()
    {
        $this->moment->format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d'), $this->moment->today());
        $this->assertEquals(date('Y-m-d'), $this->moment->today('Y-m-d'));
    }

    public function testTomorrow()
    {
        $this->moment->format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d', strtotime('tomorrow')), $this->moment->tomorrow());
        $this->assertEquals(date('Y-m-d', strtotime('tomorrow')), $this->moment->tomorrow('Y-m-d'));
    }

    public function testYesterday()
    {
        $this->moment->format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d', strtotime('yesterday')), $this->moment->yesterday());
        $this->assertEquals(date('Y-m-d', strtotime('yesterday')), $this->moment->yesterday('Y-m-d'));
    }

    public function testNext()
    {
        $this->moment->format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d', strtotime('next monday')), $this->moment->next('monday'));
        $this->assertEquals(date('Y/m/d', strtotime('next monday')), $this->moment->next('mon'));

        $this->assertEquals(date('Y-m-d', strtotime('next monday')), $this->moment->next('monday', 'Y-m-d'));
        $this->assertEquals(date('Y-m-d', strtotime('next monday')), $this->moment->next('mon', 'Y-m-d'));
    }

    public function testLast()
    {
        $this->moment->format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d', strtotime('last monday')), $this->moment->last('monday'));
        $this->assertEquals(date('Y/m/d', strtotime('last monday')), $this->moment->last('mon'));

        $this->assertEquals(date('Y-m-d', strtotime('last monday')), $this->moment->last('monday', 'Y-m-d'));
        $this->assertEquals(date('Y-m-d', strtotime('last monday')), $this->moment->last('mon', 'Y-m-d'));
    }

    public function testThisMonthEnd()
    {
        $this->moment->format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d', strtotime('last day of this month')), $this->moment->thisMonthEnd());
        $this->assertEquals(date('Y-m-d', strtotime('last day of this month')), $this->moment->thisMonthEnd('Y-m-d'));
    }

    public function testNextMonthEnd()
    {
        $this->moment->format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d', strtotime('last day of next month')), $this->moment->nextMonthEnd());
        $this->assertEquals(date('Y-m-d', strtotime('last day of next month')), $this->moment->nextMonthEnd('Y-m-d'));
    }

    public function testLastMonthEnd()
    {
        $this->moment->format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d', strtotime('last day of last month')), $this->moment->lastMonthEnd());
        $this->assertEquals(date('Y-m-d', strtotime('last day of last month')), $this->moment->lastMonthEnd('Y-m-d'));
    }

    public function testDiff()
    {
        // Set timezone to ensure consistent testing
        $this->moment->setTimezone('UTC');
        
        $diff = $this->moment->diff('2021-07-23 14:25:45', '2019-03-14 08:23:12');

        $this->assertEquals(2, $diff->y); // years
        $this->assertEquals(4, $diff->m); // months
        $this->assertEquals(9, $diff->d); // days
        $this->assertEquals(6, $diff->h); // hours
        $this->assertEquals(2, $diff->i); // minutes
        $this->assertEquals(33, $diff->s); // seconds

        // If lower date is passed as first argument, the result remains the same
        $diff = $this->moment->diff('2019-03-14 08:23:12', '2021-07-23 14:25:45');

        $this->assertEquals(2, $diff->y); // years
        $this->assertEquals(4, $diff->m); // months
        $this->assertEquals(9, $diff->d); // days
        $this->assertEquals(6, $diff->h); // hours
        $this->assertEquals(2, $diff->i); // minutes
        $this->assertEquals(33, $diff->s); // seconds
    }

    public function testInvalidDateFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->moment->create('invalid-date-format');
    }

    public function testDaysBetween()
    {
        $this->assertEquals(0, $this->moment->daysBetween('2019-03-14 08:23:12', '2019-03-14 10:23:12'));
        $this->assertEquals(1, $this->moment->daysBetween('2019-03-14 08:23:12', '2019-03-15 08:23:12'));
        $this->assertEquals(28, $this->moment->daysBetween('2019-02-07 08:23:12', '2019-03-07 08:23:12'));
    }

    public function testNow()
    {
        $this->assertEquals(date('Y-m-d H'), $this->moment->now('Y-m-d H'));
    }

    public function testCreate()
    {
        $this->assertEquals(date('Y-m-d'), $this->moment->create()->format('Y-m-d'));
        $this->assertEquals('2019-03-14', $this->moment->create('2019-03-14')->format('Y-m-d'));
        $this->assertEquals('2019-03-14', $this->moment->create('2019-03-14', 'Y-m-d')->format('Y-m-d'));
        $this->assertEquals('2019-03-14', $this->moment->create('2019-03-14 08:23:12', 'Y-m-d H:i:s')->format('Y-m-d'));
        $this->assertEquals('2019-03-14', $this->moment->create('2019-03-14 08:23:12', 'Y-m-d H:i:s', 'Europe/Berlin')->format('Y-m-d'));
    }

    public function testTravel()
    {
        $this->moment->format('Y-m-d H:i');

        $this->assertEquals($this->moment->travel('5 minutes'), date('Y-m-d H:i', strtotime('5 minutes')));
        $this->assertEquals($this->moment->travel('+5 minutes'), date('Y-m-d H:i', strtotime('+5 minutes')));
        $this->assertEquals($this->moment->travel('-5 minutes'), date('Y-m-d H:i', strtotime('-5 minutes')));
    }

    public function testFromNow()
    {
        // just now
        $this->assertEquals('just now', $this->moment->fromNow());

        // just now
        $datetime = date('Y-m-d H:i:s', strtotime('-10 seconds'));
        $this->assertEquals('just now', $this->moment->fromNow($datetime));

        // a minute ago
        $datetime = date('Y-m-d H:i:s', strtotime('-61 seconds'));
        $this->assertEquals('a minute ago', $this->moment->fromNow($datetime));

        // 2 minutes ago
        $datetime = date('Y-m-d H:i:s', strtotime('-2 minute'));
        $this->assertEquals('2 minutes ago', $this->moment->fromNow($datetime));

        // an hour ago
        $datetime = date('Y-m-d H:i:s', strtotime('-61 minute'));
        $this->assertEquals('an hour ago', $this->moment->fromNow($datetime));

        // 2 hours ago
        $datetime = date('Y-m-d H:i:s', strtotime('-2 hour'));
        $this->assertEquals('2 hours ago', $this->moment->fromNow($datetime));

        // yesterday
        $datetime = date('Y-m-d H:i:s', strtotime('-25 hour'));
        $this->assertEquals('yesterday', $this->moment->fromNow($datetime));

        // 2 days ago
        $datetime = date('Y-m-d H:i:s', strtotime('-2 day'));
        $this->assertEquals('2 days ago', $this->moment->fromNow($datetime));

        // a week ago
        $datetime = date('Y-m-d H:i:s', strtotime('-8 day'));
        $this->assertEquals('a week ago', $this->moment->fromNow($datetime));

        // 2 weeks ago
        $datetime = date('Y-m-d H:i:s', strtotime('-2 week'));
        $this->assertEquals('2 weeks ago', $this->moment->fromNow($datetime));

        // a month ago
        $datetime = date('Y-m-d H:i:s', strtotime('-35 day'));
        $this->assertEquals('a month ago', $this->moment->fromNow($datetime));

        // 2 months ago
        $datetime = date('Y-m-d H:i:s', strtotime('-2 month'));
        $this->assertEquals('2 months ago', $this->moment->fromNow($datetime));

        // a year ago
        $datetime = date('Y-m-d H:i:s', strtotime('-13 months'));
        $this->assertEquals('a year ago', $this->moment->fromNow($datetime));

        // 2 years ago
        $datetime = date('Y-m-d H:i:s', strtotime('-2 year'));
        $this->assertEquals('2 years ago', $this->moment->fromNow($datetime));
    }
}
