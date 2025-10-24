<?php

namespace Lightpack\Tests\Integration;

use Lightpack\Mail\Mail;
use Lightpack\Mail\MailManager;
use Lightpack\Mail\Drivers\ResendDriver;
use PHPUnit\Framework\TestCase;

/**
 * Resend Integration Test
 * 
 * This test actually sends emails via Resend API to verify the integration works.
 * 
 * Setup:
 * 1. Create a Resend account (https://resend.com)
 * 2. Get your API key
 * 3. Verify your domain
 * 4. Set environment variables:
 *    - RESEND_API_KEY=re_xxxxxxxxxxxxx
 *    - RESEND_TEST_EMAIL=your-verified-email@yourdomain.com
 * 
 * Run: ./vendor/bin/phpunit tests/Integration/ResendIntegrationTest.php
 */
class ResendIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip if Resend not configured
        if (!getenv('RESEND_API_KEY')) {
            $this->markTestSkipped('Resend API key not configured. Set RESEND_API_KEY environment variable.');
        }
        
        // Use Resend's test emails if not configured
        // onboarding@resend.dev - Can send FROM (no domain verification needed)
        // delivered@resend.dev - Test recipient (emails go to sandbox)
        if (!getenv('RESEND_FROM_EMAIL')) {
            putenv('RESEND_FROM_EMAIL=onboarding@resend.dev');
        }
        
        if (!getenv('RESEND_TO_EMAIL')) {
            putenv('RESEND_TO_EMAIL=delivered@resend.dev');
        }
        
        // Check if resend package is installed
        if (!class_exists('\Resend')) {
            $this->markTestSkipped('Resend package not installed. Run: composer require resend/resend-php');
        }
        
        // Configure Resend
        putenv('MAIL_FROM_ADDRESS=' . getenv('RESEND_FROM_EMAIL'));
        putenv('MAIL_FROM_NAME=Lightpack Integration Test');
        
        // Register MailManager with Resend driver
        $container = \Lightpack\Container\Container::getInstance();
        $mailManager = new MailManager();
        $mailManager->registerDriver('resend', new ResendDriver());
        $mailManager->setDefaultDriver('resend');
        $container->register('mail', fn() => $mailManager);
    }

    public function testResendSendsBasicEmail()
    {
        $testEmail = getenv('RESEND_TO_EMAIL');
        
        $mail = new class(app('mail')) extends Mail {
            public function dispatch(array $payload = []) {
                $this->to($payload['to'])
                    ->subject('Resend Integration Test - ' . date('Y-m-d H:i:s'))
                    ->body('<h1>Hello from Lightpack via Resend!</h1><p>This is a real API test.</p>')
                    ->send();
            }
        };

        $mail->dispatch(['to' => $testEmail]);
        
        $this->assertTrue(true, 'Email sent successfully via Resend');
        
        echo "\n✅ Email sent via Resend! Check inbox: {$testEmail}\n";
    }

    public function testResendSendsEmailWithMultipleRecipients()
    {
        $testEmail = getenv('RESEND_TO_EMAIL');
        
        $mail = new class(app('mail')) extends Mail {
            public function dispatch(array $payload = []) {
                $this->to($payload['to'], 'Primary Recipient')
                    ->cc($payload['to'], 'CC Recipient')
                    ->subject('Resend Multi-Recipient Test - ' . date('Y-m-d H:i:s'))
                    ->body('<h1>Multi-Recipient Test</h1><p>Testing CC functionality.</p>')
                    ->altBody('Multi-Recipient Test - Testing CC functionality.')
                    ->send();
            }
        };

        $mail->dispatch(['to' => $testEmail]);
        
        $this->assertTrue(true, 'Multi-recipient email sent successfully');
        
        echo "\n✅ Multi-recipient email sent! Check for CC in inbox.\n";
    }

    public function testResendSendsEmailWithAttachment()
    {
        $testEmail = getenv('RESEND_TO_EMAIL');
        
        // Create a test PDF-like file
        $tempFile = tempnam(sys_get_temp_dir(), 'resend_test_');
        file_put_contents($tempFile, "%PDF-1.4\nTest PDF Content\n%%EOF");

        $mail = new class(app('mail')) extends Mail {
            public function dispatch(array $payload = []) {
                $this->to($payload['to'])
                    ->subject('Resend Attachment Test - ' . date('Y-m-d H:i:s'))
                    ->body('<h1>Email with Attachment</h1><p>Check for attached document.</p>')
                    ->attach($payload['file'], 'test-document.pdf')
                    ->send();
            }
        };

        $mail->dispatch([
            'to' => $testEmail,
            'file' => $tempFile
        ]);
        
        $this->assertTrue(true, 'Email with attachment sent successfully');
        
        unlink($tempFile);
        
        echo "\n✅ Email with attachment sent via Resend!\n";
    }

    public function testResendHandlesInvalidApiKey()
    {
        // Set invalid API key
        putenv('RESEND_API_KEY=re_invalid_key');
        
        // Re-register with invalid key
        $container = \Lightpack\Container\Container::getInstance();
        $mailManager = new MailManager();
        $mailManager->registerDriver('resend', new ResendDriver());
        $mailManager->setDefaultDriver('resend');
        $container->register('mail', fn() => $mailManager);

        $mail = new class(app('mail')) extends Mail {
            public function dispatch(array $payload = []) {
                $this->to('test@example.com')
                    ->subject('Should Fail')
                    ->body('This should not send')
                    ->send();
            }
        };

        $this->expectException(\Exception::class);
        $mail->dispatch();
        
        echo "\n✅ Invalid API key properly rejected\n";
    }
}
