<?php

use Lightpack\Schedule\Event;
use Lightpack\Schedule\Schedule;
use PHPUnit\Framework\TestCase;

class ScheduleTest extends TestCase
{
    public function testJob()
    {
        $schedule = new Schedule();
        $event = $schedule->job('MyJob');
        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals('job', $event->getType());
        $this->assertEquals('MyJob', $event->getName());
    }
    
    public function testGetEvents()
    {
        $schedule = new Schedule();
        $event1 = $schedule->job('Job1');
        $event2 = $schedule->job('Job2');
        
        $events = $schedule->getEvents();
        
        $this->assertCount(2, $events);
        $this->assertContains($event1, $events);
        $this->assertContains($event2, $events);
    }
    
    public function testGetDueEvents()
    {
        $schedule = new Schedule();
        $event1 = $schedule->job('Job1')->cron('* * * * *');
        $event2 = $schedule->job('Job2')->cron('* * * * *');
        
        $dueEvents = $schedule->getDueEvents();
        
        $this->assertCount(2, $dueEvents);
        $this->assertContains($event1, $dueEvents);
        $this->assertContains($event2, $dueEvents);
    }
}
