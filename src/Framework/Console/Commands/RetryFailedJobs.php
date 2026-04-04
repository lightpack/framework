<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Jobs\Connection;

class RetryFailedJobs extends Command
{
    public function run(): int
    {
        $engine = Connection::getJobEngine();
        $jobId = $this->args->argument(0);
        $queue = $this->args->get('queue');
        
        $count = $engine->retryFailedJobs($jobId, $queue);
        
        if ($count === 0) {
            if ($jobId) {
                $this->output->error("Job #{$jobId} not found or not failed.");
            } elseif ($queue) {
                $this->output->line("No failed jobs to retry in queue '{$queue}'.");
            } else {
                $this->output->line("No failed jobs to retry.");
            }
            $this->output->newline();
            return self::SUCCESS;
        }
        
        if ($jobId) {
            $this->output->success("✓ Job #{$jobId} queued for retry.");
        } elseif ($queue) {
            $this->output->success("✓ {$count} job(s) from queue '{$queue}' queued for retry.");
        } else {
            $this->output->success("✓ {$count} job(s) queued for retry.");
        }
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
