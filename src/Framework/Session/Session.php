<?php

namespace Lightpack\Session;

use Lightpack\Utils\Arr;

class Session
{
    private DriverInterface $driver;
    private Arr $arr;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
        $this->arr = new Arr();
    }

    public function set(string $key, $value)
    {
        if(!str_contains($key, '.')) {
            $this->driver->set($key, $value);
            return;
        }

        $topKey = explode('.', $key)[0];
        $data = [];
        $data[$topKey] = $this->driver->get($topKey) ?? [];
        
        $this->arr->set($key, $value, $data);
        
        // Only store the top-level key that was modified
        $this->driver->set($topKey, $data[$topKey]);
    }

    public function get(string $key = null, $default = null)
    {
        if($key === null) {
            return $this->driver->get();
        }

        if(!str_contains($key, '.')) {
            return $this->driver->get($key, $default);
        }

        $data = [];
        $topKey = explode('.', $key)[0];
        $data[$topKey] = $this->driver->get($topKey) ?? [];
        
        return $this->arr->get($key, $data, $default);
    }

    public function delete(string $key)
    {
        if(!str_contains($key, '.')) {
            $this->driver->delete($key);
            return;
        }

        $topKey = explode('.', $key)[0];
        $data = [];
        $data[$topKey] = $this->driver->get($topKey) ?? [];
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
        return $this->driver->verifyAgent();
    }

    public function destroy()
    {
        $this->driver->destroy();
    }

    public function has(string $key): bool
    {
        if(!str_contains($key, '.')) {
            return $this->get($key) !== null;
        }

        $data = [];
        $topKey = explode('.', $key)[0];
        $data[$topKey] = $this->driver->get($topKey) ?? [];
        
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
