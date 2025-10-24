<?php

namespace Lightpack\Mail\Drivers;

use Exception as GlobalException;
use Lightpack\Mail\DriverInterface;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class SmtpDriver implements DriverInterface
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    private function configure(): void
    {
        // Configure SMTP
        $this->mailer->isSMTP();
        $this->mailer->SMTPAuth     = true;
        $this->mailer->Host         = get_env('MAIL_HOST');
        $this->mailer->Port         = get_env('MAIL_PORT');
        $this->mailer->Username     = get_env('MAIL_USERNAME');
        $this->mailer->Password     = get_env('MAIL_PASSWORD');
        $this->mailer->SMTPSecure   = get_env('MAIL_ENCRYPTION');

        // Set default from
        $this->mailer->setFrom(
            get_env('MAIL_FROM_ADDRESS'),
            get_env('MAIL_FROM_NAME')
        );

        $this->mailer->isHTML(true);
    }

    public function send(array $data): bool
    {
        try {
            // Data is already normalized by MailData
            // Clear any previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearCCs();
            $this->mailer->clearBCCs();
            $this->mailer->clearReplyTos();
            $this->mailer->clearAttachments();

            // Set from
            if (!empty($data['from'])) {
                $this->mailer->setFrom(
                    $data['from']['email'], 
                    $data['from']['name'] ?? ''
                );
            }

            // Set recipients
            foreach ($data['to'] as $recipient) {
                $this->mailer->addAddress($recipient['email'], $recipient['name'] ?? '');
            }

            // Set CC
            if (!empty($data['cc'])) {
                foreach ($data['cc'] as $cc) {
                    $this->mailer->addCC($cc['email'], $cc['name'] ?? '');
                }
            }

            // Set BCC
            if (!empty($data['bcc'])) {
                foreach ($data['bcc'] as $bcc) {
                    $this->mailer->addBCC($bcc['email'], $bcc['name'] ?? '');
                }
            }

            // Set Reply-To
            if (!empty($data['reply_to'])) {
                foreach ($data['reply_to'] as $replyTo) {
                    $this->mailer->addReplyTo($replyTo['email'], $replyTo['name'] ?? '');
                }
            }

            // Set subject and body
            $this->mailer->Subject = $data['subject'];
            $this->mailer->Body = $data['html_body'];
            
            if (!empty($data['text_body'])) {
                $this->mailer->AltBody = $data['text_body'];
            }

            // Set attachments
            if (!empty($data['attachments'])) {
                foreach ($data['attachments'] as $attachment) {
                    $this->mailer->addAttachment($attachment['path'], $attachment['filename'] ?? '');
                }
            }

            return $this->mailer->send();
        } catch (Exception $e) {
            throw new GlobalException("Message could not be sent. Mailer Error: {$this->mailer->ErrorInfo}");
        }
    }
}
