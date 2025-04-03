<?php

namespace Lightpack\Tests\Cache;

/**
 * Mock class for PHP's Memcached when extension is not installed
 */
interface MockMemcached 
{
    public function addServer(string $host, int $port): bool;
    public function addServers(array $servers): bool;
    public function get(string $key);
    public function set(string $key, $value, int $expiration): bool;
    public function delete(string $key): bool;
    public function flush(): bool;
    public function getStats(): array;
    public function getVersion(): array;
}
