<?php

namespace Lightpack\Jobs\Engines;

use Lightpack\Jobs\BaseEngine;
use Lightpack\Utils\Moment;
use Throwable;

class DatabaseEngine extends BaseEngine
{
    public function addJob(string $jobHandler, array $payload, string $delay, string $queue)
    {
        db()->table('jobs')->insert([
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
        db()->query("DELETE FROM jobs WHERE id = {$job->id}");
    }

    public function markFailedJob($job, Throwable $e)
    {
        db()->query("UPDATE jobs SET `status` = :status, `exception` = :exception, `failed_at` = :failed_at WHERE `id` = {$job->id}", [
            'status' => 'failed',
            'exception' => (string) $e,
            'failed_at' => (new Moment)->now(),
        ]);
    }

    /**
     * Finds a job for update with status 'new'. 
     *
     * @return object|null
     */
    private function findNextQueuedJob(string $queue = null)
    {
        $now = date('Y-m-d H:i:s');

        db()->begin();

        $whereQueue = $queue ? "AND queue = '{$queue}'" : '';

        // Selectively lock the row exclusively for update
        $job = db()
            ->query("SELECT * FROM jobs WHERE status = 'new' AND scheduled_at <= '{$now}' {$whereQueue} ORDER BY id ASC LIMIT 1 FOR UPDATE")
            ->fetch(\PDO::FETCH_OBJ);

        // If job found, update its status to 'queued'
        if ($job) {
            db()->query("UPDATE jobs SET status = 'queued' WHERE id = {$job->id}");
        }

        db()->commit();

        return $job;
    }
}
