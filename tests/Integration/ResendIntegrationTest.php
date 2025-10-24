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
        
        // Check if resend package is installed
        if (!class_exists('\Resend')) {
            $this->markTestSkipped('Resend package not installed. Run: composer require resend/resend-php');
        }
        
        // Configure Resend with test emails
        // onboarding@resend.dev - Can send FROM (no domain verification needed)
        // delivered@resend.dev - Test recipient (emails go to sandbox)
        putenv('MAIL_FROM_ADDRESS=onboarding@resend.dev');
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
        $mail = new class(app('mail')) extends Mail {
            public function dispatch(array $payload = []) {
                $this->to('delivered@resend.dev')
                    ->subject('Resend Integration Test - ' . date('Y-m-d H:i:s'))
                    ->body('<h1>Hello from Lightpack via Resend!</h1><p>This is a real API test.</p>')
                    ->send();
            }
        };

        $mail->dispatch();
        
        $this->assertTrue(true, 'Email sent successfully via Resend');
        
        echo "\n✅ Email sent via Resend!\n";
        
        // Respect Resend rate limit (2 requests/second)
        sleep(1);
    }

    public function testResendSendsEmailWithMultipleRecipients()
    {
        // Resend supports email labeling with + syntax
        // delivered+user1@resend.dev, delivered+user2@resend.dev, etc.
        
        $mail = new class(app('mail')) extends Mail {
            public function dispatch(array $payload = []) {
                $this->to('delivered+user1@resend.dev', 'User One')
                    ->to('delivered+user2@resend.dev', 'User Two')
                    ->to('delivered+user3@resend.dev', 'User Three')
                    ->cc('delivered+cc@resend.dev', 'CC Recipient')
                    ->bcc('delivered+bcc@resend.dev', 'BCC Recipient')
                    ->subject('Resend Multi-Recipient Test - ' . date('Y-m-d H:i:s'))
                    ->body('<h1>Multi-Recipient Test</h1><p>Testing multiple TO, CC, and BCC recipients.</p>')
                    ->altBody('Multi-Recipient Test - Testing multiple TO, CC, and BCC recipients.')
                    ->send();
            }
        };

        $mail->dispatch();
        
        $this->assertTrue(true, 'Multi-recipient email sent successfully');
        
        echo "\n✅ Multi-recipient email sent!\n";
        echo "   - 3 TO recipients (delivered+user1/2/3@resend.dev)\n";
        echo "   - 1 CC recipient (delivered+cc@resend.dev)\n";
        echo "   - 1 BCC recipient (delivered+bcc@resend.dev)\n";
        
        // Respect Resend rate limit (2 requests/second)
        sleep(1);
    }

    public function testResendSendsEmailWithAttachment()
    {
        // Create a test PDF-like file
        $tempFile = tempnam(sys_get_temp_dir(), 'resend_test_');
        file_put_contents($tempFile, "%PDF-1.4\nTest PDF Content\n%%EOF");

        $mail = new class(app('mail')) extends Mail {
            public function dispatch(array $payload = []) {
                $this->to('delivered@resend.dev')
                    ->subject('Resend Attachment Test - ' . date('Y-m-d H:i:s'))
                    ->body('<h1>Email with Attachment</h1><p>Check for attached document.</p>')
                    ->attach($payload['file'], 'test-document.pdf')
                    ->send();
            }
        };

        $mail->dispatch(['file' => $tempFile]);
        
        $this->assertTrue(true, 'Email with attachment sent successfully');
        
        unlink($tempFile);
        
        echo "\n✅ Email with attachment sent via Resend!\n";
    }

    public function testResendSendsBatchEmails()
    {
        // Create batch using cleaner API
        $batch = app('mail')->batch();
        
        // Add 5 emails to batch
        for ($i = 1; $i <= 5; $i++) {
            $batch->add(function($mail) use ($i) {
                $mail->to("delivered+batch{$i}@resend.dev", "User {$i}")
                    ->subject("Batch Email #{$i} - " . date('Y-m-d H:i:s'))
                    ->body("<h1>Batch Email #{$i}</h1><p>This is email number {$i} in the batch.</p>")
                    ->altBody("Batch Email #{$i} - This is email number {$i} in the batch.");
            });
        }
        
        $this->assertEquals(5, $batch->count(), 'Batch should contain 5 emails');
        
        // Send batch via Resend
        $results = $batch->driver('resend')->send();
        
        $this->assertIsArray($results, 'Batch send should return array of results');
        $this->assertNotEmpty($results, 'Results should not be empty');
        
        echo "\n✅ Batch of {$batch->count()} emails sent via Resend!\n";
        echo "   Results: " . count($results) . " emails processed\n";
        
        // Respect Resend rate limit
        sleep(1);
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
