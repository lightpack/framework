<?php

namespace Lightpack\Tests\Mail;

use Lightpack\Mail\Mail;
use Lightpack\Mail\MailTemplate;
use PHPUnit\Framework\TestCase;

class TestTemplatedMail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $this->to($payload['to'] ?? 'test@example.com')
            ->subject($payload['subject'] ?? 'Test Subject')
            ->template($payload['template'] ?? 'welcome', $payload['data'] ?? [])
            ->send();
    }
    
    public static function make(): self
    {
        return new self(app('mail'));
    }
}

class MailTemplateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        putenv('MAIL_DRIVER=array');
        putenv('MAIL_FROM_ADDRESS=sender@example.com');
        putenv('MAIL_FROM_NAME=Test Sender');
        putenv('APP_NAME=Test App');
        putenv('APP_URL=https://example.com');
        
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

    public function testWelcomeTemplate()
    {
        $mail = TestTemplatedMail::make();
        $mail->dispatch([
            'to' => 'user@example.com',
            'subject' => 'Welcome!',
            'template' => 'welcome',
            'data' => [
                'name' => 'John Doe',
                'action_url' => 'https://example.com/start',
                'action_text' => 'Get Started Now',
            ]
        ]);

        $sentMails = Mail::getSentMails();
        $this->assertCount(1, $sentMails);
        
        $mail = $sentMails[0];
        $this->assertEquals('Welcome!', $mail['subject']);
        $this->assertStringContainsString('Welcome to Test App', $mail['html_body']);
        $this->assertStringContainsString('John Doe', $mail['html_body']);
        $this->assertStringContainsString('Get Started Now', $mail['html_body']);
        $this->assertStringContainsString('https://example.com/start', $mail['html_body']);
        
        // Verify plain text was auto-generated
        $this->assertNotEmpty($mail['text_body']);
        $this->assertStringContainsString('John Doe', $mail['text_body']);
    }

    public function testPasswordResetTemplate()
    {
        $mail = TestTemplatedMail::make();
        $mail->dispatch([
            'to' => 'user@example.com',
            'subject' => 'Reset Your Password',
            'template' => 'passwordReset',
            'data' => [
                'name' => 'Jane Smith',
                'reset_url' => 'https://example.com/reset/token123',
                'expires_in' => '30 minutes',
            ]
        ]);

        $sentMails = Mail::getSentMails();
        $mail = $sentMails[0];
        
        $this->assertStringContainsString('Reset Your Password', $mail['html_body']);
        $this->assertStringContainsString('Jane Smith', $mail['html_body']);
        $this->assertStringContainsString('https://example.com/reset/token123', $mail['html_body']);
        $this->assertStringContainsString('30 minutes', $mail['html_body']);
    }

    public function testVerifyEmailTemplate()
    {
        $mail = TestTemplatedMail::make();
        $mail->dispatch([
            'to' => 'user@example.com',
            'subject' => 'Verify Your Email',
            'template' => 'verifyEmail',
            'data' => [
                'name' => 'Bob Johnson',
                'verify_url' => 'https://example.com/verify/abc123',
            ]
        ]);

        $sentMails = Mail::getSentMails();
        $mail = $sentMails[0];
        
        $this->assertStringContainsString('Verify Your Email Address', $mail['html_body']);
        $this->assertStringContainsString('Bob Johnson', $mail['html_body']);
        $this->assertStringContainsString('https://example.com/verify/abc123', $mail['html_body']);
    }

    public function testNotificationTemplate()
    {
        $mail = TestTemplatedMail::make();
        $mail->dispatch([
            'to' => 'user@example.com',
            'subject' => 'New Notification',
            'template' => 'notification',
            'data' => [
                'title' => 'Important Update',
                'message' => 'Your account settings have been updated successfully.',
                'action_url' => 'https://example.com/settings',
                'action_text' => 'View Settings',
            ]
        ]);

        $sentMails = Mail::getSentMails();
        $mail = $sentMails[0];
        
        $this->assertStringContainsString('Important Update', $mail['html_body']);
        $this->assertStringContainsString('Your account settings have been updated', $mail['html_body']);
        $this->assertStringContainsString('View Settings', $mail['html_body']);
    }

    public function testInvoiceTemplate()
    {
        $mail = TestTemplatedMail::make();
        $mail->dispatch([
            'to' => 'customer@example.com',
            'subject' => 'Your Invoice',
            'template' => 'invoice',
            'data' => [
                'name' => 'Alice Cooper',
                'invoice_number' => 'INV-2024-001',
                'date' => 'January 15, 2024',
                'items' => [
                    ['name' => 'Product A', 'amount' => '$50.00'],
                    ['name' => 'Product B', 'amount' => '$75.00'],
                    ['name' => 'Shipping', 'amount' => '$10.00'],
                ],
                'total' => '$135.00',
            ]
        ]);

        $sentMails = Mail::getSentMails();
        $mail = $sentMails[0];
        
        $this->assertStringContainsString('Invoice #INV-2024-001', $mail['html_body']);
        $this->assertStringContainsString('Alice Cooper', $mail['html_body']);
        $this->assertStringContainsString('Product A', $mail['html_body']);
        $this->assertStringContainsString('$50.00', $mail['html_body']);
        $this->assertStringContainsString('$135.00', $mail['html_body']);
    }

    public function testOrderConfirmationTemplate()
    {
        $mail = TestTemplatedMail::make();
        $mail->dispatch([
            'to' => 'customer@example.com',
            'subject' => 'Order Confirmed',
            'template' => 'orderConfirmation',
            'data' => [
                'name' => 'Charlie Brown',
                'order_number' => 'ORD-12345',
                'items' => [
                    ['name' => 'Widget', 'quantity' => 2],
                    ['name' => 'Gadget', 'quantity' => 1],
                ],
                'tracking_url' => 'https://example.com/track/12345',
            ]
        ]);

        $sentMails = Mail::getSentMails();
        $mail = $sentMails[0];
        
        $this->assertStringContainsString('Order Confirmed', $mail['html_body']);
        $this->assertStringContainsString('Charlie Brown', $mail['html_body']);
        $this->assertStringContainsString('ORD-12345', $mail['html_body']);
        $this->assertStringContainsString('Widget (x2)', $mail['html_body']);
        $this->assertStringContainsString('Track Your Order', $mail['html_body']);
    }

    public function testAccountAlertTemplate()
    {
        $mail = TestTemplatedMail::make();
        $mail->dispatch([
            'to' => 'user@example.com',
            'subject' => 'Security Alert',
            'template' => 'accountAlert',
            'data' => [
                'alert_type' => 'warning',
                'title' => 'Unusual Login Detected',
                'message' => 'We detected a login from a new device in New York, USA.',
                'action_url' => 'https://example.com/security',
                'action_text' => 'Review Activity',
            ]
        ]);

        $sentMails = Mail::getSentMails();
        $mail = $sentMails[0];
        
        $this->assertStringContainsString('Unusual Login Detected', $mail['html_body']);
        $this->assertStringContainsString('new device in New York', $mail['html_body']);
        $this->assertStringContainsString('Review Activity', $mail['html_body']);
    }

    public function testTeamInvitationTemplate()
    {
        $mail = TestTemplatedMail::make();
        $mail->dispatch([
            'to' => 'newmember@example.com',
            'subject' => 'Team Invitation',
            'template' => 'teamInvitation',
            'data' => [
                'inviter_name' => 'Sarah Johnson',
                'team_name' => 'Acme Corp',
                'accept_url' => 'https://example.com/invite/xyz789',
                'expires_in' => '7 days',
            ]
        ]);

        $sentMails = Mail::getSentMails();
        $mail = $sentMails[0];
        
        $this->assertStringContainsString('You\'ve Been Invited', $mail['html_body']);
        $this->assertStringContainsString('Sarah Johnson', $mail['html_body']);
        $this->assertStringContainsString('Acme Corp', $mail['html_body']);
        $this->assertStringContainsString('Accept Invitation', $mail['html_body']);
    }

    public function testAutoPlainTextGeneration()
    {
        $mail = TestTemplatedMail::make();
        $mail->dispatch([
            'to' => 'user@example.com',
            'subject' => 'Test',
            'template' => 'welcome',
            'data' => ['name' => 'Test User']
        ]);

        $sentMails = Mail::getSentMails();
        $mail = $sentMails[0];
        
        // Verify plain text was generated
        $this->assertNotEmpty($mail['text_body']);
        
        // Verify it doesn't contain HTML tags
        $this->assertStringNotContainsString('<html>', $mail['text_body']);
        $this->assertStringNotContainsString('<table>', $mail['text_body']);
        $this->assertStringNotContainsString('<div>', $mail['text_body']);
        
        // Verify it contains the content
        $this->assertStringContainsString('Test User', $mail['text_body']);
    }

    public function testCustomColors()
    {
        $template = new MailTemplate([
            'colors' => [
                'primary' => '#FF0000',
            ]
        ]);
        
        $mail = TestTemplatedMail::make();
        $mail->setMailTemplate($template)
            ->to('user@example.com')
            ->subject('Custom Colors')
            ->template('welcome', ['name' => 'Test'])
            ->send();

        $sentMails = Mail::getSentMails();
        $mail = $sentMails[0];
        
        // Verify custom color is used
        $this->assertStringContainsString('#FF0000', $mail['html_body']);
    }

    public function testDisableAutoPlainText()
    {
        $mail = TestTemplatedMail::make();
        $mail->to('user@example.com')
            ->subject('Test')
            ->template('welcome', ['name' => 'Test'])
            ->disableAutoPlainText()
            ->send();

        $sentMails = Mail::getSentMails();
        $mail = $sentMails[0];
        
        // Plain text should be empty when disabled
        $this->assertEmpty($mail['text_body']);
    }

    public function testManualPlainTextOverridesAuto()
    {
        $mail = TestTemplatedMail::make();
        $mail->to('user@example.com')
            ->subject('Test')
            ->template('welcome', ['name' => 'Test'])
            ->altBody('Custom plain text')
            ->send();

        $sentMails = Mail::getSentMails();
        $mail = $sentMails[0];
        
        // Should use manual plain text, not auto-generated
        $this->assertEquals('Custom plain text', $mail['text_body']);
    }

    public function testTemplateWithFooterLinks()
    {
        $mail = TestTemplatedMail::make();
        $mail->to('user@example.com')
            ->subject('Test')
            ->template('welcome', [
                'name' => 'Test',
                'footer_links' => [
                    'Privacy Policy' => 'https://example.com/privacy',
                    'Terms of Service' => 'https://example.com/terms',
                    'Contact Us' => 'https://example.com/contact',
                ]
            ])
            ->send();

        $sentMails = Mail::getSentMails();
        $mail = $sentMails[0];
        
        $this->assertStringContainsString('Privacy Policy', $mail['html_body']);
        $this->assertStringContainsString('Terms of Service', $mail['html_body']);
        $this->assertStringContainsString('Contact Us', $mail['html_body']);
        $this->assertStringContainsString('https://example.com/privacy', $mail['html_body']);
    }

    public function testMailTemplateComponents()
    {
        $template = new MailTemplate();
        
        // Test button component
        $button = $template->button('Click Me', 'https://example.com', 'primary');
        $this->assertStringContainsString('Click Me', $button);
        $this->assertStringContainsString('https://example.com', $button);
        
        // Test heading component
        $heading = $template->heading('Test Heading', 1);
        $this->assertStringContainsString('Test Heading', $heading);
        $this->assertStringContainsString('<h1', $heading);
        
        // Test paragraph component
        $paragraph = $template->paragraph('Test paragraph text');
        $this->assertStringContainsString('Test paragraph text', $paragraph);
        
        // Test alert component
        $alert = $template->alert('Warning message', 'warning');
        $this->assertStringContainsString('Warning message', $alert);
        
        // Test code component
        $code = $template->code('echo "Hello World";');
        $this->assertStringContainsString('echo "Hello World";', $code);
        
        // Test bullet list component
        $list = $template->bulletList(['Item 1', 'Item 2', 'Item 3']);
        $this->assertStringContainsString('Item 1', $list);
        $this->assertStringContainsString('Item 2', $list);
        
        // Test key-value table component
        $table = $template->keyValueTable(['Name' => 'John', 'Email' => 'john@example.com']);
        $this->assertStringContainsString('Name', $table);
        $this->assertStringContainsString('John', $table);
    }

    public function testPlainTextConversion()
    {
        $template = new MailTemplate();
        
        $html = '<h1>Heading</h1><p>Paragraph text</p><a href="https://example.com">Link</a>';
        $plainText = $template->toPlainText($html);
        
        $this->assertStringContainsString('Heading', $plainText);
        $this->assertStringContainsString('Paragraph text', $plainText);
        $this->assertStringContainsString('Link (https://example.com)', $plainText);
        $this->assertStringNotContainsString('<h1>', $plainText);
        $this->assertStringNotContainsString('<p>', $plainText);
    }

    public function testHtmlStructureIsEmailSafe()
    {
        $mail = TestTemplatedMail::make();
        $mail->dispatch([
            'to' => 'user@example.com',
            'subject' => 'Test',
            'template' => 'welcome',
            'data' => ['name' => 'Test']
        ]);

        $sentMails = Mail::getSentMails();
        $html = $sentMails[0]['html_body'];
        
        // Verify table-based layout (email-safe)
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('role="presentation"', $html);
        
        // Verify inline styles (email-safe)
        $this->assertStringContainsString('style=', $html);
        
        // Verify proper DOCTYPE
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        
        // Verify Outlook compatibility
        $this->assertStringContainsString('xmlns:v="urn:schemas-microsoft-com:vml"', $html);
        $this->assertStringContainsString('xmlns:o="urn:schemas-microsoft-com:office:office"', $html);
    }
}
