<?php

namespace Lightpack\Jobs;

class Job
{
    /**
     * @var string  The name of the queue to which the job will be dispatched.
     */
    protected $queue = 'default';

    /**
     * Delay dispatching the job.
     *
     * @var string 'strtotime' compatible string.
     */
    protected $delay = 'now';

    /**
     * @var array   Payload data.
     */
    protected array $payload = [];

    /**
     * The number of times the job may be attempted.
     */
    protected $attempts = 1;

    /**
     * Retry after when the job fails.
     */
    protected $retryAfter = 'now';

    /**
     * Dispatch the job.
     *
     * @param array $payload
     * @return self
     */
    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * Dispatch the job into the queue.
     *
     * @param array $payload
     * @return void
     */
    public function dispatch(array $payload = [])
    {
        $jobEngine = Connection::getJobEngine();

        $jobEngine->addJob(
            static::class,
            $payload,
            $this->delay,
            $this->queue
        );
    }

    public function maxAttempts(): int
    {
        return $this->attempts;
    }

    public function retryAfter(): string
    {
        return $this->retryAfter;
    }
}
