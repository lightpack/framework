<?php

namespace Lightpack\Testing\Http;

use Lightpack\Http\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use AssertionTrait;

    /** @var \Lightpack\Container\Container */
    protected $container;

    /** @var \Lightpack\Http\Response */
    protected $response;

    protected $isJsonRequest = false;
    
    protected $isMultipartFormdata = false;

    public function request(string $method, string $route, array $params = []): Response
    {
        $method = strtoupper($method);
        $method === 'GET' ? ($_GET = $params) : ($_POST = $params);

        $_SERVER['REQUEST_URI'] = $route;
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTP_USER_AGENT'] = 'fake-agent';

        if ($this->isJsonRequest) {
            $_SERVER['X_LIGHTPACK_RAW_INPUT'] = json_encode($params);
        }

        if ($this->isMultipartFormdata) {
            $_SERVER['X_LIGHTPACK_TEST_UPLOAD'] = true;
        }

        $this->setRequestContentType();
        $this->registerAppRequest();

        $this->container->get('request')->setMethod($method);

        return $this->response = \Lightpack\App::run();
    }

    public function requestJson(string $method, string $route, array $params = []): Response
    {
        $this->isJsonRequest = true;

        return $this->request($method, $route, $params);
    }

    protected function registerAppRequest()
    {
        $this->container->register('request', function () {
            return new \Lightpack\Http\Request('/');
        });

        $this->container->alias(\Lightpack\Http\Request::class, 'request');
    }

    public function withHeaders(array $headers): self
    {
        foreach ($headers as $header => $value) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $header))] = $value;
        }

        return $this;
    }

    public function withCookies(array $cookies): self
    {
        foreach ($cookies as $cookie => $value) {
            $_COOKIE[$cookie] = $value;
        }

        return $this;
    }

    public function withSession(array $session): self
    {
        foreach ($session as $key => $value) {
            session()->set($key, $value);
        }

        return $this;
    }

    public function withFiles(array $files): self
    {
        $this->isMultipartFormdata = true;

        foreach ($files as $file => $value) {
            $_FILES[$file] = $value;
        }

        return $this;
    }

    public function getArrayResponse(): array
    {
        if (!$this->isJsonRequest) {
            return [];
        }

        return json_decode($this->response->getBody(), true);
    }

    protected function setRequestContentType()
    {
        if ($this->isMultipartFormdata) {
            $_SERVER['CONTENT_TYPE'] = 'multipart/form-data';
            return;
        }

        if ($this->isJsonRequest) {
            $_SERVER['CONTENT_TYPE'] = 'application/json';
            return;
        }

        $_SERVER['CONTENT_TYPE'] = 'text/html';
    }
}
