# Email Templates

Lightpack provides a powerful `MailTemplate` class for building beautiful, responsive emails programmatically. It handles all the complexity of email client compatibility while giving you a clean, fluent API.

## Why MailTemplate?

Building HTML emails is notoriously difficult:
- **Email clients are inconsistent** - What works in Gmail might break in Outlook
- **Inline styles required** - External CSS doesn't work in most email clients
- **Table-based layouts** - Modern CSS flexbox/grid aren't supported
- **Plain text versions** - Many users prefer plain text emails

`MailTemplate` solves all of this:
- ✅ **Email client compatible** - Works in Gmail, Outlook, Apple Mail, etc.
- ✅ **Automatic plain text** - Generates readable plain text from your components
- ✅ **Beautiful default layout** - Professional design out of the box
- ✅ **Programmatic building** - Type-safe, IDE-friendly fluent interface
- ✅ **XSS protection** - Automatic HTML escaping
- ✅ **Customizable** - Override colors, fonts, spacing, and layouts

## Quick Start

### Basic Example (Minimal - No Header/Footer)

```php
use Lightpack\Mail\Mail;
use Lightpack\Mail\MailTemplate;

class WelcomeMail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $template = new MailTemplate();
        
        $template
            ->heading('Welcome!')
            ->paragraph('Thanks for signing up! We\'re excited to have you on board.')
            ->button('Get Started', $payload['actionUrl'], 'primary')
            ->divider()
            ->paragraph('If you have any questions, feel free to reply to this email.');
        
        $this->to($payload['email'])
            ->subject('Welcome!')
            ->template($template)  // Sets both HTML and plain text
            ->send();
    }
}
```

### With Logo and Footer

```php
class WelcomeMail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $template = new MailTemplate();
        
        // Optional: Add logo in header (outside content box)
        $template->logo('https://yourapp.com/logo.png', 120);
        
        $template
            ->heading('Welcome!')
            ->paragraph('Thanks for signing up!')
            ->button('Get Started', $payload['actionUrl']);
        
        // Optional: Add footer (outside content box)
        $template
            ->footer('&copy; 2025 YourApp. All rights reserved.')
            ->footerLinks([
                'Privacy Policy' => 'https://yourapp.com/privacy',
                'Terms of Service' => 'https://yourapp.com/terms',
            ]);
        
        $this->to($payload['email'])
            ->subject('Welcome!')
            ->template($template)
            ->send();
    }
}
```

### Usage

```php
$mail = new WelcomeMail(app('mail'));
$mail->dispatch([
    'email' => 'user@example.com',
    'actionUrl' => 'https://example.com/dashboard',
]);
```

## Available Components

### Heading

```php
$template->heading('Welcome!', 1);  // H1
$template->heading('Section Title', 2);  // H2
$template->heading('Subsection', 3);  // H3
```

### Paragraph

```php
$template->paragraph('This is a paragraph of text.');
```

### Button

```php
$template->button('Click Me', 'https://example.com', 'primary');

// Available colors: primary, secondary, success, danger, warning, info
$template->button('Delete', 'https://example.com/delete', 'danger');
```

### Link

Display a clickable URL with proper word-breaking (prevents horizontal scroll):

```php
// Show URL as clickable link
$template->link('https://example.com/very/long/url/that/might/cause/scroll');

// Show custom text for the link
$template->link('https://example.com/verify?token=abc123', 'Click here to verify');
```

**Use `link()` instead of `paragraph()` for URLs** to prevent horizontal scrolling on long URLs.

### HTML Content (with Entities)

For content with HTML entities like `&copy;`, `&trade;`, `&reg;`:

```php
// HTML entities are preserved (NOT escaped)
$template->html('&copy; 2025 MyApp. All rights reserved.');
$template->html('MyApp&trade; is a registered trademark.');
```

**⚠️ Security Warning:** The `html()` method does NOT escape content. Only use it for:
- HTML entities (`&copy;`, `&trade;`, `&reg;`, `&nbsp;`, etc.)
- Trusted content you control
- **NEVER** use it for user-generated content

