<?php

namespace Lightpack\Jobs\Engines;

use stdClass;
use Exception;
use Lightpack\Jobs\BaseEngine;
use Lightpack\Moment\Moment;
use Pheanstalk\Pheanstalk;

class BeanstalkEngine extends BaseEngine
{
    private $job;

    /** @inheritDoc */
    public function addJob(string $jobHandler, array $payload = [], string $queue = 'default', string $schedule = 'now')
    {
        $job = new stdClass();

        $job->name = $jobHandler;
        $job->payload = $payload;
        $job->scheduled_at = Moment::travel($schedule);

        app('beanstalk')
            ->useTube($queue)
            ->put(json_encode($job), Pheanstalk::DEFAULT_PRIORITY, 30, 60);
    }

    /** @inheritDoc */
    public function fetchNextJob()
    {
        $job = $this->findNextQueuedJob();

        return $job;
    }

    /** @inheritDoc */
    public function deleteJob($id = null)
    {
        app('beanstalk')->delete($this->job);
    }

    /** @inheritDoc */
    public function markFailedJob($id = null)
    {
        // todo
    }

    /**
     * Finds a job for update with status 'new'. 
     *
     * @return object|null
     */
    private function findNextQueuedJob(string $queue = 'default')
    {
        $this->job = app('beanstalk')->watch($queue)->reserve();

        try {
            $this->jobData = json_decode($this->job->getData());
        } catch (Exception $e) {
            app('beanstalk')->release($this->job);
        }
    }
}
