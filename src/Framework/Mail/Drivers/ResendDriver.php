<?php

namespace Lightpack\Mail\Drivers;

use Exception as GlobalException;
use Lightpack\Mail\DriverInterface;

/**
 * Resend API Driver
 * 
 * Complete implementation for Resend email service.
 * 
 * Installation:
 * composer require resend/resend-php
 * 
 * Configuration (.env):
 * MAIL_DRIVER=resend
 * RESEND_API_KEY=re_xxxxxxxxxxxxx
 * MAIL_FROM_ADDRESS=onboarding@resend.dev
 * MAIL_FROM_NAME="Your App"
 * 
 * Usage:
 * Register in your app's MailProvider:
 * $mailManager->registerDriver('resend', new ResendDriver());
 */
class ResendDriver implements DriverInterface
{
    private $resend;

    public function __construct()
    {
        // Check if Resend package is installed
        if (!class_exists('\Resend')) {
            throw new GlobalException(
                'ResendDriver requires resend/resend-php package. ' .
                'Install via: composer require resend/resend-php'
            );
        }

        // Initialize Resend client
        $apiKey = get_env('RESEND_API_KEY');
        if (empty($apiKey)) {
            throw new GlobalException('RESEND_API_KEY environment variable is not set');
        }

        $this->resend = \Resend::client($apiKey);
    }

    public function send(array $data): bool
    {
        try {
            // Data is already normalized by MailData
            // Just transform to Resend's specific format
            $payload = [
                'from' => $this->formatAddress($data['from']),
                'to' => $this->formatAddresses($data['to']),
                'subject' => $data['subject'],
                'html' => $data['html_body'],
            ];

            // Add optional fields
            if (!empty($data['text_body'])) {
                $payload['text'] = $data['text_body'];
            }

            if (!empty($data['cc'])) {
                $payload['cc'] = $this->formatAddresses($data['cc']);
            }

            if (!empty($data['bcc'])) {
                $payload['bcc'] = $this->formatAddresses($data['bcc']);
            }

            if (!empty($data['reply_to'])) {
                $payload['reply_to'] = $data['reply_to'][0]['email'];
            }

            if (!empty($data['attachments'])) {
                $payload['attachments'] = $this->formatAttachments($data['attachments']);
            }

            // Send via Resend API
            $result = $this->resend->emails->send($payload);
            
            return isset($result['id']);
        } catch (\Exception $e) {
            throw new GlobalException("Resend API Error: {$e->getMessage()}");
        }
    }

    private function formatAddress(array $address): string
    {
        return !empty($address['name'])
            ? "{$address['name']} <{$address['email']}>"
            : $address['email'];
    }

    private function formatAddresses(array $addresses): array
    {
        return array_map(fn($addr) => $this->formatAddress($addr), $addresses);
    }

    private function formatAttachments(array $attachments): array
    {
        return array_map(function($att) {
            return [
                'filename' => $att['name'] ?: basename($att['path']),
                'content' => base64_encode(file_get_contents($att['path'])),
            ];
        }, $attachments);
    }
}
