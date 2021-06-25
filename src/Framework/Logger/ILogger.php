<?php

namespace Lightpack\Logger;

/**
 * Interface that all log drivers must implement.
 */

interface ILogger
{
    public function log($level, $message, array $context = []);
}
