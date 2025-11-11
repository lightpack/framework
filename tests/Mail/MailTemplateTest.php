<?php

namespace Lightpack\Tests\Mail;

use Lightpack\Mail\MailTemplate;
use PHPUnit\Framework\TestCase;

class MailTemplateTest extends TestCase
{
    public function testFluentInterface()
    {
        $template = new MailTemplate();
        
        $result = $template
            ->heading('Welcome')
            ->paragraph('Hello world')
            ->button('Click', 'https://example.com')
            ->divider()
            ->alert('Info message', 'info');
        
        $this->assertInstanceOf(MailTemplate::class, $result);
    }

    public function testHeadingComponent()
    {
        $template = new MailTemplate();
        $template->heading('Test Heading', 1);
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('Test Heading', $html);
    }

    public function testParagraphComponent()
    {
        $template = new MailTemplate();
        $template->paragraph('Test paragraph text');
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('<p', $html);
        $this->assertStringContainsString('Test paragraph text', $html);
    }

    public function testButtonComponent()
    {
        $template = new MailTemplate();
        $template->button('Click Me', 'https://example.com', 'primary');
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('Click Me', $html);
        $this->assertStringContainsString('https://example.com', $html);
        $this->assertStringContainsString('<a href', $html);
    }

    public function testDividerComponent()
    {
        $template = new MailTemplate();
        $template->divider();
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('<hr', $html);
    }

    public function testAlertComponent()
    {
        $template = new MailTemplate();
        $template->alert('Warning message', 'warning');
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('Warning message', $html);
    }

    public function testCodeComponent()
    {
        $template = new MailTemplate();
        $template->code('<?php echo "Hello";');
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('<pre', $html);
        $this->assertStringContainsString('&lt;?php echo &quot;Hello&quot;;', $html);
    }

    public function testBulletListComponent()
    {
        $template = new MailTemplate();
        $template->bulletList(['Item 1', 'Item 2', 'Item 3']);
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('Item 1', $html);
        $this->assertStringContainsString('Item 2', $html);
        $this->assertStringContainsString('Item 3', $html);
        $this->assertStringContainsString('•', $html);
    }

    public function testKeyValueTableComponent()
    {
        $template = new MailTemplate();
        $template->keyValueTable([
            'Name' => 'John Doe',
            'Email' => 'john@example.com',
        ]);
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('Name', $html);
        $this->assertStringContainsString('John Doe', $html);
        $this->assertStringContainsString('Email', $html);
        $this->assertStringContainsString('john@example.com', $html);
    }

    public function testDefaultLayoutIsApplied()
    {
        $template = new MailTemplate();
        $template->paragraph('Test');
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('</html>', $html);
    }

    public function testWithoutLayout()
    {
        $template = new MailTemplate();
        $template->paragraph('Test')->withoutLayout();
        
        $html = $template->toHtml();
        
        $this->assertStringNotContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<p', $html);
    }

    public function testPlainTextGeneration()
    {
        $template = new MailTemplate();
        $template
            ->heading('Welcome')
            ->paragraph('Hello world')
            ->button('Click', 'https://example.com');
        
        $text = $template->toPlainText();
        
        $this->assertStringContainsString('WELCOME', $text);
        $this->assertStringContainsString('Hello world', $text);
        $this->assertStringContainsString('Click: https://example.com', $text);
    }

    public function testPlainTextWithDivider()
    {
        $template = new MailTemplate();
        $template->divider();
        
        $text = $template->toPlainText();
        
        $this->assertStringContainsString('--------------------------------------------------', $text);
    }

    public function testPlainTextWithAlert()
    {
        $template = new MailTemplate();
        $template->alert('Important message', 'danger');
        
        $text = $template->toPlainText();
        
        $this->assertStringContainsString('[DANGER]', $text);
        $this->assertStringContainsString('Important message', $text);
    }

    public function testPlainTextWithBulletList()
    {
        $template = new MailTemplate();
        $template->bulletList(['Item 1', 'Item 2']);
        
        $text = $template->toPlainText();
        
        $this->assertStringContainsString('• Item 1', $text);
        $this->assertStringContainsString('• Item 2', $text);
    }

    public function testPlainTextWithKeyValueTable()
    {
        $template = new MailTemplate();
        $template->keyValueTable(['Name' => 'John', 'Age' => '30']);
        
        $text = $template->toPlainText();
        
        $this->assertStringContainsString('Name: John', $text);
        $this->assertStringContainsString('Age: 30', $text);
    }

