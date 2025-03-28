<?php

namespace Lightpack\Exceptions;

class TooManyRequestsException extends HttpException
{
    protected $headers = [];

    public function __construct(string $message = '', int $code = 429) 
    {
        parent::__construct($message, $code);
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
