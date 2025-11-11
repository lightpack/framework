<?php

namespace Lightpack\Tests\Mail;

use Lightpack\Mail\Mail;
use Lightpack\Mail\MailTemplate;
use PHPUnit\Framework\TestCase;

class TestMailWithTemplate extends Mail
{
    public function dispatch(array $payload = [])
    {
        $template = new MailTemplate();
        $template
            ->heading('Welcome!')
            ->paragraph('Thanks for signing up.')
            ->button('Get Started', $payload['url'] ?? 'https://example.com');
        
        $this->to($payload['to'] ?? 'test@example.com')
            ->subject($payload['subject'] ?? 'Welcome')
            ->template($template)
            ->send();
    }
    
    public static function make(): self
    {
        return new self(app('mail'));
    }
}

class MailTemplateIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        putenv('MAIL_DRIVER=array');
        putenv('MAIL_FROM_ADDRESS=sender@example.com');
        putenv('MAIL_FROM_NAME=Test Sender');
        putenv('APP_NAME=Test App');
        
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

    public function testMailTemplateIntegrationWithMailClass()
    {
        $mail = TestMailWithTemplate::make();
        $mail->dispatch([
            'to' => 'user@example.com',
            'subject' => 'Welcome to Our App',
            'url' => 'https://example.com/start',
        ]);

        $sentMails = Mail::getSentMails();
        
        $this->assertCount(1, $sentMails);
        $this->assertEquals('user@example.com', $sentMails[0]['to'][0]['email']);
        $this->assertEquals('Welcome to Our App', $sentMails[0]['subject']);
        
        // Check HTML body contains template content
        $this->assertStringContainsString('Welcome!', $sentMails[0]['html_body']);
        $this->assertStringContainsString('Thanks for signing up', $sentMails[0]['html_body']);
        $this->assertStringContainsString('Get Started', $sentMails[0]['html_body']);
        $this->assertStringContainsString('https://example.com/start', $sentMails[0]['html_body']);
        
        // Check plain text body was auto-generated
        $this->assertNotEmpty($sentMails[0]['text_body']);
        $this->assertStringContainsString('WELCOME', $sentMails[0]['text_body']);
        $this->assertStringContainsString('Thanks for signing up', $sentMails[0]['text_body']);
        $this->assertStringContainsString('Get Started: https://example.com/start', $sentMails[0]['text_body']);
    }

    public function testTemplateMethodSetsHtmlAndTextBodies()
    {
        $template = new MailTemplate();
        $template
            ->heading('Test Heading')
            ->paragraph('Test paragraph')
            ->button('Click', 'https://example.com');
        
        $mail = TestMailWithTemplate::make();
        $mail->to('user@example.com')
            ->subject('Test')
            ->template($template)
            ->send();

        $sentMails = Mail::getSentMails();
        
        // HTML body should contain template HTML
        $this->assertStringContainsString('<h1', $sentMails[0]['html_body']);
        $this->assertStringContainsString('Test Heading', $sentMails[0]['html_body']);
        
        // Text body should contain plain text version
        $this->assertStringContainsString('TEST HEADING', $sentMails[0]['text_body']);
        $this->assertStringContainsString('Test paragraph', $sentMails[0]['text_body']);
    }

    public function testTemplateWithoutLayout()
    {
        $template = new MailTemplate();
        $template
            ->paragraph('Simple message')
            ->withoutLayout();
        
        $mail = TestMailWithTemplate::make();
        $mail->to('user@example.com')
            ->subject('Test')
            ->template($template)
            ->send();

        $sentMails = Mail::getSentMails();
        
        // Should not contain full HTML document structure
        $this->assertStringNotContainsString('<!DOCTYPE html>', $sentMails[0]['html_body']);
        $this->assertStringContainsString('<p', $sentMails[0]['html_body']);
        $this->assertStringContainsString('Simple message', $sentMails[0]['html_body']);
    }

    public function testTemplateWithCustomColors()
    {
        $template = new MailTemplate([
            'colors' => [
                'primary' => '#FF5733',
            ],
        ]);
        
        $template->button('Custom Button', 'https://example.com', 'primary');
        
        $mail = TestMailWithTemplate::make();
        $mail->to('user@example.com')
            ->subject('Test')
            ->template($template)
            ->send();

        $sentMails = Mail::getSentMails();
        
        $this->assertStringContainsString('#FF5733', $sentMails[0]['html_body']);
    }

    public function testComplexEmailTemplate()
    {
        $template = new MailTemplate();
        $template
            ->heading('Order Confirmation', 1)
            ->paragraph('Thank you for your order!')
            ->divider()
            ->heading('Order Details', 2)
            ->keyValueTable([
                'Order ID' => '#12345',
                'Date' => '2024-01-01',
                'Total' => '$99.99',
            ])
            ->divider()
            ->heading('Items', 2)
            ->bulletList([
                'Product A - $49.99',
                'Product B - $49.99',
            ])
            ->divider()
            ->alert('Your order will ship within 2-3 business days.', 'info')
            ->button('Track Order', 'https://example.com/track/12345', 'primary');
        
        $mail = TestMailWithTemplate::make();
        $mail->to('customer@example.com')
            ->subject('Order Confirmation #12345')
            ->template($template)
            ->send();

        $sentMails = Mail::getSentMails();
        
        // Verify all components are present
        $html = $sentMails[0]['html_body'];
        $this->assertStringContainsString('Order Confirmation', $html);
        $this->assertStringContainsString('Order Details', $html);
        $this->assertStringContainsString('#12345', $html);
        $this->assertStringContainsString('Product A', $html);
        $this->assertStringContainsString('Track Order', $html);
        
        // Verify plain text version
        $text = $sentMails[0]['text_body'];
        $this->assertStringContainsString('ORDER CONFIRMATION', $text);
        $this->assertStringContainsString('Order ID: #12345', $text);
        $this->assertStringContainsString('• Product A', $text);
        $this->assertStringContainsString('Track Order: https://example.com/track/12345', $text);
    }
}
