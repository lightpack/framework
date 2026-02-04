<?php

namespace Lightpack\Logger;

/**
 * Interface that all log drivers must implement.
 */

interface LoggerInterface
{
    public function log($level, $message, array $context = []);
}
