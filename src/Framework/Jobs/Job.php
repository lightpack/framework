<?php

namespace Lightpack\Jobs;

abstract class Job
{
    /**
     * Delay dispatching the job.
     *
     * @var string 'strtotime' compatible string.
     */
    protected $delay = 'now';

    /**
     * Execute the job passing it the payload.
     *
     * @param array $payload
     * @return void
     */
    abstract public function execute(array $payload);

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
            $this->delay
        );
    }
}
