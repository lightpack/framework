<?php

namespace Lightpack\Tests\Mail;

use Lightpack\Mail\Mail;
use PHPUnit\Framework\TestCase;

class TestMail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $this->to($payload['to'] ?? 'test@example.com')
            ->subject($payload['subject'] ?? 'Test Subject')
            ->body($payload['body'] ?? 'Test Body')
            ->send();
    }
}

class MailCompositionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set test environment
        putenv('MAIL_DRIVER=array');
        putenv('MAIL_FROM_ADDRESS=sender@example.com');
        putenv('MAIL_FROM_NAME=Test Sender');
        
        Mail::clearSentMails();
    }

    public function testMailUsesComposition()
    {
        $mail = new TestMail();
        
        // Verify that Mail class has a mailer property (composition)
        $reflection = new \ReflectionClass($mail);
        $property = $reflection->getProperty('mailer');
        $property->setAccessible(true);
        
        $this->assertInstanceOf(\PHPMailer\PHPMailer\PHPMailer::class, $property->getValue($mail));
    }

    public function testMailSendsSuccessfully()
    {
        $mail = new TestMail();
        $mail->dispatch([
            'to' => 'recipient@example.com',
            'subject' => 'Hello World',
            'body' => 'This is a test email'
        ]);

        $sentMails = Mail::getSentMails();
        
        $this->assertCount(1, $sentMails);
        $this->assertEquals(['recipient@example.com'], $sentMails[0]['to']);
        $this->assertEquals('Hello World', $sentMails[0]['subject']);
        $this->assertEquals('This is a test email', $sentMails[0]['html_body']);
    }

    public function testMailFluentApi()
    {
        $mail = new TestMail();
        
        $result = $mail->to('user@example.com')
            ->cc('cc@example.com')
            ->bcc('bcc@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();

        $this->assertTrue($result);
        
        $sentMails = Mail::getSentMails();
        $this->assertCount(1, $sentMails);
        $this->assertEquals(['user@example.com'], $sentMails[0]['to']);
        $this->assertEquals(['cc@example.com'], $sentMails[0]['cc']);
        $this->assertEquals(['bcc@example.com'], $sentMails[0]['bcc']);
    }

    public function testMailWithAttachments()
    {
        $mail = new TestMail();
        
        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'mail_test_');
        file_put_contents($tempFile, 'Test content');
        
        $mail->to('user@example.com')
            ->subject('With Attachment')
            ->body('Body')
            ->attach($tempFile, 'test.txt')
            ->send();

        $sentMails = Mail::getSentMails();
        $this->assertCount(1, $sentMails);
        $this->assertCount(1, $sentMails[0]['attachments']);
        // PHPMailer stores attachments with path and filename swapped in the array
        $this->assertNotEmpty($sentMails[0]['attachments'][0]['path']);
        $this->assertNotEmpty($sentMails[0]['attachments'][0]['filename']);
        
        // Cleanup
        unlink($tempFile);
    }

    public function testSmtpConfigurationOnlyForSmtpDriver()
    {
        putenv('MAIL_DRIVER=log');
        putenv('MAIL_HOST=smtp.example.com');
        putenv('MAIL_PORT=587');
        
        $mail = new TestMail();
        
        // Access the mailer to check SMTP is not configured for non-SMTP drivers
        $reflection = new \ReflectionClass($mail);
        $property = $reflection->getProperty('mailer');
        $property->setAccessible(true);
        $mailer = $property->getValue($mail);
        
        // For log driver, SMTP should not be configured
        $this->assertNotEquals('smtp.example.com', $mailer->Host);
    }

    protected function tearDown(): void
    {
        Mail::clearSentMails();
        parent::tearDown();
    }
}
