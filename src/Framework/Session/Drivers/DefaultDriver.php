<?php

namespace Lightpack\Session\Drivers;

use Lightpack\Session\DriverInterface;
use RuntimeException;

class DefaultDriver implements DriverInterface
{
    public function __construct()
    {
        if ($this->started()) {
            return;
        }

        if (headers_sent($file, $line)) {
            throw new RuntimeException(
                sprintf('Session cannot be started: headers already sent by %s:%d', $file, $line)
            );
        }

        session_start();
    }

    public function set(string $key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function get(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $_SESSION;
        }

        return $_SESSION[$key] ?? $default;
    }

    public function delete(string $key)
    {
        if ($_SESSION[$key] ?? null) {
            unset($_SESSION[$key]);
        }
    }

    public function regenerate(): bool
    {
        return session_regenerate_id(true);
    }

    public function destroy()
    {
        $_SESSION = [];
        session_destroy();
    }

    public function started(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }
}
