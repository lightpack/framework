# Mail Templates - Quick Reference Card

## 🚀 Quick Start (Copy & Paste)

```php
// Send a welcome email
$mail->to('user@example.com')
    ->subject('Welcome!')
    ->template('welcome', [
        'name' => 'John Doe',
        'action_url' => 'https://example.com/start',
        'action_text' => 'Get Started'
    ])
    ->send();
```

## 📧 All Templates

| Template | Code |
|----------|------|
| **Welcome** | `->template('welcome', ['name', 'action_url', 'action_text'])` |
| **Password Reset** | `->template('passwordReset', ['name', 'reset_url', 'expires_in'])` |
| **Verify Email** | `->template('verifyEmail', ['name', 'verify_url'])` |
| **Notification** | `->template('notification', ['title', 'message', 'action_url?', 'action_text?'])` |
| **Invoice** | `->template('invoice', ['name', 'invoice_number', 'date', 'items', 'total'])` |
| **Order Confirmation** | `->template('orderConfirmation', ['name', 'order_number', 'items', 'tracking_url?'])` |
| **Account Alert** | `->template('accountAlert', ['alert_type', 'title', 'message', 'action_url?'])` |
| **Team Invitation** | `->template('teamInvitation', ['inviter_name', 'team_name', 'accept_url', 'expires_in'])` |

## 🎨 Components

```php
$template = new MailTemplate();

$template->heading('Title', 1)                    // H1, H2, H3
$template->paragraph('Text...')                   // Paragraph
$template->button('Click', 'url', 'primary')      // Button (primary, success, danger, etc.)
$template->alert('Message', 'warning')            // Alert (info, success, warning, danger)
$template->divider()                              // Horizontal line
$template->code('echo "code";')                   // Code block
$template->bulletList(['Item 1', 'Item 2'])       // Bullet list
$template->keyValueTable(['Key' => 'Value'])      // Data table
```

## ⚙️ Customization

```php
// Custom colors
$template = new MailTemplate([
    'colors' => ['primary' => '#FF6B6B']
]);
$mail->setMailTemplate($template);

// Disable auto plain-text
$mail->disableAutoPlainText();

// Manual plain-text
$mail->altBody('Custom plain text');

// Footer links
->template('welcome', [
    'footer_links' => [
        'Privacy' => 'https://...',
        'Terms' => 'https://...'
    ]
])
```

## 📝 Common Patterns

### Welcome Email
```php
class WelcomeEmail extends Mail {
    public function dispatch(array $payload = []) {
        $this->to($payload['email'])
            ->subject('Welcome!')
            ->template('welcome', [
                'name' => $payload['name'],
                'action_url' => url('/dashboard'),
                'action_text' => 'Get Started'
            ])
            ->send();
    }
}
```

### Password Reset
```php
class PasswordResetEmail extends Mail {
    public function dispatch(array $payload = []) {
        $this->to($payload['email'])
            ->subject('Reset Your Password')
            ->template('passwordReset', [
                'name' => $payload['name'],
                'reset_url' => $payload['reset_url'],
                'expires_in' => '60 minutes'
            ])
            ->send();
    }
}
```

### Invoice
```php
class InvoiceEmail extends Mail {
    public function dispatch(array $payload = []) {
        $this->to($payload['email'])
            ->subject('Invoice #' . $payload['invoice_number'])
            ->template('invoice', [
                'name' => $payload['name'],
                'invoice_number' => $payload['invoice_number'],
                'date' => date('F j, Y'),
                'items' => [
                    ['name' => 'Item 1', 'amount' => '$50.00'],
                    ['name' => 'Item 2', 'amount' => '$75.00']
                ],
                'total' => '$125.00'
            ])
            ->attach($payload['pdf_path'], 'invoice.pdf')
            ->send();
    }
}
```

### Custom Email
```php
$template = new MailTemplate();
$html = $template->heading('Custom Email', 1) .
        $template->paragraph('Your content...') .
        $template->button('Action', 'https://...');

$mail->body($html)->send();
```

## 🔧 Cheat Sheet

```php
// Basic send
$mail->template('welcome', $data)->send();

// With attachments
$mail->template('invoice', $data)
    ->attach('/path/to/file.pdf', 'invoice.pdf')
    ->send();

// Multiple recipients
$mail->to('user1@example.com')
    ->to('user2@example.com')
    ->cc('manager@example.com')
    ->template('notification', $data)
    ->send();

// Custom from
$mail->from('custom@example.com', 'Custom Name')
    ->template('welcome', $data)
    ->send();

// Different driver
$mail->driver('log')
    ->template('welcome', $data)
    ->send();

// Custom colors
$template = new MailTemplate(['colors' => ['primary' => '#FF0000']]);
$mail->setMailTemplate($template)
    ->template('welcome', $data)
    ->send();
```

