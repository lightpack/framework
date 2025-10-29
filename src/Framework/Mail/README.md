# Lightpack Mail Templating System

A production-ready email templating system with beautiful defaults, maximum email client compatibility, and zero configuration required.

## Features

✨ **Beautiful Default Templates** - 8 professionally designed templates ready to use  
📱 **Maximum Compatibility** - Table-based layouts work in all email clients (even Outlook 2007!)  
🎨 **Easy Customization** - Override colors, fonts, and spacing with simple config  
📝 **Auto Plain-Text** - Automatically generates plain-text versions from HTML  
🧩 **Component System** - Reusable components for buttons, alerts, tables, etc.  
🔒 **Email-Safe** - Inline CSS, proper DOCTYPE, and Outlook compatibility built-in  
⚡ **Zero Dependencies** - No external CSS inliner needed for basic usage  

## Quick Start

### Using Built-in Templates

```php
use Lightpack\Mail\Mail;

class WelcomeEmail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $this->to($payload['email'])
            ->subject('Welcome to Our Platform!')
            ->template('welcome', [
                'name' => $payload['name'],
                'action_url' => 'https://example.com/get-started',
                'action_text' => 'Get Started Now'
            ])
            ->send();
    }
}

// Send the email
$email = new WelcomeEmail(app('mail'));
$email->dispatch([
    'email' => 'user@example.com',
    'name' => 'John Doe'
]);
```

That's it! You get a beautiful, responsive email with:
- Professional header and footer
- Branded colors
- Call-to-action button
- Auto-generated plain-text version
- Works in all email clients

## Available Templates

### 1. Welcome Email (`welcome`)

Perfect for onboarding new users.

```php
->template('welcome', [
    'name' => 'John Doe',
    'action_url' => 'https://example.com/start',
    'action_text' => 'Get Started',
])
```

### 2. Password Reset (`passwordReset`)

Secure password reset with expiration warning.

```php
->template('passwordReset', [
    'name' => 'Jane Smith',
    'reset_url' => 'https://example.com/reset/token123',
    'expires_in' => '60 minutes',
])
```

### 3. Email Verification (`verifyEmail`)

Email address verification with clear CTA.

```php
->template('verifyEmail', [
    'name' => 'Bob Johnson',
    'verify_url' => 'https://example.com/verify/abc123',
])
```

### 4. Notification (`notification`)

General purpose notification template.

```php
->template('notification', [
    'title' => 'Important Update',
    'message' => 'Your account settings have been updated.',
    'action_url' => 'https://example.com/settings',
    'action_text' => 'View Settings',
])
```

### 5. Invoice/Receipt (`invoice`)

Professional invoice with itemized table.

```php
->template('invoice', [
    'name' => 'Alice Cooper',
    'invoice_number' => 'INV-2024-001',
    'date' => 'January 15, 2024',
    'items' => [
        ['name' => 'Product A', 'amount' => '$50.00'],
        ['name' => 'Product B', 'amount' => '$75.00'],
    ],
    'total' => '$125.00',
])
```

### 6. Order Confirmation (`orderConfirmation`)

Order confirmation with tracking.

```php
->template('orderConfirmation', [
    'name' => 'Charlie Brown',
    'order_number' => 'ORD-12345',
    'items' => [
        ['name' => 'Widget', 'quantity' => 2],
        ['name' => 'Gadget', 'quantity' => 1],
    ],
    'tracking_url' => 'https://example.com/track/12345',
])
```

### 7. Account Alert (`accountAlert`)

Security and account alerts.

```php
->template('accountAlert', [
    'alert_type' => 'warning', // info, success, warning, danger
    'title' => 'Unusual Login Detected',
    'message' => 'We detected a login from a new device.',
    'action_url' => 'https://example.com/security',
    'action_text' => 'Review Activity',
])
```

### 8. Team Invitation (`teamInvitation`)

Team/workspace invitations.

```php
->template('teamInvitation', [
    'inviter_name' => 'Sarah Johnson',
    'team_name' => 'Acme Corp',
    'accept_url' => 'https://example.com/invite/xyz789',
    'expires_in' => '7 days',
])
```

## Customization

### Custom Colors

```php
use Lightpack\Mail\MailTemplate;

$template = new MailTemplate([
    'colors' => [
        'primary' => '#FF6B6B',    // Your brand color
        'success' => '#51CF66',
        'text' => '#2C3E50',
    ]
]);

$mail->setMailTemplate($template)
    ->template('welcome', [...])
    ->send();
```

### Custom Fonts

```php
$template = new MailTemplate([
    'fonts' => [
        'family' => 'Georgia, serif',
        'sizeBase' => '18px',
    ]
]);
```

### Custom Spacing

```php
$template = new MailTemplate([
    'spacing' => [
        'md' => '20px',
        'lg' => '30px',
    ]
]);
```

### Footer Links

Add custom footer links to any template:

```php
->template('welcome', [
    'name' => 'John',
    'footer_links' => [
        'Privacy Policy' => 'https://example.com/privacy',
        'Terms of Service' => 'https://example.com/terms',
        'Contact Us' => 'https://example.com/contact',
    ]
])
```

