# Mail Template Gallery

Visual guide to all available templates and their use cases.

## Template Overview

| Template | Use Case | Key Features |
|----------|----------|--------------|
| `welcome` | New user onboarding | CTA button, friendly tone |
| `passwordReset` | Password recovery | Security warning, expiration notice |
| `verifyEmail` | Email verification | Clear CTA, security message |
| `notification` | General notifications | Flexible content, optional CTA |
| `invoice` | Billing/receipts | Itemized table, professional |
| `orderConfirmation` | E-commerce orders | Order details, tracking link |
| `accountAlert` | Security alerts | Alert styling, urgent CTA |
| `teamInvitation` | Collaboration invites | Inviter info, expiration |

---

## 1. Welcome Template

**Perfect for:** User onboarding, account creation, first-time setup

```php
->template('welcome', [
    'name' => 'John Doe',
    'action_url' => 'https://example.com/get-started',
    'action_text' => 'Get Started Now',
])
```

**Visual Structure:**
```
┌─────────────────────────────────────┐
│         [App Logo/Name]             │
├─────────────────────────────────────┤
│                                     │
│  Welcome to [App Name]!             │
│                                     │
│  Hi John Doe,                       │
│                                     │
│  We're excited to have you on       │
│  board! Your account has been       │
│  successfully created...            │
│                                     │
│      ┌─────────────────┐            │
│      │ Get Started Now │            │
│      └─────────────────┘            │
│                                     │
│  If you have any questions...       │
│                                     │
├─────────────────────────────────────┤
│  © 2025 App Name                    │
└─────────────────────────────────────┘
```

**Best Practices:**
- Keep action text clear and actionable
- Use friendly, welcoming tone
- Include next steps

---

## 2. Password Reset Template

**Perfect for:** Password recovery, account security

```php
->template('passwordReset', [
    'name' => 'Jane Smith',
    'reset_url' => 'https://example.com/reset/token123',
    'expires_in' => '60 minutes',
])
```

**Visual Structure:**
```
┌─────────────────────────────────────┐
│         [App Logo/Name]             │
├─────────────────────────────────────┤
│                                     │
│  Reset Your Password                │
│                                     │
│  Hi Jane Smith,                     │
│                                     │
│  We received a request to reset     │
│  your password...                   │
│                                     │
│      ┌─────────────────┐            │
│      │ Reset Password  │            │
│      └─────────────────┘            │
│                                     │
│  ⚠️  This link expires in 60 min    │
│                                     │
│  If you didn't request this...      │
│                                     │
└─────────────────────────────────────┘
```

**Security Features:**
- Clear expiration warning
- "Didn't request this?" message
- Single-use token URL

---

## 3. Email Verification Template

**Perfect for:** Email confirmation, account activation

```php
->template('verifyEmail', [
    'name' => 'Bob Johnson',
    'verify_url' => 'https://example.com/verify/abc123',
])
```

**Visual Structure:**
```
┌─────────────────────────────────────┐
│         [App Logo/Name]             │
├─────────────────────────────────────┤
│                                     │
│  Verify Your Email Address          │
│                                     │
│  Hi Bob Johnson,                    │
│                                     │
│  Thanks for signing up! Please      │
│  verify your email address...       │
│                                     │
│      ┌─────────────────┐            │
│      │  Verify Email   │ (Green)    │
│      └─────────────────┘            │
│                                     │
│  If you didn't create an account... │
│                                     │
└─────────────────────────────────────┘
```

**Key Elements:**
- Green success-style button
- Clear verification purpose
- Security disclaimer

---

## 4. Notification Template

**Perfect for:** General updates, system notifications, alerts

```php
->template('notification', [
    'title' => 'Important Update',
    'message' => 'Your settings have been updated successfully.',
    'action_url' => 'https://example.com/settings',
    'action_text' => 'View Settings',
])
```

**Visual Structure:**
```
┌─────────────────────────────────────┐
│         [App Logo/Name]             │
├─────────────────────────────────────┤
│                                     │
│  Important Update                   │
│                                     │
│  Your settings have been updated    │
│  successfully.                      │
│                                     │
│      ┌─────────────────┐            │
│      │  View Settings  │            │
│      └─────────────────┘            │
│                                     │
└─────────────────────────────────────┘
```

