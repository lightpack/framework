<?php

namespace Lightpack\Testing;

use Lightpack\Http\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;

class HttpTestCase extends BaseTestCase
{
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

        return $this->response = $this->dispatchAppRequest($route);
    }

    public function requestJson(string $method, string $route, array $params = []): Response
    {
        $this->isJsonRequest = true;

        return $this->request($method, $route, $params);
    }

    public function makeFile(): HttpTestCase
    {
        $_SERVER['X_LIGHTPACK_TEST_UPLOAD'] = true;

        // $this->isJsonRequest = false;
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

    public function assertResponseCode(int $code)
    {
        $this->assertEquals($code, $this->response->getCode());
    }

    public function assertResponseBody(string $body)
    {
        $this->assertEquals($body, $this->response->getBody());
    }

    public function assertResponseHasValidJson()
    {
        $this->assertJson($this->response->getBody());
    }

    public function assertResponseJsonEquals(array $json)
    {
        $this->assertEquals($json, json_decode($this->response->getBody(), true));
    }

    public function assertResponseHasHeader(string $header)
    {
        $this->assertTrue($this->response->hasHeader($header));
    }

    public function assertResponseHeaderEquals(string $header, string $value)
    {
        $this->assertEquals($value, $this->response->getHeader($header));
    }

    protected function setRequestContentType()
    {
        if($this->isMultipartFormdata) {
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
