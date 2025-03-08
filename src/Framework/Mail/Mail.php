<?php

namespace Lightpack\Mail;

use Exception as GlobalException;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

abstract class Mail extends PHPMailer
{
    protected $textView;
    protected $htmlView;
    protected $viewData = [];
    protected static $sentMails = [];

    abstract public function dispatch(array $payload = []);

    public function __construct()
    {
        $this->isSMTP();
        $this->SMTPAuth     = true;
        $this->Host         = get_env('MAIL_HOST');
        $this->Port         = get_env('MAIL_PORT');
        $this->Username     = get_env('MAIL_USERNAME');
        $this->Password     = get_env('MAIL_PASSWORD');
        $this->SMTPSecure   = get_env('MAIL_ENCRYPTION');

        $this->setFrom(
            get_env('MAIL_FROM_ADDRESS'),
            get_env('MAIL_FROM_NAME')
        );

        $this->isHTML(true);

        parent::__construct(true);
    }

    public function from(string $address, string $name = ''): self
    {
        $this->setFrom($address, $name);

        return $this;
    }

    public function to(string $address, string $name = ''): self
    {
        $this->addAddress($address, $name);

        return $this;
    }

    public function replyTo($address, string $name = ''): self
    {
        if (is_array($address)) {
            $this->setAddresses($address, 'reply_to');

            return $this;
        }

        if (is_string($address)) {
            $this->addReplyTo($address, $name);
        }

        return $this;
    }

    public function cc($address, string $name = ''): self
    {
        if (is_array($address)) {
            $this->setAddresses($address, 'cc');

            return $this;
        }

        if (is_string($address)) {
            $this->addCC($address, $name);
        }

        return $this;
    }

    public function bcc($address, string $name = ''): self
    {
        if (is_array($address)) {
            $this->setAddresses($address, 'bcc');

            return $this;
        }

        if (is_string($address)) {
            $this->addBCC($address, $name);
        }

        return $this;
    }

    public function attach($path, string $name = ''): self
    {
        if (is_array($path)) {
            $this->setAttachments($path);

            return $this;
        }

        if (is_string($path)) {
            $this->addAttachment($path, $name);
        }

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->Subject = $subject;

        return $this;
    }

    public function body(string $body): self
    {
        $this->Body = $body;

        return $this;
    }

    public function altBody(string $body): self
    {
        $this->AltBody = $body;

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
        $this->setBody();

        try {
            return match (get_env('MAIL_DRIVER', 'smtp')) {
                'log' => $this->logMail(),
                'array' => $this->arrayMail(),
                'smtp' => parent::send(),
                default => throw new GlobalException('Invalid mail driver: ' . get_env('MAIL_DRIVER')),
            };
        } catch (Exception $e) {
            throw new GlobalException("Message could not be sent. Mailer Error: {$this->ErrorInfo}");
        }
    }

    private function setBody()
    {
        if ($this->htmlView) {
            $this->Body = app('template')->setData($this->viewData)->render($this->htmlView);
        }

        if ($this->textView) {
            $this->AltBody = app('template')->setData($this->viewData)->render($this->textView);
        }
    }

    protected function arrayMail(): bool
    {
        $mail = [
            'id' => uniqid(),
            'timestamp' => time(),
            'to' => $this->getToAddresses()[0][0],
            'from' => $this->From,
            'subject' => $this->Subject,
            'html_body' => $this->Body,
            'text_body' => $this->AltBody,
        ];

        static::$sentMails[] = $mail;
        return true;
    }

    public static function getSentMails(): array
    {
        return static::$sentMails;
    }

    public static function clearSentMails(): void
    {
        static::$sentMails = [];
    }

    private function setAddresses(array $addresses, string $type)
    {
        foreach ($addresses as $key => $value) {
            list($email, $name) = $this->normalizeAddress($key, $value);

            switch ($type) {
                case 'cc':
                    $this->addCC($email, $name);
                    break;
                case 'bcc':
                    $this->addBCC($email, $name);
                    break;
                case 'reply_to':
                    $this->addReplyTo($email, $name);
                    break;
            }
        }
    }

    private function normalizeAddress($key, $value): array
    {
        if (is_int($key)) {
            return [$value, ''];
        }

        return [$key, $value];
    }

    private function setAttachments(array $paths)
    {
        foreach ($paths as $key => $value) {
            if (is_int($key)) {
                list($path, $name) = [$value, ''];
            } else {
                list($path, $name) = [$key, $value];
            }

            $this->addAttachment($path, $name);
        }
    }

    protected function logMail(): bool
    {
        $mail = [
            'id' => uniqid(),
            'timestamp' => time(),
            'to' => $this->getToAddresses()[0][0],
            'from' => $this->From,
            'subject' => $this->Subject,
            'html_body' => $this->Body,
            'text_body' => $this->AltBody,
        ];

        $logFile = DIR_STORAGE . '/logs/mails.json';
        $mails = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
        $mails[] = $mail;
        file_put_contents($logFile, json_encode($mails, JSON_PRETTY_PRINT));

        return true;
    }
}
