<?php

namespace Lightpack\Secrets;

use Lightpack\Database\DB;
use Lightpack\Cache\Cache;
use Lightpack\Config\Config;
use Lightpack\Utils\Crypto;

class Secrets
{
    protected string $group = 'global';
    protected ?int $ownerId = null;
    protected DB $db;
    protected Cache $cache;
    protected Config $config;
    protected Crypto $crypto;

    public function __construct(DB $db, Cache $cache, Config $config, Crypto $crypto)
    {
        $this->db = $db;
        $this->cache = $cache;
        $this->config = $config;
        $this->crypto = $crypto;
    }

    public function group(string $group): self
    {
        $this->group = $group;
        return $this;
    }

    public function owner(?int $ownerId): self
    {
        $this->ownerId = $ownerId;
        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->cacheKey($key);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        $query = $this->db->table('secrets')
            ->where('key', $key)
            ->where('group', $this->group);

        if($this->ownerId) {
            $query->where('owner_id', $this->ownerId);
        }

        $record = $query->one();
        if (!$record) {
            return $default;
        }
        $decrypted = $this->crypto->decrypt($record->value);
        $value = json_decode($decrypted, true);
        $this->cache->set($cacheKey, $value, 300);
        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $encrypted = $this->crypto->encrypt(json_encode($value));
        $this->db->table('secrets')->upsert([
            'key' => $key,
            'value' => $encrypted,
            'group' => $this->group,
            'owner_id' => $this->ownerId,
        ], ['value']);
        $this->cache->delete($this->cacheKey($key));
    }

    public function delete(string $key): void
    {
        $this->db->table('secrets')
            ->where('key', $key)
            ->where('group', $this->group)
            ->where('owner_id', $this->ownerId)
            ->delete();
        $this->cache->delete($this->cacheKey($key));
    }

    protected function cacheKey(string $key): string
    {
        return 'secrets:' . $this->group . ':' . ($this->ownerId ?? '0') . ':' . $key;
    }
}
