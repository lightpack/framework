<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Jobs\Worker;

class ProcessJobs extends Command
{
    public function run()
    {
        $queues = $this->parseQueueArgument() ?? ['default'];
        $sleep = $this->args->get('sleep') ?? 5;
        $cooldown = $this->args->get('cooldown') ?? 0;

        $this->printBanner($queues, $sleep, $cooldown);

        $worker = new Worker(['sleep' => $sleep, 'queues' => $queues, 'cooldown' => $cooldown]);
        $worker->run();

        return self::SUCCESS;
    }

    private function printBanner(array $queues, int $sleep, int $cooldown): void
    {
        $this->output->newline();
        $this->output->line(' Engine:   ' . get_env('JOB_ENGINE', 'sync'));
        $this->output->line(' Queues:   ' . implode(', ', $queues));
        $this->output->newline();
        $this->output->infoLabel('WORKER');
        $this->output->info(' Worker started');
        $this->output->newline();
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
