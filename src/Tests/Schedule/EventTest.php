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
}
