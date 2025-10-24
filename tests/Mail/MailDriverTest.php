<?php

namespace Lightpack\Tests\Mail;

use Lightpack\Mail\Mail;
use Lightpack\Mail\MailManager;
use Lightpack\Mail\Drivers\ArrayDriver;
use Lightpack\Mail\Drivers\LogDriver;
use Lightpack\Mail\Drivers\SmtpDriver;
use PHPUnit\Framework\TestCase;

class TestDriverMail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $this->to($payload['to'] ?? 'test@example.com')
            ->subject($payload['subject'] ?? 'Test Subject')
            ->body($payload['body'] ?? 'Test Body')
            ->send();
    }
    
    public static function make(): self
    {
        return new self(app('mail'));
    }
}

class MailDriverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        putenv('MAIL_DRIVER=array');
        putenv('MAIL_FROM_ADDRESS=sender@example.com');
        putenv('MAIL_FROM_NAME=Test Sender');
        
        // Register MailManager in container for tests
        $container = \Lightpack\Container\Container::getInstance();
        $mailManager = new \Lightpack\Mail\MailManager();
        $mailManager->registerDriver('smtp', new \Lightpack\Mail\Drivers\SmtpDriver());
        $mailManager->registerDriver('array', new \Lightpack\Mail\Drivers\ArrayDriver());
        $mailManager->registerDriver('log', new \Lightpack\Mail\Drivers\LogDriver());
        $mailManager->setDefaultDriver('array');
        $container->register('mail', fn() => $mailManager);
        
        Mail::clearSentMails();
    }

    protected function tearDown(): void
    {
        Mail::clearSentMails();
        putenv('MAIL_DRIVER=array');
        parent::tearDown();
    }

    // ===== MailManager Tests =====

    public function testMailManagerRegistersDrivers()
    {
        $manager = new MailManager();
        $driver = new ArrayDriver();
        
        $manager->registerDriver('test', $driver);
        
        $this->assertSame($driver, $manager->driver('test'));
    }

    public function testMailManagerSetsDefaultDriver()
    {
        $manager = new MailManager();
        $driver1 = new ArrayDriver();
        $driver2 = new LogDriver();
        
        $manager->registerDriver('driver1', $driver1);
        $manager->registerDriver('driver2', $driver2);
        $manager->setDefaultDriver('driver2');
        
        $this->assertSame($driver2, $manager->getDefaultDriver());
    }

    public function testMailManagerThrowsExceptionForUnregisteredDriver()
    {
        $manager = new MailManager();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Mail driver 'nonexistent' is not registered");
        
        $manager->driver('nonexistent');
    }

    public function testMailManagerThrowsExceptionWhenSettingUnregisteredDefault()
    {
        $manager = new MailManager();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Mail driver 'nonexistent' is not registered");
        
        $manager->setDefaultDriver('nonexistent');
    }

    public function testMailManagerGetsDriverNames()
    {
        $manager = new MailManager();
        $manager->registerDriver('smtp', new SmtpDriver());
        $manager->registerDriver('array', new ArrayDriver());
        $manager->registerDriver('log', new LogDriver());
        
        $names = $manager->getDriverNames();
        
        $this->assertCount(3, $names);
        $this->assertContains('smtp', $names);
        $this->assertContains('array', $names);
        $this->assertContains('log', $names);
    }

    // ===== Driver Tests =====

    public function testArrayDriverStoresMails()
    {
        $driver = new ArrayDriver();
        
        $data = [
            'to' => [['email' => 'user@example.com', 'name' => 'User']],
            'from' => ['email' => 'sender@example.com', 'name' => 'Sender'],
            'subject' => 'Test',
            'html_body' => 'Body',
            'text_body' => '',
            'cc' => [],
            'bcc' => [],
            'reply_to' => [],
            'attachments' => [],
        ];
        
        $driver->send($data);
        
        $sentMails = ArrayDriver::getSentMails();
        $this->assertCount(1, $sentMails);
        $this->assertArrayHasKey('id', $sentMails[0]);
        $this->assertArrayHasKey('timestamp', $sentMails[0]);
    }

    public function testArrayDriverClearsMails()
    {
        $driver = new ArrayDriver();
        
        $data = [
            'to' => [['email' => 'user@example.com', 'name' => '']],
            'from' => ['email' => 'sender@example.com', 'name' => ''],
            'subject' => 'Test',
            'html_body' => 'Body',
            'text_body' => '',
            'cc' => [],
            'bcc' => [],
            'reply_to' => [],
            'attachments' => [],
        ];
        
        $driver->send($data);
        $this->assertCount(1, ArrayDriver::getSentMails());
        
        ArrayDriver::clearSentMails();
        $this->assertCount(0, ArrayDriver::getSentMails());
    }

    // ===== LogDriver Tests =====

    public function testLogDriverWritesToFile()
    {
        putenv('MAIL_DRIVER=log');
        
        $logFile = DIR_STORAGE . '/logs/mails.json';
        
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        
        $driver = new LogDriver();
        
        $data = [
            'to' => [['email' => 'user@example.com', 'name' => 'User']],
            'from' => ['email' => 'sender@example.com', 'name' => 'Sender'],
            'subject' => 'Log Test',
            'html_body' => 'Log Body',
            'text_body' => '',
            'cc' => [],
            'bcc' => [],
            'reply_to' => [],
            'attachments' => [],
        ];
        
        $driver->send($data);
        
        $this->assertFileExists($logFile);
        
        $logs = json_decode(file_get_contents($logFile), true);
        $this->assertIsArray($logs);
        $this->assertCount(1, $logs);
        $this->assertEquals('Log Test', $logs[0]['subject']);
        
        unlink($logFile);
    }

    public function testLogDriverCreatesDirectoryIfNotExists()
    {
        $logFile = DIR_STORAGE . '/logs/mails.json';
        $logsDir = dirname($logFile);
        
        // Remove directory if exists
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        if (is_dir($logsDir)) {
            rmdir($logsDir);
        }
        
        $driver = new LogDriver();
        
        $data = [
            'to' => [['email' => 'user@example.com', 'name' => '']],
            'from' => ['email' => 'sender@example.com', 'name' => ''],
            'subject' => 'Test',
            'html_body' => 'Body',
            'text_body' => '',
            'cc' => [],
            'bcc' => [],
            'reply_to' => [],
            'attachments' => [],
        ];
        
        $driver->send($data);
        
        $this->assertDirectoryExists($logsDir);
        $this->assertFileExists($logFile);
        
        unlink($logFile);
    }

    // ===== Integration Tests =====

    public function testMailUsesMailManager()
    {
        $mailManager = app('mail');
        $this->assertInstanceOf(MailManager::class, $mailManager);
    }

    public function testMailUsesDefaultDriver()
    {
        $mail = TestDriverMail::make();
        $mail->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();
        
        $sentMails = Mail::getSentMails();
        $this->assertCount(1, $sentMails);
    }

    public function testMailCanSwitchDriversPerMail()
    {
        // This test verifies the driver() method exists and works
        $mail = TestDriverMail::make();
        
        // Should not throw exception
        $result = $mail->driver('array');
        $this->assertInstanceOf(Mail::class, $result);
    }

    public function testPerMailDriverSelection()
    {
        // Register a second driver for testing
        $mailManager = app('mail');
        $mailManager->registerDriver('test-driver', new ArrayDriver());
        
        $mail = TestDriverMail::make();
        $mail->driver('test-driver')
            ->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();
        
        // Should work without errors
        $this->assertTrue(true);
    }

    // ===== Edge Cases =====

    public function testEmptyRecipientArrays()
    {
        $mail = TestDriverMail::make();
        $mail->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();
        
        $sentMails = Mail::getSentMails();
        $this->assertIsArray($sentMails[0]['cc']);
        $this->assertIsArray($sentMails[0]['bcc']);
        $this->assertIsArray($sentMails[0]['reply_to']);
        $this->assertEmpty($sentMails[0]['cc']);
        $this->assertEmpty($sentMails[0]['bcc']);
        $this->assertEmpty($sentMails[0]['reply_to']);
    }

    public function testMultipleRecipientsOfSameType()
    {
        $mail = TestDriverMail::make();
        $mail->to('user1@example.com', 'User One')
            ->to('user2@example.com', 'User Two')
            ->to('user3@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();
        
        $sentMails = Mail::getSentMails();
        $this->assertCount(3, $sentMails[0]['to']);
        $this->assertEquals('user1@example.com', $sentMails[0]['to'][0]['email']);
        $this->assertEquals('User One', $sentMails[0]['to'][0]['name']);
        $this->assertEquals('user2@example.com', $sentMails[0]['to'][1]['email']);
        $this->assertEquals('user3@example.com', $sentMails[0]['to'][2]['email']);
        $this->assertEquals('', $sentMails[0]['to'][2]['name']);
    }

    public function testAttachmentsWithAndWithoutCustomNames()
    {
        $tempFile1 = tempnam(sys_get_temp_dir(), 'mail_test_');
        $tempFile2 = tempnam(sys_get_temp_dir(), 'mail_test_');
        file_put_contents($tempFile1, 'Content 1');
        file_put_contents($tempFile2, 'Content 2');
        
        $mail = TestDriverMail::make();
        $mail->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->attach($tempFile1, 'custom-name.txt')
            ->attach($tempFile2)
            ->send();
        
        $sentMails = Mail::getSentMails();
        $this->assertCount(2, $sentMails[0]['attachments']);
        $this->assertEquals('custom-name.txt', $sentMails[0]['attachments'][0]['name']);
        $this->assertEquals('', $sentMails[0]['attachments'][1]['name']);
        
        unlink($tempFile1);
        unlink($tempFile2);
    }

    public function testMailIdIsUnique()
    {
        $mail1 = TestDriverMail::make();
        $mail1->to('user1@example.com')->subject('Test 1')->body('Body 1')->send();
        
        $mail2 = TestDriverMail::make();
        $mail2->to('user2@example.com')->subject('Test 2')->body('Body 2')->send();
        
        $sentMails = Mail::getSentMails();
        $this->assertNotEquals($sentMails[0]['id'], $sentMails[1]['id']);
    }

    public function testTimestampIsReasonable()
    {
        $beforeTime = time();
        
        $mail = TestDriverMail::make();
        $mail->to('user@example.com')->subject('Test')->body('Body')->send();
        
        $afterTime = time();
        
        $sentMails = Mail::getSentMails();
        $timestamp = $sentMails[0]['timestamp'];
        
        $this->assertGreaterThanOrEqual($beforeTime, $timestamp);
        $this->assertLessThanOrEqual($afterTime, $timestamp);
    }

    public function testFromOverridesDefault()
    {
        $mail = TestDriverMail::make();
        $mail->from('custom@example.com', 'Custom Sender')
            ->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();
        
        $sentMails = Mail::getSentMails();
        $this->assertEquals('custom@example.com', $sentMails[0]['from']['email']);
        $this->assertEquals('Custom Sender', $sentMails[0]['from']['name']);
    }

    public function testHtmlAndTextBodies()
    {
        $mail = TestDriverMail::make();
        $mail->to('user@example.com')
            ->subject('Test')
            ->body('<h1>HTML Body</h1>')
            ->altBody('Plain text body')
            ->send();
        
        $sentMails = Mail::getSentMails();
        $this->assertEquals('<h1>HTML Body</h1>', $sentMails[0]['html_body']);
        $this->assertEquals('Plain text body', $sentMails[0]['text_body']);
    }
}
