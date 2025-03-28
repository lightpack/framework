<?php

namespace Lightpack\Exceptions;

class TooManyRequestsException extends HttpException
{
    public function __construct(string $message = '', int $code = 429) 
    {
        parent::__construct($message, $code);
    }
}
