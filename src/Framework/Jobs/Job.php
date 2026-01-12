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
    public function setPayload(array $payload): static
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
     * Set the delay for the job.
     *
     * @param string $delay 'strtotime' compatible string (e.g., '+30 seconds', '+1 hour')
     * @return self
     */
    public function delay(string $delay): self
    {
        $this->delay = $delay;
        return $this;
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
            $this->onQueue(),
        );
    }

    /**
     * Dispatch the job immediately without queuing.
     * 
     * This will call the executing job class run() method.
     */
    public function dispatchSync(array $payload)
    {
        return (new static)->setPayload($payload)->run();
    }

    public function onQueue(): string
    {
        return $this->queue;
    }

    public function maxAttempts(): int
    {
        return $this->attempts;
    }

    public function retryAfter(): string
    {
        return $this->retryAfter;
    }

    /**
     * Configure rate limiting for this job.
     * 
     * Return an array with 'limit' and 'seconds' keys, or null to disable.
     * 
     * Examples:
     *   return ['limit' => 10, 'seconds' => 1];     // 10 per second
     *   return ['limit' => 100, 'seconds' => 60];   // 100 per minute
     *   return null;                                 // No rate limiting
     * 
     * @return array|null ['limit' => int, 'seconds' => int, 'key' => string (optional)]
     */
    public function rateLimit(): ?array
    {
        return null;
    }

    /**
     * Fail the job permanently without retrying.
     * 
     * Use this for business logic failures where retrying won't help:
     * - Invalid data that won't change
     * - Insufficient balance/credits
     * - Resource not found (deleted user, etc.)
     * - Permission denied
     * 
     * The job will be marked as failed immediately without consuming retry attempts.
     * 
     * Example:
     * ```php
     * if ($response['status'] === 'insufficient_balance') {
     *     $this->failPermanently('SMS Provider: Insufficient balance');
     * }
     * ```
     * 
     * @param string $reason The reason for permanent failure
     * @return never
     * @throws \Lightpack\Jobs\Exceptions\PermanentJobFailureException
     */
    protected function failPermanently(string $reason): never
    {
        throw new \Lightpack\Jobs\Exceptions\PermanentJobFailureException($reason);
    }
}
