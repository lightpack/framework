<?php

namespace Lightpack\Jobs\Engines;

use Throwable;
use Lightpack\Utils\Moment;
use Lightpack\Jobs\BaseEngine;

class DatabaseEngine extends BaseEngine
{
    public function addJob(string $jobHandler, array $payload, string $delay, string $queue): void
    {
        db()->table('jobs')->insert([
            'handler' => $jobHandler,
            'payload' => json_encode($payload),
            'scheduled_at' => (new Moment)->travel($delay),
            'status' => 'new',
            'queue' => $queue,
            'attempts' => 0,
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

    public function deleteJob($job): void
    {
        db()->query("DELETE FROM jobs WHERE id = {$job->id}");
    }

    public function markFailedJob($job, Throwable $e): void
    {
        db()->query("UPDATE jobs SET `status` = :status, `exception` = :exception, `failed_at` = :failed_at WHERE `id` = :id", [
            'status' => 'failed',
            'exception' => (string) $e,
            'failed_at' => (new Moment)->now(),
            'id' => $job->id,
        ]);
    }

    public function release($job, string $delay = 'now'): void
    {
        db()->query("UPDATE jobs SET `status` = :status, `exception` = :exception, `failed_at` = :failed_at, `scheduled_at` = :scheduled_at, `attempts` = :attempts WHERE `id` = :id", [
            'status' => 'new',
            'exception' => null,
            'failed_at' => null,
            'scheduled_at' => (new Moment)->travel($delay),
            'attempts' => $job->attempts + 1,
            'id' => $job->id,
        ]);
    }

    /**
     * Find the next queued job.
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
            ->query("SELECT * FROM jobs WHERE status = 'new' AND scheduled_at <= '{$now}' {$whereQueue} ORDER BY id ASC LIMIT 1 FOR UPDATE SKIP LOCKED")
            ->fetch(\PDO::FETCH_OBJ);

        // If job found, update its status to 'queued'
        if ($job) {
            db()->query("UPDATE jobs SET `status` = :status, `attempts` = :attempts WHERE id = :id", [
                'status' => 'queued',
                'attempts' => $job->attempts + 1,
                'id' => $job->id,
            ]);
        }

        db()->commit();

        return $job;
    }
}
