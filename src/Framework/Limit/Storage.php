<?php

namespace Lightpack\Limit;

interface Storage 
{
    public function exists(string $key): bool;
    public function create(string $key, int $minutes): void;
    public function increment(string $key): void;
    public function getHits(string $key): int;
    public function delete(string $key): void;
    public function deleteExpired(): void;
}
