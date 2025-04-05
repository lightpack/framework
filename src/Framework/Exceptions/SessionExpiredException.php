<?php

namespace Lightpack\Exceptions;

class SessionExpiredException extends HttpException
{
    public function __construct(
        string $message = 'Session has expired',
        int $code = 419  // Using 419 (Authentication Timeout)
    ) {
        parent::__construct($message, $code);
    }
}
