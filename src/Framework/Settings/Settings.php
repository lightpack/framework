<?php

namespace Framework\Settings;

use Lightpack\Cache\Cache;
use Lightpack\Config\Config;
use Lightpack\Database\DB;

class Settings
{
    protected string $modelType;
    protected int $modelId;
    protected string $cacheKey;
    protected DB $db;
    protected Cache $cache;
    protected Config $config;

    public function __construct(string $modelType, int $modelId, DB $db, Cache $cache, Config $config)
    {
        $this->modelType = $modelType;
        $this->modelId = $modelId;
        $this->db = $db;
        $this->cache = $cache;
        $this->config = $config;
        $this->cacheKey = $this->makeCacheKey();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        if (!array_key_exists($key, $settings)) {
            return $default;
        }
        return $settings[$key]['value'];
    }

    public function set(string $key, mixed $value, $type = null)
    {
        // Type detection if not provided
        if ($type === null) {
            $type = $this->detectType($value);
        }
        $castedValue = $this->serializeValue($value, $type);
        $now = date('Y-m-d H:i:s');
        $this->db->table('settings')->upsert([
            [
                'key' => $key,
                'value' => $castedValue,
                'type' => $type,
                'model_type' => $this->modelType,
                'model_id' => $this->modelId,
                'updated_at' => $now,
            ]
        ], ['value', 'type', 'updated_at']);
        $this->invalidateCache();
    }

    public function all(): array
    {
        if ($this->config->get('settings.cache')) {
            $settings = $this->cache->get($this->cacheKey);
            if ($settings !== null) {
                return $settings;
            }
        }
        $rows = $this->db->table('settings')
            ->where('model_type', $this->modelType)
            ->where('model_id', $this->modelId)
            ->all();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = [
                'value' => $this->castValue($row['value'], $row['type']),
                'type' => $row['type'],
                'updated_at' => $row['updated_at'],
            ];
        }
        if ($this->cache) {
            $this->cache->set(
                $this->cacheKey, 
                $settings, 
                $this->config->get('settings.ttl', 3600)
            );
        }
        return $settings;
    }

    public function forget($key)
    {
        $this->db->table('settings')
            ->where('model_type', $this->modelType)
            ->where('model_id', $this->modelId)
            ->where('key', $key)
            ->delete();
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
        if (is_array($value)) return 'json';
        return 'string';
    }

    protected function makeCacheKey()
    {
        return 'settings:' . $this->modelType . ':' . $this->modelId;
    }

    protected function invalidateCache()
    {
        if ($this->cache) {
            $this->cache->delete($this->cacheKey);
        }
    }
}
