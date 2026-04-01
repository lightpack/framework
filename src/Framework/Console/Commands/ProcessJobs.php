<?php

namespace Lightpack\Console\Commands;

use Lightpack\Jobs\Worker;
use Lightpack\Console\BaseCommand;

class ProcessJobs extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        $queues = $this->parseQueueArgument() ?? ['default'];
        $sleep = $this->args->get('sleep') ?? 5;
        $cooldown = $this->args->get('cooldown') ?? 0;

        $worker = new Worker(['sleep' => $sleep, 'queues' => $queues, 'cooldown' => $cooldown]);

        $worker->run();
        
        return 0;
    }

    private function parseQueueArgument()
    {
        $queue = $this->args->get('queue');
        
        if ($queue) {
            $queues = explode(',', $queue);
            return array_map('trim', $queues);
        }
        
        return null;
    }
}