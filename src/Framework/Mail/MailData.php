<?php

namespace Lightpack\Mail;

/**
 * Fluent mail data builder
 * 
 * Provides a clean, readable way to build mail data internally
 * while maintaining array compatibility for drivers
 */
class MailData
{
    private array $to = [];
    private array $cc = [];
    private array $bcc = [];
    private array $replyTo = [];
    private array $attachments = [];
    private array $from = [];
    private string $subject = '';
    private string $htmlBody = '';
    private string $textBody = '';

    public function from(string $email, string $name = ''): self
    {
        $this->from = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function to(string $email, string $name = ''): self
    {
        $this->to[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function cc(string $email, string $name = ''): self
    {
        $this->cc[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function bcc(string $email, string $name = ''): self
    {
        $this->bcc[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function replyTo(string $email, string $name = ''): self
    {
        $this->replyTo[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function htmlBody(string $body): self
    {
        $this->htmlBody = $body;
        return $this;
    }

    public function textBody(string $body): self
    {
        $this->textBody = $body;
        return $this;
    }

    public function attach(string $path, string $name = ''): self
    {
        $this->attachments[] = ['path' => $path, 'name' => $name];
        return $this;
    }

    /**
     * Convert to array for driver consumption
     */
    public function toArray(): array
    {
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

    /**
     * Create from array (for backward compatibility)
     */
    public static function fromArray(array $data): self
    {
        $mailData = new self();
        
        if (isset($data['from'])) {
            $mailData->from = $data['from'];
        }
        
        $mailData->to = $data['to'] ?? [];
        $mailData->cc = $data['cc'] ?? [];
        $mailData->bcc = $data['bcc'] ?? [];
        $mailData->replyTo = $data['reply_to'] ?? [];
        $mailData->subject = $data['subject'] ?? '';
        $mailData->htmlBody = $data['html_body'] ?? '';
        $mailData->textBody = $data['text_body'] ?? '';
        $mailData->attachments = $data['attachments'] ?? [];
        
        return $mailData;
    }
}
