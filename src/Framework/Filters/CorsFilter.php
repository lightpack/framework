<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\FilterInterface;

class CorsFilter implements FilterInterface
{
    public function before(Request $request, array $params = [])
    {
        if (request()->method() === 'OPTIONS') {
            return response()
                ->setStatus(204)
                ->setMessage('No Content')
                ->setType('text/plain')
                ->setHeaders(config('cors.headers'));
        }
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response->setHeaders(config('cors.headers'));
    }
}
