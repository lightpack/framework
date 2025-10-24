<?php

namespace Lightpack\Tests\Mail;

use Lightpack\Mail\Mail;
use Lightpack\Testing\MailAssertionTrait;
use PHPUnit\Framework\TestCase;

class TestMailForAssertions extends Mail
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

class MailAssertionTraitTest extends TestCase
{
    use MailAssertionTrait;

    protected function setUp(): void
    {
        parent::setUp();
        
        putenv('MAIL_DRIVER=array');
        putenv('MAIL_FROM_ADDRESS=sender@example.com');
        putenv('MAIL_FROM_NAME=Test Sender');
        
        // Register MailManager
        $container = \Lightpack\Container\Container::getInstance();
        $mailManager = new \Lightpack\Mail\MailManager();
        $mailManager->registerDriver('array', new \Lightpack\Mail\Drivers\ArrayDriver());
        $mailManager->setDefaultDriver('array');
        $container->register('mail', fn() => $mailManager);
        
        Mail::clearSentMails();
    }

    protected function tearDown(): void
    {
        Mail::clearSentMails();
        parent::tearDown();
    }

    public function testAssertMailSent()
    {
        $mail = TestMailForAssertions::make();
        $mail->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();

        $this->assertMailSent();
    }

    public function testAssertMailNotSent()
    {
        $this->assertMailNotSent();
    }

    public function testAssertMailSubject()
    {
        $mail = TestMailForAssertions::make();
        $mail->to('user@example.com')
            ->subject('Welcome Email')
            ->body('Body')
            ->send();

        $this->assertMailSubject('Welcome Email');
    }

    public function testAssertMailContains()
    {
        $mail = TestMailForAssertions::make();
        $mail->to('user@example.com')
            ->subject('Test')
            ->body('<h1>Hello World</h1>')
            ->send();

        $this->assertMailContains('Hello World');
    }

    public function testAssertMailSentFrom()
    {
        $mail = TestMailForAssertions::make();
        $mail->from('custom@example.com', 'Custom Sender')
            ->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();

        $this->assertMailSentFrom('custom@example.com');
    }

    public function testAssertMailCount()
    {
        $mail1 = TestMailForAssertions::make();
        $mail1->to('user1@example.com')->subject('Test 1')->body('Body')->send();

        $mail2 = TestMailForAssertions::make();
        $mail2->to('user2@example.com')->subject('Test 2')->body('Body')->send();

        $this->assertMailCount(2);
    }

    public function testAssertMailSentTo()
    {
        $mail = TestMailForAssertions::make();
        $mail->to('john@example.com', 'John Doe')
            ->subject('Test')
            ->body('Body')
            ->send();

        $this->assertMailSentTo('john@example.com');
    }

    public function testAssertNoMailSentTo()
    {
        $mail = TestMailForAssertions::make();
        $mail->to('john@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();

        $this->assertNoMailSentTo('jane@example.com');
    }

    public function testAssertMailSentToAll()
    {
        $mail = TestMailForAssertions::make();
        $mail->to('user1@example.com', 'User One')
            ->to('user2@example.com', 'User Two')
            ->to('user3@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();

        $this->assertMailSentToAll(['user1@example.com', 'user2@example.com', 'user3@example.com']);
    }

    public function testAssertMailCc()
    {
        $mail = TestMailForAssertions::make();
        $mail->to('user@example.com')
            ->cc('manager@example.com', 'Manager')
            ->subject('Test')
            ->body('Body')
            ->send();

        $this->assertMailCc('manager@example.com');
    }

    public function testAssertMailBcc()
    {
        $mail = TestMailForAssertions::make();
        $mail->to('user@example.com')
            ->bcc('admin@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();

        $this->assertMailBcc('admin@example.com');
    }

    public function testAssertMailReplyTo()
    {
        $mail = TestMailForAssertions::make();
        $mail->to('user@example.com')
            ->replyTo('support@example.com', 'Support')
            ->subject('Test')
            ->body('Body')
            ->send();

        $this->assertMailReplyTo('support@example.com');
    }

    public function testAssertMailCcAll()
    {
        $mail = TestMailForAssertions::make();
        $mail->to('user@example.com')
            ->cc('manager1@example.com')
            ->cc('manager2@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();

        $this->assertMailCcAll(['manager1@example.com', 'manager2@example.com']);
    }

    public function testAssertMailBccAll()
    {
        $mail = TestMailForAssertions::make();
        $mail->to('user@example.com')
            ->bcc('admin1@example.com')
            ->bcc('admin2@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();

        $this->assertMailBccAll(['admin1@example.com', 'admin2@example.com']);
    }

    public function testAssertMailReplyToAll()
    {
        $mail = TestMailForAssertions::make();
        $mail->to('user@example.com')
            ->replyTo('support@example.com')
            ->replyTo('sales@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();

        $this->assertMailReplyToAll(['support@example.com', 'sales@example.com']);
    }

    public function testAssertMailHasAttachment()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');

        $mail = TestMailForAssertions::make();
        $mail->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->attach($tempFile, 'document.pdf')
            ->send();

        $this->assertMailHasAttachment('document.pdf');

        unlink($tempFile);
    }

    public function testAssertMailHasNoAttachments()
    {
        $mail = TestMailForAssertions::make();
        $mail->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->send();

        $this->assertMailHasNoAttachments();
    }

    public function testAssertMailHasAttachments()
    {
        $tempFile1 = tempnam(sys_get_temp_dir(), 'test1');
        $tempFile2 = tempnam(sys_get_temp_dir(), 'test2');
        file_put_contents($tempFile1, 'content 1');
        file_put_contents($tempFile2, 'content 2');

        $mail = TestMailForAssertions::make();
        $mail->to('user@example.com')
            ->subject('Test')
            ->body('Body')
            ->attach($tempFile1, 'doc1.pdf')
            ->attach($tempFile2, 'doc2.pdf')
            ->send();

        $this->assertMailHasAttachments(['doc1.pdf', 'doc2.pdf']);

        unlink($tempFile1);
        unlink($tempFile2);
    }

    public function testFluentChaining()
    {
        $mail = TestMailForAssertions::make();
        $mail->to('user@example.com')
            ->cc('manager@example.com')
            ->subject('Fluent Test')
            ->body('Testing fluent interface')
            ->send();

        // Test that all assertions can be chained
        $this->assertMailSent()
            ->assertMailCount(1)
            ->assertMailSubject('Fluent Test')
            ->assertMailContains('fluent interface')
            ->assertMailSentTo('user@example.com')
            ->assertMailCc('manager@example.com');
    }
}
