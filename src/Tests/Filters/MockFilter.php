<?php

use \Lightpack\Http\Request;
use \Lightpack\Http\Response;
use \Lightpack\Filters\IFilter;
use Lightpack\Utils\Url;

class MockFilter implements IFilter
{
    public function before(Request $request, array $params = [])
    {
        if($request->isPost()) {
            $_POST['framework'] = 'Lightpack';
        }

        if($request->isGet()) {
            return new Response(new Url);
        }
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        $response->setBody('hello');
        return $response;
    }
}