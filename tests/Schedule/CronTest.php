<?php

use Lightpack\Schedule\Cron;
use PHPUnit\Framework\TestCase;

final class CronTest extends TestCase
{
    public function testValidCronExpression()
    {
        $cronExpression = '* * * * *';
        $cron = new Cron($cronExpression);

        $this->assertInstanceOf(Cron::class, $cron);
    }

    public function testInvalidCronExpression()
    {
        $this->expectException(\Exception::class);

        $cronExpression = 'invalid';
        $cron = new Cron($cronExpression);
    }

    public function testMinuteIsDue()
    {
        $cronExpression = '* * * * *';
        $cron = new Cron($cronExpression);

        // Set the current time to 10 minutes past the hour
        $currentDateTime = new \DateTime('2023-05-14 10:10:00');

        $this->assertTrue($cron->minuteIsDue($currentDateTime));
    }

    public function testMinuteIsNotDue()
    {
        $cronExpression = '5 * * * *'; // Every hour at minute 5
        $cron = new Cron($cronExpression);

        // Set the current time to 10 minutes past the hour
        $currentDateTime = new \DateTime('2023-05-14 10:10:00');

        $this->assertFalse($cron->minuteIsDue($currentDateTime));
    }

    public function testHourIsDue()
    {
        $cronExpression = '0 * * * *'; // Every hour at minute 0
        $cron = new Cron($cronExpression);

        // Set the current time to the start of the hour
        $currentDateTime = new \DateTime('2023-05-14 10:00:00');

        $this->assertTrue($cron->hourIsDue($currentDateTime));
    }

    public function testHourIsNotDue()
    {
        $cronExpression = '0 5 * * *'; // Every day at 5:00
        $cron = new Cron($cronExpression);

        // Set the current time to 6:00
        $currentDateTime = new \DateTime('2023-05-14 06:00:00');

        $this->assertFalse($cron->hourIsDue($currentDateTime));
    }

    public function testDayIsDue()
    {
        $cronExpression = '0 0 1 * *'; // Every 1st day of the month
        $cron = new Cron($cronExpression);

        // Set the current date to the 1st day of the month
        $currentDateTime = new \DateTime('2023-05-01 00:00:00');

        $this->assertTrue($cron->dayIsDue($currentDateTime));
    }

    public function testDayIsNotDue()
    {
        $cronExpression = '0 0 1 * *'; // Every 1st day of the month
        $cron = new Cron($cronExpression);

        // Set the current date to the 2nd day of the month
        $currentDateTime = new \DateTime('2023-05-02 00:00:00');

        $this->assertFalse($cron->dayIsDue($currentDateTime));
    }

    public function testMonthIsDue()
    {
        $cronExpression = '0 0 1 5 *'; // Every 1st day of May
        $cron = new Cron($cronExpression);

        // Set the current date to May 1st
        $currentDateTime = new \DateTime('2023-05-01');

        $this->assertTrue($cron->monthIsDue($currentDateTime));
    }

    public function testMonthIsNotDue()
    {
        $cronExpression = '0 0 1 5 *'; // Every 1st day of May
        $cron = new Cron($cronExpression);

        // Set the current date to June 1st
        $currentDateTime = new \DateTime('2023-06-01 00:00:00');

        $this->assertFalse($cron->monthIsDue($currentDateTime));
    }

    public function testWeekdayIsDue()
    {
        $cronExpression = '* * * * 1-5'; // Monday to Friday
        $cron = new Cron($cronExpression);

        // Set the current date to a Tuesday
        $currentDateTime = new \DateTime('2023-05-16 00:00:00');

        $this->assertTrue($cron->weekdayIsDue($currentDateTime));
    }

    public function testWeekdayIsNotDue()
    {
        $cronExpression = '* * * * 1-5'; // Monday to Friday
        $cron = new Cron($cronExpression);

        // Set the current date to a Saturday
        $currentDateTime = new \DateTime('2023-05-20 00:00:00');

        $this->assertFalse($cron->weekdayIsDue($currentDateTime));
    }

    public function testIsDue()
    {
        $cronExpression = '* * * * *'; // Every minute
        $currentDateTime = new \DateTime();
        $cron = new Cron($cronExpression);

        $this->assertTrue($cron->isDue($currentDateTime));
    }

    public function testIsNotDue()
    {
        $cronExpression = '0 0 * * *'; // Every day at midnight
        $currentDateTime = new \DateTime();
        $cron = new Cron($cronExpression);

        $this->assertFalse($cron->isDue($currentDateTime));
    }

    public function testMinuteRangeIsDue()
    {
        $cronExpression = '10-20 * * * *'; // Every minute from 10 to 20
        $cron = new Cron($cronExpression);

        // Set the current time to 15 minutes past the hour
        $currentDateTime = new \DateTime('2023-05-14 10:15:00');

        $this->assertTrue($cron->minuteIsDue($currentDateTime));
    }

