<?php

namespace Lightpack\Exceptions;

class TooManyRequestsException extends \Exception 
{
    public function __construct(string $message = '', int $code = 429) 
    {
        parent::__construct($message, $code);
    }
}
