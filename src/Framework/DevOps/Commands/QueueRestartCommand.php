<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;
use Lightpack\DevOps\QueueManager;

/**
 * Restart the background queue worker daemon.
 *
 * Stops the current worker (if running) and starts a new one.
 *
 * Usage:
 *   php lightpack queue:restart              Restart with default queue
 *   php lightpack queue:restart --queue=mail Restart with 'mail' queue
 */
class QueueRestartCommand extends Command
{
    public function run()
    {
        $manager = new QueueManager();

        $options = [
            'queue' => $this->args->get('queue') ?? 'default',
            'sleep' => $this->args->get('sleep') ?? null,
            'cooldown' => $this->args->get('cooldown') ?? null,
        ];

        $this->output->info('Restarting queue worker ...');
        $this->output->newline();

        $result = $manager->restart($options);

        if ($result['success']) {
            $this->output->success($result['message']);
            return self::SUCCESS;
        }

        $this->output->error($result['message']);
        return self::FAILURE;
    }
}
