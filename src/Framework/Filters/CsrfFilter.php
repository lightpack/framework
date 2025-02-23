<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\IFilter;
use Lightpack\Exceptions\InvalidCsrfTokenException;

class CsrfFilter implements IFilter
{
    private array $protectedMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function before(Request $request, array $params = [])
    {
        if(in_array($request->method(), $this->protectedMethods)) {
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