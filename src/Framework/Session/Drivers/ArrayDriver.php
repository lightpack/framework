<?php

namespace Lightpack\Session\Drivers;

use Lightpack\Session\DriverInterface;

class ArrayDriver implements DriverInterface
{
    protected $store = [];

    public function set(string $key, $value)
    {
        $this->store[$key] = $value;
    }

    public function get(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->store;
        }

        return $this->store[$key] ?? $default;
    }

    public function delete(string $key)
    {
        if ($this->store[$key] ?? null) {
            unset($this->store[$key]);
        }
    }

    public function regenerate(): bool
    {
        return true;
    }

    public function destroy()
    {
        $this->store = [];
    }

    public function started(): bool
    {
        return true;
    }
}