    public function testMinuteStepIsDue()
    {
        $cronExpression = '*/5 * * * *'; // Every 5 minutes
        $cron = new Cron($cronExpression);

        // Set the current time to 10 minutes past the hour
        $currentDateTime = new \DateTime('2023-05-14 10:10:00');

        $this->assertTrue($cron->minuteIsDue($currentDateTime));
    }

    public function testDayRangeIsDue()
    {
        $cronExpression = '0 0 10-20 * *'; // Every day from the 10th to the 20th
        $cron = new Cron($cronExpression);

        // Set the current date to the 15th
        $currentDateTime = new \DateTime('2023-05-15 00:00:00');

        $this->assertTrue($cron->dayIsDue($currentDateTime));
    }

    public function testDayStepIsDue()
    {
        $cronExpression = '0 0 */5 * *'; // Every 5th day of the month
        $cron = new Cron($cronExpression);

        // Set the current date to the 10th
        $currentDateTime = new \DateTime('2023-05-10 00:00:00');

        $this->assertTrue($cron->dayIsDue($currentDateTime));
    }

    public function testMonthRangeIsDue()
    {
        $cronExpression = '0 0 1 * 4-6'; // Every 1st day of April to June
        $cron = new Cron($cronExpression);

        // Set the current date to May 1st
        $currentDateTime = new \DateTime('2023-05-01 00:00:00');

        $this->assertTrue($cron->monthIsDue($currentDateTime));
    }

    public function testMonthStepIsDue()
    {
        $cronExpression = '0 0 1 */2 *'; // Every 1st day of every even month
        $cron = new Cron($cronExpression);

        // Set the current date to February 1st
        $currentDateTime = new \DateTime('2024-02-01 00:00:00');

        $this->assertTrue($cron->monthIsDue($currentDateTime));
    }

    public function testWeekdayCommaSeparatedIsDue()
    {
        $cronExpression = '* * * * 1,3,5'; // Monday, Wednesday, Friday
        $cron = new Cron($cronExpression);

        // Set the current date to a Wednesday
        $currentDateTime = new \DateTime('2023-05-17 00:00:00');

        $this->assertTrue($cron->weekdayIsDue($currentDateTime));
    }

    public function testWeekdayCommaSeparatedIsNotDue()
    {
        $cronExpression = '* * * * 1,3,5'; // Monday, Wednesday, Friday
        $cron = new Cron($cronExpression);

        // Set the current date to a Tuesday
        $currentDateTime = new \DateTime('2023-05-16 00:00:00');

        $this->assertFalse($cron->weekdayIsDue($currentDateTime));
    }

    public function testWildcardIsDue()
    {
        $cronExpression = '* * * * *'; // Every minute
        $cron = new Cron($cronExpression);

        // Set the current time to any valid minute
        $currentDateTime = new \DateTime();

        $this->assertTrue($cron->minuteIsDue($currentDateTime));
        $this->assertTrue($cron->hourIsDue($currentDateTime));
        $this->assertTrue($cron->dayIsDue($currentDateTime));
        $this->assertTrue($cron->monthIsDue($currentDateTime));
        $this->assertTrue($cron->weekdayIsDue($currentDateTime));
    }

    public function testWildcardIsNotDue()
    {
        $cronExpression = '0 0 1 1 *'; // January 1st
        $cron = new Cron($cronExpression);

        // Set the current date to any non-January 1st date
        $currentDateTime = new \DateTime('2023-05-14 00:00:00');

        $this->assertFalse($cron->dayIsDue($currentDateTime));
        $this->assertFalse($cron->monthIsDue($currentDateTime));
        $this->assertTrue($cron->weekdayIsDue($currentDateTime));
        $this->assertFalse($cron->isDue($currentDateTime));
    }


    public function testFullCronExpressionIsDue()
    {
        $cronExpression = '0 12 1 */2 *'; // Every even month on the 1st day at 12:00 PM
        $currentDateTime = new \DateTime('2024-06-01 12:00:00');
        $cron = new Cron($cronExpression);

        $this->assertTrue($cron->isDue($currentDateTime));
    }

    public function testFullCronExpressionIsNotDue()
    {
        $cronExpression = '0 12 1 */2 *'; // Every even month on the 1st day at 12:00 PM
        $currentDateTime = new \DateTime('2023-05-01 12:00:00');
        $cron = new Cron($cronExpression);

        $this->assertFalse($cron->isDue($currentDateTime));
    }

    public function testHourRangeIsDue()
    {
        $cronExpression = '0 8-17 * * *'; // Every hour from 8 AM to 5 PM
        $cron = new Cron($cronExpression);

        // Set the current time to 10 AM
        $currentDateTime = new \DateTime('2023-05-14 10:00:00');

        $this->assertTrue($cron->hourIsDue($currentDateTime));
    }

