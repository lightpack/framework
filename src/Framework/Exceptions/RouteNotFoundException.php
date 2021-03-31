<?php

namespace Lightpack\Exceptions;

class RouteNotFoundException extends HttpException 
{
    public function __construct(string $message) 
    {
        parent::__construct($message, 404);
    }
}