<?php

namespace Lightpack\Session;

use Lightpack\Utils\Arr;
use Lightpack\Config\Config;

class Session
{
    private DriverInterface $driver;
    private Arr $arr;
    private string $name;
    private Config $config;

    public function __construct(DriverInterface $driver, Config $config)
    {
        $this->arr = new Arr();
        $this->driver = $driver;
        $this->config = $config;
        $this->name = $this->config->get('session.name', 'lightpack_session');
    }

    /**
     * Check if key uses dot notation
     */
    private function hasDotNotation(string $key): bool
    {
        return str_contains($key, '.');
    }

    /**
     * Get data for dot notation operations
     */
    private function getDataForDotNotation(string $key): array
    {
        $topKey = explode('.', $key)[0];
        $data = [];
        $data[$topKey] = $this->driver->get($topKey) ?? [];
        return [$topKey, $data];
    }

    public function set(string $key, $value)
    {
        if(!$this->hasDotNotation($key)) {
            $this->driver->set($key, $value);
            return;
        }

        [$topKey, $data] = $this->getDataForDotNotation($key);
        $this->arr->set($key, $value, $data);
        $this->driver->set($topKey, $data[$topKey]);
    }

    public function get(?string $key = null, $default = null)
    {
        if($key === null) {
            return $this->driver->get();
        }

        if(!$this->hasDotNotation($key)) {
            return $this->driver->get($key, $default);
        }

        [$topKey, $data] = $this->getDataForDotNotation($key);
        return $this->arr->get($key, $data) ?? $default;
    }

    public function delete(string $key)
    {
        if(!$this->hasDotNotation($key)) {
            $this->driver->delete($key);
            return;
        }

        [$topKey, $data] = $this->getDataForDotNotation($key);
        $this->arr->delete($key, $data);
        $this->driver->set($topKey, $data[$topKey]);
    }

    public function regenerate(): bool
    {
        $this->delete('_token');
        return $this->driver->regenerate();
    }

    public function verifyAgent(): bool
    {
        if ($this->driver->get('_user_agent') == $_SERVER['HTTP_USER_AGENT']) {
            return true;
        }

        return false;
    }

    public function destroy()
    {
        $this->driver->destroy();
    }

    public function has(string $key): bool
    {
        if(!$this->hasDotNotation($key)) {
            return $this->get($key) !== null;
        }

        [$topKey, $data] = $this->getDataForDotNotation($key);
        return $this->arr->has($key, $data);
    }

    public function token(): string
    {
        $token = $this->get('_token');

        if (!$token) {
            $token = bin2hex(openssl_random_pseudo_bytes(8));
            $this->set('_token', $token);
        }

        return $token;
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

    public function hasInvalidAgent(): bool
    {
        return !$this->verifyAgent();
    }

    public function setUserAgent(string $agent)
    {
        $this->driver->set('_user_agent', $agent);
    }
}
