<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Schedule\Schedule;

class ScheduleEvents extends Command
{
    /** @var Schedule */
    protected $schedule;

    public function run(): int
    {
        $this->schedule = schedule();
        $this->schedule->run();
        
        return self::SUCCESS;
    }
}
