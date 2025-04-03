<?php

namespace Lightpack\Cache;

class Memcached
{
    private $client;
    
    public function __construct(array $servers = [['127.0.0.1', 11211]])
    {
        if (!extension_loaded('memcached')) {
            throw new \RuntimeException('Memcached extension not installed');
        }

        $this->client = new \Memcached();
        $this->addServers($servers);
    }
    
    public function getClient()
    {
        return $this->client;
    }
    
    public function addServers(array $servers): bool
    {
        return $this->client->addServers($servers);
    }
    
    public function addServer(string $host, int $port): bool
    {
        return $this->client->addServer($host, $port);
    }
    
    public function getStats(): array
    {
        return $this->client->getStats();
    }
    
    public function getVersion(): array
    {
        return $this->client->getVersion();
    }
}