    public function testHourStepIsDue()
    {
        $cronExpression = '0 */2 * * *'; // Every 2 hours
        $cron = new Cron($cronExpression);

        // Set the current time to 4 PM
        $currentDateTime = new \DateTime('2023-05-14 16:00:00');

        $this->assertTrue($cron->hourIsDue($currentDateTime));
    }

    public function testWildcardWithRangeIsDue()
    {
        $cronExpression = '* 9-17 * * *'; // Every minute from 9 AM to 5 PM
        $cron = new Cron($cronExpression);

        // Set the current time to 10 AM
        $currentDateTime = new \DateTime('2023-05-14 10:00:00');

        $this->assertTrue($cron->minuteIsDue($currentDateTime));
    }

    public function testWildcardWithStepIsDue()
    {
        $cronExpression = '* */2 * * *'; // Every minute of every 2 hours
        $cron = new Cron($cronExpression);

        // Set the current time to 4 PM
        $currentDateTime = new \DateTime('2023-05-14 16:00:00');

        $this->assertTrue($cron->minuteIsDue($currentDateTime));
    }

    public function testWeekdayRangeIsDue()
    {
        $cronExpression = '* * * * 1-5'; // Monday to Friday
        $cron = new Cron($cronExpression);

        // Set the current date to a Wednesday
        $currentDateTime = new \DateTime('2023-05-17 00:00:00');

        $this->assertTrue($cron->weekdayIsDue($currentDateTime));
    }

    public function testWeekdayRangeIsNotDue()
    {
        $cronExpression = '* * * * 1-5'; // Monday to Friday
        $cron = new Cron($cronExpression);

        // Set the current date to a Sunday
        $currentDateTime = new \DateTime('2023-05-14 00:00:00');

        $this->assertFalse($cron->weekdayIsDue($currentDateTime));
    }

    public function testInvalidCronExpressionThrowsException()
    {
        $this->expectException(\Exception::class);

        $cronExpression = '*/5 * * *'; // Missing one field
        $cron = new Cron($cronExpression);
    }

    public function testNextDueAt()
    {
        $cronExpression = '*/15 * * * *'; // Every 15 minutes
        $cron = new Cron($cronExpression);

        $currentDateTime = new \DateTime('2023-05-14 08:00:00');

        // Test next due date within the same hour
        $expectedNextDateTime = new \DateTime('2023-05-14 08:15:00');
        $this->assertEquals($expectedNextDateTime, $cron->nextDueAt($currentDateTime));

        // Test next due date on the next hour
        $currentDateTime = new \DateTime('2023-05-14 08:45:00');
        $expectedNextDateTime = new \DateTime('2023-05-14 09:00:00');
        $this->assertEquals($expectedNextDateTime, $cron->nextDueAt($currentDateTime));

        // Test next due date on the next day
        $currentDateTime = new \DateTime('2023-05-14 23:45:00');
        $expectedNextDateTime = new \DateTime('2023-05-15 00:00:00');
        $this->assertEquals($expectedNextDateTime, $cron->nextDueAt($currentDateTime));

        // Test next due date on the next month
        $cronExpression = '0 0 1 * *'; // Every 1st day of the month
        $cron = new Cron($cronExpression);
        $currentDateTime = new \DateTime('2023-05-31 23:59:00');
        $expectedNextDateTime = new \DateTime('2023-06-01 00:00:00');
        $this->assertEquals($expectedNextDateTime, $cron->nextDueAt($currentDateTime));
    }

    public function testPreviousDueAt()
    {
        $cronExpression = '*/15 * * * *'; // Every 15 minutes
        $cron = new Cron($cronExpression);

        $currentDateTime = new \DateTime('2023-05-14 08:30:00');

        // Test previous due date within the same hour
        $expectedPreviousDateTime = new \DateTime('2023-05-14 08:15:00');
        $this->assertEquals($expectedPreviousDateTime, $cron->previousDueAt($currentDateTime));

        // Test previous due date on the previous hour
        $currentDateTime = new \DateTime('2023-05-14 09:00:00');
        $expectedPreviousDateTime = new \DateTime('2023-05-14 08:45:00');
        $this->assertEquals($expectedPreviousDateTime, $cron->previousDueAt($currentDateTime));

        // Test previous due date on the previous day
        $currentDateTime = new \DateTime('2023-05-15 00:00:00');
        $expectedPreviousDateTime = new \DateTime('2023-05-14 23:45:00');
        $this->assertEquals($expectedPreviousDateTime, $cron->previousDueAt($currentDateTime));

        // Test previous due date on the previous month
        $cronExpression = '0 0 1 * *'; // Every 1st day of the month
        $cron = new Cron($cronExpression);
        $currentDateTime = new \DateTime('2023-06-01 00:00:00');

        $expectedPreviousDateTime = new \DateTime('2023-05-01 00:00:00');
        $this->assertEquals($expectedPreviousDateTime, $cron->previousDueAt($currentDateTime));
    }
}
