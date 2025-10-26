<?php

namespace Lightpack\Exceptions;

class InvalidUrlSignatureException extends HttpException
{
    public function __construct()
    {
        parent::__construct('The URL signature is invalid.', 403);
    }
}
