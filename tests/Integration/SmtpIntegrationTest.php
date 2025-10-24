<?php

namespace Lightpack\Tests\Integration;

use Lightpack\Mail\Mail;
use Lightpack\Mail\MailManager;
use Lightpack\Mail\Drivers\SmtpDriver;
use PHPUnit\Framework\TestCase;

/**
 * SMTP Integration Test
 * 
 * This test actually sends emails via SMTP to verify the mail system works end-to-end.
 * 
 * Setup:
 * 1. Create a Mailtrap account (https://mailtrap.io)
 * 2. Get your SMTP credentials
 * 3. Set these environment variables:
 *    - MAILTRAP_HOST
 *    - MAILTRAP_PORT
 *    - MAILTRAP_USERNAME
 *    - MAILTRAP_PASSWORD
 * 
 * Run: ./vendor/bin/phpunit tests/Integration/SmtpIntegrationTest.php
 */
class SmtpIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip if Mailtrap credentials not configured
        if (!getenv('MAILTRAP_HOST')) {
            $this->markTestSkipped('Mailtrap credentials not configured. Set MAILTRAP_* environment variables.');
        }
        
        // Configure SMTP with Mailtrap
        putenv('MAIL_HOST=' . getenv('MAILTRAP_HOST'));
        putenv('MAIL_PORT=' . getenv('MAILTRAP_PORT'));
        putenv('MAIL_USERNAME=' . getenv('MAILTRAP_USERNAME'));
        putenv('MAIL_PASSWORD=' . getenv('MAILTRAP_PASSWORD'));
        putenv('MAIL_ENCRYPTION=tls');
        putenv('MAIL_FROM_ADDRESS=test@example.com');
        putenv('MAIL_FROM_NAME=Integration Test');
        
        // Register MailManager with SMTP driver
        $container = \Lightpack\Container\Container::getInstance();
        $mailManager = new MailManager();
        $mailManager->registerDriver('smtp', new SmtpDriver());
        $mailManager->setDefaultDriver('smtp');
        $container->register('mail', fn() => $mailManager);
    }

    public function testSmtpSendsBasicEmail()
    {
        $mail = new class(app('mail')) extends Mail {
            public function dispatch(array $payload = []) {
                $this->to('recipient@example.com', 'Test Recipient')
                    ->subject('SMTP Integration Test')
                    ->body('<h1>Hello from Lightpack!</h1><p>This is a real SMTP test.</p>')
                    ->send();
            }
        };

        // This will actually send via SMTP (throws exception on failure)
        $mail->dispatch();
        
        $this->assertTrue(true, 'Email sent successfully via SMTP');
        
        echo "\n✅ Email sent! Check your Mailtrap inbox.\n";
    }

    public function testSmtpSendsEmailWithAllFeatures()
    {
        $mail = new class(app('mail')) extends Mail {
            public function dispatch(array $payload = []) {
                $this->to('user1@example.com', 'User One')
                    ->to('user2@example.com', 'User Two')
                    ->cc('manager@example.com', 'Manager')
                    ->bcc('admin@example.com', 'Admin')
                    ->replyTo('support@example.com', 'Support Team')
                    ->subject('Full Feature Test')
                    ->body('<h1>Full Feature Email</h1><p>Testing all features.</p>')
                    ->altBody('Full Feature Email - Testing all features.')
                    ->send();
            }
        };

        $mail->dispatch();
        
        $this->assertTrue(true, 'Complex email sent successfully');
        
        echo "\n✅ Complex email sent! Verify in Mailtrap:\n";
        echo "   - 2 recipients (To)\n";
        echo "   - 1 CC\n";
        echo "   - 1 BCC\n";
        echo "   - Reply-To header\n";
        echo "   - HTML + Text versions\n";
    }

    public function testSmtpSendsEmailWithAttachment()
    {
        // Create a temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'smtp_test_');
        file_put_contents($tempFile, "This is a test attachment.\nLine 2\nLine 3");

        $mail = new class(app('mail')) extends Mail {
            public function dispatch(array $payload = []) {
                $this->to('recipient@example.com')
                    ->subject('Attachment Test')
                    ->body('<h1>Email with Attachment</h1>')
                    ->attach($payload['file'], 'test-document.txt')
                    ->send();
            }
        };

        $mail->dispatch(['file' => $tempFile]);
        
        $this->assertTrue(true, 'Email with attachment sent successfully');
        
        unlink($tempFile);
        
        echo "\n✅ Email with attachment sent! Check Mailtrap for 'test-document.txt'\n";
    }

    public function testSmtpHandlesInvalidCredentials()
    {
        // Temporarily set invalid credentials
        putenv('MAIL_PASSWORD=invalid_password');
        
        // Re-register with invalid credentials
        $container = \Lightpack\Container\Container::getInstance();
        $mailManager = new MailManager();
        $mailManager->registerDriver('smtp', new SmtpDriver());
        $mailManager->setDefaultDriver('smtp');
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
        
        echo "\n✅ Invalid credentials properly rejected\n";
    }
}