## Using Components

Build custom emails using components:

```php
use Lightpack\Mail\MailTemplate;

$template = new MailTemplate();

$html = $template->heading('Custom Email', 1) .
        $template->paragraph('This is a custom email built with components.') .
        $template->button('Click Here', 'https://example.com', 'primary') .
        $template->divider() .
        $template->alert('Important notice!', 'warning') .
        $template->bulletList([
            'Feature 1',
            'Feature 2',
            'Feature 3',
        ]) .
        $template->keyValueTable([
            'Name' => 'John Doe',
            'Email' => 'john@example.com',
            'Status' => 'Active',
        ]);

$mail->body($html)->send();
```

### Available Components

- `heading($text, $level)` - H1, H2, H3 headings
- `paragraph($text)` - Paragraph text
- `button($text, $url, $color)` - Call-to-action button
- `alert($text, $type)` - Alert boxes (info, success, warning, danger)
- `divider()` - Horizontal rule
- `code($code)` - Code block
- `bulletList($items)` - Bullet list
- `keyValueTable($data)` - Key-value table

## Plain Text Generation

Plain text versions are automatically generated from HTML:

```php
// Automatic (default)
$mail->template('welcome', [...])->send();
// Plain text is auto-generated

// Disable auto-generation
$mail->template('welcome', [...])
    ->disableAutoPlainText()
    ->send();

// Manual override
$mail->template('welcome', [...])
    ->altBody('Custom plain text version')
    ->send();
```

## Email Client Compatibility

The templating system uses email-safe techniques:

✅ **Table-based layouts** - Works in all email clients  
✅ **Inline CSS** - No external stylesheets  
✅ **Outlook compatibility** - Special tags for Outlook 2007-2019  
✅ **Mobile responsive** - Where supported (Gmail, Apple Mail, etc.)  
✅ **No JavaScript** - Pure HTML/CSS  
✅ **Web fonts fallback** - System fonts as fallback  

Tested and working in:
- Gmail (Desktop, Mobile, App)
- Outlook (2007, 2010, 2013, 2016, 2019, 365)
- Apple Mail (macOS, iOS)
- Yahoo Mail
- AOL Mail
- Thunderbird
- And many more...

## Advanced Usage

### Custom Template Method

Extend MailTemplate to add your own templates:

```php
use Lightpack\Mail\MailTemplate;

class MyMailTemplate extends MailTemplate
{
    public function customTemplate(): string
    {
        $name = $this->data['name'] ?? 'there';
        
        return $this->heading('My Custom Template', 1) .
               $this->paragraph("Hi {$name}!") .
               $this->paragraph('This is my custom template.') .
               $this->button('Custom Action', $this->data['url'] ?? '#');
    }
}

// Use it
$mail->setMailTemplate(new MyMailTemplate())
    ->template('customTemplate', ['name' => 'John', 'url' => '...'])
    ->send();
```

### Direct HTML with Auto Plain-Text

Even when using raw HTML, you get auto plain-text:

```php
$mail->body('<h1>Hello</h1><p>World</p>')
    ->send();
// Plain text is automatically generated
```

## Configuration

Set defaults in your `.env`:

```env
APP_NAME="My Application"
APP_URL="https://myapp.com"
MAIL_FROM_ADDRESS="noreply@myapp.com"
MAIL_FROM_NAME="My Application"
```

These are automatically used in all templates.

## Best Practices

1. **Always set a subject** - Required for all emails
2. **Use templates for consistency** - Maintain brand identity
3. **Test in multiple clients** - Use services like Litmus or Email on Acid
4. **Keep it simple** - Complex layouts may break in some clients
5. **Provide plain text** - Some users prefer text-only emails
6. **Use alt text for images** - If you add images (not included by default)
7. **Test links** - Ensure all URLs are absolute and working

## Why Table-Based Layouts?

Modern email clients (especially Outlook) don't support flexbox, grid, or many CSS3 features. Table-based layouts are the only reliable way to create consistent layouts across all email clients.

Our templates use:
- `<table>` for structure
- Inline CSS for styling
- `role="presentation"` for accessibility
- Conditional comments for Outlook
- Mobile-first approach (where supported)

## Performance

- **Zero external requests** - All CSS is inline
- **Minimal HTML** - Optimized for fast loading
- **No images by default** - Text-based for reliability
- **Small file size** - Typical email is ~15-20KB

## Troubleshooting

### Template not rendering?

Make sure you're calling `->send()` after setting the template.

### Plain text not generated?

Check that `autoPlainText` is enabled (it is by default).

### Custom colors not working?

Make sure you're setting the MailTemplate instance before calling `template()`.

### Outlook rendering issues?

Our templates are tested in Outlook, but if you modify them, ensure you:
- Use tables for layout
- Keep CSS inline
- Avoid CSS3 features
- Test in Outlook 2007+ (the most restrictive)

## Examples

See `tests/Mail/MailTemplateTest.php` for comprehensive examples of all features.

## License

Part of the Lightpack Framework. Same license applies.
