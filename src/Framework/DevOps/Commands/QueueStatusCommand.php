<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;
use Lightpack\DevOps\QueueManager;

/**
 * Check the status of the background queue worker daemon.
 *
 * Usage:
 *   php console queue:status
 */
class QueueStatusCommand extends Command
{
    public function run()
    {
        $manager = new QueueManager();
        $status = $manager->status();

        if ($status['running']) {
            $this->output->success('Queue worker is running.');
            $this->output->newline();
            $this->output->line("  PID: {$status['pid']}");

            if ($status['uptime'] > 0) {
                $minutes = (int) ($status['uptime'] / 60);
                $this->output->line("  Uptime: {$minutes} minutes");
            }
        } else {
            $this->output->warning('Queue worker is not running.');
            $this->output->newline();
            $this->output->line('Start it with: php console queue:daemon');
        }

        return self::SUCCESS;
    }
}
