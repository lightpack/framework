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

    public function assertSessionHas(string $key, $value = null): self
    {
        $this->assertTrue(
            session()->has($key),
            "Failed asserting that session has key '{$key}'"
        );

        if ($value !== null) {
            $this->assertEquals(
                $value,
                session()->get($key),
                "Failed asserting that session key '{$key}' has value '{$value}'"
            );
        }

        return $this;
    }

    public function assertSessionHasErrors(array $keys = []): self
    {
        $errors = session()->get('_validation_errors', []);

        if (empty($keys)) {
            $this->assertTrue(!empty($errors), 'Session has no validation errors');
            return $this;
        }

        if (is_array($keys)) {
            foreach ($keys as $key) {
                $this->assertTrue(isset($errors[$key]), "Session missing error: '{$key}'");
            }
        }

        return $this;
    }

    public function assertSessionHasOldInput(array $keys = []): self
    {
        $arr = new Arr;
        $old = session()->get('_old_input', []);

        if (empty($keys)) {
            $this->assertTrue(!empty($old), 'Session has no old input data');
            return $this;
        }

        foreach ($keys as $key => $value) {
            if (is_int($key)) {
                $this->assertTrue($arr->has($value, $old), "Session missing old input: '{$value}'");
            } else {
                $this->assertTrue($arr->has($key, $old), "Session missing old input: '{$key}'");
                $this->assertEquals($value, $arr->get($key, $old), "Old input '{$key}' has wrong value");
            }
        }

        return $this;
    }
}
