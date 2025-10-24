<?php

namespace Lightpack\Mail;

use Lightpack\Mail\Drivers\ArrayDriver;

abstract class Mail
{
    protected array $to = [];
    protected array $cc = [];
    protected array $bcc = [];
    protected array $replyTo = [];
    protected array $from = [];
    protected string $subject = '';
    protected string $htmlBody = '';
    protected string $textBody = '';
    protected array $attachments = [];
    protected ?string $htmlView = null;
    protected ?string $textView = null;
    protected array $viewData = [];
    protected ?string $driverName = null;
    protected MailManager $mailManager;

    abstract public function dispatch(array $payload = []);

    public function __construct(MailManager $mailManager)
    {
        $this->mailManager = $mailManager;
        
        // Set default from address
        $this->from = [
            'email' => get_env('MAIL_FROM_ADDRESS'),
            'name' => get_env('MAIL_FROM_NAME', '')
        ];
    }

    /**
     * Set the driver to use for this mail
     * Allows per-mail driver selection
     */
    public function driver(string $name): self
    {
        $this->driverName = $name;
        return $this;
    }

    public function from(string $address, string $name = ''): self
    {
        $this->from = ['email' => $address, 'name' => $name];
        return $this;
    }

    public function to(string $address, string $name = ''): self
    {
        $this->to[] = ['email' => $address, 'name' => $name];
        return $this;
    }

    public function replyTo($address, string $name = ''): self
    {
        if (is_array($address)) {
            foreach ($address as $key => $value) {
                list($email, $recipientName) = $this->normalizeAddress($key, $value);
                $this->replyTo[] = ['email' => $email, 'name' => $recipientName];
            }
            return $this;
        }

        if (is_string($address)) {
            $this->replyTo[] = ['email' => $address, 'name' => $name];
        }

        return $this;
    }

    public function cc($address, string $name = ''): self
    {
        if (is_array($address)) {
            foreach ($address as $key => $value) {
                list($email, $recipientName) = $this->normalizeAddress($key, $value);
                $this->cc[] = ['email' => $email, 'name' => $recipientName];
            }
            return $this;
        }

        if (is_string($address)) {
            $this->cc[] = ['email' => $address, 'name' => $name];
        }

        return $this;
    }

    public function bcc($address, string $name = ''): self
    {
        if (is_array($address)) {
            foreach ($address as $key => $value) {
                list($email, $recipientName) = $this->normalizeAddress($key, $value);
                $this->bcc[] = ['email' => $email, 'name' => $recipientName];
            }
            return $this;
        }

        if (is_string($address)) {
            $this->bcc[] = ['email' => $address, 'name' => $name];
        }

        return $this;
    }

    public function attach($path, string $name = ''): self
    {
        if (is_array($path)) {
            foreach ($path as $key => $value) {
                if (is_int($key)) {
                    $this->attachments[] = ['path' => $value, 'filename' => ''];
                } else {
                    $this->attachments[] = ['path' => $key, 'filename' => $value];
                }
            }
            return $this;
        }

        if (is_string($path)) {
            $this->attachments[] = ['path' => $path, 'filename' => $name];
        }

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function body(string $body): self
    {
        $this->htmlBody = $body;
        return $this;
    }

    public function altBody(string $body): self
    {
        $this->textBody = $body;
        return $this;
    }

    public function textView(string $template): self
    {
        $this->textView = $template;

        return $this;
    }

    public function htmlView(string $template): self
    {
        $this->htmlView = $template;

        return $this;
    }

    public function viewData(array $data): self
    {
        $this->viewData = $data;

        return $this;
    }

    /**
     * Set markdown template for email
     * 
     * Uses .md.php files with PHP variables (<?= $var ?>)
     * Automatically converts markdown to HTML and generates plain text
     * 
     * Example:
     * $this->markdownView('emails/welcome.md', ['name' => 'Bob']);
     * 
     * File: emails/welcome.md.php
     * # Hello <?= $name ?>!
     * Welcome to **Lightpack**.
     */
    public function markdownView(string $template, array $data = []): self
    {
        $this->viewData($data);
        
        // Render markdown template (uses .md.php file)
        $markdownContent = app('template')
            ->setData($data)
            ->include($template);
        
        // Convert markdown to HTML
        $converter = new \League\CommonMark\CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        
        $this->htmlBody = $converter->convert($markdownContent)->getContent();
        
        return $this;
    }

    public function send()
    {
        $this->renderViews();
        
        // Get driver from injected MailManager
        $driver = $this->driverName 
            ? $this->mailManager->driver($this->driverName)
            : $this->mailManager->getDefaultDriver();
        
        // Build simple array - no MailData needed
        return $driver->send([
            'to' => $this->to,
            'from' => $this->from,
            'subject' => $this->subject,
            'html_body' => $this->htmlBody,
            'text_body' => $this->textBody,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'reply_to' => $this->replyTo,
            'attachments' => $this->attachments,
        ]);
    }

    private function renderViews(): void
    {
        if ($this->htmlView) {
            $this->htmlBody = app('template')->setData($this->viewData)->include($this->htmlView);
        }

        if ($this->textView) {
            $this->textBody = app('template')->setData($this->viewData)->include($this->textView);
        }
    }

    /**
     * Get sent mails (for array driver testing)
     */
    public static function getSentMails(): array
    {
        return ArrayDriver::getSentMails();
    }

    /**
     * Clear sent mails (for array driver testing)
     */
    public static function clearSentMails(): void
    {
        ArrayDriver::clearSentMails();
    }

    private function normalizeAddress($key, $value): array
    {
        if (is_int($key)) {
            return [$value, ''];
        }

        return [$key, $value];
    }

}

