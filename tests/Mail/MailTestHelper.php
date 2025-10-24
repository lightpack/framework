<?php

namespace Lightpack\Tests\Mail;

/**
 * Helper functions for mail testing
 */
class MailTestHelper
{
    /**
     * Extract email addresses from normalized mail data
     */
    public static function extractEmails(array $recipients): array
    {
        return array_map(fn($r) => $r['email'], $recipients);
    }

    /**
     * Check if email exists in recipients
     */
    public static function hasEmail(array $recipients, string $email): bool
    {
        return in_array($email, self::extractEmails($recipients));
    }
}
