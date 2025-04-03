<?php

namespace Lightpack\Cache;

class Memcached
{
    private $client;
    
    public function __construct(array $servers = [])
    {
        if (!extension_loaded('memcached')) {
            throw new \RuntimeException('Memcached extension not installed');
        }

        $this->client = new \Memcached();
        
        if (empty($servers)) {
            $servers = [['host' => '127.0.0.1', 'port' => 11211]];
        }

        foreach ($servers as $server) {
            $this->addServer($server['host'], $server['port']);
        }
    }
    
    public function getClient()
    {
        return $this->client;
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
