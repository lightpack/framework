# Mail Drivers

Lightpack's mail system uses a driver-based architecture, allowing you to easily switch between different email sending services without changing your application code.

## Available Drivers

### SMTP Driver (Default)
Uses PHPMailer to send emails via SMTP.

```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Your App Name"
```

### Array Driver (Testing)
Stores emails in memory for testing purposes.

```env
MAIL_DRIVER=array
```

Access sent emails in tests:
```php
$sentMails = Mail::getSentMails();
Mail::clearSentMails();
```

### Log Driver (Development)
Writes emails to a JSON file for debugging.

```env
MAIL_DRIVER=log
```

Emails are logged to: `storage/logs/mails.json`

## Creating Custom Drivers

You can create custom drivers for services like Resend, Postmark, SendGrid, etc.

### 1. Create Driver Class

```php
<?php

namespace Lightpack\Mail\Drivers;

use Lightpack\Mail\DriverInterface;

class CustomDriver implements DriverInterface
{
    public function send(array $data): bool
    {
        // $data contains:
        // - to: array of ['email' => '...', 'name' => '...']
        // - from: ['email' => '...', 'name' => '...']
        // - subject: string
        // - html_body: string
        // - text_body: string
        // - cc: array (optional)
        // - bcc: array (optional)
        // - reply_to: array (optional)
        // - attachments: array (optional)
        
        // Transform to your service's format and send
        
        return true; // or throw exception on failure
    }
}
```

### 2. Register Driver

Update `Mail.php` to include your driver:

```php
private function createDriver(): DriverInterface
{
    return match (get_env('MAIL_DRIVER', 'smtp')) {
        'smtp' => new SmtpDriver(),
        'array' => new ArrayDriver(),
        'log' => new LogDriver(),
        'custom' => new CustomDriver(),
        default => throw new GlobalException('Invalid mail driver'),
    };
}
```

### 3. Configure Environment

```env
MAIL_DRIVER=custom
CUSTOM_API_KEY=your_api_key
```

## Example: Resend Driver

```php
<?php

namespace Lightpack\Mail\Drivers;

use Lightpack\Mail\DriverInterface;
use Resend;

class ResendDriver implements DriverInterface
{
    private $resend;

    public function __construct()
    {
        $this->resend = Resend::client(get_env('RESEND_API_KEY'));
    }

    public function send(array $data): bool
    {
        $payload = [
            'from' => "{$data['from']['name']} <{$data['from']['email']}>",
            'to' => array_map(fn($r) => $r['email'], $data['to']),
            'subject' => $data['subject'],
            'html' => $data['html_body'],
        ];

        if (!empty($data['text_body'])) {
            $payload['text'] = $data['text_body'];
        }

        $result = $this->resend->emails->send($payload);
        
        return isset($result['id']);
    }
}
```

## Migration Guide

### From Old Implementation

The public API remains **100% backward compatible**. No code changes needed:

```php
// This code works exactly the same
$mail = new WelcomeEmail();
$mail->to('user@example.com')
    ->cc('admin@example.com')
    ->attach('/path/to/file.pdf')
    ->send();
```

### Switching Drivers

Simply change your `.env` file:

```env
# From SMTP
MAIL_DRIVER=smtp

# To Resend
MAIL_DRIVER=resend
RESEND_API_KEY=your_key
```

No application code changes required!

## Benefits

1. **Zero Breaking Changes** - Existing code continues to work
2. **Easy Testing** - Use array driver in tests
3. **Service Flexibility** - Switch providers without code changes
4. **Future-Proof** - Add new services easily
5. **Clean Architecture** - Separation of concerns

## Data Structure

All drivers receive normalized data:

```php
[
    'to' => [
        ['email' => 'user@example.com', 'name' => 'User Name'],
    ],
    'from' => ['email' => 'sender@example.com', 'name' => 'Sender'],
    'subject' => 'Email Subject',
    'html_body' => '<h1>HTML Content</h1>',
    'text_body' => 'Plain text fallback',
    'cc' => [['email' => 'cc@example.com', 'name' => '']],
    'bcc' => [['email' => 'bcc@example.com', 'name' => '']],
    'reply_to' => [['email' => 'reply@example.com', 'name' => '']],
    'attachments' => [
        ['path' => '/path/to/file.pdf', 'name' => 'document.pdf'],
    ],
]
```

This consistent format makes it easy to integrate any email service.
