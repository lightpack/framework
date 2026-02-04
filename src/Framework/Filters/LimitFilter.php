<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\FilterInterface;
use Lightpack\Utils\Limiter;
use Lightpack\Exceptions\TooManyRequestsException;

class LimitFilter implements FilterInterface
{
    private Limiter $limiter;
    private $max; // requests
    private $mins; // time window
    
    public function __construct() 
    {
        $this->limiter = new Limiter();
    }
    
    public function before(Request $request, array $params = [])
    {
        $this->max = $params[0] ?? config('limit.default.max', 60);
        $this->mins = $params[1] ?? config('limit.default.mins', 1);
        
        $key = $this->resolveKey($request);
        
        // Check rate limit
        if (!$this->limiter->attempt($key, $this->max, $this->mins * 60)) {
            $hits = (int) ($this->limiter->getHits($key) ?? 0);
            
            $exception = new TooManyRequestsException(
                "Too many requests. Please try again in {$this->mins} minute(s).",
                429
            );

            $exception->setheaders($this->getRateLimitHeaders($hits));

            throw $exception;
        }
    }
    
    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
    }
    
    private function getRateLimitHeaders($hits): array
    {
        $headers = [];
        $remaining = max(0, $this->max - $hits);
        $reset = time() + ($this->mins * 60);
        
        $headers['X-RateLimit-Limit'] = (string) $this->max;
        $headers['X-RateLimit-Remaining'] = (string) $remaining;
        $headers['X-RateLimit-Reset'] = (string) $reset;
        
        if ($remaining == 0) {
            $headers['Retry-After'] = (string) ($this->mins * 60);
        }

        return $headers;
    }

    private function resolveKey(Request $request): string 
    {
        $pathHash = md5($request->path());
        
        // Use user ID if authenticated
        if ($user = auth()->user()) {
            return 'user:' . $user->id . ':' . $pathHash;
        }
        
        // Otherwise use IP address
        return 'ip:' . $request->ip() . ':' . $pathHash;
    }
}
