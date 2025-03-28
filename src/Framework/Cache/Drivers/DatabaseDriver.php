<?php

namespace Lightpack\Cache\Drivers;

use Lightpack\Database\DB;
use Lightpack\Cache\DriverInterface;
use Lightpack\Database\Schema\Schema;

class DatabaseDriver implements DriverInterface
{
    private Schema $schema;
    private string $table = 'cache';

    public function __construct(private DB $db)
    {
        $this->db = $db;
        $this->schema = new Schema($db);
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function get(string $key)
    {
        $entry = $this->db
            ->table($this->table)
            ->where('key', $key)
            ->where('expires_at', '>', time())
            ->one();

        if (!$entry) {
            $this->delete($key);
            return null;
        }

        return unserialize($entry->value);
    }

    public function set(string $key, $value, int $lifetime, bool $preserveTtl = false)
    {
        $data = [
            'key' => $key,
            'value' => serialize($value),
        ];

        $entry = $this->db->table($this->table)
            ->where('key', $key)
            ->one();

        if ($entry) {
            if ($preserveTtl) {
                // Keep existing expiry
                $data['expires_at'] = $entry->expires_at;
            } else {
                $data['expires_at'] = $lifetime;
            }

            $this->db->table($this->table)
                ->where('key', $key)
                ->update($data);
        } else {
            // New entry must have expiry
            $data['expires_at'] = $lifetime;
            $this->db->table($this->table)
                ->insert($data);
        }
    }

    public function delete($key)
    {
        $this->db
            ->table($this->table)
            ->where('key', $key)
            ->delete();
    }

    public function flush()
    {
        $this->schema->truncateTable($this->table);
    }
}
