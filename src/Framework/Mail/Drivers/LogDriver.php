<?php

namespace Lightpack\Mail\Drivers;

use Lightpack\Mail\DriverInterface;
use Lightpack\Mail\MailData;

class LogDriver implements DriverInterface
{
    public function send(array $data): bool
    {
        // Convert to MailData for fluent handling
        $mailData = MailData::fromArray($data);
        
        // Add metadata
        $enrichedData = $mailData->toArray();
        $enrichedData['id'] = uniqid();
        $enrichedData['timestamp'] = time();

        $logFile = DIR_STORAGE . '/logs/mails.json';
        
        // Ensure logs directory exists
        $logsDir = dirname($logFile);
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }

        $mails = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
        $mails[] = $enrichedData;
        file_put_contents($logFile, json_encode($mails, JSON_PRETTY_PRINT));

        return true;
    }
}
