<?php

namespace Lightpack\Testing;

use Lightpack\Http\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;

class HttpTestCase extends BaseTestCase
{
    /** @var \Lightpack\Container\Container */
    protected $container;

    protected $isJsonRequest = false;
    protected $isMultipartFormdata = false;

    public function request(string $method, string $route, array $params = []): Response
    {
        $method = strtoupper($method);
        $method === 'GET' ? ($_GET = $params) : ($_POST = $params);

        $_SERVER['REQUEST_URI'] = $route;
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTP_USER_AGENT'] = 'fake-agent';

        if($this->isJsonRequest) {
            $_SERVER['CONTENT_TYPE'] = 'application/json';
            $_SERVER['X_LIGHTPACK_RAW_INPUT'] = json_encode($params);
        }

        if($this->isMultipartFormdata) {
            $_SERVER['CONTENT_TYPE'] = 'multipart/form-data';
        }

        return $this->dispatchAppRequest($route);
    }

    public function get(string $route, array $params = []): Response
    {
        return $this->request('GET', $route, $params);
    }

    public function post(string $route, array $params = []): Response
    {
        return $this->request('POST', $route, $params);
    }

    public function put(string $route, array $params = []): Response
    {
        return $this->request('PUT', $route, $params);
    }

    public function patch(string $route, array $params = []): Response
    {
        return $this->request('PATCH', $route, $params);
    }

    public function delete(string $route, array $params = []): Response
    {
        return $this->request('DELETE', $route, $params);
    }

    public function makeJson(): HttpTestCase
    {
        $this->isJsonRequest = true;

        return $this;
    }

    public function makeFile(): HttpTestCase
    {
        $this->isJsonRequest = false;
        $this->isMultipartFormdata = true;

        return $this;
    }

    protected function dispatchAppRequest(string $route): Response
    {
        $this->registerAppRequest();

        $this->container->get('router')->parse($route);

        \Lightpack\App::run($this->container);

        return $this->container->get('response');
    }

    protected function registerAppRequest()
    {
        $this->container->register('request', function () {
            return new \Lightpack\Http\Request('/');
        });
    }
}
