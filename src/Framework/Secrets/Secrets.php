<?php

namespace Lightpack\Secrets;

use Lightpack\Database\DB;

use Lightpack\Utils\Crypto;

class Secrets
{
    protected string $group = 'global';
    protected ?int $ownerId = null;
    protected DB $db;

    protected Crypto $crypto;

    public function __construct(DB $db, Crypto $crypto)
    {
        $this->db = $db;
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
        $query = $this->db->table('secrets')
            ->where('key', $key)
            ->where('group', $this->group);

        if ($this->ownerId) {
            $query->where('owner_id', $this->ownerId);
        }

        $record = $query->one();
        if (!$record) {
            return $default;
        }
        $decrypted = $this->crypto->decrypt($record->value);
        $value = json_decode($decrypted, true);
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
    }

    public function delete(string $key): void
    {
        $this->db->table('secrets')
            ->where('key', $key)
            ->where('group', $this->group)
            ->where('owner_id', $this->ownerId)
            ->delete();
    }

    /**
     * Rotates all secrets in the database from an old encryption key to a new one.
     *
     * @param string $oldKey The previous encryption key.
     * @param string $newKey The new encryption key to use.
     * @param int $batchSize Number of secrets to process per batch (default 500).
     * @return array Summary of rotation: ['success' => int, 'fail' => int]
     * @throws \InvalidArgumentException If either key is missing.
     */
    public function rotateKey(string $oldKey, string $newKey, int $batchSize = 500): array
    {
        if (empty($oldKey) || empty($newKey)) {
            throw new \InvalidArgumentException('Both old and new keys must be provided for key rotation.');
        }

        $oldCrypto = new Crypto($oldKey);
        $newCrypto = new Crypto($newKey);
        $success = 0;
        $fail = 0;

        $this->db->table('secrets')->chunk($batchSize, function ($secrets) use (&$success, &$fail, $oldCrypto, $newCrypto) {
            foreach ($secrets as $secret) {
                $decrypted = $oldCrypto->decrypt($secret->value);
                if ($decrypted === false) {
                    $fail++;
                    continue;
                }
                $reencrypted = $newCrypto->encrypt($decrypted);
                $updated = $this->db->table('secrets')->where('id', $secret->id)->update(['value' => $reencrypted]);
                if ($updated) {
                    $success++;
                } else {
                    $fail++;
                }
            }
        });

        return ['success' => $success, 'fail' => $fail];
    }
}
