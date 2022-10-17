<?php

namespace Lightpack\Session;

class Session
{
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function set(string $key, $value)
    {
        $this->driver->set($key, $value);
    }

    public function get(string $key = null, $default = null)
    {
        return $this->driver->get($key, $default);
    }

    public function delete(string $key)
    {
        $this->driver->delete($key);
    }

    public function regenerate(): bool
    {
        return $this->driver->regenerate();
    }

    public function verifyAgent(): bool
    {
        return $this->driver->verifyAgent();
    }

    public function destroy()
    {
        $this->driver->destroy();
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function token()
    {
        $token = bin2hex(openssl_random_pseudo_bytes(8));

        $this->set('_token', $token);

        return $token;
    }

    public function verifyToken(): bool
    {
        if (!$this->driver->started() || !isset($_POST['_token'])) {
            return false;
        }

        if ($this->get('_token') !== $_POST['_token']) {
            return false;
        }

        return true;
    }

    public function flash(string $key, $value = null)
    {
        if ($value) {
            $this->driver->set($key, $value);
            return;
        }

        $flash = $this->driver->get($key);
        $this->driver->delete($key);
        return $flash;
    }
}
