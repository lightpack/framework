<?php

namespace Lightpack\Filters\Auth;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\IFilter;

class WebFilter implements IFilter
{
    public function before(Request $request, array $params = [])
    {
        if(auth()->isGuest()) {
            auth()->redirectLoginUrl();
        }
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
    }
}