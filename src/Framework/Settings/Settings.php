<?php

namespace Lightpack\Settings;

use Lightpack\Cache\Cache;
use Lightpack\Config\Config;
use Lightpack\Database\DB;

class Settings
{
    /**
     * The logical group or namespace for the settings.
     * E.g. 'global', 'users', 'orgs', etc.
     * @var string
     */
    protected string $group = 'global';

    /**
     * The owner id within the group, or null for global/group-level settings.
     * @var int|null
     */
    protected ?int $ownerId = null;

    protected DB $db;
    protected Cache $cache;
    protected Config $config;

    public function __construct(DB $db, Cache $cache, Config $config)
    {
        $this->db = $db;
        $this->cache = $cache;
        $this->config = $config;
    }

    /**
     * Set the group and owner for this settings instance (fluent API).
     *
     * @param string $group
     * @param int|null $ownerId
     * @return $this
     */
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

    /**
     * Get a settings value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed The value if found, or $default if not set.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        if (!array_key_exists($key, $settings)) {
            return $default;
        }
        return $settings[$key]['value'];
    }

    /**
     * Set a settings value by key.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $type Optional type hint
     * @return void
     */
    /**
     * Set a settings value for a given key.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $type Optional type hint
     * @throws \InvalidArgumentException If $value is null. Use delete() to remove a key instead.
     * @return void
     */
    public function set(string $key, mixed $value, $type = null)
    {
        // Disallow setting null values
        if ($value === null) {
            throw new \InvalidArgumentException('Cannot set null value. Use delete() to remove a key.');
        }
        // Type detection if not provided
        if ($type === null) {
            $type = $this->detectType($value);
        }
        $castedValue = $this->serializeValue($value, $type);
        $now = date('Y-m-d H:i:s');
        $this->db->table('settings')->upsert([
            [
                'key' => $key,
                'key_type' => $type,
                'value' => $castedValue,
                'group' => $this->group,
                'owner_id' => $this->ownerId,
                'updated_at' => $now,
            ]
        ], ['value', 'key_type', 'updated_at']);
        $this->invalidateCache();
    }

    /**
     * Get all settings for this group/owner.
     *
     * @return array<string, array{value:mixed, key_type:string|null, updated_at:string}>
     *         Associative array keyed by setting key.
     */
    public function all(): array
    {
        if ($this->config->get('settings.cache')) {
            $settings = $this->cache->get($this->makeCacheKey());
            if ($settings !== null) {
                return $settings;
            }
        }
        $query = $this->db->table('settings')
            ->where('group', $this->group);
        if ($this->ownerId === null) {
            $query->whereNull('owner_id');
        } else {
            $query->where('owner_id', $this->ownerId);
        }
        $rows = $query->all();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->key] = [
                'value' => $this->castValue($row->value, $row->key_type),
                'key_type' => $row->key_type,
                'group' => $row->group,
                'owner_id' => $row->owner_id,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];
        }

        if ($this->cache) {
            $this->cache->set(
                $this->makeCacheKey(),
                $settings,
                $this->config->get('settings.ttl')
            );
        }
        return $settings;
    }

    /**
     * Delete a settings value by key.
     *
     * @param string $key
     * @return void
     */
    public function forget($key)
    {
        $query = $this->db->table('settings')
            ->where('group', $this->group)
            ->where('key', $key);
        if ($this->ownerId === null) {
            $query->whereNull('owner_id');
        } else {
            $query->where('owner_id', $this->ownerId);
        }
        $query->delete();
        $this->invalidateCache();
    }

    protected function castValue($value, $type)
    {
        switch ($type) {
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'bool':
                return $value == '1' || $value === true || $value === 1;
            case 'json':
            case 'array':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    protected function serializeValue($value, $type)
    {
        switch ($type) {
            case 'json':
            case 'array':
                return json_encode($value);
            case 'bool':
                return $value ? '1' : '0';
            default:
                return (string) $value;
        }
    }

    protected function detectType($value)
    {
        if (is_int($value)) return 'int';
        if (is_float($value)) return 'float';
        if (is_bool($value)) return 'bool';
        if (is_array($value)) return 'array';
        return 'string';
    }

    protected function makeCacheKey(): string
    {
        return 'settings:' . $this->group . ':' . ($this->ownerId ?? 'null');
    }

    protected function invalidateCache()
    {
        if ($this->cache) {
            $this->cache->delete($this->makeCacheKey());
        }
    }
}
