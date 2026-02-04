<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Filters\FilterInterface;

class SignedFilter implements FilterInterface
{
    public function before(Request $request, array $params = [])
    {
        $request->validateUrlSignature();
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
    }
}
