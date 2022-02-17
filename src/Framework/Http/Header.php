<?php

namespace Lightpack\Http;

class Header
{
    private $headers = [];

    public function __construct()
    {
        $this->setHeaders();
    }

    public function has(string $key): bool
    {
        return $this->headers[$key] ? true : false;
    }

    public function get(string $key, string $default = null): ?string
    {
        return $this->headers[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->headers;
    }

    private function setHeaders()
    {
        // if (function_exists('getallheaders')) {
        //     $this->headers = getallheaders();
        //     return;
        // }

        $this->parseRequestHeaders();
    }

    private function parseRequestHeaders()
    {
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $this->headers[$key] = $value;
            }
        }
    }
}
