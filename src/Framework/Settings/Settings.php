<?php

namespace Framework\Settings;

use Lightpack\Database\DB;
use Lightpack\Cache\Cache;

class Settings
{
    protected $modelType;
    protected $modelId;
    protected $cache;
    protected $cacheKey;
    protected $db;

    public function __construct($modelType, $modelId, ?Cache $cache = null)
    {
        $this->modelType = $modelType;
        $this->modelId = $modelId;
        $this->cache = $cache;
        $this->cacheKey = $this->makeCacheKey();
        $this->db = app('db');
    }

    public static function for($modelType, $modelId = null)
    {
        return new static($modelType, $modelId, app('cache'));
    }

    public function get($key, $default = null)
    {
        $settings = $this->all();
        if (!array_key_exists($key, $settings)) {
            return $default;
        }
        return $settings[$key]['value'];
    }

    public function set($key, $value, $type = null)
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

    public function all()
    {
        if ($this->cache) {
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
            $this->cache->set($this->cacheKey, $settings, 3600); // 1 hour cache
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
