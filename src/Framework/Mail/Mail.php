<?php

namespace Lightpack\Mail;

use Exception as GlobalException;
use Lightpack\Mail\Drivers\ArrayDriver;
use Lightpack\Mail\Drivers\LogDriver;
use Lightpack\Mail\Drivers\SmtpDriver;

abstract class Mail
{
    protected DriverInterface $driver;
    protected $textView;
    protected $htmlView;
    protected $viewData = [];
    
    // Mail composition data
    protected array $to = [];
    protected array $cc = [];
    protected array $bcc = [];
    protected array $replyTo = [];
    protected array $attachments = [];
    protected array $from = [];
    protected string $subject = '';
    protected string $htmlBody = '';
    protected string $textBody = '';

    abstract public function dispatch(array $payload = []);

    public function __construct()
    {
        $this->driver = $this->createDriver();
        
        // Set default from address
        $this->from = [
            'email' => get_env('MAIL_FROM_ADDRESS'),
            'name' => get_env('MAIL_FROM_NAME', '')
        ];
    }

    private function createDriver(): DriverInterface
    {
        return match (get_env('MAIL_DRIVER', 'smtp')) {
            'smtp' => new SmtpDriver(),
            'array' => new ArrayDriver(),
            'log' => new LogDriver(),
            default => throw new GlobalException('Invalid mail driver: ' . get_env('MAIL_DRIVER')),
        };
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
                    $this->attachments[] = ['path' => $value, 'name' => ''];
                } else {
                    $this->attachments[] = ['path' => $key, 'name' => $value];
                }
            }
            return $this;
        }

        if (is_string($path)) {
            $this->attachments[] = ['path' => $path, 'name' => $name];
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

    public function send()
    {
        $this->renderViews();
        
        $data = $this->buildMailData();
        
        return $this->driver->send($data);
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

    private function buildMailData(): array
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

