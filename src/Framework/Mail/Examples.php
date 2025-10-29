<?php

/**
 * Lightpack Mail Template Examples
 * 
 * Real-world examples of using the mail templating system
 */

namespace Lightpack\Mail\Examples;

use Lightpack\Mail\Mail;
use Lightpack\Mail\MailTemplate;

// ============================================================================
// Example 1: Welcome Email for New Users
// ============================================================================

class WelcomeEmail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $this->to($payload['email'], $payload['name'])
            ->subject('Welcome to ' . get_env('APP_NAME', 'Our Platform') . '!')
            ->template('welcome', [
                'name' => $payload['name'],
                'action_url' => get_env('APP_URL') . '/dashboard',
                'action_text' => 'Go to Dashboard',
            ])
            ->send();
    }
}

// Usage:
// $email = new WelcomeEmail(app('mail'));
// $email->dispatch([
//     'email' => 'newuser@example.com',
//     'name' => 'John Doe'
// ]);


// ============================================================================
// Example 2: Password Reset with Security Best Practices
// ============================================================================

class PasswordResetEmail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $this->to($payload['email'])
            ->subject('Reset Your Password')
            ->template('passwordReset', [
                'name' => $payload['name'],
                'reset_url' => $payload['reset_url'],
                'expires_in' => '60 minutes',
            ])
            ->send();
    }
}

// Usage:
// $token = generateSecureToken();
// $email = new PasswordResetEmail(app('mail'));
// $email->dispatch([
//     'email' => 'user@example.com',
//     'name' => 'Jane Smith',
//     'reset_url' => "https://myapp.com/reset-password?token={$token}"
// ]);


// ============================================================================
// Example 3: Order Confirmation with Tracking
// ============================================================================

class OrderConfirmationEmail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $order = $payload['order'];
        
        $items = array_map(function($item) {
            return [
                'name' => $item['product_name'],
                'quantity' => $item['quantity']
            ];
        }, $order['items']);
        
        $this->to($order['customer_email'], $order['customer_name'])
            ->subject('Order Confirmation - #' . $order['order_number'])
            ->template('orderConfirmation', [
                'name' => $order['customer_name'],
                'order_number' => $order['order_number'],
                'items' => $items,
                'tracking_url' => $order['tracking_url'] ?? null,
            ])
            ->send();
    }
}

// Usage:
// $email = new OrderConfirmationEmail(app('mail'));
// $email->dispatch([
//     'order' => [
//         'order_number' => 'ORD-2024-001',
//         'customer_email' => 'customer@example.com',
//         'customer_name' => 'Alice Cooper',
//         'items' => [
//             ['product_name' => 'Blue Widget', 'quantity' => 2],
//             ['product_name' => 'Red Gadget', 'quantity' => 1],
//         ],
//         'tracking_url' => 'https://shipping.com/track/ABC123'
//     ]
// ]);


// ============================================================================
// Example 4: Invoice Email with Itemized Billing
// ============================================================================

class InvoiceEmail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $invoice = $payload['invoice'];
        
        $items = array_map(function($item) {
            return [
                'name' => $item['description'],
                'amount' => '$' . number_format($item['amount'], 2)
            ];
        }, $invoice['line_items']);
        
        $this->to($invoice['customer_email'], $invoice['customer_name'])
            ->subject('Invoice #' . $invoice['invoice_number'])
            ->template('invoice', [
                'name' => $invoice['customer_name'],
                'invoice_number' => $invoice['invoice_number'],
                'date' => date('F j, Y', strtotime($invoice['date'])),
                'items' => $items,
                'total' => '$' . number_format($invoice['total'], 2),
            ])
            ->attach($invoice['pdf_path'], 'invoice.pdf') // Attach PDF
            ->send();
    }
}


// ============================================================================
// Example 5: Security Alert with Custom Styling
// ============================================================================

class SecurityAlertEmail extends Mail
{
    public function dispatch(array $payload = [])
    {
        // Use custom colors for security alerts
        $template = new MailTemplate([
            'colors' => [
                'primary' => '#DC2626', // Red for security
                'warning' => '#F59E0B',
            ]
        ]);
        
        $this->setMailTemplate($template)
            ->to($payload['email'])
            ->subject('Security Alert: ' . $payload['alert_title'])
            ->template('accountAlert', [
                'alert_type' => 'danger',
                'title' => $payload['alert_title'],
                'message' => $payload['message'],
                'action_url' => get_env('APP_URL') . '/security',
                'action_text' => 'Review Security Settings',
            ])
            ->send();
    }
}

// Usage:
// $email = new SecurityAlertEmail(app('mail'));
// $email->dispatch([
//     'email' => 'user@example.com',
//     'alert_title' => 'New Login from Unknown Device',
//     'message' => 'We detected a login from a new device in New York, USA at 2:30 PM.'
// ]);


// ============================================================================
// Example 6: Custom Email Using Components
// ============================================================================

