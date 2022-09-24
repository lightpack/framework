<?php

namespace Lightpack\Jobs;

use Exception;
use Lightpack\Container\Container;
use Throwable;

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

    /**
     * @var \Lightpack\Container\Container
     */
    private $container;


    public function __construct(array $options = [])
    {
        $this->jobEngine = Connection::getJobEngine();
        $this->sleepInterval = $options['sleep'] ?? 5;
        $this->container = Container::getInstance();
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
            $jobHandler = $this->container->resolve($job->name);
            $jobHandler->setPayload($job->payload);

            $this->container->call($job->name, 'run');
            $this->jobEngine->deleteJob($job);

            fputs(STDOUT, "✔ Job processed successfully: {$job->id}\n");
        } catch (Throwable $e) {
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
