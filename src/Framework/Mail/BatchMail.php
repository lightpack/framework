<?php

namespace Lightpack\Mail;

/**
 * Batch Mail Sender
 * 
 * Send emails in batches using Resend's batch API
 * 
 * Usage:
 * 
 * $batch = new BatchMail(app('mail'));
 * 
 * $batch->add(function($mail) {
 *     $mail->to('user1@example.com')
 *         ->subject('Welcome')
 *         ->body('Hello User 1');
 * });
 * 
 * $batch->add(function($mail) {
 *     $mail->to('user2@example.com')
 *         ->subject('Welcome')
 *         ->body('Hello User 2');
 * });
 * 
 * $results = $batch->send();  // Sends all at once via Resend batch API
 */
class BatchMail
{
    protected MailManager $mailManager;
    protected array $emails = [];
    protected string $driver = 'resend';

    public function __construct(MailManager $mailManager)
    {
        $this->mailManager = $mailManager;
    }

    /**
     * Add email to batch
     */
    public function add(callable $callback): self
    {
        $mail = new class($this->mailManager) extends Mail {
            public function dispatch(array $payload = []) {
                // Not used - we collect data instead
            }
            
            public function getData(): array {
                // Build data array from protected properties
                return [
                    'to' => $this->to,
                    'from' => $this->from,
                    'subject' => $this->subject,
                    'html_body' => $this->htmlBody,
                    'text_body' => $this->textBody,
                    'cc' => $this->cc,
                    'bcc' => $this->bcc,
                    'reply_to' => $this->replyTo,
                    'attachments' => $this->attachments,
                ];
            }
        };
        
        $callback($mail);
        
        $this->emails[] = $mail->getData();
        
        return $this;
    }

    /**
     * Set driver to use for batch sending
     */
    public function driver(string $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Send all emails in batch
     * 
     * @return array Results from batch send
     */
    public function send(): array
    {
        if (empty($this->emails)) {
            return [];
        }

        $driver = $this->mailManager->driver($this->driver);
        
        // Check if driver supports batch sending
        if (method_exists($driver, 'sendBatch')) {
            return $driver->sendBatch($this->emails);
        }
        
        // Fallback: Send individually
        $results = [];
        foreach ($this->emails as $email) {
            try {
                $driver->send($email);
                $results[] = ['success' => true, 'email' => $email['to'][0]['email']];
            } catch (\Exception $e) {
                $results[] = ['success' => false, 'email' => $email['to'][0]['email'], 'error' => $e->getMessage()];
            }
        }
        
        return $results;
    }

    /**
     * Get number of emails in batch
     */
    public function count(): int
    {
        return count($this->emails);
    }

    /**
     * Clear batch
     */
    public function clear(): self
    {
        $this->emails = [];
        return $this;
    }
}
