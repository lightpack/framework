<?php

namespace Lightpack\Session\Drivers;

use Lightpack\Session\DriverInterface;
use Lightpack\Cache\Cache;
use Lightpack\Http\Cookie;
use RuntimeException;

class CacheDriver implements DriverInterface
{
    private Cache $cache;
    private Cookie $cookie;
    private string $sessionId;
    private bool $started = false;
    private array $data = [];

    public function __construct(Cache $cache, Cookie $cookie) 
    {
        $this->cache = $cache;
        $this->cookie = $cookie;
        $this->sessionId = '';
    }

    public function start()
    {
        $this->started = true;

        // Get or generate session ID
        $this->sessionId = $this->cookie->get(session_name()) ?? $this->generateSessionId();
        
        // Set cookie
        $this->cookie->set(session_name(), $this->sessionId);

        // Load session data
        $this->data = $this->cache->get($this->sessionId) ?? [];
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
        $this->cache->delete($this->sessionId);
        
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
        $this->cache->delete($this->sessionId);
        $this->cookie->delete(session_name());
        $this->data = [];
        $this->started = false;
        $this->sessionId = ''; // Reset to initial state
    }

    public function started(): bool
    {
        return $this->started;
    }

    private function generateSessionId(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function save(): void
    {
        if ($this->sessionId === '') {
            return; // Don't save if no valid session ID
        }
        $this->cache->set($this->sessionId, $this->data, 86400); // 24 hours TTL
    }
}