For regular text, use `paragraph()` which provides XSS protection.

### Divider

```php
$template->divider();
```

### Alert Box

```php
$template->alert('Important message', 'info');

// Available types: info, success, warning, danger
$template->alert('Account verified!', 'success');
$template->alert('Payment failed', 'danger');
```

### Code Block

```php
$template->code('<?php echo "Hello World";');
```

### Bullet List

```php
$template->bulletList([
    'First item',
    'Second item',
    'Third item',
]);
```

### Key-Value Table

Simple two-column table with alternating row colors:

```php
$template->keyValueTable([
    'Order ID' => '#12345',
    'Date' => '2025-01-01',
    'Total' => '$100.00',
]);
```

### Multi-Column Table

For complex data with multiple columns and headers:

```php
$template->table(
    ['Name', 'Email', 'Status', 'Joined'],  // Headers
    [
        ['John Doe', 'john@example.com', 'Active', '2025-01-01'],
        ['Jane Smith', 'jane@example.com', 'Inactive', '2024-12-15'],
        ['Bob Johnson', 'bob@example.com', 'Active', '2025-01-10'],
    ]
);
```

**Features:**
- ✅ Colored header row (uses primary color)
- ✅ Alternating row colors for readability
- ✅ Automatic XSS protection
- ✅ Responsive on mobile

## Fluent Interface

All component methods return `$this`, allowing you to chain them:

```php
$template
    ->heading('Order Confirmation')
    ->paragraph('Thank you for your order!')
    ->divider()
    ->keyValueTable(['Order ID' => '#12345'])
    ->button('View Order', 'https://example.com/orders/12345');
```

## Layout Architecture

### Three-Layer Structure

Emails have a clean three-layer structure:

```
┌─────────────────────────────────┐
│  Gray Background (body padding) │
│                                  │
│  ┌───────────────────────────┐  │
│  │  Logo (optional)          │  │ ← Outside content box
│  └───────────────────────────┘  │
│                                  │
│  ┌───────────────────────────┐  │
│  │ ┏━━━━━━━━━━━━━━━━━━━━━━┓ │  │
│  │ ┃ White Box (content)  ┃ │  │ ← Your components
│  │ ┃ • Heading            ┃ │  │
│  │ ┃ • Paragraph          ┃ │  │
│  │ ┃ • Button             ┃ │  │
│  │ ┗━━━━━━━━━━━━━━━━━━━━━━┛ │  │
│  └───────────────────────────┘  │
│                                  │
│  ┌───────────────────────────┐  │
│  │  Footer (optional)        │  │ ← Outside content box
│  └───────────────────────────┘  │
└─────────────────────────────────┘
```

**Key Points:**
- ✅ **Header and footer are optional** - Only render if you explicitly set them
- ✅ **Header/footer outside content box** - They appear in the gray background area
- ✅ **Only content has white background** - Clean separation with border and border-radius
- ✅ **Nothing renders by default** - You control everything

### Adding Logo (Optional)

```php
// Logo appears above content box
$template->logo('https://yourapp.com/logo.png', 120);  // 120px width
```

### Adding Footer (Optional)

```php
// Footer text (supports HTML entities like &copy;)
$template->footer('&copy; 2025 YourApp. All rights reserved.');

// Footer links
$template->footerLinks([
    'Privacy' => 'https://yourapp.com/privacy',
    'Terms' => 'https://yourapp.com/terms',
]);

// Or both
$template
    ->footer('&copy; 2025 YourApp')
    ->footerLinks(['Privacy' => 'https://yourapp.com/privacy']);
```

### Without Layout

For embedding in other templates or custom layouts:

```php
$template
    ->heading('Hello')
    ->paragraph('World')
    ->withoutLayout();

$html = $template->toHtml();  // Just the components, no layout wrapper
```

## Customization

### Custom Colors

