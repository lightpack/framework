<?php

namespace Lightpack\Logger\Drivers;

use Lightpack\Logger\ILogger;

class NullLogger implements ILogger
{
    public function log($level, $message, array $context = [])
    {
        // Do nothing
    }
}
