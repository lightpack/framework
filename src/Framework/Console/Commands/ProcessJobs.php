<?php

namespace Lightpack\Console\Commands;

use Lightpack\Jobs\Worker;
use Lightpack\Console\ICommand;

class ProcessJobs implements ICommand
{
    public function run(array $arguments = [])
    {
        $queues = $this->parseQueueArgument($arguments) ?? ['default']; // 'default' queue
        $sleep = $this->parseSleepArgument($arguments) ?? 5; // seconds
        $cooldown = $this->parseCooldownArgument($arguments) ?? 60; // 1 minute

        $worker = new Worker(['sleep' => $sleep, 'queues' => $queues, 'cooldown' => $cooldown]);

        $worker->run();
    }

    private function parseQueueArgument($args)
    {
        $queues = [];

        foreach ($args as $arg) {
            if (strpos($arg, '--queue=') !== false) {
                $queues = explode(',', str_replace('--queue=', '', $arg));
                $queues = array_map('trim', $queues);
            }
        }

        return empty($queues) ? null : $queues;
    }

    private function parseSleepArgument($args)
    {
        foreach($args as $arg) {
            if(strpos($arg, '--sleep') === 0) {
                $fragments = explode('=', $arg);

                if(isset($fragments[1])) {
                    return (int) $fragments[1];
                }
            }
        }
    }

    private function parseCooldownArgument($args)
    {
        foreach($args as $arg) {
            if(strpos($arg, '--cooldown') === 0) {
                $fragments = explode('=', $arg);

                if(isset($fragments[1])) {
                    return (int) $fragments[1];
                }
            }
        }
    }
}