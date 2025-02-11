<?php

namespace Lightpack\Session;

class Session
{
    private DriverInterface $driver;

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
}
