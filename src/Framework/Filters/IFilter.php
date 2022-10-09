<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;

interface IFilter
{
    public function before(Request $request, array $params = []);
    public function after(Request $request, Response $response, array $params = []): Response;
}
