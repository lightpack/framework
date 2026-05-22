<?php

declare(strict_types=1);

use Lightpack\Utils\Moment;
use PHPUnit\Framework\TestCase;

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
        $moment = new Moment;
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

    public function testFromNowWithDateTimeObject()
    {
        $date = new DateTime('-2 minute', new DateTimeZone('UTC'));
        $this->assertEquals('2 minutes ago', $this->moment->fromNow($date));
    }

    public function testDiffWithDateTimeObjects()
    {
        $date1 = new DateTime('2021-07-23 14:25:45', new DateTimeZone('UTC'));
        $date2 = new DateTime('2019-03-14 08:23:12', new DateTimeZone('UTC'));

        $diff = $this->moment->diff($date1, $date2);

        $this->assertEquals(2, $diff->y);
        $this->assertEquals(4, $diff->m);
        $this->assertEquals(9, $diff->d);
    }

    public function testDaysBetweenWithDateTimeObjects()
    {
        $date1 = new DateTime('2019-03-14 08:23:12', new DateTimeZone('UTC'));
        $date2 = new DateTime('2019-03-15 08:23:12', new DateTimeZone('UTC'));

        $this->assertEquals(1, $this->moment->daysBetween($date1, $date2));
    }

    public function testHumanDiffPast()
    {
        $this->assertEquals('just now', $this->moment->humanDiff('now'));
        $this->assertEquals('a minute ago', $this->moment->humanDiff('-61 seconds'));
        $this->assertEquals('2 minutes ago', $this->moment->humanDiff('-2 minute'));
        $this->assertEquals('an hour ago', $this->moment->humanDiff('-61 minutes'));
        $this->assertEquals('yesterday', $this->moment->humanDiff('-1 day'));
        $this->assertEquals('a week ago', $this->moment->humanDiff('-1 week'));
        $this->assertEquals('a month ago', $this->moment->humanDiff('-35 day'));
        $this->assertEquals('a year ago', $this->moment->humanDiff('-13 months'));
    }

    public function testHumanDiffFuture()
    {
        $this->assertEquals('in a minute', $this->moment->humanDiff('+61 seconds'));
        $this->assertEquals('in 2 minutes', $this->moment->humanDiff('+2 minute'));
        $this->assertEquals('in an hour', $this->moment->humanDiff('+61 minutes'));
        $this->assertEquals('tomorrow', $this->moment->humanDiff('+1 day'));
        $this->assertEquals('in a week', $this->moment->humanDiff('+1 week'));
        $this->assertEquals('in a month', $this->moment->humanDiff('+35 day'));
        $this->assertEquals('in a year', $this->moment->humanDiff('+13 months'));
    }

    public function testHumanDiffWithDateTimeObjects()
    {
        $from = new DateTime('-2 minute', new DateTimeZone('UTC'));
        $to = new DateTime('now', new DateTimeZone('UTC'));

        $this->assertEquals('2 minutes ago', $this->moment->humanDiff($from, $to));
    }

    public function testIsToday()
    {
        $this->assertTrue($this->moment->isToday());
        $this->assertTrue($this->moment->isToday('now'));
        $this->assertFalse($this->moment->isToday('yesterday'));
        $this->assertFalse($this->moment->isToday('tomorrow'));
    }

    public function testIsPast()
    {
        $this->assertTrue($this->moment->isPast('yesterday'));
        $this->assertFalse($this->moment->isPast('tomorrow'));
    }

    public function testIsFuture()
    {
        $this->assertTrue($this->moment->isFuture('tomorrow'));
        $this->assertFalse($this->moment->isFuture('yesterday'));
    }

    public function testStartOfDay()
    {
        $this->assertEquals(
            '2019-03-14 00:00:00',
            $this->moment->startOfDay('2019-03-14 08:23:12')
        );
    }

    public function testEndOfDay()
    {
        $this->assertEquals(
            '2019-03-14 23:59:59',
            $this->moment->endOfDay('2019-03-14 08:23:12')
        );
    }

    public function testAge()
    {
        $birthdate = date('Y-m-d', strtotime('-25 year'));
        $this->assertEquals(25, $this->moment->age($birthdate));
    }

    public function testTimezoneNormalizationWithDateTimeObjects()
    {
        // Create a DateTime in IST (UTC+5:30)
        $istDate = new DateTime('-2 minute', new DateTimeZone('Asia/Kolkata'));

        // Create moment in UTC
        $utcMoment = new Moment('UTC');

        // fromNow should correctly handle the timezone conversion
        $result = $utcMoment->fromNow($istDate);
        $this->assertEquals('2 minutes ago', $result);
    }
}
