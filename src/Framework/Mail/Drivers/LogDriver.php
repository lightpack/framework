<?php

namespace Lightpack\Mail\Drivers;

use Lightpack\Mail\DriverInterface;

class LogDriver implements DriverInterface
{
    public function send(array $data): bool
    {
        // Add metadata
        $data['id'] = uniqid();
        $data['timestamp'] = time();

        // Filter out empty values for cleaner logs
        $data = array_filter($data, function($value) {
            if (is_array($value)) {
                return !empty($value);
            }
            if (is_string($value)) {
                return $value !== '';
            }
            return $value !== null;
        });

        $logFile = DIR_STORAGE . '/logs/mails.json';
        
        // Ensure logs directory exists
        $logsDir = dirname($logFile);
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }

        $mails = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
        $mails[] = $data;
        file_put_contents($logFile, json_encode($mails, JSON_PRETTY_PRINT));

        return true;
    }
}
