<?php

namespace Lightpack\Session\Drivers;

use Lightpack\Session\DriverInterface;
use RuntimeException;

class DefaultDriver implements DriverInterface
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function start()
    {
        if ($this->started()) {
            return;
        }

        $name = $this->config->get('session.name', 'lightpack_session');

        if (headers_sent($file, $line)) {
            throw new RuntimeException(
                sprintf('Session cannot be started: headers already sent by %s:%d', $file, $line)
            );
        }

        // Save path (prevents shared hosting collisions)
        $savePath = $this->config->get('session.path');
        if ($savePath && !is_dir($savePath)) {
            @mkdir($savePath, 0755, true);
        }
        if ($savePath && is_dir($savePath)) {
            ini_set('session.save_path', $savePath);
        }

        // Security baseline
        ini_set('session.use_only_cookies', TRUE);
        ini_set('session.use_trans_sid', FALSE);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', $this->config->get('session.http_only'));
        ini_set('session.cookie_secure', $this->config->get('session.https'));
        ini_set('session.cookie_samesite', $this->config->get('session.same_site'));

        // Lifetime: session cookie (client) + inactivity timeout (server)
        ini_set('session.cookie_lifetime', '0');
        ini_set('session.gc_maxlifetime', (int) $this->config->get('session.lifetime', 7200));

        session_name($name);
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
        unset($_SESSION[$key]);
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
