<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Jobs\Connection;

class RetryFailedJobs implements ICommand
{
    public function run(array $arguments = [])
    {
        $engine = Connection::getJobEngine();
        $jobId = $this->parseJobIdArgument($arguments);
        
        $count = $engine->retryFailedJobs($jobId);
        
        if ($count === 0) {
            if ($jobId) {
                fputs(STDERR, "Job #{$jobId} not found or not failed.\n");
            } else {
                fputs(STDOUT, "No failed jobs to retry.\n");
            }
            return;
        }
        
        if ($jobId) {
            fputs(STDOUT, "✓ Job #{$jobId} queued for retry.\n");
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
}
