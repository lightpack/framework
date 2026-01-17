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
            $homeRoute = config()->get('auth.routes.home', 'dashboard');
            return redirect()->route($homeRoute);
        }
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
    }
}