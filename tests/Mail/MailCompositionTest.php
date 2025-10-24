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
        
        // Register MailManager in container for tests
        $container = \Lightpack\Container\Container::getInstance();
        if (!$container->has('mail')) {
            $mailManager = new \Lightpack\Mail\MailManager();
            $mailManager->registerDriver('smtp', new \Lightpack\Mail\Drivers\SmtpDriver());
            $mailManager->registerDriver('array', new \Lightpack\Mail\Drivers\ArrayDriver());
            $mailManager->registerDriver('log', new \Lightpack\Mail\Drivers\LogDriver());
            $mailManager->setDefaultDriver('array');
            $container->register('mail', fn() => $mailManager);
        }
        
        Mail::clearSentMails();
    }

    protected function tearDown(): void
    {
        Mail::clearSentMails();
        
        // Reset environment to array driver
        putenv('MAIL_DRIVER=array');
        
        parent::tearDown();
    }

    /**
     * Helper to extract emails from normalized recipient array
     */
    private function extractEmails(array $recipients): array
    {
        return array_map(fn($r) => $r['email'], $recipients);
    }

    public function testMailUsesDriverArchitecture()
    {
        // Verify MailManager is registered
        $mailManager = app('mail');
        $this->assertInstanceOf(\Lightpack\Mail\MailManager::class, $mailManager);
        
        // Verify default driver is accessible
        $driver = $mailManager->getDefaultDriver();
        $this->assertInstanceOf(\Lightpack\Mail\DriverInterface::class, $driver);
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
        $this->assertCount(1, $sentMails[0]['to']);
        $this->assertEquals('recipient@example.com', $sentMails[0]['to'][0]['email']);
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
        $this->assertEquals('user@example.com', $sentMails[0]['to'][0]['email']);
        $this->assertEquals('cc@example.com', $sentMails[0]['cc'][0]['email']);
        $this->assertEquals('bcc@example.com', $sentMails[0]['bcc'][0]['email']);
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
        $this->assertNotEmpty($sentMails[0]['attachments'][0]['path']);
        $this->assertEquals('test.txt', $sentMails[0]['attachments'][0]['name']);
        
        // Cleanup
        unlink($tempFile);
    }

    public function testDifferentDriversCanBeUsed()
    {
        // Test that we can switch between drivers
        putenv('MAIL_DRIVER=log');
        
        $mail = new TestMail();
        $mail->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();
        
        // Should work without errors
        $this->assertTrue(true);
        
        // Switch back to array driver
        putenv('MAIL_DRIVER=array');
    }

    public function testFromMethodOverridesDefaultFrom()
    {
        $mail = new TestMail();
        
        $mail->from('custom@example.com', 'Custom Sender')
            ->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();

        $sentMails = Mail::getSentMails();
        $this->assertEquals('custom@example.com', $sentMails[0]['from']['email']);
        $this->assertEquals('Custom Sender', $sentMails[0]['from']['name']);
    }

    public function testReplyToWithSingleAddress()
    {
        $mail = new TestMail();
        
        $mail->to('user@example.com')
            ->replyTo('reply@example.com', 'Reply Name')
            ->subject('Test')
            ->body('Body')
            ->send();

        $sentMails = Mail::getSentMails();
        $this->assertCount(1, $sentMails);
        $this->assertCount(1, $sentMails[0]['reply_to']);
        $this->assertEquals('reply@example.com', $sentMails[0]['reply_to'][0]['email']);
    }

    public function testReplyToWithMultipleAddresses()
    {
        $mail = new TestMail();
        
        $mail->to('user@example.com')
            ->replyTo(['reply1@example.com', 'reply2@example.com'])
            ->subject('Test')
            ->body('Body')
            ->send();

        $sentMails = Mail::getSentMails();
        $this->assertCount(2, $sentMails[0]['reply_to']);
        $emails = $this->extractEmails($sentMails[0]['reply_to']);
        $this->assertContains('reply1@example.com', $emails);
        $this->assertContains('reply2@example.com', $emails);
    }

    public function testReplyToWithArrayOfEmailsAndNames()
    {
        $mail = new TestMail();
        
        $mail->to('user@example.com')
            ->replyTo(['reply1@example.com' => 'Reply One', 'reply2@example.com' => 'Reply Two'])
            ->subject('Test')
            ->body('Body')
            ->send();

        $sentMails = Mail::getSentMails();
        $this->assertCount(2, $sentMails[0]['reply_to']);
        $emails = $this->extractEmails($sentMails[0]['reply_to']);
        $this->assertContains('reply1@example.com', $emails);
        $this->assertContains('reply2@example.com', $emails);
    }

    public function testCcWithMultipleAddresses()
    {
        $mail = new TestMail();
        
        $mail->to('user@example.com')
            ->cc(['cc1@example.com', 'cc2@example.com'])
            ->subject('Test')
            ->body('Body')
            ->send();

        $sentMails = Mail::getSentMails();
        $this->assertCount(2, $sentMails[0]['cc']);
        $emails = $this->extractEmails($sentMails[0]['cc']);
        $this->assertContains('cc1@example.com', $emails);
        $this->assertContains('cc2@example.com', $emails);
    }

    public function testCcWithArrayOfEmailsAndNames()
    {
        $mail = new TestMail();
        
        $mail->to('user@example.com')
            ->cc(['cc1@example.com' => 'CC One', 'cc2@example.com' => 'CC Two'])
            ->subject('Test')
            ->body('Body')
            ->send();

        $sentMails = Mail::getSentMails();
        $this->assertCount(2, $sentMails[0]['cc']);
        $emails = $this->extractEmails($sentMails[0]['cc']);
        $this->assertContains('cc1@example.com', $emails);
        $this->assertContains('cc2@example.com', $emails);
    }

    public function testBccWithMultipleAddresses()
    {
        $mail = new TestMail();
        
        $mail->to('user@example.com')
            ->bcc(['bcc1@example.com', 'bcc2@example.com'])
            ->subject('Test')
            ->body('Body')
            ->send();

        $sentMails = Mail::getSentMails();
        $this->assertCount(2, $sentMails[0]['bcc']);
        $emails = $this->extractEmails($sentMails[0]['bcc']);
        $this->assertContains('bcc1@example.com', $emails);
        $this->assertContains('bcc2@example.com', $emails);
    }

    public function testBccWithArrayOfEmailsAndNames()
    {
        $mail = new TestMail();
        
        $mail->to('user@example.com')
            ->bcc(['bcc1@example.com' => 'BCC One', 'bcc2@example.com' => 'BCC Two'])
            ->subject('Test')
            ->body('Body')
            ->send();

        $sentMails = Mail::getSentMails();
        $this->assertCount(2, $sentMails[0]['bcc']);
        $emails = $this->extractEmails($sentMails[0]['bcc']);
        $this->assertContains('bcc1@example.com', $emails);
        $this->assertContains('bcc2@example.com', $emails);
    }

    public function testMultipleAttachmentsAsArray()
    {
        $mail = new TestMail();
        
        // Create temporary files
        $tempFile1 = tempnam(sys_get_temp_dir(), 'mail_test_');
        $tempFile2 = tempnam(sys_get_temp_dir(), 'mail_test_');
        file_put_contents($tempFile1, 'Test content 1');
        file_put_contents($tempFile2, 'Test content 2');
        
        $mail->to('user@example.com')
            ->subject('Multiple Attachments')
            ->body('Body')
            ->attach([$tempFile1, $tempFile2])
            ->send();

        $sentMails = Mail::getSentMails();
        $this->assertCount(2, $sentMails[0]['attachments']);
        
        // Cleanup
        unlink($tempFile1);
        unlink($tempFile2);
    }

    public function testMultipleAttachmentsWithCustomNames()
    {
        $mail = new TestMail();
        
        // Create temporary files
        $tempFile1 = tempnam(sys_get_temp_dir(), 'mail_test_');
        $tempFile2 = tempnam(sys_get_temp_dir(), 'mail_test_');
        file_put_contents($tempFile1, 'Test content 1');
        file_put_contents($tempFile2, 'Test content 2');
        
        $mail->to('user@example.com')
            ->subject('Multiple Attachments')
            ->body('Body')
            ->attach([$tempFile1 => 'file1.txt', $tempFile2 => 'file2.txt'])
            ->send();

        $sentMails = Mail::getSentMails();
        $this->assertCount(2, $sentMails[0]['attachments']);
        $filenames = array_column($sentMails[0]['attachments'], 'name');
        $this->assertContains('file1.txt', $filenames);
        $this->assertContains('file2.txt', $filenames);
        
        // Cleanup
        unlink($tempFile1);
        unlink($tempFile2);
    }

    public function testAltBodyMethod()
    {
        $mail = new TestMail();
        
        $mail->to('user@example.com')
            ->subject('Test')
            ->body('<h1>HTML Body</h1>')
            ->altBody('Plain text body')
            ->send();

        $sentMails = Mail::getSentMails();
        $this->assertEquals('<h1>HTML Body</h1>', $sentMails[0]['html_body']);
        $this->assertEquals('Plain text body', $sentMails[0]['text_body']);
    }

    public function testMultipleToRecipients()
    {
        $mail = new TestMail();
        
        $mail->to('user1@example.com')
            ->to('user2@example.com')
            ->to('user3@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();

        $sentMails = Mail::getSentMails();
        $this->assertCount(3, $sentMails[0]['to']);
        $emails = $this->extractEmails($sentMails[0]['to']);
        $this->assertContains('user1@example.com', $emails);
        $this->assertContains('user2@example.com', $emails);
        $this->assertContains('user3@example.com', $emails);
    }

    public function testComplexMailWithAllFields()
    {
        $mail = new TestMail();
        
        $tempFile = tempnam(sys_get_temp_dir(), 'mail_test_');
        file_put_contents($tempFile, 'Test content');
        
        $mail->from('sender@example.com', 'Sender Name')
            ->to('user1@example.com', 'User One')
            ->to('user2@example.com')
            ->cc(['cc1@example.com', 'cc2@example.com' => 'CC Two'])
            ->bcc(['bcc1@example.com'])
            ->replyTo('reply@example.com', 'Reply Name')
            ->subject('Complex Mail')
            ->body('<h1>HTML Body</h1>')
            ->altBody('Plain text')
            ->attach($tempFile, 'document.txt')
            ->send();

        $sentMails = Mail::getSentMails();
        $mail = $sentMails[0];
        
        $this->assertEquals('sender@example.com', $mail['from']['email']);
        $this->assertCount(2, $mail['to']);
        $this->assertCount(2, $mail['cc']);
        $this->assertCount(1, $mail['bcc']);
        $this->assertCount(1, $mail['reply_to']);
        $this->assertEquals('Complex Mail', $mail['subject']);
        $this->assertEquals('<h1>HTML Body</h1>', $mail['html_body']);
        $this->assertEquals('Plain text', $mail['text_body']);
        $this->assertCount(1, $mail['attachments']);
        $this->assertArrayHasKey('id', $mail);
        $this->assertArrayHasKey('timestamp', $mail);
        
        // Cleanup
        unlink($tempFile);
    }

    public function testInvalidMailDriverThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Mail driver 'invalid_driver' is not registered");
        
        $mail = new TestMail();
        $mail->driver('invalid_driver')
            ->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();
    }

    public function testLogDriverWritesToFile()
    {
        $logFile = DIR_STORAGE . '/logs/mails.json';
        
        // Clean up any existing log file
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        
        // Set log driver as default
        $mailManager = app('mail');
        $mailManager->setDefaultDriver('log');
        
        $mail = new TestMail();
        $mail->to('user@example.com')
            ->subject('Log Test')
            ->body('Log Body')
            ->send();
        
        $this->assertFileExists($logFile);
        
        $logs = json_decode(file_get_contents($logFile), true);
        $this->assertIsArray($logs);
        $this->assertCount(1, $logs);
        $this->assertEquals('user@example.com', $logs[0]['to'][0]['email']);
        $this->assertEquals('Log Test', $logs[0]['subject']);
        $this->assertEquals('Log Body', $logs[0]['html_body']);
        
        // Cleanup
        unlink($logFile);
        $mailManager->setDefaultDriver('array');
    }

    public function testLogDriverAppendsToExistingFile()
    {
        $logFile = DIR_STORAGE . '/logs/mails.json';
        
        // Clean up any existing log file
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        
        // Set log driver as default
        $mailManager = app('mail');
        $mailManager->setDefaultDriver('log');
        
        // Send first mail
        $mail1 = new TestMail();
        $mail1->to('user1@example.com')
            ->subject('First Mail')
            ->body('First Body')
            ->send();
        
        // Send second mail
        $mail2 = new TestMail();
        $mail2->to('user2@example.com')
            ->subject('Second Mail')
            ->body('Second Body')
            ->send();
        
        $logs = json_decode(file_get_contents($logFile), true);
        $this->assertCount(2, $logs);
        $this->assertEquals('First Mail', $logs[0]['subject']);
        $this->assertEquals('Second Mail', $logs[1]['subject']);
        
        // Cleanup
        unlink($logFile);
        $mailManager->setDefaultDriver('array');
    }

    public function testMailDataNormalization()
    {
        $mail = new TestMail();
        
        $mail->to('user@example.com', 'User Name')
            ->cc('cc@example.com')
            ->bcc('bcc@example.com')
            ->replyTo('reply@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();

        $sentMails = Mail::getSentMails();
        $mailData = $sentMails[0];
        
        // Verify all required fields exist
        $this->assertArrayHasKey('id', $mailData);
        $this->assertArrayHasKey('timestamp', $mailData);
        $this->assertArrayHasKey('to', $mailData);
        $this->assertArrayHasKey('from', $mailData);
        $this->assertArrayHasKey('subject', $mailData);
        $this->assertArrayHasKey('html_body', $mailData);
        $this->assertArrayHasKey('text_body', $mailData);
        $this->assertArrayHasKey('cc', $mailData);
        $this->assertArrayHasKey('bcc', $mailData);
        $this->assertArrayHasKey('reply_to', $mailData);
        $this->assertArrayHasKey('attachments', $mailData);
        
        // Verify data types
        $this->assertIsString($mailData['id']);
        $this->assertIsInt($mailData['timestamp']);
        $this->assertIsArray($mailData['to']);
        $this->assertIsArray($mailData['cc']);
        $this->assertIsArray($mailData['bcc']);
        $this->assertIsArray($mailData['reply_to']);
        $this->assertIsArray($mailData['attachments']);
    }

    public function testEmptyRecipientsArrays()
    {
        $mail = new TestMail();
        
        $mail->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();

        $sentMails = Mail::getSentMails();
        $mailData = $sentMails[0];
        
        // When no CC, BCC, or ReplyTo are set, arrays should be empty
        $this->assertIsArray($mailData['cc']);
        $this->assertIsArray($mailData['bcc']);
        $this->assertIsArray($mailData['reply_to']);
        $this->assertEmpty($mailData['cc']);
        $this->assertEmpty($mailData['bcc']);
        $this->assertEmpty($mailData['reply_to']);
    }

    public function testMailIdIsUnique()
    {
        $mail1 = new TestMail();
        $mail1->to('user1@example.com')
            ->subject('Test 1')
            ->body('Body 1')
            ->send();
        
        $mail2 = new TestMail();
        $mail2->to('user2@example.com')
            ->subject('Test 2')
            ->body('Body 2')
            ->send();

        $sentMails = Mail::getSentMails();
        $this->assertNotEquals($sentMails[0]['id'], $sentMails[1]['id']);
    }

    public function testTimestampIsReasonable()
    {
        $beforeTime = time();
        
        $mail = new TestMail();
        $mail->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();
        
        $afterTime = time();

        $sentMails = Mail::getSentMails();
        $timestamp = $sentMails[0]['timestamp'];
        
        $this->assertGreaterThanOrEqual($beforeTime, $timestamp);
        $this->assertLessThanOrEqual($afterTime, $timestamp);
    }

    public function testClearSentMailsWorks()
    {
        $mail = new TestMail();
        $mail->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();
        
        $this->assertCount(1, Mail::getSentMails());
        
        Mail::clearSentMails();
        
        $this->assertCount(0, Mail::getSentMails());
    }

    public function testSmtpDriverIsRegistered()
    {
        $mailManager = app('mail');
        
        // Verify SMTP driver is registered
        $drivers = $mailManager->getDriverNames();
        $this->assertContains('smtp', $drivers);
        
        // Verify we can get the SMTP driver
        $smtpDriver = $mailManager->driver('smtp');
        $this->assertInstanceOf(\Lightpack\Mail\Drivers\SmtpDriver::class, $smtpDriver);
    }

    public function testAllBuiltInDriversAreRegistered()
    {
        $mailManager = app('mail');
        $drivers = $mailManager->getDriverNames();
        
        $this->assertContains('smtp', $drivers);
        $this->assertContains('array', $drivers);
        $this->assertContains('log', $drivers);
    }
}
