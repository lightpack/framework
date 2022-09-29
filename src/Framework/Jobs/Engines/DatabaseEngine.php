<?php

namespace Lightpack\Jobs\Engines;

use Lightpack\Jobs\BaseEngine;
use Lightpack\Utils\Moment;

class DatabaseEngine extends BaseEngine
{
    public function addJob(string $jobHandler, array $payload, string $delay, string $queue)
    {
        app('db')->table('jobs')->insert([
            'handler' => $jobHandler,
            'payload' => json_encode($payload),
            'scheduled_at' => (new Moment)->travel($delay),
            'status' => 'new',
            'queue' => $queue,
        ]);
    }

    public function fetchNextJob(?string $queue = null)
    {
        $job = $this->findNextQueuedJob($queue);

        if ($job) {
            $this->deserializePayload($job);
        }

        return $job;
    }

    public function deleteJob($job)
    {
        app('db')->query("DELETE FROM jobs WHERE id = {$job->id}");
    }

    public function markFailedJob($job)
    {
        app('db')->query("UPDATE jobs SET status = 'failed' WHERE id = {$job->id}");
    }

    /**
     * Finds a job for update with status 'new'. 
     *
     * @return object|null
     */
    private function findNextQueuedJob(string $queue = null)
    {
        $now = date('Y-m-d H:i:s');

        app('db')->begin();

        $whereQueue = $queue ? "AND queue = '{$queue}'" : '';

        // Selectively lock the row exclusively for update
        $job = app('db')
            ->query("SELECT * FROM jobs WHERE status = 'new' AND scheduled_at <= '{$now}' {$whereQueue} ORDER BY id ASC LIMIT 1 FOR UPDATE")
            ->fetch(\PDO::FETCH_OBJ);

        // If job found, update its status to 'queued'
        if ($job) {
            app('db')->query("UPDATE jobs SET status = 'queued' WHERE id = {$job->id}");
        }

        app('db')->commit();

        return $job;
    }
}
