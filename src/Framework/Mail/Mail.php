<?php

namespace Lightpack\Mail;

use Exception as GlobalException;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

abstract class Mail
{
    protected PHPMailer $mailer;
    protected $textView;
    protected $htmlView;
    protected $viewData = [];
    protected static $sentMails = [];

    abstract public function dispatch(array $payload = []);

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        
        if (get_env('MAIL_DRIVER', 'smtp') === 'smtp') {
            $this->configureSMTP();
        }

        $this->mailer->setFrom(
            get_env('MAIL_FROM_ADDRESS'),
            get_env('MAIL_FROM_NAME')
        );

        $this->mailer->isHTML(true);
    }

    private function configureSMTP(): void
    {
        $this->mailer->isSMTP();
        $this->mailer->SMTPAuth     = true;
        $this->mailer->Host         = get_env('MAIL_HOST');
        $this->mailer->Port         = get_env('MAIL_PORT');
        $this->mailer->Username     = get_env('MAIL_USERNAME');
        $this->mailer->Password     = get_env('MAIL_PASSWORD');
        $this->mailer->SMTPSecure   = get_env('MAIL_ENCRYPTION');
    }

    public function from(string $address, string $name = ''): self
    {
        $this->mailer->setFrom($address, $name);

        return $this;
    }

    public function to(string $address, string $name = ''): self
    {
        $this->mailer->addAddress($address, $name);

        return $this;
    }

    public function replyTo($address, string $name = ''): self
    {
        if (is_array($address)) {
            $this->setAddresses($address, 'reply_to');

            return $this;
        }

        if (is_string($address)) {
            $this->mailer->addReplyTo($address, $name);
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
            $this->mailer->addCC($address, $name);
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
            $this->mailer->addBCC($address, $name);
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
            $this->mailer->addAttachment($path, $name);
        }

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->mailer->Subject = $subject;

        return $this;
    }

    public function body(string $body): self
    {
        $this->mailer->Body = $body;

        return $this;
    }

    public function altBody(string $body): self
    {
        $this->mailer->AltBody = $body;

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
                'smtp' => $this->mailer->send(),
                default => throw new GlobalException('Invalid mail driver: ' . get_env('MAIL_DRIVER')),
            };
        } catch (Exception $e) {
            throw new GlobalException("Message could not be sent. Mailer Error: {$this->mailer->ErrorInfo}");
        }
    }

    private function setBody()
    {
        if ($this->htmlView) {
            $this->mailer->Body = app('template')->setData($this->viewData)->include($this->htmlView);
        }

        if ($this->textView) {
            $this->mailer->AltBody = app('template')->setData($this->viewData)->include($this->textView);
        }
    }



    protected function logMail(): bool
    {
        $mail = $this->getNormalizedMailData();

        $logFile = DIR_STORAGE . '/logs/mails.json';
        $mails = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
        $mails[] = $mail;
        file_put_contents($logFile, json_encode($mails, JSON_PRETTY_PRINT));

        return true;
    }

    protected function arrayMail(): bool
    {
        $mail = $this->getNormalizedMailData();

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
                    $this->mailer->addCC($email, $name);
                    break;
                case 'bcc':
                    $this->mailer->addBCC($email, $name);
                    break;
                case 'reply_to':
                    $this->mailer->addReplyTo($email, $name);
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

            $this->mailer->addAttachment($path, $name);
        }
    }

    private function getNormalizedMailData(): array
    {
        return [
            'id' => uniqid(),
            'timestamp' => time(),
            'to' => array_column($this->mailer->getToAddresses(), 0),
            'from' => $this->mailer->From,
            'subject' => $this->mailer->Subject,
            'html_body' => $this->mailer->Body,
            'text_body' => $this->mailer->AltBody,
            'cc' => array_column($this->mailer->getCcAddresses(), 0),
            'bcc' => array_column($this->mailer->getBccAddresses(), 0),
            'reply_to' => array_column($this->mailer->getReplyToAddresses(), 0),
            'attachments' => array_map(function ($attachment) {
                return [
                    'filename' => $attachment[1],
                    'path' => $attachment[0],
                ];
            }, $this->mailer->getAttachments()),
        ];
    }
}
