<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Moment\Moment;

final class MomentTest extends TestCase
{
    public function testToday()
    {
        Moment::format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d'), Moment::today());
        $this->assertEquals(date('Y-m-d'), Moment::today('Y-m-d'));
    }

    public function testTomorrow()
    {
        Moment::format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d', strtotime('tomorrow')), Moment::tomorrow());
        $this->assertEquals(date('Y-m-d', strtotime('tomorrow')), Moment::tomorrow('Y-m-d'));
    }

    public function testYesterday()
    {
        Moment::format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d', strtotime('yesterday')), Moment::yesterday());
        $this->assertEquals(date('Y-m-d', strtotime('yesterday')), Moment::yesterday('Y-m-d'));
    }

    public function testNext()
    {
        Moment::format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d', strtotime('next monday')), Moment::next('monday'));
        $this->assertEquals(date('Y/m/d', strtotime('next monday')), Moment::next('mon'));

        $this->assertEquals(date('Y-m-d', strtotime('next monday')), Moment::next('monday', 'Y-m-d'));
        $this->assertEquals(date('Y-m-d', strtotime('next monday')), Moment::next('mon', 'Y-m-d'));
    }

    public function testLast()
    {
        Moment::format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d', strtotime('last monday')), Moment::last('monday'));
        $this->assertEquals(date('Y/m/d', strtotime('last monday')), Moment::last('mon'));

        $this->assertEquals(date('Y-m-d', strtotime('last monday')), Moment::last('monday', 'Y-m-d'));
        $this->assertEquals(date('Y-m-d', strtotime('last monday')), Moment::last('mon', 'Y-m-d'));
    }

    public function testThisMonthEnd()
    {
        Moment::format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d', strtotime('last day of this month')), Moment::thisMonthEnd());
        $this->assertEquals(date('Y-m-d', strtotime('last day of this month')), Moment::thisMonthEnd('Y-m-d'));
    }

    public function testNextMonthEnd()
    {
        Moment::format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d', strtotime('last day of next month')), Moment::nextMonthEnd());
        $this->assertEquals(date('Y-m-d', strtotime('last day of next month')), Moment::nextMonthEnd('Y-m-d'));
    }

    public function testLastMonthEnd()
    {
        Moment::format('Y/m/d');
        
        $this->assertEquals(date('Y/m/d', strtotime('last day of last month')), Moment::lastMonthEnd());
        $this->assertEquals(date('Y-m-d', strtotime('last day of last month')), Moment::lastMonthEnd('Y-m-d'));
    }

    public function testDiff()
    {
        $diff = Moment::diff('2021-07-23 14:25:45', '2019-03-14 08:23:12');

        $this->assertEquals(2, $diff->y); // years
        $this->assertEquals(4, $diff->m); // months
        $this->assertEquals(9, $diff->d); // days
        $this->assertEquals(6, $diff->h); // hours
        $this->assertEquals(2, $diff->i); // minutes
        $this->assertEquals(33, $diff->s); // seconds

        // If lower date is passed as first argument, the result remains the same
        $diff = Moment::diff('2019-03-14 08:23:12', '2021-07-23 14:25:45');

        $this->assertEquals(2, $diff->y); // years
        $this->assertEquals(4, $diff->m); // months
        $this->assertEquals(9, $diff->d); // days
        $this->assertEquals(6, $diff->h); // hours
        $this->assertEquals(2, $diff->i); // minutes
        $this->assertEquals(33, $diff->s); // seconds
    }

    public function testDaysBetween()
    {
        $this->assertEquals(0, Moment::daysBetween('2019-03-14 08:23:12', '2019-03-14 10:23:12'));
        $this->assertEquals(1, Moment::daysBetween('2019-03-14 08:23:12', '2019-03-15 08:23:12'));
        $this->assertEquals(28, Moment::daysBetween('2019-02-07 08:23:12', '2019-03-07 08:23:12'));
    }

    public function testNow()
    {
        $this->assertEquals(date('Y-m-d H'), Moment::now('Y-m-d H'));
    }

    public function testCreate()
    {
        $this->assertEquals(date('Y-m-d'), Moment::create()->format('Y-m-d'));
        $this->assertEquals('2019-03-14', Moment::create('2019-03-14')->format('Y-m-d'));
        $this->assertEquals('2019-03-14', Moment::create('2019-03-14', 'Y-m-d')->format('Y-m-d'));
        $this->assertEquals('2019-03-14', Moment::create('2019-03-14 08:23:12', 'Y-m-d H:i:s')->format('Y-m-d'));
        $this->assertEquals('2019-03-14', Moment::create('2019-03-14 08:23:12', 'Y-m-d H:i:s', 'Europe/Berlin')->format('Y-m-d'));
    }

    public function testTravel()
    {
        Moment::format('Y-m-d H:i');

        $this->assertEquals(Moment::travel('5 minutes'), date('Y-m-d H:i', strtotime('5 minutes')));
        $this->assertEquals(Moment::travel('+5 minutes'), date('Y-m-d H:i', strtotime('+5 minutes')));
        $this->assertEquals(Moment::travel('-5 minutes'), date('Y-m-d H:i', strtotime('-5 minutes')));
    }

    public function testFromNow()
    {
        // just now
        $this->assertEquals('just now', Moment::fromNow());

        // just now
        $datetime = date('Y-m-d H:i:s', strtotime('-10 seconds'));
        $this->assertEquals('just now', Moment::fromNow($datetime));

        // a minute ago
        $datetime = date('Y-m-d H:i:s', strtotime('-61 seconds'));
        $this->assertEquals('a minute ago', Moment::fromNow($datetime));

        // 2 minutes ago
        $datetime = date('Y-m-d H:i:s', strtotime('-2 minute'));
        $this->assertEquals('2 minutes ago', Moment::fromNow($datetime));

        // an hour ago
        $datetime = date('Y-m-d H:i:s', strtotime('-61 minute'));
        $this->assertEquals('an hour ago', Moment::fromNow($datetime));

        // 2 hours ago
        $datetime = date('Y-m-d H:i:s', strtotime('-2 hour'));
        $this->assertEquals('2 hours ago', Moment::fromNow($datetime));

        // yesterday
        $datetime = date('Y-m-d H:i:s', strtotime('-25 hour'));
        $this->assertEquals('yesterday', Moment::fromNow($datetime));

        // 2 days ago
        $datetime = date('Y-m-d H:i:s', strtotime('-2 day'));
        $this->assertEquals('2 days ago', Moment::fromNow($datetime));

        // a week ago
        $datetime = date('Y-m-d H:i:s', strtotime('-8 day'));
        $this->assertEquals('a week ago', Moment::fromNow($datetime));

        // 2 weeks ago
        $datetime = date('Y-m-d H:i:s', strtotime('-2 week'));
        $this->assertEquals('2 weeks ago', Moment::fromNow($datetime));

        // a month ago
        $datetime = date('Y-m-d H:i:s', strtotime('-35 day'));
        $this->assertEquals('a month ago', Moment::fromNow($datetime));

        // 2 months ago
        $datetime = date('Y-m-d H:i:s', strtotime('-2 month'));
        $this->assertEquals('2 months ago', Moment::fromNow($datetime));

        // a year ago
        $datetime = date('Y-m-d H:i:s', strtotime('-13 months'));
        $this->assertEquals('a year ago', Moment::fromNow($datetime));

        // 2 years ago
        $datetime = date('Y-m-d H:i:s', strtotime('-2 year'));
        $this->assertEquals('2 years ago', Moment::fromNow($datetime));
    }
}
