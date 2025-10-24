<?php

namespace Lightpack\Mail\Drivers;

use Lightpack\Mail\DriverInterface;
use Lightpack\Mail\MailData;

class ArrayDriver implements DriverInterface
{
    private static array $sentMails = [];

    public function send(array $data): bool
    {
        // Convert to MailData for fluent handling
        $mailData = MailData::fromArray($data);
        
        // Add metadata
        $enrichedData = $mailData->toArray();
        $enrichedData['id'] = uniqid();
        $enrichedData['timestamp'] = time();
        
        static::$sentMails[] = $enrichedData;
        
        return true;
    }

    public static function getSentMails(): array
    {
        return static::$sentMails;
    }

    public static function clearSentMails(): void
    {
        static::$sentMails = [];
    }
}
