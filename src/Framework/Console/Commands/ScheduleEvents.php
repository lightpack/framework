<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Schedule\Schedule;

class ScheduleEvents implements ICommand
{
    /** @var Schedule */
    protected $schedule;

    public function __construct()
    {
        $this->schedule = schedule();
    }

    public function run(array $arguments = [])
    {
        $this->schedule->run();
    }
}
