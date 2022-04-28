<?php

namespace Lightpack\Schedule;

class Schedule
{
    public function command(string $command, array $args = [])
    {
        $process = new Process($command, $args);
        $process->run();
    }
}