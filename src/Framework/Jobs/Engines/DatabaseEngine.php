<?php

namespace Lightpack\Jobs\Engines;

use Lightpack\Jobs\BaseEngine;
use Lightpack\Moment\Moment;

class DatabaseEngine extends BaseEngine
{
    /** @inheritDoc */
    public function addJob(string $jobHandler, array $payload, string $delay)
    {
        app('db')->table('jobs')->insert([
            'name' => $jobHandler,
            'payload' => json_encode($payload),
            'scheduled_at' => Moment::travel($delay),
            'status' => 'new'
        ]);
    }

    /** @inheritDoc */
    public function fetchNextJob()
    {
        $job = $this->findNextQueuedJob();

        if ($job) {
            $this->deserializePayload($job);
        }

        return $job;
    }

    /** @inheritDoc */
    public function deleteJob($job)
    {
        app('db')->query("DELETE FROM jobs WHERE id = {$job->id}");
    }

    /** @inheritDoc */
    public function markFailedJob($job)
    {
        app('db')->query("UPDATE jobs SET status = 'failed' WHERE id = {$job->id}");
    }

    /**
     * Finds a job for update with status 'new'. 
     *
     * @return object|null
     */
    private function findNextQueuedJob()
    {
        $now = date('Y-m-d H:i:s');

        app('db')->begin();

        // Selectively lock the row exclusively for update
        $job = app('db')
            ->query("SELECT * FROM jobs WHERE status = 'new' AND scheduled_at <= '{$now}' ORDER BY id ASC LIMIT 1 FOR UPDATE")
            ->fetch(\PDO::FETCH_OBJ);

        // If job found, update its status to 'queued'
        if ($job) {
            app('db')->query("UPDATE jobs SET status = 'queued' WHERE id = {$job->id}");
        }

        app('db')->commit();

        return $job;
    }
}
