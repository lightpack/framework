<?php

namespace Lightpack\Session\Drivers;

use Lightpack\Session\DriverInterface;
use Lightpack\Cache\Cache;
use Lightpack\Http\Cookie;
use Lightpack\Config\Config;

class CacheDriver implements DriverInterface
{
    private Cache $cache;
    private Cookie $cookie;
    private string $sessionId = '';
    private bool $started = false;
    private array $data = [];
    private string $prefix = 'session:';
    private Config $config;

    public function __construct(Cache $cache, Cookie $cookie, Config $config) 
    {
        $this->cache = $cache;
        $this->cookie = $cookie;
        $this->config = $config;
    }

    public function start()
    {
        $this->started = true;

        // Set session name before using it
        $name = $this->config->get('session.name', 'lightpack_session');
        session_name($name);

        // Get or generate session ID
        $this->sessionId = $this->cookie->get(session_name()) ?: $this->generateSessionId();
        
        // Set cookie with same lifetime as session
        $lifetime = (int) $this->config->get('session.lifetime', 7200);
        $this->cookie->set(
            session_name(), 
            $this->sessionId,
            time() + $lifetime,
            [
                'path' => '/',
                'domain' => '',
                'secure' => $this->config->get('session.https'),
                'http_only' => $this->config->get('session.http_only'),
                'same_site' => $this->config->get('session.same_site'),
            ]
        );

        // Load session data with TTL check
        $data = $this->cache->get($this->getCacheKey());
        
        // If no data or TTL expired, start fresh
        if ($data === null) {
            $this->data = [];
        } else {
            $this->data = $data;
        }
    }

    public function set(string $key, $value)
    {
        $this->data[$key] = $value;
        $this->save();
    }

    public function get(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key] ?? $default;
    }

    public function delete(string $key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
            $this->save();
        }
    }

    public function regenerate(): bool
    {
        // Delete old session
        $this->cache->delete($this->getCacheKey());
        
        // Generate new session ID
        $this->sessionId = $this->generateSessionId();
        
        // Set new cookie
        $this->cookie->set(session_name(), $this->sessionId);

        // Save current data with new ID
        $this->save();

        return true;
    }

    public function destroy()
    {
        if ($this->started && $this->sessionId !== '') {
            $this->cache->delete($this->getCacheKey());
            $this->cookie->delete(session_name());
        }
        
        $this->data = [];
        $this->started = false;
        $this->sessionId = '';
    }

    public function started(): bool
    {
        return $this->started;
    }

    private function generateSessionId(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function getCacheKey(): string
    {
        return $this->prefix . $this->sessionId;
    }

    private function save(): void
    {
        if (!$this->started) {
            return;
        }
        
        // Use session lifetime from config for cache TTL
        $lifetime = (int) $this->config->get('session.lifetime', 7200);
        $this->cache->set($this->getCacheKey(), $this->data, $lifetime);
    }
}