```php
$template = new MailTemplate([
    'colors' => [
        'primary' => '#FF5733',
        'success' => '#28A745',
    ],
]);

$template->button('Click', 'https://example.com', 'primary');
// Button will use #FF5733
```

Or set colors after instantiation:

```php
$template = new MailTemplate();
$template->setColors([
    'primary' => '#FF5733',
]);
```

### Custom Fonts

You can customize fonts via constructor or the `setFonts()` method:

```php
// Via constructor
$template = new MailTemplate([
    'fonts' => [
        'family' => 'Georgia, "Times New Roman", serif',
        'sizeBase' => '18px',
        'sizeH1' => '36px',
        'sizeH2' => '28px',
        'sizeH3' => '22px',
        'sizeSmall' => '13px',
    ],
]);

// Or after instantiation
$template = new MailTemplate();
$template->setFonts([
    'family' => 'Verdana, Geneva, sans-serif',
    'sizeBase' => '17px',
]);
```

**Email-Safe Fonts:**

Email clients have limited font support. Use these web-safe fonts:

**Sans-serif (recommended):**
- `Arial, Helvetica, sans-serif` - Universal, clean
- `Verdana, Geneva, sans-serif` - Wider, very readable
- `Tahoma, Geneva, sans-serif` - Compact, modern
- `"Trebuchet MS", Helvetica, sans-serif` - Friendly, rounded

**Serif:**
- `Georgia, "Times New Roman", serif` - Elegant, professional
- `"Times New Roman", Times, serif` - Classic, formal

**Monospace (for code blocks):**
- `"Courier New", Courier, monospace` - Default for code
- `Monaco, "Lucida Console", monospace` - Alternative

**System fonts (modern approach):**
```php
$template->setFonts([
    'family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
]);
```

> **Note:** The template automatically applies `font-family` to every element with `!important` to ensure consistent rendering across all email clients, including Outlook and MailTrap.

### Custom Spacing

```php
$template = new MailTemplate([
    'spacing' => [
        'md' => '20px',
        'lg' => '30px',
    ],
]);
```

### Template Data

Pass data for the layout (app name, footer links, etc.):

```php
$template->setData([
    'app_name' => 'My Custom App',
    'app_url' => 'https://myapp.com',
    'footer_links' => [
        'Privacy Policy' => 'https://myapp.com/privacy',
        'Terms' => 'https://myapp.com/terms',
    ],
]);
```

## Plain Text Generation

Plain text is automatically generated from your components:

```php
$template
    ->heading('Welcome')
    ->paragraph('Hello world')
    ->button('Click', 'https://example.com');

echo $template->toPlainText();
```

Output:
```
WELCOME
==================================================

Hello world

Click: https://example.com
```

## Complete Examples

### Order Confirmation Email

```php
class OrderConfirmationMail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $order = $payload['order'];
        
        $template = new MailTemplate();
        $template
            ->heading('Order Confirmation', 1)
            ->paragraph('Thank you for your order!')
            ->divider()
            ->heading('Order Details', 2)
            ->keyValueTable([
                'Order ID' => $order['id'],
                'Date' => $order['date'],
                'Total' => $order['total'],
            ])
            ->divider()
            ->heading('Items', 2)
            ->bulletList($order['items'])
            ->divider()
            ->alert('Your order will ship within 2-3 business days.', 'info')
            ->button('Track Order', $order['tracking_url'], 'primary');
        
        $this->to($order['customer_email'])
            ->subject('Order Confirmation #' . $order['id'])
            ->template($template)
            ->send();
    }
}
```

### Password Reset Email

```php
class PasswordResetMail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $template = new MailTemplate();
        
        // Optional: Add logo
        $template->logo('https://yourapp.com/logo.png', 100);
        
        $template
            ->heading('Reset Your Password')
            ->paragraph('You requested to reset your password. Click the button below to continue.')
            ->button('Reset Password', $payload['resetUrl'], 'primary')
            ->divider()
            ->alert('This link will expire in 60 minutes.', 'warning')
            ->paragraph('If you didn\'t request this, you can safely ignore this email.');
        
        // Optional: Add footer
        $template->footer('&copy; 2025 YourApp. All rights reserved.');
        
        $this->to($payload['email'])
            ->subject('Password Reset Request')
            ->template($template)
            ->send();
    }
}
```

