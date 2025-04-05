<?php

namespace Lightpack\Session\Drivers;

use Lightpack\Session\DriverInterface;
use Lightpack\Utils\Crypto;

class EncryptedDriver implements DriverInterface
{
    public function __construct(
        private DriverInterface $driver,
        private Crypto $crypto
    ) {}

    public function start()
    {
        return $this->driver->start();
    }

    public function regenerate(): bool
    {
        return $this->driver->regenerate();
    }

    public function destroy()
    {
        return $this->driver->destroy();
    }

    public function started(): bool
    {
        return $this->driver->started();
    }

    public function get(?string $key = null, $default = null)
    {
        $data = $this->driver->get($key, $default);
        
        if ($data === null) {
            return $default;
        }

        if ($key !== null) {
            return $this->decryptValue($data);
        }

        // Decrypt all session data
        return array_map(
            fn($value) => $this->decryptValue($value),
            $data
        );
    }

    public function set(string $key, $value)
    {
        $this->driver->set($key, $this->encryptValue($value));
    }

    public function delete(string $key)
    {
        $this->driver->delete($key);
    }

    private function encryptValue($value): string
    {
        return $this->crypto->encrypt(serialize($value));
    }

    private function decryptValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        try {
            $decrypted = $this->crypto->decrypt($value);
            return $decrypted ? unserialize($decrypted) : $value;
        } catch (\Exception $e) {
            // If decryption fails, return original value
            return $value;
        }
    }
}
