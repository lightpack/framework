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
    }
}