### Email Verification with Long URL

```php
class VerifyEmailMail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $template = new MailTemplate();
        
        $template->logo(asset()->url('img/logo.svg'), 50);
        
        $template
            ->heading('Verify Your Email Address')
            ->paragraph('Please verify your email address by clicking the link below.')
            ->button('Verify Email', $payload['verification_url'], 'primary')
            ->divider()
            ->paragraph('If you are not able to click the link, copy and paste the URL into your browser.')
            ->link($payload['verification_url'])  // Use link() for long URLs
            ->divider()
            ->paragraph('If you did not create an account, please ignore this email.');
        
        $template
            ->footer('&copy; 2025 AuthKit. All rights reserved.')
            ->footerLinks([
                'Privacy' => 'https://authkit.com/privacy',
                'Terms' => 'https://authkit.com/terms',
            ]);
        
        $this->to($payload['email'])
            ->subject('Verify Your Email Address')
            ->template($template)
            ->send();
    }
}
```

### Weekly Digest Email

```php
class WeeklyDigestMail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $template = new MailTemplate();
        $template
            ->heading('Your Weekly Digest', 1)
            ->paragraph('Here\'s what happened this week:')
            ->divider();
        
        foreach ($payload['sections'] as $section) {
            $template
                ->heading($section['title'], 2)
                ->paragraph($section['description'])
                ->bulletList($section['items'])
                ->divider();
        }
        
        $template
            ->button('View Dashboard', $payload['dashboardUrl'], 'primary')
            ->paragraph('Have a great week!');
        
        $this->to($payload['email'])
            ->subject('Your Weekly Digest')
            ->template($template)
            ->send();
    }
}
```

### Invoice Email

```php
class InvoiceMail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $invoice = $payload['invoice'];
        
        $template = new MailTemplate();
        $template
            ->heading('Invoice #' . $invoice['number'], 1)
            ->keyValueTable([
                'Invoice Number' => $invoice['number'],
                'Date' => $invoice['date'],
                'Due Date' => $invoice['due_date'],
            ])
            ->divider()
            ->heading('Line Items', 2)
            ->keyValueTable($invoice['line_items'])
            ->divider()
            ->keyValueTable([
                'Subtotal' => $invoice['subtotal'],
                'Tax' => $invoice['tax'],
                'Total' => $invoice['total'],
            ])
            ->divider()
            ->button('Pay Invoice', $invoice['payment_url'], 'success')
            ->paragraph('Thank you for your business!');
        
        $this->to($invoice['customer_email'])
            ->subject('Invoice #' . $invoice['number'])
            ->template($template)
            ->send();
    }
}
```

## Security

All user input is automatically escaped to prevent XSS attacks:

```php
$template->heading('<script>alert("xss")</script>');
// Output: &lt;script&gt;alert("xss")&lt;/script&gt;
```

This applies to all components:
- Headings
- Paragraphs
- Button text and URLs
- Alert messages
- Code blocks
- List items
- Table keys and values

## Email Client Compatibility

`MailTemplate` generates HTML that works across all major email clients:

- ✅ Gmail (web, iOS, Android)
- ✅ Outlook (2007, 2010, 2013, 2016, 2019, 365)
- ✅ Apple Mail (macOS, iOS)
- ✅ Yahoo Mail
- ✅ AOL Mail
- ✅ Thunderbird
- ✅ Samsung Email
- ✅ Windows Mail

### Compatibility Features

- **Table-based layouts** - Works in all email clients
- **Inline styles** - No external CSS dependencies
- **Outlook conditional comments** - Special handling for Outlook
- **VML namespaces** - Proper Outlook rendering
- **role="presentation"** - Accessibility and compatibility
- **Responsive meta tags** - Mobile-friendly where supported

