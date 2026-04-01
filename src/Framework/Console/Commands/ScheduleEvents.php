<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\BaseCommand;
use Lightpack\Schedule\Schedule;

class ScheduleEvents extends BaseCommand
{
    /** @var Schedule */
    protected $schedule;

    public function run(array $arguments = []): int
    {
        $this->schedule = schedule();
        $this->schedule->run();
        
        return 0;
    }
}
