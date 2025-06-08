<?php

use Lightpack\Schedule\Event;
use Lightpack\Schedule\Cron;
use PHPUnit\Framework\TestCase;

/**
 * Note: I had to name this class as ScheduleEventTest because there is already a class named EventTest already declared.
 */
class ScheduleEventTest extends TestCase
{
    public function testConstructor()
    {
        $event = new Event('job', 'MyJob');

        $this->assertEquals('job', $event->getType());
        $this->assertEquals('MyJob', $event->getName());
        $this->assertEquals('* * * * *', $event->getCronExpression());
    }

    public function testCron()
    {
        $event = new Event('job', 'MyJob');
        $event->cron('* * * * *');

        $this->assertEquals('* * * * *', $event->getCronExpression());
    }

    public function testIsDue()
    {
        // Mock the Cron class
        $cron = $this->getMockBuilder(Cron::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set up the Cron mock to return true
        $cron->expects($this->once())
            ->method('isDue')
            ->willReturn(true);

        $event = new Event('job', 'MyJob');
        $event->cron('* * * * *');

        // Set the mock Cron instance
        $event->setCronInstance($cron);

        $isDue = $event->isDue();

        $this->assertTrue($isDue);
    }

    public function testIsDueAt()
    {
        // Mock the Cron class
        $cron = $this->getMockBuilder(Cron::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set up the Cron mock to return true
        $cron->expects($this->once())
            ->method('isDue')
            ->willReturn(true);

        $event = new Event('job', 'MyJob');
        $event->cron('* * * * *');

        // Set the mock Cron instance
        $event->setCronInstance($cron);

        $dateTime = new DateTime();
        $isDueAt = $event->isDueAt($dateTime);

        $this->assertTrue($isDueAt);
    }

    public function testNextDueAt()
    {
        // Mock the Cron class
        $cron = $this->getMockBuilder(Cron::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set up the Cron mock to return a DateTime object
        $nextDueDateTime = new DateTime('+1 minute');
        $cron->expects($this->once())
            ->method('nextDueAt')
            ->willReturn($nextDueDateTime);

        $event = new Event('job', 'MyJob');
        $event->cron('* * * * *');

        // Set the mock Cron instance
        $event->setCronInstance($cron);

        $nextDueAt = $event->nextDueAt();

        $this->assertInstanceOf(DateTime::class, $nextDueAt);
        $this->assertEquals($nextDueDateTime, $nextDueAt);
    }

    public function testPreviousDueAt()
    {
        // Mock the Cron class
        $cron = $this->getMockBuilder(Cron::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set up the Cron mock to return a DateTime object
        $nextDueDateTime = new DateTime('-1 minute');
        $cron->expects($this->once())
            ->method('previousDueAt')
            ->willReturn($nextDueDateTime);

        $event = new Event('job', 'MyJob');
        $event->cron('* * * * *');

        // Set the mock Cron instance
        $event->setCronInstance($cron);

        $previousdueAt = $event->previousdueAt();

        $this->assertInstanceOf(DateTime::class, $previousdueAt);
        $this->assertEquals($nextDueDateTime, $previousdueAt);
    }

    public function testDailyHelperSetsCorrectCron()
    {
        $event = new Event('job', 'Test');
        $event->daily();
        $this->assertEquals('0 0 * * *', $event->getCronExpression());
    }

    public function testHourlyHelperSetsCorrectCron()
    {
        $event = new Event('job', 'Test');
        $event->hourly();
        $this->assertEquals('0 * * * *', $event->getCronExpression());
    }

    public function testWeeklyHelperSetsCorrectCron()
    {
        $event = new Event('job', 'Test');
        $event->weekly();
        $this->assertEquals('0 0 * * 0', $event->getCronExpression());
    }

    public function testMonthlyHelperSetsCorrectCron()
    {
        $event = new Event('job', 'Test');
        $event->monthly();
        $this->assertEquals('0 0 1 * *', $event->getCronExpression());
    }

    public function testEveryMinutesHelperSetsCorrectCron()
    {
        $event = new Event('job', 'Test');
        $event->everyMinutes(15);
        $this->assertEquals('*/15 * * * *', $event->getCronExpression());
    }

    public function testEveryMinutesHelperEdgeCaseOneMinute()
    {
        $event = new Event('job', 'Test');
        $event->everyMinutes(1);
        $this->assertEquals('*/1 * * * *', $event->getCronExpression());
    }

    public function testDailyAtHelperSetsCorrectCron()
    {
        $event = new Event('job', 'Test');
        $event->dailyAt('13:45');
        $this->assertEquals('45 13 * * *', $event->getCronExpression());
    }

    public function testMondaysHelperSetsCorrectCron()
    {
        $event = new Event('job', 'Test');
        $event->mondays();
        $this->assertEquals('0 0 * * 1', $event->getCronExpression());
    }

    public function testTuesdaysHelperSetsCorrectCron()
    {
        $event = new Event('job', 'Test');
        $event->tuesdays();
        $this->assertEquals('0 0 * * 2', $event->getCronExpression());
    }

    public function testWednesdaysHelperSetsCorrectCron()
    {
        $event = new Event('job', 'Test');
        $event->wednesdays();
        $this->assertEquals('0 0 * * 3', $event->getCronExpression());
    }

    public function testThursdaysHelperSetsCorrectCron()
    {
        $event = new Event('job', 'Test');
        $event->thursdays();
        $this->assertEquals('0 0 * * 4', $event->getCronExpression());
    }

    public function testFridaysHelperSetsCorrectCron()
    {
        $event = new Event('job', 'Test');
        $event->fridays();
        $this->assertEquals('0 0 * * 5', $event->getCronExpression());
    }

    public function testSaturdaysHelperSetsCorrectCron()
    {
        $event = new Event('job', 'Test');
        $event->saturdays();
        $this->assertEquals('0 0 * * 6', $event->getCronExpression());
    }

    public function testSundaysHelperSetsCorrectCron()
    {
        $event = new Event('job', 'Test');
        $event->sundays();
        $this->assertEquals('0 0 * * 0', $event->getCronExpression());
    }

    public function testMonthlyOnHelperSetsCorrectCron()
    {
        $event = new Event('job', 'Test');
        $event->monthlyOn(15, '09:00');
        $this->assertEquals('0 9 15 * *', $event->getCronExpression());
    }
    
    public function testMonthlyOnHelperDefaultTime()
    {
        $event = new Event('job', 'Test');
        $event->monthlyOn(10);
        $this->assertEquals('0 0 10 * *', $event->getCronExpression());
    }
}