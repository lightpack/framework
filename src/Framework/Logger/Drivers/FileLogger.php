<?php

namespace Lightpack\Logger\Drivers;

use Lightpack\Logger\ILogger;

class FileLogger implements ILogger
{
    private $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function log($level, $message, array $context = [])
    {
        $content = date('Y-m-d H:i:s') . " $level : " . $message . PHP_EOL;

        if($context) {
            $content .= 'Context: ' . json_encode($context) . PHP_EOL;
        }

        file_put_contents($this->filename, $content, LOCK_EX | FILE_APPEND);
    }
}
