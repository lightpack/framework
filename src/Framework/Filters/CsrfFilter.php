<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\IFilter;
use Lightpack\Exceptions\InvalidCsrfTokenException;

class CsrfFilter implements IFilter
{
    public function before(Request $request, array $params = [])
    {
        if(request()->isPost()) {
            if(session()->verifyToken() === false) {
                throw new InvalidCsrfTokenException('CSRF security token is invalid');
            }
        }
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
    } 
}