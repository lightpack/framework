<?php

namespace Lightpack\Exceptions;

class HttpException extends \Exception {
    
    protected array $headers = [];

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}