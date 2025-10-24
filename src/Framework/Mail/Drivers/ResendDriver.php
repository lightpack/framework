<?php

namespace Lightpack\Mail\Drivers;

use Exception as GlobalException;
use Lightpack\Mail\DriverInterface;

/**
 * Resend API Driver
 * 
 * Example implementation showing how to integrate third-party email services.
 * Install: composer require resend/resend-php
 * 
 * Usage in .env:
 * MAIL_DRIVER=resend
 * RESEND_API_KEY=your_api_key_here
 */
class ResendDriver implements DriverInterface
{
    private $resend;

    public function __construct()
    {
        // Example: Initialize Resend client
        // $this->resend = Resend::client(get_env('RESEND_API_KEY'));
    }

    public function send(array $data): bool
    {
        try {
            // Transform Lightpack's normalized data to Resend's format
            $payload = [
                'from' => $data['from']['name'] 
                    ? "{$data['from']['name']} <{$data['from']['email']}>"
                    : $data['from']['email'],
                'to' => array_map(function($recipient) {
                    return $recipient['name']
                        ? "{$recipient['name']} <{$recipient['email']}>"
                        : $recipient['email'];
                }, $data['to']),
                'subject' => $data['subject'],
                'html' => $data['html_body'],
            ];

            // Add optional fields
            if (!empty($data['text_body'])) {
                $payload['text'] = $data['text_body'];
            }

            if (!empty($data['cc'])) {
                $payload['cc'] = array_map(fn($r) => $r['email'], $data['cc']);
            }

            if (!empty($data['bcc'])) {
                $payload['bcc'] = array_map(fn($r) => $r['email'], $data['bcc']);
            }

            if (!empty($data['reply_to'])) {
                $payload['reply_to'] = $data['reply_to'][0]['email'];
            }

            if (!empty($data['attachments'])) {
                $payload['attachments'] = array_map(function($att) {
                    return [
                        'filename' => $att['name'] ?: basename($att['path']),
                        'content' => base64_encode(file_get_contents($att['path'])),
                    ];
                }, $data['attachments']);
            }

            // Example: Send via Resend API
            // $result = $this->resend->emails->send($payload);
            
            // For now, throw exception to indicate driver needs configuration
            throw new GlobalException('ResendDriver requires resend/resend-php package. Install via: composer require resend/resend-php');

            // return isset($result['id']);
        } catch (\Exception $e) {
            throw new GlobalException("Resend API Error: {$e->getMessage()}");
        }
    }
}