**Flexibility:**
- Optional action button
- Custom title and message
- Adaptable to any notification type

---

## 5. Invoice Template

**Perfect for:** Billing, receipts, payment confirmations

```php
->template('invoice', [
    'name' => 'Alice Cooper',
    'invoice_number' => 'INV-2024-001',
    'date' => 'January 15, 2024',
    'items' => [
        ['name' => 'Product A', 'amount' => '$50.00'],
        ['name' => 'Product B', 'amount' => '$75.00'],
        ['name' => 'Shipping', 'amount' => '$10.00'],
    ],
    'total' => '$135.00',
])
```

**Visual Structure:**
```
┌─────────────────────────────────────┐
│         [App Logo/Name]             │
├─────────────────────────────────────┤
│                                     │
│  Invoice #INV-2024-001              │
│                                     │
│  Hi Alice Cooper,                   │
│                                     │
│  Thank you for your purchase!       │
│                                     │
│  ┌─────────────────────────────┐   │
│  │ Invoice Number: INV-2024-001│   │
│  │ Date: January 15, 2024      │   │
│  └─────────────────────────────┘   │
│                                     │
│  ┌─────────────────────────────┐   │
│  │ Item            │   Amount   │   │
│  ├─────────────────┼───────────┤   │
│  │ Product A       │   $50.00  │   │
│  │ Product B       │   $75.00  │   │
│  │ Shipping        │   $10.00  │   │
│  ├─────────────────┼───────────┤   │
│  │ Total           │  $135.00  │   │
│  └─────────────────────────────┘   │
│                                     │
└─────────────────────────────────────┘
```

**Professional Features:**
- Itemized table
- Clear total
- Invoice metadata
- Attachment-ready

---

## 6. Order Confirmation Template

**Perfect for:** E-commerce, order tracking, shipping updates

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

**Visual Structure:**
```
┌─────────────────────────────────────┐
│         [App Logo/Name]             │
├─────────────────────────────────────┤
│                                     │
│  Order Confirmed! ✓                 │
│                                     │
│  Hi Charlie Brown,                  │
│                                     │
│  Thank you for your order!          │
│                                     │
│  ✓ Order Number: ORD-12345          │
│                                     │
│  • Widget (x2)                      │
│  • Gadget (x1)                      │
│                                     │
│      ┌─────────────────┐            │
│      │ Track Your Order│            │
│      └─────────────────┘            │
│                                     │
└─────────────────────────────────────┘
```

**E-commerce Optimized:**
- Order number prominent
- Item list with quantities
- Tracking link
- Confirmation messaging

---

## 7. Account Alert Template

**Perfect for:** Security notifications, suspicious activity, account changes

```php
->template('accountAlert', [
    'alert_type' => 'warning', // info, success, warning, danger
    'title' => 'Unusual Login Detected',
    'message' => 'We detected a login from a new device in New York, USA.',
    'action_url' => 'https://example.com/security',
    'action_text' => 'Review Activity',
])
```

**Visual Structure:**
```
┌─────────────────────────────────────┐
│         [App Logo/Name]             │
├─────────────────────────────────────┤
│                                     │
│  Unusual Login Detected             │
│                                     │
│  ┌─────────────────────────────┐   │
│  │ ⚠️  We detected a login from │   │
│  │     a new device in New York │   │
│  └─────────────────────────────┘   │
│                                     │
│      ┌─────────────────┐            │
│      │ Review Activity │            │
│      └─────────────────┘            │
│                                     │
│  If you didn't perform this         │
│  action, contact support...         │
│                                     │
└─────────────────────────────────────┘
```

**Alert Types:**
- **info** (blue): Informational updates
- **success** (green): Positive confirmations
- **warning** (amber): Caution notices
- **danger** (red): Critical alerts

---

## 8. Team Invitation Template

**Perfect for:** Workspace invites, collaboration, team management

```php
->template('teamInvitation', [
    'inviter_name' => 'Sarah Johnson',
    'team_name' => 'Acme Corp',
    'accept_url' => 'https://example.com/invite/xyz789',
    'expires_in' => '7 days',
])
```

