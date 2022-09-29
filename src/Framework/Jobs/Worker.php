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
     * @var array
     * 
     * List of queues to process.
     */
    private $queues = [];

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
        $this->queues = $options['queues'] ?? ['default'];
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
            foreach ($this->queues as $queue) {
                $this->processQueue($queue);
            }

            sleep($this->sleepInterval);
        }
    }

    protected function processQueue(?string $queue = null)
    {
        while ($job = $this->jobEngine->fetchNextJob($queue)) {
            $this->dispatchJob($job);
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
            $jobHandler = $this->container->resolve($job->handler);
            $jobHandler->setPayload($job->payload);

            $this->container->call($job->handler, 'run');
            $this->jobEngine->deleteJob($job);

            fputs(STDOUT, "✔ Job processed successfully: {$job->id}\n");
        } catch (Throwable $e) {
            $this->jobEngine->markFailedJob($job, $e);
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
