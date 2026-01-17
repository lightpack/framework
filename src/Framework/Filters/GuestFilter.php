<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\IFilter;

class GuestFilter implements IFilter
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