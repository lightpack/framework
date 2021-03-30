<?php

namespace Lightpack\Exceptions;

class InvalidCsrfTokenException extends HttpException 
{
    public function __construct(string $message) 
    {
        parent::__construct($message, 403);
    }
}