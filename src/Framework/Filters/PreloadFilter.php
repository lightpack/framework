<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;

class PreloadFilter implements IFilter
{
    public function before(Request $request, array $params = [])
    {
        // ...
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        foreach (asset()->getPreloadHeaders() as [$name, $value]) {
            $response->setHeader($name, $value);
        }

        return $response;
    }
}