class MonthlyReportEmail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $template = $this->getMailTemplate();
        
        $html = $template->heading('Your Monthly Report', 1) .
                $template->paragraph("Hi {$payload['name']},") .
                $template->paragraph("Here's your activity summary for " . date('F Y') . ":") .
                $template->keyValueTable([
                    'Total Orders' => $payload['stats']['orders'],
                    'Revenue' => '$' . number_format($payload['stats']['revenue'], 2),
                    'New Customers' => $payload['stats']['new_customers'],
                    'Growth' => $payload['stats']['growth'] . '%',
                ]) .
                $template->divider() .
                $template->heading('Top Products', 2) .
                $template->bulletList($payload['top_products']) .
                $template->button('View Full Report', $payload['report_url'], 'primary');
        
        $this->to($payload['email'], $payload['name'])
            ->subject('Your Monthly Report - ' . date('F Y'))
            ->body($html)
            ->send();
    }
}


// ============================================================================
// Example 7: Team Invitation with Expiration
// ============================================================================

class TeamInvitationEmail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $this->to($payload['invitee_email'])
            ->subject($payload['inviter_name'] . ' invited you to join ' . $payload['team_name'])
            ->template('teamInvitation', [
                'inviter_name' => $payload['inviter_name'],
                'team_name' => $payload['team_name'],
                'accept_url' => $payload['invitation_url'],
                'expires_in' => '7 days',
            ])
            ->send();
    }
}


// ============================================================================
// Example 8: Notification with Multiple Recipients
// ============================================================================

class SystemNotificationEmail extends Mail
{
    public function dispatch(array $payload = [])
    {
        // Add multiple recipients
        foreach ($payload['recipients'] as $recipient) {
            $this->to($recipient['email'], $recipient['name']);
        }
        
        $this->subject($payload['title'])
            ->template('notification', [
                'title' => $payload['title'],
                'message' => $payload['message'],
                'action_url' => $payload['action_url'] ?? null,
                'action_text' => $payload['action_text'] ?? 'View Details',
            ])
            ->send();
    }
}


// ============================================================================
// Example 9: Email with Custom Footer Links
// ============================================================================

class MarketingEmail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $this->to($payload['email'], $payload['name'])
            ->subject($payload['subject'])
            ->template('notification', [
                'title' => $payload['title'],
                'message' => $payload['message'],
                'action_url' => $payload['cta_url'],
                'action_text' => $payload['cta_text'],
                'footer_links' => [
                    'Unsubscribe' => get_env('APP_URL') . '/unsubscribe/' . $payload['unsubscribe_token'],
                    'Privacy Policy' => get_env('APP_URL') . '/privacy',
                    'Contact Us' => get_env('APP_URL') . '/contact',
                ]
            ])
            ->send();
    }
}


// ============================================================================
// Example 10: Transactional Email with Attachments
// ============================================================================

class ReceiptEmail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $transaction = $payload['transaction'];
        
        $this->to($transaction['customer_email'])
            ->subject('Receipt for ' . $transaction['description'])
            ->template('invoice', [
                'name' => $transaction['customer_name'],
                'invoice_number' => $transaction['receipt_number'],
                'date' => date('F j, Y'),
                'items' => [
                    [
                        'name' => $transaction['description'],
                        'amount' => '$' . number_format($transaction['amount'], 2)
                    ]
                ],
                'total' => '$' . number_format($transaction['amount'], 2),
            ])
            ->attach($transaction['receipt_pdf'], 'receipt.pdf')
            ->send();
    }
}


// ============================================================================
// Example 11: Scheduled Digest Email
// ============================================================================

class WeeklyDigestEmail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $template = $this->getMailTemplate();
        
        $html = $template->heading('Your Weekly Digest', 1) .
                $template->paragraph("Hi {$payload['name']},") .
                $template->paragraph("Here's what happened this week:") .
                $template->divider();
        
        // Add each section
        foreach ($payload['sections'] as $section) {
            $html .= $template->heading($section['title'], 2) .
                     $template->paragraph($section['description']) .
                     $template->bulletList($section['items']) .
                     $template->divider();
        }
        
        $html .= $template->button('View All Updates', $payload['view_all_url']);
        
        $this->to($payload['email'], $payload['name'])
            ->subject('Your Weekly Digest - ' . date('F j, Y'))
            ->body($html)
            ->send();
    }
}


// ============================================================================
// Example 12: Multi-Driver Usage (Testing vs Production)
// ============================================================================

class TestableEmail extends Mail
{
    public function dispatch(array $payload = [])
    {
        // Use different driver based on environment
        $driver = get_env('APP_ENV') === 'testing' ? 'array' : 'smtp';
        
        $this->driver($driver)
            ->to($payload['email'])
            ->subject($payload['subject'])
            ->template($payload['template'], $payload['data'])
            ->send();
    }
}


// ============================================================================
// Example 13: Email with Manual Plain Text (Accessibility)
// ============================================================================

class AccessibleEmail extends Mail
{
    public function dispatch(array $payload = [])
    {
        $this->to($payload['email'])
            ->subject($payload['subject'])
            ->template('notification', $payload['data'])
            ->altBody($this->generatePlainText($payload['data']))
            ->send();
    }
    
    private function generatePlainText(array $data): string
    {
        return <<<TEXT
{$data['title']}

{$data['message']}

{$data['action_text']}: {$data['action_url']}

---
© 2025 {get_env('APP_NAME')}
TEXT;
    }
}
