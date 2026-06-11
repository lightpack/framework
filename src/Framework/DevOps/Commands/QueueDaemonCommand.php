<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;
use Lightpack\DevOps\QueueManager;

/**
 * Start a background queue worker daemon.
 *
 * The daemon runs `jobs:run` in the background and writes its PID
 * to storage/worker.pid. No root or Supervisor required.
 *
 * Usage:
 *   php lightpack queue:daemon              Start with default queue
 *   php lightpack queue:daemon --queue=mail Start processing 'mail' queue
 *   php lightpack queue:daemon --sleep=3  Check for jobs every 3 seconds
 */
class QueueDaemonCommand extends Command
{
    public function run()
    {
        $manager = new QueueManager();

        $options = [
            'queue' => $this->args->get('queue') ?? 'default',
            'sleep' => $this->args->get('sleep') ?? null,
            'cooldown' => $this->args->get('cooldown') ?? null,
        ];

        $this->output->info('Starting queue worker daemon ...');
        $this->output->newline();

        $result = $manager->start($options);

        if ($result['success']) {
            $this->output->success($result['message']);
            $this->output->newline();
            $this->output->line('Manage with:');
            $this->output->line('  php lightpack queue:status  Check status');
            $this->output->line('  php lightpack queue:restart   Restart worker');
            $this->output->line('  php lightpack queue:stop      Stop worker');
            return self::SUCCESS;
        }

        $this->output->error($result['message']);
        return self::FAILURE;
    }
}
