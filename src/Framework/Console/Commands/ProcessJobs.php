<?php

namespace Lightpack\Console\Commands;

use Lightpack\Jobs\Worker;
use Lightpack\Console\Command;
use Lightpack\Console\WatchesEnvTrait;

class ProcessJobs extends Command
{
    use WatchesEnvTrait;
    
    public function run()
    {
        $queues = $this->parseQueueArgument() ?? ['default'];
        $sleep = (int) ($this->args->get('sleep') ?? 5);
        $cooldown = (int) ($this->args->get('cooldown') ?? 0);

        if ($this->args->has('no-watch')) {
            $this->printBanner($queues, $sleep, $cooldown);
            $worker = new Worker(['sleep' => $sleep, 'queues' => $queues, 'cooldown' => $cooldown]);
            $worker->run();
            return self::SUCCESS;
        }

        $this->runWatched($this->buildWorkerCommand());

        return self::SUCCESS;
    }

    private function buildWorkerCommand(): array
    {
        $command = [PHP_BINARY, DIR_ROOT . DIRECTORY_SEPARATOR . 'console', 'jobs:run', '--no-watch'];

        foreach ($this->args->options() as $key => $value) {
            $command[] = $value === true ? "--{$key}" : "--{$key}={$value}";
        }

        return $command;
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
