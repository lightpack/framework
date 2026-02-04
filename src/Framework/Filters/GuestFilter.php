<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\FilterInterface;

class GuestFilter implements FilterInterface
{
    public function before(Request $request, array $params = [])
    {
        if (auth()->isLoggedIn()) {
            $authenticatedRoute = config('auth.routes.authenticated', 'dashboard');
            return redirect()->route($authenticatedRoute);
        }
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
    }
}