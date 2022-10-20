<?php

namespace Lightpack\Exceptions;

class InvalidHttpMethodException extends HttpException
{
    public function __construct(string $message) 
    {
        parent::__construct($message, 405);
    }
}