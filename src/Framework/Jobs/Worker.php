<?php

namespace Lightpack\Jobs;

use Throwable;
use Lightpack\Container\Container;

class Worker
{
    /** 
     * @var int 
     * 
     * Number of seconds the worker should sleep before checking for new jobs.
     */
    protected int $sleepInterval;

    /**
     * @var array
     * 
     * List of queues to process.
     */
    protected array  $queues = [];

    /**
     * @var int
     * 
     * Max number of seconds after which the worker should stop processing jobs.
     */
    protected int $cooldown;

    /**
     * @var int
     * 
     * Start time of the worker.
     */
    protected $startTime;

    /**
     * Job engine used to work with persisted jobs.
     *
     * @var \Lightpack\Jobs\BaseEngine
     */
    protected BaseEngine $jobEngine;

    /**
     * @var \Lightpack\Container\Container
     */
    protected Container $container;

    /**
     * @var bool
     */
    protected bool $running = true;

    public function __construct(array $options = [])
    {
        $this->jobEngine = Connection::getJobEngine();
        $this->sleepInterval = $options['sleep'] ?? 5;
        $this->queues = $options['queues'] ?? ['default'];
        $this->cooldown = $options['cooldown'] ?? 60 * 60; // 1 hour
        $this->container = Container::getInstance();
        $this->startTime = time();
        $this->registerSignalHandlers();
    }

    /**
     * Run the worker as a loop.
     *
     * @return void
     */
    public function run()
    {
        $this->startTime = time();

        while ($this->running) {
            foreach ($this->queues as $queue) {
                $this->processQueue($queue);
            }

            sleep($this->sleepInterval);
        }
    }

    protected function processQueue(?string $queue = null)
    {
        while ($this->running && $job = $this->jobEngine->fetchNextJob($queue)) {
            $this->dispatchJob($job);

            if($this->shouldCooldown()) {
                $this->pleaseCooldown();
            }
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
            $this->container->callIf($job->handler, 'onSuccess');
            $this->logJobProcessed($job);
        } catch (Throwable $e) {
            $jobHandler->setException($e);

            if ($jobHandler->maxAttempts() > $job->attempts + 1) {
                $this->jobEngine->release($job, $jobHandler->retryAfter());
            } else {
                $this->jobEngine->markFailedJob($job, $e);
                $this->container->callIf($job->handler, 'onFailure');
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
        $status = str_pad('[PROCESSING]', 20, '.', STR_PAD_RIGHT);
        $status = "\033[33m {$status} \033[0m";
        $status .= $job->handler;
        $status .= "\033[34m" . ' #ID: ' . $job->id . "\033[0m" . PHP_EOL;

        fputs(STDOUT, $status);
    }

    protected function logJobProcessed($job)
    {
        $status = str_pad('[PROCESSED]', 20, '.', STR_PAD_RIGHT);
        $status = "\033[32m {$status} \033[0m";
        $status .= $job->handler;
        $status .= "\033[34m" . ' #ID: ' . $job->id . "\033[0m" . PHP_EOL;

        fputs(STDOUT, $status);
    }

    protected function logJobFailed($job)
    {
        $status = str_pad('[FAILED]:', 20, '.', STR_PAD_RIGHT);
        $status = "\033[31m {$status} \033[0m";
        $status .= $job->handler;
        $status .= "\033[34m" . ' #ID: ' . $job->id . "\033[0m" . PHP_EOL;

        fputs(STDERR, $status);
    }

    protected function shouldRegisterSignalHandlers()
    {
        return extension_loaded('pcntl');
    }

    protected function registerSignalHandlers()
    {
        if (false === $this->shouldRegisterSignalHandlers()) {
            fputs(STDERR, "\033[31m pcntl extension is not loaded. Signal handlers will not be registered. \033[0m" . PHP_EOL);

            return;
        }

        fputs(STDOUT, "\033[34m [Info]\033[m Registering signal handlers." . PHP_EOL);

        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, fn () => $this->stopRunning());
        pcntl_signal(SIGINT, fn () => $this->stopRunning());

        fputs(STDOUT, "\033[34m [Info]\033[m Signal handlers registered." . PHP_EOL);
    }

    protected function stopRunning()
    {
        fputs(STDOUT, "\033[31m Interrupt signal received, stopping the worker... \033[0m" . PHP_EOL);

        $this->running = false;
    }

    protected function pleaseCooldown()
    {
        fputs(STDERR, "\033[32m Cooldown reached, stopping the worker... \033[0m" . PHP_EOL);
        fputs(STDERR, "\033[34m" . ' Worker ran for ' . (time() - $this->startTime) . ' seconds. ' . "\033[0m" . PHP_EOL);

        $this->running = false;
    }

    protected function shouldCooldown()
    {
        return time() - $this->startTime > $this->cooldown;
    }
}
