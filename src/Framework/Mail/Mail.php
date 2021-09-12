<?php

namespace Lightpack\Mail;

use Exception as GlobalException;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

abstract class Mail extends PHPMailer
{
    protected $textView;
    protected $htmlView;
    protected $data = [];

    abstract public function execute(array $payload = []);

    public function __construct()
    {
        $this->isSMTP();
        $this->SMTPDebug    = get_env('APP_DEBUG') ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
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
        if(is_array($address)) {
            $this->setAddresses($address, 'reply_to');

            return $this;
        }

        if(is_string($address)) {
            $this->addReplyTo($address, $name);
        }

        return $this;
    }

    public function cc($address, string $name = ''): self
    {
        if(is_array($address)) {
            $this->setAddresses($address, 'cc');

            return $this;
        }

        if(is_string($address)) {
            $this->addCC($address, $name);
        }

        return $this;
    }

    public function bcc($address, string $name = ''): self
    {
        if(is_array($address)) {
            $this->setAddresses($address, 'bcc');

            return $this;
        }

        if(is_string($address)) {
            $this->addBCC($address, $name);
        }

        return $this;
    }

    public function attach($path, string $name = ''): self
    {
        if(is_array($path)) {
            $this->setAttachments($path);

            return $this;
        }

        if(is_string($path)) {
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

    public function send()
    {
        $this->setBody();

        try {
            parent::send();
        } catch (Exception $e) {
            throw new GlobalException("Message could not be sent. Mailer Error: {$this->ErrorInfo}");
        }
    }

    private function setBody()
    {
        if($this->htmlView) {
            $this->Body = app('template')->setData($this->data)->render($this->htmlView);
        }

        if($this->textView) {
            $this->AltBody = app('template')->setData($this->data)->render($this->textView);
        }
    }

    private function setAddresses(array $addresses, string $type)
    {
        foreach($addresses as $key => $value) {
            list($email, $name) = $this->normalizeAddress($key, $value);

            switch($type) {
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
        if(is_int($key)) {
            return [$value, ''];
        }

        return [$key, $value];
    }

    private function setAttachments(array $paths)
    {
        foreach($paths as $key => $value) {
            if(is_int($key)) {
                list($path, $name) = [$value, ''];
            } else {
                list($path, $name) = [$key, $value];
            }

            $this->addAttachment($path, $name);
        }
    }
}
