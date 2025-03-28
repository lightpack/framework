<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\IFilter;
use Lightpack\Exceptions\TooManyRequestsException;
use Lightpack\Utils\Limiter;

class LimitFilter implements IFilter
{
    private $limiter;
    
    public function __construct() 
    {
        $this->limiter = new Limiter();
    }
    
    public function before(Request $request, array $params = [])
    {
        $max = $params[0] ?? config('limit.default.max', 60);
        $mins = $params[1] ?? config('limit.default.mins', 1);
        
        $key = $this->resolveKey($request);
        
        if (!$this->limiter->attempt($key, $max, $mins)) {
            throw new TooManyRequestsException('Too many requests. Please try again later.');
        }
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
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
