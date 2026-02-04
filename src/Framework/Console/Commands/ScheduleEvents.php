<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Lightpack\Schedule\Schedule;

class ScheduleEvents implements CommandInterface
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
