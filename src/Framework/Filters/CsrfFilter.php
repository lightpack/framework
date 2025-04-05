<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\IFilter;
use Lightpack\Exceptions\InvalidCsrfTokenException;
use Lightpack\Exceptions\SessionExpiredException;

class CsrfFilter implements IFilter
{
    private array $protectedMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function before(Request $request, array $params = [])
    {
        if(in_array($request->method(), $this->protectedMethods)) {
            if(!session()->has('_token')) {
                throw new SessionExpiredException('Your session has expired. Please refresh the page and try again.');
            }

            if($request->csrfToken() !== session()->get('_token')) {
                throw new InvalidCsrfTokenException('CSRF security token is invalid');
            }
        }
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
    } 
}