**Visual Structure:**
```
┌─────────────────────────────────────┐
│         [App Logo/Name]             │
├─────────────────────────────────────┤
│                                     │
│  You've Been Invited! 🎉            │
│                                     │
│  Sarah Johnson has invited you      │
│  to join Acme Corp.                 │
│                                     │
│      ┌─────────────────┐            │
│      │ Accept Invitation│ (Green)   │
│      └─────────────────┘            │
│                                     │
│  ℹ️  This invitation expires in     │
│     7 days.                         │
│                                     │
│  If you don't want to join...       │
│                                     │
└─────────────────────────────────────┘
```

**Collaboration Features:**
- Personal inviter name
- Team/workspace context
- Expiration notice
- Opt-out message

---

## Component System

Build custom emails using reusable components:

### Available Components

```php
$template = new MailTemplate();

// Headings (H1, H2, H3)
$template->heading('Main Title', 1);
$template->heading('Section Title', 2);

// Paragraph
$template->paragraph('Your text here...');

// Button (with color options)
$template->button('Click Me', 'https://...', 'primary');
// Colors: primary, secondary, success, danger, warning, info

// Alert Box
$template->alert('Important message', 'warning');
// Types: info, success, warning, danger

// Divider
$template->divider();

// Code Block
$template->code('echo "Hello World";');

// Bullet List
$template->bulletList(['Item 1', 'Item 2', 'Item 3']);

// Key-Value Table
$template->keyValueTable([
    'Name' => 'John Doe',
    'Email' => 'john@example.com',
    'Status' => 'Active'
]);
```

### Custom Email Example

```php
$html = $template->heading('Monthly Report', 1) .
        $template->paragraph('Here are your stats:') .
        $template->keyValueTable([
            'Orders' => '150',
            'Revenue' => '$12,500',
            'Growth' => '+25%'
        ]) .
        $template->divider() .
        $template->alert('New feature available!', 'info') .
        $template->button('View Dashboard', 'https://...', 'primary');

$mail->body($html)->send();
```

---

## Color Customization

### Default Color Palette

```php
'primary'    => '#4F46E5'  // Indigo
'secondary'  => '#6B7280'  // Gray
'success'    => '#10B981'  // Green
'danger'     => '#EF4444'  // Red
'warning'    => '#F59E0B'  // Amber
'info'       => '#3B82F6'  // Blue
'text'       => '#1F2937'  // Dark gray
'background' => '#F9FAFB'  // Light gray
```

### Custom Brand Colors

```php
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

---

## Email Client Compatibility

All templates are tested and working in:

✅ **Desktop Clients**
- Outlook 2007, 2010, 2013, 2016, 2019, 365
- Apple Mail (macOS)
- Thunderbird
- Windows Mail

✅ **Webmail**
- Gmail (Desktop & Mobile)
- Outlook.com
- Yahoo Mail
- AOL Mail
- ProtonMail

✅ **Mobile**
- iOS Mail
- Gmail App (iOS & Android)
- Outlook App (iOS & Android)
- Samsung Email

---

## Technical Details

### HTML Structure

- **Layout:** Table-based (email-safe)
- **CSS:** Inline styles (maximum compatibility)
- **Width:** 600px (standard email width)
- **Responsive:** Where supported
- **Accessibility:** Semantic HTML, proper alt text

### File Size

- Welcome email: ~18KB
- Invoice email: ~22KB (with table)
- Notification: ~15KB

### Performance

- Zero external requests
- No images by default
- Fast rendering in all clients
- Mobile-optimized

---

## Best Practices

1. **Keep it simple** - Complex layouts may break
2. **Test in Outlook** - Most restrictive client
3. **Use alt text** - If adding images
4. **Absolute URLs** - Always use full URLs
5. **Plain text** - Always provide (auto-generated)
6. **Mobile first** - Most emails opened on mobile
7. **Clear CTA** - One primary action per email
8. **Unsubscribe link** - For marketing emails

---

## Need Help?

- See `README.md` for full documentation
- See `Examples.php` for code examples
- See `MIGRATION.md` for upgrade guide
- Run tests: `phpunit tests/Mail/MailTemplateTest.php`
