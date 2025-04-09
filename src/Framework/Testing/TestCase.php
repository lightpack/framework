<?php

namespace Lightpack\Testing;

use Lightpack\Mail\Mail;
use Lightpack\Http\Response;
use Lightpack\Container\Container;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * @method void beginTransaction()
 * @method void rollbackTransaction()
 */
class TestCase extends BaseTestCase
{
    use AssertionTrait;
    use MailAssertionTrait;

    protected Container $container;
    protected Response $response;
    protected $isJsonRequest = false;
    protected $isMultipartFormdata = false;

    protected function setUp(): void
    {
        parent::setUp();

        require getcwd() . '/bootstrap/init.php';

        $this->container = Container::getInstance();

        Mail::clearSentMails();

        if(method_exists($this, 'beginTransaction')) {
            $this->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        if(method_exists($this, 'rollbackTransaction')) {
            $this->rollbackTransaction();
        }

        parent::tearDown();
    }

    public function request(string $method, string $route, array $params = []): Response
    {
        $method = strtoupper($method);

        // Parse the route to separate path and query
        $parsedUrl = parse_url($route);
        $path = $parsedUrl['path'];
        $queryString = $parsedUrl['query'] ?? '';

        // Parse query parameters
        parse_str($queryString, $queryParams);

        // Set GET/POST params
        if ($method === 'GET') {
            $_GET = array_merge($queryParams, $params);
        } else {
            $_POST = $params;
            $_GET = $queryParams; 
        }

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['REQUEST_URI'] = empty($queryString) ? $path : "{$path}?{$queryString}";
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        if ($this->isJsonRequest) {
            $_SERVER['HTTP_ACCEPT'] = 'application/json';
            $_SERVER['X_LIGHTPACK_RAW_INPUT'] = json_encode($params);
        } else {
            $_SERVER['HTTP_ACCEPT'] = 'text/html';
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

        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
            return;
        }

        $_SERVER['CONTENT_TYPE'] = 'text/html';
    }
}
