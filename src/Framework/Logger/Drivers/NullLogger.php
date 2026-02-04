<?php

namespace Lightpack\Logger\Drivers;

use Lightpack\Logger\LoggerInterface;

class NullLogger implements LoggerInterface
{
    public function log($level, $message, array $context = [])
    {
        // Do nothing
    }
}
