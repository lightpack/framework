<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;
use Lightpack\DevOps\QueueManager;

/**
 * Stop the background queue worker daemon.
 *
 * Sends SIGTERM for graceful shutdown. Uses SIGKILL if the process
 * does not exit within 5 seconds.
 *
 * Usage:
 *   php console queue:stop
 */
class QueueStopCommand extends Command
{
    public function run()
    {
        $manager = new QueueManager();

        $this->output->info('Stopping queue worker ...');
        $this->output->newline();

        $result = $manager->stop();

        if ($result['success']) {
            $this->output->success($result['message']);
            return self::SUCCESS;
        }

        $this->output->error($result['message']);
        return self::FAILURE;
    }
}
