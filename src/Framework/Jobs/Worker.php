<?php

namespace Lightpack\Jobs;

use Exception;

class Worker
{
    /** 
     * @var int 
     * 
     * Number of seconds the job should sleep.
     */
    private $sleepInterval;

    /**
     * Job engine used to work with persisted jobs.
     *
     * @var \Lightpack\Jobs\BaseEngine
     */
    private $jobEngine;


    public function __construct(array $options = [])
    {
        $this->jobEngine = Connection::getJobEngine();
        $this->sleepInterval = $options['sleep'] ?? 5;
    }

    /**
     * Run the worker as a loop.
     *
     * @return void
     */
    public function run()
    {
        while (true) {
            $nextJob = $this->jobEngine->fetchNextJob();

            if ($nextJob) {
                $this->dispatchJob($nextJob);
                continue;
            }

            $this->sleep();
        }
    }

    /**
     * Dispatches the job handler with payload data.
     *
     * @param object $job
     * @return void
     */
    protected function dispatchJob($job)
    {
        try {
            $jobHandler = new $job->name;
            $jobHandler->execute($job->payload);
            $this->jobEngine->deleteJob($job);
            fputs(STDOUT, "✔ Job processed successfully: {$job->id}\n");
        } catch (Exception $e) {
            $this->jobEngine->markFailedJob($job);
            fputs(STDERR, "✖ Error dispatching job: {$job->id} - " . $e->getMessage() . "\n");
        }
    }

    /**
     * Make the worker sleep for specified seconds.
     *
     * @return void
     */
    protected function sleep()
    {
        sleep($this->sleepInterval);
    }
}
