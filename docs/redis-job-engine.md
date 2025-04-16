# Redis Job Engine for Lightpack

The Redis job engine provides a high-performance, scalable job queue implementation for Lightpack using Redis as the backend storage.

## Features

- Fast, in-memory job processing
- Reliable job queuing with persistence
- Support for delayed jobs
- Multiple queues for job prioritization
- Atomic job claiming to prevent race conditions
- Comprehensive job lifecycle management
- Failed job tracking and retry support

## Configuration

### Environment Variables

Configure the Redis job engine in your `.env` file:

```
# Use Redis as the job engine
JOB_ENGINE=redis

# Optional Redis job settings
REDIS_JOB_PREFIX=jobs:
REDIS_JOB_DB=0
```

### Redis Configuration

The Redis job engine uses the configuration defined in `config/redis.php`:

```php
'jobs' => [
    'connection' => 'default',
    'prefix' => $_ENV['REDIS_JOB_PREFIX'] ?? 'jobs:',
    'database' => $_ENV['REDIS_JOB_DB'] ?? 0,
],
```

## Usage

### Creating Jobs

Create job classes that extend `Lightpack\Jobs\Job`:

```php
<?php

namespace App\Jobs;

use Lightpack\Jobs\Job;

class ProcessPayment extends Job
{
    // Set queue name (optional, default is 'default')
    protected $queue = 'payments';
    
    // Set delay (optional, default is 'now')
    protected $delay = '+5 minutes';
    
    // Set retry attempts (optional, default is 1)
    protected $attempts = 3;
    
    // Set retry interval (optional, default is 'now')
    protected $retryAfter = '+30 seconds';
    
    // Required handle method
    public function handle()
    {
        // Process payment logic
        $payment = $this->payload['payment'];
        // ...
    }
}
```

### Dispatching Jobs

Dispatch jobs to the queue:

```php
// Simple dispatch
(new \App\Jobs\ProcessPayment)->dispatch(['payment' => $payment]);

// With fluent API
(new \App\Jobs\ProcessPayment)
    ->setPayload(['payment' => $payment])
    ->dispatch();
```

### Running the Worker

Process jobs using the Lightpack worker:

```bash
php lightpack job:work
```

With queue specification:

```bash
php lightpack job:work --queue=payments
```

## Redis Data Structure

The Redis job engine uses the following Redis data structures:

1. **Hash Storage**: Each job is stored as a Redis hash with all job properties
2. **Sorted Sets**: Jobs are queued in sorted sets ordered by scheduled time
3. **Failed Queue**: Failed jobs are tracked in a separate sorted set

## Performance Considerations

- The Redis job engine is significantly faster than the database engine
- For high-throughput applications, consider using a dedicated Redis instance
- Monitor Redis memory usage when handling large job payloads
- Use appropriate TTL settings to prevent Redis from growing unbounded

## Error Handling

The Redis job engine provides robust error handling:

- Failed jobs are tracked with their exceptions
- Automatic retry with configurable attempts and delay
- Jobs that exhaust retries remain in the failed queue for inspection

## Comparison with Other Engines

| Feature | Redis Engine | Database Engine | Sync Engine |
|---------|-------------|-----------------|-------------|
| Speed | Very Fast | Moderate | Immediate |
| Reliability | High | High | N/A |
| Scalability | High | Moderate | None |
| Persistence | Configurable | Yes | None |
| Distributed | Yes | Yes | No |
| Memory Usage | Low-Moderate | Low | N/A |
