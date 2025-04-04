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

    /**
     * Configure session cookie and timeout settings
     */
    public function configureCookie()
    {
        // Basic security settings
        ini_set('session.use_only_cookies', TRUE);
        ini_set('session.use_trans_sid', FALSE);
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        
        // Session lifetime from config
        $lifetime = (int) $this->config->get('session.lifetime', 7200);
        ini_set('session.gc_maxlifetime', $lifetime);
        ini_set('session.cookie_lifetime', $lifetime);

        // Secure cookies in HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', '1');
        }

        // SameSite setting from config
        $sameSite = strtolower($this->config->get('session.same_site', 'lax'));
        if (!in_array($sameSite, ['lax', 'strict', 'none'])) {
            $sameSite = 'lax';
        }
        ini_set('session.cookie_samesite', $sameSite);

        session_name($this->name);
    }

    /**
     * Check if session has expired
     */
    public function hasExpired(): bool
    {
        return !$this->driver->started() || $this->driver->get() === null;
    }

    /**
     * Verify CSRF token with proper session expiry handling
     */
    public function verifyToken(): bool
    {
        // First check session expiry
        if ($this->hasExpired()) {
            throw new \Lightpack\Exceptions\SessionExpiredException(
                'Your session has expired. Please refresh the page and try again.'
            );
        }

        if (!$this->driver->started()) {
            return false;
        }

        $token = null;

        // Check headers first (for AJAX/API requests)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        // Check POST data
        else if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_token'] ?? null;
        }
        // Check JSON body
        else if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) {
            $rawBody = file_get_contents('php://input');
            if ($rawBody) {
                $jsonData = json_decode($rawBody, true);
                $token = $jsonData['_token'] ?? null;
            }
        }

        if (!$token) {
            return false;
        }

        return $this->get('_token') === $token;
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

    public function hasInvalidToken(): bool
    {
        return !$this->verifyToken();
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
