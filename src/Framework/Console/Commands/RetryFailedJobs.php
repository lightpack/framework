<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Lightpack\Jobs\Connection;

class RetryFailedJobs implements CommandInterface
{
    public function run(array $arguments = [])
    {
        $engine = Connection::getJobEngine();
        $jobId = $this->parseJobIdArgument($arguments);
        $queue = $this->parseQueueArgument($arguments);
        
        $count = $engine->retryFailedJobs($jobId, $queue);
        
        if ($count === 0) {
            if ($jobId) {
                fputs(STDERR, "Job #{$jobId} not found or not failed.\n");
            } elseif ($queue) {
                fputs(STDOUT, "No failed jobs to retry in queue '{$queue}'.\n");
            } else {
                fputs(STDOUT, "No failed jobs to retry.\n");
            }
            return;
        }
        
        if ($jobId) {
            fputs(STDOUT, "✓ Job #{$jobId} queued for retry.\n");
        } elseif ($queue) {
            fputs(STDOUT, "✓ {$count} job(s) from queue '{$queue}' queued for retry.\n");
        } else {
            fputs(STDOUT, "✓ {$count} job(s) queued for retry.\n");
        }
    }
    
    private function parseJobIdArgument(array $args)
    {
        foreach ($args as $arg) {
            if (is_numeric($arg)) {
                return (int) $arg;
            }
            // For Redis string IDs
            if (!str_starts_with($arg, '--')) {
                return $arg;
            }
        }
        
        return null;
    }
    
    private function parseQueueArgument(array $args)
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--queue=')) {
                return substr($arg, 8);
            }
        }
        
        return null;
    }
}
