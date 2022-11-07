<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\IFilter;

class AuthFilter implements IFilter
{
    public function before(Request $request, array $params = [])
    {
        $type = $params[0] ?? 'web';

        if('web' === $type && auth()->isGuest()) {
            return auth()->recall();
        }

        if('api' === $type && false === auth()->viaToken()->isSuccess()) {
            return response()->setStatus(401)->json([
                'error' => 'Unauthorized',
            ]);
        }
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
    }
}