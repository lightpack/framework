<?php

namespace Lightpack\Jobs;

use Throwable;
use Lightpack\Container\Container;
use Lightpack\Utils\Limiter;

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
     * @var \Lightpack\Utils\Limiter
     */
    protected Limiter $limiter;

    /**
     * @var bool
     */
    protected bool $running = true;

    public function __construct(array $options = [])
    {
        $this->jobEngine = Connection::getJobEngine();
        $this->sleepInterval = $options['sleep'] ?? 5;
        $this->queues = $options['queues'] ?? ['default'];
        $this->cooldown = $options['cooldown'] ?? 0; // 0 means unlimited run time
        $this->container = Container::getInstance();
        $this->limiter = new Limiter();
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

        if ($this->isRateLimited($jobHandler)) {
            $this->releaseRateLimitedJob($job, $jobHandler);
            return;
        }

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
     * Check if the job is rate limited.
     */
    protected function isRateLimited($jobHandler): bool
    {
        $config = $jobHandler->rateLimit();
        
        // No rate limit configured
        if ($config === null) {
            return false;
        }

        $limit = $config['limit'] ?? null;
        $seconds = $this->resolveRateLimitWindow($config);
        $key = $config['key'] ?? 'job:' . str_replace('\\', '.', get_class($jobHandler));

        if ($limit === null) {
            return false;
        }

        // Return true if rate limit exceeded (attempt failed)
        return !$this->limiter->attempt($key, $limit, $seconds);
    }

    /**
     * Resolve the rate limit window from config, supporting multiple time units.
     * 
     * Supports: seconds, minutes, hours, days
     * Priority: seconds > minutes > hours > days
     * 
     * @throws \InvalidArgumentException if no time unit is specified
     */
    protected function resolveRateLimitWindow(array $config): int
    {
        if (isset($config['seconds'])) {
            return (int) $config['seconds'];
        }

        if (isset($config['minutes'])) {
            return (int) $config['minutes'] * 60;
        }

        if (isset($config['hours'])) {
            return (int) $config['hours'] * 3600;
        }

        if (isset($config['days'])) {
            return (int) $config['days'] * 86400;
        }

        throw new \InvalidArgumentException(
            'Rate limit configuration must specify a time unit: seconds, minutes, hours, or days'
        );
    }

    /**
     * Release a rate-limited job back to the queue.
     * 
     * The job is delayed until the rate limit window expires to avoid
     * repeatedly hitting the rate limit.
     */
    protected function releaseRateLimitedJob($job, $jobHandler): void
    {
        $config = $jobHandler->rateLimit();
        $seconds = $this->resolveRateLimitWindow($config);
        
        $this->logJobRateLimited($job);
        $this->jobEngine->releaseWithoutIncrement($job, '+' . $seconds . ' seconds');
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

    protected function logJobRateLimited($job)
    {
        $status = str_pad('[RATE LIMITED]', 20, '.', STR_PAD_RIGHT);
        $status = "\033[33m {$status} \033[0m";
        $status .= $job->handler;
        $status .= "\033[34m" . ' #ID: ' . $job->id . "\033[0m" . PHP_EOL;

        fputs(STDOUT, $status);
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

    protected function shouldCooldown(): bool
    {
        if($this->cooldown == 0) {
            return false;
        }

        return time() - $this->startTime > $this->cooldown;
    }
}
