<?php

namespace Lightpack\Jobs;

use Throwable;

class Job
{
    /**
     * @var string  The name of the queue to which the job will be dispatched.
     */
    protected $queue = 'default';

    /**
     * Delay dispatching the job by the specified interval. By default, 
     * the job will be dispatched immediately.
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
     * Retry after specified interval when the job fails. By default, 
     * the job will be retried immediately.
     * 
     * @var string 'strtotime' compatible string.
     */
    protected $retryAfter = 'now';

    /**
     * The exception thrown by the job when it fails.
     */
    protected ?Throwable $exception = null;

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
     * This method sets the exception thrown by the job when it fails.
     */
    public function setException(Throwable $exception): self
    {
        $this->exception = $exception;

        return $this;
    }

    /**
     * This method returns the exception thrown by the job when it fails.
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }

    /**
     * Dispatch the job into the queue.
     *
     * @param array $payload
     * @return void
     */
    public function dispatch(array $payload = [])
    {
        $this->setPayload($payload);
        
        $jobEngine = Connection::getJobEngine();

        $jobEngine->addJob(
            static::class,
            $this->payload,
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
