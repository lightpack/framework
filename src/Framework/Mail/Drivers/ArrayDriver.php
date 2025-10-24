<?php

namespace Lightpack\Mail\Drivers;

use Lightpack\Mail\DriverInterface;

class ArrayDriver implements DriverInterface
{
    private static array $sentMails = [];

    public function send(array $data): bool
    {
        $data['id'] = uniqid();
        $data['timestamp'] = time();
        
        static::$sentMails[] = $data;
        
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
