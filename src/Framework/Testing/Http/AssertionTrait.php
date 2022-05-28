<?php

namespace Lightpack\Testing\Http;

use Lightpack\Utils\Arr;
use Lightpack\Exceptions\RouteNotFoundException;

trait AssertionTrait
{
    public function assertResponseStatus(int $code)
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

    public function assertResponseJson(array $json)
    {
        $this->assertEquals($json, json_decode($this->response->getBody(), true));
    }

    public function assertResponseJsonHasKey(string $key)
    {
        $this->assertTrue(Arr::has($key, $this->getArrayResponse()));
    }

    public function assertResponseJsonKeyValue(string $key, $value)
    {
        $this->assertSame($value === Arr::get($key, $this->getResponseAray()));
    }

    public function assertResponseHasHeader(string $header)
    {
        $this->assertTrue($this->response->hasHeader($header));
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