## ✅ Testing

```php
// In tests
Mail::clearSentMails();

$mail->template('welcome', ['name' => 'Test'])->send();

$sentMails = Mail::getSentMails();
$this->assertCount(1, $sentMails);
$this->assertStringContainsString('Test', $sentMails[0]['html_body']);
```

## 🎯 Template Data Reference

### Welcome
```php
[
    'name' => 'John Doe',              // Required
    'action_url' => 'https://...',     // Required
    'action_text' => 'Get Started',    // Optional (default: 'Get Started')
    'app_name' => 'My App',            // Optional (from env)
    'footer_links' => [...]            // Optional
]
```

### Password Reset
```php
[
    'name' => 'Jane Smith',            // Required
    'reset_url' => 'https://...',      // Required
    'expires_in' => '60 minutes',      // Optional (default: '60 minutes')
]
```

### Verify Email
```php
[
    'name' => 'Bob Johnson',           // Required
    'verify_url' => 'https://...',     // Required
]
```

### Notification
```php
[
    'title' => 'Update',               // Required
    'message' => 'Your message...',    // Required
    'action_url' => 'https://...',     // Optional
    'action_text' => 'View',           // Optional (default: 'View Details')
]
```

### Invoice
```php
[
    'name' => 'Customer',              // Required
    'invoice_number' => 'INV-001',     // Required
    'date' => 'Jan 15, 2024',          // Optional (default: today)
    'items' => [                       // Required
        ['name' => 'Item', 'amount' => '$50.00']
    ],
    'total' => '$50.00',               // Required
]
```

### Order Confirmation
```php
[
    'name' => 'Customer',              // Required
    'order_number' => 'ORD-001',       // Required
    'items' => [                       // Required
        ['name' => 'Widget', 'quantity' => 2]
    ],
    'tracking_url' => 'https://...',   // Optional
]
```

### Account Alert
```php
[
    'alert_type' => 'warning',         // Required (info, success, warning, danger)
    'title' => 'Alert Title',          // Required
    'message' => 'Alert message...',   // Required
    'action_url' => 'https://...',     // Optional
    'action_text' => 'Review',         // Optional (default: 'Review Activity')
]
```

### Team Invitation
```php
[
    'inviter_name' => 'Sarah',         // Required
    'team_name' => 'Acme Corp',        // Required
    'accept_url' => 'https://...',     // Required
    'expires_in' => '7 days',          // Optional (default: '7 days')
]
```

## 🎨 Color Options

```php
'primary'    => '#4F46E5'  // Indigo (default)
'secondary'  => '#6B7280'  // Gray
'success'    => '#10B981'  // Green
'danger'     => '#EF4444'  // Red
'warning'    => '#F59E0B'  // Amber
'info'       => '#3B82F6'  // Blue
```

## 📱 Email Client Support

✅ Outlook 2007-2019  
✅ Gmail (all platforms)  
✅ Apple Mail  
✅ Yahoo, AOL  
✅ Mobile apps  
✅ 99%+ compatibility  

## 💡 Tips

1. **Always set subject** before template
2. **Use absolute URLs** for links
3. **Test in Outlook** (most restrictive)
4. **Keep it simple** for best compatibility
5. **Plain text is auto-generated** (no work needed)
6. **Templates are responsive** (where supported)
7. **File size ~15-20KB** (fast loading)

## 🐛 Troubleshooting

**Template not rendering?**
- Make sure you call `->send()` at the end

**Plain text not generated?**
- It's automatic unless you call `->disableAutoPlainText()`

**Custom colors not working?**
- Set template before calling `->template()`

**Outlook looks broken?**
- Templates are tested in Outlook - if you modify them, use tables only

## 📚 Full Documentation

- **README.md** - Complete guide
- **TEMPLATES.md** - Visual gallery
- **Examples.php** - Code examples
- **MIGRATION.md** - Upgrade guide

## ⚡ One-Liners

```php
// Welcome
$mail->template('welcome', ['name' => 'John', 'action_url' => '...'])->send();

// Password Reset
$mail->template('passwordReset', ['name' => 'Jane', 'reset_url' => '...'])->send();

// Notification
$mail->template('notification', ['title' => 'Update', 'message' => '...'])->send();

// Invoice
$mail->template('invoice', ['name' => 'Alice', 'invoice_number' => 'INV-001', 'items' => [...], 'total' => '$100'])->send();
```

---

**That's it! Start sending beautiful emails in seconds.** 🚀
