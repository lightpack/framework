<?php

namespace Lightpack\Testing;

use Lightpack\Utils\Arr;
use Lightpack\Http\Redirect;
use Lightpack\Exceptions\RouteNotFoundException;

trait AssertionTrait
{
    public function assertResponseStatus(int $code): self
    {
        $this->assertEquals($code, $this->response->getStatus());

        return $this;
    }

    public function assertResponseBody(string $body): self
    {
        $this->assertEquals($body, $this->response->getBody());

        return $this;
    }

    public function assertResponseHasValidJson(): self
    {
        $this->assertJson($this->response->getBody());

        return $this;
    }

    public function assertResponseJson(array $json): self
    {
        $this->assertEquals($json, json_decode($this->response->getBody(), true));

        return $this;
    }

    public function assertResponseJsonHasKey(string $key): self
    {
        $this->assertTrue((new Arr)->has($key, $this->getArrayResponse()));

        return $this;
    }

    public function assertResponseJsonKeyValue(string $key, $value): self
    {
        $this->assertSame($value, (new Arr)->get($key, $this->getArrayResponse()));

        return $this;
    }

    public function assertResponseHasHeader(string $header): self
    {
        $this->assertTrue($this->response->hasHeader($header));

        return $this;
    }

    public function assertResponseIsRedirect(): self
    {
        $this->assertInstanceof(Redirect::class, $this->response);

        return $this;
    }

    public function assertRedirectUrl(string $url): self
    {
        $this->assertEquals($url, $this->response->getRedirectUrl());

        return $this;
    }

    public function assertResponseHeaderEquals(string $header, string $value): self
    {
        $this->assertEquals($value, $this->response->getHeader($header));

        return $this;
    }

    public function assertRouteNotFound(string $route)
    {
        $this->expectException(RouteNotFoundException::class);

        $this->request('GET', $route);
    }
}
