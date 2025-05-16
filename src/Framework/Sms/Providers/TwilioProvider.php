<?php

namespace Lightpack\Sms\Providers;

use Twilio\Rest\Client;
use Lightpack\Logger\Logger;
use Lightpack\Sms\SmsProviderInterface;

class TwilioProvider implements SmsProviderInterface
{
    protected $client;
    protected $from;

    public function __construct(protected Logger $log, array $config)
    {
        $this->from = $config['from'];
        $this->client = new Client($config['sid'], $config['token']);
    }

    public function send(string $phone, string $message, array $options = []): bool
    {
        try {
            $this->client->messages->create($phone, [
                'from' => $this->from,
                'body' => $message,
            ]);
            return true;
        } catch (\Exception $e) {
            $this->log->error('[Twilio SMS] ' . $e->getMessage(), [
                'phone' => $phone,
                'message' => $message,
            ]);
            return false;
        }
    }
}