    public function testXssProtectionInHeading()
    {
        $template = new MailTemplate();
        $template->heading('<script>alert("xss")</script>');
        
        $html = $template->toHtml();
        
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testXssProtectionInParagraph()
    {
        $template = new MailTemplate();
        $template->paragraph('<img src=x onerror=alert(1)>');
        
        $html = $template->toHtml();
        
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
    }

    public function testXssProtectionInButton()
    {
        $template = new MailTemplate();
        $template->button('<script>alert(1)</script>', 'https://example.com');
        
        $html = $template->toHtml();
        
        // Button text should be escaped
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testCustomColors()
    {
        $template = new MailTemplate([
            'colors' => [
                'primary' => '#FF0000',
            ],
        ]);
        
        $template->button('Click', 'https://example.com', 'primary');
        $html = $template->toHtml();
        
        // Custom primary color should be in the button
        $this->assertStringContainsString('#FF0000', $html);
    }

    public function testSetColors()
    {
        $template = new MailTemplate();
        $template->setColors(['primary' => '#00FF00']);
        $template->button('Click', 'https://example.com', 'primary');
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('#00FF00', $html);
    }

    public function testSetData()
    {
        $template = new MailTemplate();
        $template->setData(['app_name' => 'My App']);
        $template->paragraph('Test');
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('My App', $html);
    }

    public function testRenderWithData()
    {
        $template = new MailTemplate();
        $template->paragraph('Test');
        
        $html = $template->render(['app_name' => 'Custom App']);
        
        $this->assertStringContainsString('Custom App', $html);
    }

    public function testMultipleComponents()
    {
        $template = new MailTemplate();
        $template
            ->heading('Welcome', 1)
            ->paragraph('First paragraph')
            ->paragraph('Second paragraph')
            ->button('Action', 'https://example.com')
            ->divider()
            ->alert('Note', 'info')
            ->code('code snippet')
            ->bulletList(['A', 'B', 'C'])
            ->keyValueTable(['Key' => 'Value']);
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('Welcome', $html);
        $this->assertStringContainsString('First paragraph', $html);
        $this->assertStringContainsString('Second paragraph', $html);
        $this->assertStringContainsString('Action', $html);
        $this->assertStringContainsString('Note', $html);
        $this->assertStringContainsString('code snippet', $html);
        $this->assertStringContainsString('A', $html);
        $this->assertStringContainsString('Key', $html);
    }

    public function testHeadingLevelClamping()
    {
        $template = new MailTemplate();
        $template->heading('Test', 10); // Should clamp to 3
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('<h3', $html);
    }

    public function testEmailClientCompatibility()
    {
        $template = new MailTemplate();
        $template->button('Click', 'https://example.com');
        
        $html = $template->toHtml();
        
        // Check for email-safe patterns
        $this->assertStringContainsString('role="presentation"', $html);
        $this->assertStringContainsString('cellspacing="0"', $html);
        $this->assertStringContainsString('cellpadding="0"', $html);
        $this->assertStringContainsString('border="0"', $html);
    }

    public function testOutlookCompatibility()
    {
        $template = new MailTemplate();
        $template->paragraph('Test');
        
        $html = $template->toHtml();
        
        // Check for Outlook-specific namespaces
        $this->assertStringContainsString('xmlns:v="urn:schemas-microsoft-com:vml"', $html);
        $this->assertStringContainsString('xmlns:o="urn:schemas-microsoft-com:office:office"', $html);
        $this->assertStringContainsString('<!--[if mso]>', $html);
    }

    public function testLinkComponent()
    {
        $template = new MailTemplate();
        $template->link('https://example.com/very/long/url');
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('<a href="https://example.com/very/long/url"', $html);
        $this->assertStringContainsString('word-break: break-all', $html);
    }

    public function testLinkWithCustomText()
    {
        $template = new MailTemplate();
        $template->link('https://example.com', 'Click here');
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('Click here', $html);
        $this->assertStringContainsString('https://example.com', $html);
    }

    public function testLogoInHeader()
    {
        $template = new MailTemplate();
        $template->logo('https://example.com/logo.png', 100);
        $template->paragraph('Content');
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('<img src="https://example.com/logo.png"', $html);
        $this->assertStringContainsString('width: 100px', $html);
    }

    public function testFooterText()
    {
        $template = new MailTemplate();
        $template->paragraph('Content');
        $template->footer('&copy; 2025 Test App');
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('&copy; 2025 Test App', $html);
    }

    public function testFooterLinks()
    {
        $template = new MailTemplate();
        $template->paragraph('Content');
        $template->footerLinks([
            'Privacy' => 'https://example.com/privacy',
            'Terms' => 'https://example.com/terms',
        ]);
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('Privacy', $html);
        $this->assertStringContainsString('Terms', $html);
        $this->assertStringContainsString('https://example.com/privacy', $html);
        $this->assertStringContainsString('https://example.com/terms', $html);
    }

    public function testFooterTextAndLinks()
    {
        $template = new MailTemplate();
        $template->paragraph('Content');
        $template->footer('&copy; 2025 Test');
        $template->footerLinks(['Privacy' => 'https://example.com/privacy']);
        
        $html = $template->toHtml();
        
        $this->assertStringContainsString('&copy; 2025 Test', $html);
        $this->assertStringContainsString('Privacy', $html);
    }

    public function testNoFooterWhenNotProvided()
    {
        $template = new MailTemplate();
        $template->paragraph('Content only');
        
        $html = $template->toHtml();
        
        // Should not have footer section when no footer data provided
        $plainText = $template->toPlainText();
        $this->assertEquals("Content only", $plainText);
    }
}