### Mobile Responsiveness

The template is fully responsive and mobile-optimized:

- **Fluid width** - Uses `max-width: 600px` instead of fixed width
- **Scales to fit** - No horizontal scrolling on mobile devices
- **Reduced padding** - Automatically adjusts padding on small screens (16px vs 32px)
- **Media queries** - CSS `@media` queries for mobile optimization
- **Touch-friendly** - Buttons and links are properly sized for touch

**Desktop:** 600px centered container with full padding  
**Mobile:** 100% width with reduced padding for better readability

The responsive behavior works on:
- ✅ iPhone (iOS Mail, Gmail app)
- ✅ Android (Gmail, Samsung Email)
- ✅ iPad and tablets
- ✅ Modern email clients that support media queries

> **Note:** Some older email clients (like Outlook 2007-2013) don't support media queries, but the email will still be readable with the base styles.

## Best Practices

### 1. Keep It Simple

Email clients have limited HTML/CSS support. Stick to the provided components:

```php
// ✅ Good
$template
    ->heading('Welcome')
    ->paragraph('Hello!')
    ->button('Start', 'https://example.com');

// ❌ Avoid custom HTML
$template->paragraph('<div class="custom">...</div>');
```

### 2. Test Across Clients

Always test your emails in multiple clients. Use services like:
- [Litmus](https://litmus.com/)
- [Email on Acid](https://www.emailonacid.com/)
- [Mail Tester](https://www.mail-tester.com/)

### 3. Provide Plain Text

Always use the `template()` method which automatically generates plain text:

```php
// ✅ Good - Automatic plain text
$this->template($template)->send();

// ❌ Avoid - No plain text fallback
$this->body($template->toHtml())->send();
```

### 4. Use Semantic Colors

Use color names that match intent:

```php
// ✅ Good
$template->button('Delete Account', $url, 'danger');
$template->alert('Success!', 'success');

// ❌ Confusing
$template->button('Delete Account', $url, 'success');
```

### 5. Keep Buttons Above the Fold

Place important CTAs early in the email:

```php
$template
    ->heading('Welcome!')
    ->paragraph('Quick intro...')
    ->button('Get Started', $url)  // ← Early CTA
    ->divider()
    ->paragraph('More details...');
```

## Advanced Usage

### Conditional Components

```php
$template->heading('Order Status');

if ($order['shipped']) {
    $template->alert('Your order has shipped!', 'success');
} else {
    $template->alert('Your order is being processed.', 'info');
}

$template->button('Track Order', $order['tracking_url']);
```

### Dynamic Lists

```php
$items = ['Item 1', 'Item 2', 'Item 3'];

if (!empty($items)) {
    $template
        ->heading('Your Items')
        ->bulletList($items);
}
```

### Reusable Templates

```php
class EmailTemplateBuilder
{
    public static function header(string $title): MailTemplate
    {
        $template = new MailTemplate();
        $template->heading($title, 1);
        return $template;
    }
    
    public static function footer(): MailTemplate
    {
        $template = new MailTemplate();
        $template
            ->divider()
            ->paragraph('Thanks for using our app!');
        return $template;
    }
}

// Usage
$template = EmailTemplateBuilder::header('Welcome');
$template->paragraph('Content here...');
// Note: This won't work as expected because each call creates a new instance
// Better to use a single template instance and chain methods
```

## Troubleshooting

### Font Looks Wrong in Email Client

If fonts appear as Times New Roman or another serif font instead of your chosen sans-serif:

**Cause:** Some email clients (Outlook, MailTrap) ignore font-family on body/parent elements.

**Solution:** The template already applies `font-family` to every element with `!important`. If you still see issues:

```php
// Use a more specific font stack
$template->setFonts([
    'family' => 'Arial, Helvetica, "Helvetica Neue", sans-serif',
]);
```

### Horizontal Scroll on Mobile

If your email requires horizontal scrolling on mobile devices:

**Cause:** Fixed-width content or images wider than screen.

**Solution:** The template uses responsive `max-width` by default. Ensure any custom content or images are also responsive:

```php
// ✅ Good - Responsive
$template->paragraph('Text content');  // Automatically responsive

// ❌ Avoid - Fixed width images
$template->paragraph('<img src="..." width="800">');

// ✅ Better - Responsive images
$template->paragraph('<img src="..." style="max-width: 100%; height: auto;">');
```

### Email Looks Different in Outlook

This is normal. Outlook uses Word's rendering engine. The template is designed to look good (not identical) across all clients.

### Images Not Showing

Images must be hosted online. Use absolute URLs:

```php
// ❌ Won't work
$template->paragraph('<img src="/logo.png">');

// ✅ Works
$template->paragraph('<img src="https://example.com/logo.png">');
```

### Buttons Not Clickable on Mobile

Make sure you're using the `button()` component, not custom HTML:

```php
// ✅ Good
$template->button('Click', 'https://example.com');

// ❌ May not work on all devices
$template->paragraph('<a href="...">Click</a>');
```

## Migration from Raw HTML

If you're currently using raw HTML in emails:

```php
// Before
$html = <<<HTML
<h1>Welcome</h1>
<p>Thanks for signing up!</p>
<a href="https://example.com">Get Started</a>
HTML;

$this->body($html)->send();

// After
$template = new MailTemplate();
$template
    ->heading('Welcome')
    ->paragraph('Thanks for signing up!')
    ->button('Get Started', 'https://example.com');

$this->template($template)->send();
```

Benefits:
- ✅ Email client compatibility
- ✅ Automatic plain text
- ✅ Professional layout
- ✅ XSS protection
- ✅ Maintainable code

## API Reference

### MailTemplate Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `heading(string $text, int $level = 1)` | `self` | Add heading (H1-H3) |
| `paragraph(string $text)` | `self` | Add paragraph (XSS protected) |
| `html(string $content)` | `self` | Add raw HTML (allows entities, NOT escaped) |
| `button(string $text, string $url, string $color = 'primary')` | `self` | Add button |
| `link(string $url, string $text = null)` | `self` | Add clickable link (prevents scroll on long URLs) |
| `divider()` | `self` | Add horizontal divider |
| `alert(string $text, string $type = 'info')` | `self` | Add alert box |
| `code(string $code)` | `self` | Add code block |
| `bulletList(array $items)` | `self` | Add bullet list |
| `keyValueTable(array $data)` | `self` | Add key-value table (2 columns) |
| `table(array $headers, array $rows)` | `self` | Add multi-column table with headers |
| `image(string $src, string $alt = '', ?int $width = null, string $align = 'center')` | `self` | Add image |
| `logo(string $url, int $width = 120)` | `self` | Set header logo (optional) |
| `footer(string $text)` | `self` | Set footer text (optional, supports HTML entities) |
| `footerLinks(array $links)` | `self` | Set footer links (optional) |
| `setColors(array $colors)` | `self` | Set custom colors |
| `setFonts(array $fonts)` | `self` | Set custom fonts |
| `setData(array $data)` | `self` | Set template data |
| `useLayout(?string $layout)` | `self` | Use specific layout |
| `withoutLayout()` | `self` | Disable layout wrapper |
| `toHtml()` | `string` | Render to HTML |
| `toPlainText()` | `string` | Generate plain text |
| `render(array $data = [])` | `string` | Render with data (alias for toHtml) |

### Mail Integration

| Method | Returns | Description |
|--------|---------|-------------|
| `template(MailTemplate $template)` | `self` | Use template for HTML and plain text |

### Color Options

- `primary` - Indigo (#4F46E5)
- `secondary` - Gray (#6B7280)
- `success` - Green (#10B981)
- `danger` - Red (#EF4444)
- `warning` - Amber (#F59E0B)
- `info` - Blue (#3B82F6)

### Alert Types

- `info` - Blue background
- `success` - Green background
- `warning` - Amber background
- `danger` - Red background
