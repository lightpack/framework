<?php

namespace Lightpack\Sms\Providers;

use Lightpack\Logger\Logger;
use Lightpack\Sms\SmsProviderInterface;

class LogSmsProvider implements SmsProviderInterface
{
    public function __construct(
        protected Logger $logger
    ) {}

    public function send(string $phone, string $message, array $options = []): bool
    {
        $log = sprintf('[SMS] To: %s | Message: %s', $phone, $message);
        $this->logger->info($log);
        return true;
    }
}
