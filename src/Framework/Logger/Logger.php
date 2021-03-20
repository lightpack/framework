<?php

namespace Lightpack\Logger;

use Psr\Log\LoggerInterface;

class Logger
{
    private $logger;

    public function __construct(LoggerInterface $logger) 
    {
        $this->logger = $logger;
    }

    public function log(string $level, string $message, array $context = [])
    {
        $this->logger->log($level, $message, $context);
    }
}