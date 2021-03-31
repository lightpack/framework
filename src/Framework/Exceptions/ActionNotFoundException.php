<?php

namespace Lightpack\Exceptions;

class ActionNotFoundException extends HttpException 
{
    public function __construct(string $message) 
    {
        parent::__construct($message, 500);
    }
}