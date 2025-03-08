<?php

namespace Lightpack\Testing\Http;

use Lightpack\Mail\Mail;

trait MailAssertionTrait
{
    public function assertMailSent(): self
    {
        $this->assertNotEmpty(
            Mail::getSentMails(),
            'Expected an email to be sent, but none were sent.'
        );

        return $this;
    }

    public function assertMailNotSent(): self
    {
        $this->assertEmpty(
            Mail::getSentMails(),
            'Expected no emails to be sent, but some were sent.'
        );

        return $this;
    }

    public function assertMailSentTo(string $email): self
    {
        $sent = false;
        foreach (Mail::getSentMails() as $mail) {
            if ($mail['to'] === $email) {
                $sent = true;
                break;
            }
        }

        $this->assertTrue($sent, "Expected email to be sent to {$email}, but it wasn't.");
        return $this;
    }

    public function assertMailSubject(string $subject): self
    {
        $found = false;
        foreach (Mail::getSentMails() as $mail) {
            if ($mail['subject'] === $subject) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, "Expected email with subject '{$subject}', but none found.");
        return $this;
    }

    public function assertMailContains(string $text): self
    {
        $found = false;
        foreach (Mail::getSentMails() as $mail) {
            if (str_contains($mail['html_body'], $text) || str_contains($mail['text_body'], $text)) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, "Expected email containing text '{$text}', but none found.");
        return $this;
    }
}
