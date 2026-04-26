<?php

namespace Lightpack\Session\Drivers;

use Lightpack\Session\DriverInterface;
use RuntimeException;

class DefaultDriver implements DriverInterface
{
    private $config;
    private $started = false;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function start()
    {
        if ($this->started) {
            return;
        }

        $lifetime = (int) $this->config->get('session.lifetime', 7200);
        $name = $this->config->get('session.name', 'lightpack_session');

        // Configure session save path
        $savePath = $this->config->get('session.path') ?: sys_get_temp_dir() . '/lightpack_sessions';
        if (!is_dir($savePath)) {
            @mkdir($savePath, 0755, true);
        }
        if (is_dir($savePath)) {
            ini_set('session.save_path', $savePath);
        }

        // Security settings
        ini_set('session.use_only_cookies', TRUE);
        ini_set('session.use_trans_sid', FALSE);
        ini_set('session.use_strict_mode', '1');

        // Lifetime settings
        ini_set('session.gc_maxlifetime', $lifetime); // Server-side: delete after X seconds of inactivity
        ini_set('session.cookie_lifetime', '0');      // Client-side: session cookie (expires on browser close)

        // Garbage collection settings
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');

        // Cookie settings
        ini_set('session.cookie_httponly', $this->config->get('session.http_only'));
        ini_set('session.cookie_secure', $this->config->get('session.https'));
        ini_set('session.cookie_samesite', $this->config->get('session.same_site'));
        ini_set('session.cookie_path', $this->config->get('session.cookie_path', '/'));
        ini_set('session.cookie_domain', $this->config->get('session.cookie_domain', ''));

        if (headers_sent($file, $line)) {
            throw new RuntimeException(
                sprintf('Session cannot be started: headers already sent by %s:%d', $file, $line)
            );
        }

        // Validate session ID from cookie to prevent fixation attacks
        if (isset($_COOKIE[$name])) {
            $sessionId = $_COOKIE[$name];
            if (!preg_match('/^[a-zA-Z0-9,-]{22,256}$/', $sessionId)) {
                unset($_COOKIE[$name]);
            }
        }

        session_name($name);
        session_start();
        $this->started = true;
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
