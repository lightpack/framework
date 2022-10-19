<?php

namespace Lightpack\Testing\Http;

use Lightpack\Utils\Arr;
use Lightpack\Http\Redirect;
use Lightpack\Exceptions\RouteNotFoundException;

trait AssertionTrait
{
    public function assertResponseStatus(int $code)
    {
        $this->assertEquals($code, $this->response->getStatus());
    }

    public function assertResponseBody(string $body)
    {
        $this->assertEquals($body, $this->response->getBody());
    }

    public function assertResponseHasValidJson()
    {
        $this->assertJson($this->response->getBody());
    }

    public function assertResponseJson(array $json)
    {
        $this->assertEquals($json, json_decode($this->response->getBody(), true));
    }

    public function assertResponseJsonHasKey(string $key)
    {
        $this->assertTrue((new Arr)->has($key, $this->getArrayResponse()));
    }

    public function assertResponseJsonKeyValue(string $key, $value)
    {
        $this->assertSame($value, (new Arr)->get($key, $this->getArrayResponse()));
    }

    public function assertResponseHasHeader(string $header)
    {
        $this->assertTrue($this->response->hasHeader($header));
    }

    public function assertResponseIsRedirect()
    {
        $this->assertInstanceof(Redirect::class, $this->response);
    }

    public function assertRedirectUrl(string $url)
    {
        $this->assertEquals($url, $this->response->getRedirectUrl());
    }

    public function assertResponseHeaderEquals(string $header, string $value)
    {
        $this->assertEquals($value, $this->response->getHeader($header));
    }

    public function assertRouteNotFound(string $route)
    {
        $this->expectException(RouteNotFoundException::class);

        $this->request('GET', $route);
    }
}
