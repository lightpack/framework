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
     * Number of seconds the worker should sleep before checking for new jobs.
     */
    private int $sleepInterval;

    /**
     * @var array
     * 
     * List of queues to process.
     */
    private array  $queues = [];

    /**
     * Job engine used to work with persisted jobs.
     *
     * @var \Lightpack\Jobs\BaseEngine
     */
    private BaseEngine $jobEngine;

    /**
     * @var \Lightpack\Container\Container
     */
    private Container $container;

    /** Character code for info */
    private string $info = "\u{1F6A7}";

    /** Character code for success */
    private string $success = "\u{2705}";

    /** Character code for warning */
    private string $error = "\u{274C}";

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
        $jobHandler = $this->container->resolve($job->handler);
        $jobHandler->setPayload($job->payload);

        try {
            $this->logJobProcessing($job);
            $this->container->call($job->handler, 'run');
            $this->jobEngine->deleteJob($job);
            $this->container->call($job->handler, 'onSuccess');
            $this->logJobProcessed($job);
        } catch (Throwable $e) {
            if($jobHandler->maxAttempts() > $job->attempts + 1) {
                $this->jobEngine->release($job, $jobHandler->retryAfter());
                $this->container->call($job->handler, 'onRetry');
            } else {
                $this->jobEngine->markFailedJob($job, $e);
                $this->container->call($job->handler, 'onFailure', [$e]);
            }

            $this->logJobFailed($job);
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

    protected function logJobProcessing($job)
    {
        $status = str_pad('Processing:', 11, ' ', STR_PAD_RIGHT);
        $status = "\033[33m {$status} \033[0m";
        $status .= $job->handler;
        $status .= "\033[34m" . ' #ID: ' . $job->id . "\033[0m" . PHP_EOL;

        fputs(STDOUT, $status);
    }

    protected function logJobProcessed($job)
    {
        $status = str_pad('Processed:', 11, ' ', STR_PAD_RIGHT);
        $status = "\033[32m {$status} \033[0m";
        $status .= $job->handler;
        $status .= "\033[34m" . ' #ID: ' . $job->id . "\033[0m" . PHP_EOL;

        fputs(STDOUT, $status);
    }

    protected function logJobFailed($job)
    {
        $status = str_pad('Failed:', 11, ' ', STR_PAD_RIGHT);
        $status = "\033[31m {$status} \033[0m";
        $status .= $job->handler;
        $status .= "\033[34m" . ' #ID: ' . $job->id . "\033[0m" . PHP_EOL;

        fputs(STDERR, $status);
    }
}
