<?php

namespace Lightpack\Limit;

class Limiter 
{
    private $store;

    public function __construct(Storage $store) 
    {
        $this->store = $store;
        
        // Random cleanup of expired records
        if (rand(1, 100) <= config('limit.cleanup.probability', 5)) {
            $this->store->deleteExpired();
        }
    }

    public function attempt(string $key, int $max, int $mins): bool 
    {
        if ($this->tooManyAttempts($key, $max)) {
            return false;
        }

        $this->hit($key, $mins);
        return true;
    }

    public function remaining(string $key, int $max): int 
    {
        $hits = $this->store->getHits($key);
        return max(0, $max - $hits);
    }

    public function clear(string $key): void 
    {
        $this->store->delete($key);
    }

    private function tooManyAttempts(string $key, int $max): bool 
    {
        return $this->store->getHits($key) >= $max;
    }

    private function hit(string $key, int $mins): void 
    {
        if ($this->store->exists($key)) {
            $this->store->increment($key);
        } else {
            $this->store->create($key, $mins);
        }
    }
}
