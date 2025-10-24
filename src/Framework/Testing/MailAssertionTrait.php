<?php

namespace Lightpack\Testing;

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

    public function assertMailSentFrom(string $email): self
    {
        $found = false;
        foreach (Mail::getSentMails() as $mail) {
            if ($mail['from']['email'] === $email) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, "Expected email to be sent from {$email}, but it wasn't.");
        return $this;
    }

    public function assertMailCount(int $count): self
    {
        $this->assertCount(
            $count,
            Mail::getSentMails(),
            "Expected {$count} emails to be sent, but " . count(Mail::getSentMails()) . " were sent."
        );

        return $this;
    }

    public function assertMailSentTo(string $email): self
    {
        $sent = false;
        foreach (Mail::getSentMails() as $mail) {
            foreach ($mail['to'] as $recipient) {
                if ($recipient['email'] === $email) {
                    $sent = true;
                    break 2; // Break out of both loops
                }
            }
        }

        $this->assertTrue($sent, "Expected email to be sent to {$email}, but it wasn't.");
        return $this;
    }

    public function assertNoMailSentTo(string $email): self
    {
        $sent = false;
        foreach (Mail::getSentMails() as $mail) {
            foreach ($mail['to'] as $recipient) {
                if ($recipient['email'] === $email) {
                    $sent = true;
                    break 2;
                }
            }
        }

        $this->assertFalse($sent, "Expected no email to be sent to {$email}, but one was sent.");
        return $this;
    }

    public function assertMailSentToAll(array $emails): self
    {
        $allSent = false;
        foreach (Mail::getSentMails() as $mail) {
            $allFound = true;
            foreach ($emails as $email) {
                $found = false;
                foreach ($mail['to'] as $recipient) {
                    if ($recipient['email'] === $email) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $allFound = false;
                    break;
                }
            }
            if ($allFound) {
                $allSent = true;
                break;
            }
        }

        $this->assertTrue($allSent, "Expected email to be sent to all recipients: " . implode(', ', $emails));
        return $this;
    }

    public function assertMailCc(string $email): self
    {
        $found = false;
        foreach (Mail::getSentMails() as $mail) {
            foreach ($mail['cc'] as $recipient) {
                if ($recipient['email'] === $email) {
                    $found = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($found, "Expected email to be CC'd to {$email}, but it wasn't.");
        return $this;
    }

    public function assertMailBcc(string $email): self
    {
        $found = false;
        foreach (Mail::getSentMails() as $mail) {
            foreach ($mail['bcc'] as $recipient) {
                if ($recipient['email'] === $email) {
                    $found = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($found, "Expected email to be BCC'd to {$email}, but it wasn't.");
        return $this;
    }

    public function assertMailReplyTo(string $email): self
    {
        $found = false;
        foreach (Mail::getSentMails() as $mail) {
            foreach ($mail['reply_to'] as $recipient) {
                if ($recipient['email'] === $email) {
                    $found = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($found, "Expected email to have reply-to address {$email}, but it didn't.");
        return $this;
    }

    public function assertMailHasAttachment(string $filename): self
    {
        $found = false;
        foreach (Mail::getSentMails() as $mail) {
            foreach ($mail['attachments'] as $attachment) {
                if ($attachment['name'] === $filename) {
                    $found = true;
                    break 2; // Break out of both loops
                }
            }
        }

        $this->assertTrue($found, "Expected email to have attachment '{$filename}', but none found.");
        return $this;
    }

    public function assertMailHasNoAttachments(): self
    {
        $found = false;
        foreach (Mail::getSentMails() as $mail) {
            if (!empty($mail['attachments'])) {
                $found = true;
                break;
            }
        }

        $this->assertFalse($found, "Expected email to have no attachments, but attachments were found.");
        return $this;
    }

    public function assertMailCcAll(array $emails): self
    {
        $allCced = false;
        foreach (Mail::getSentMails() as $mail) {
            $allFound = true;
            foreach ($emails as $email) {
                $found = false;
                foreach ($mail['cc'] as $recipient) {
                    if ($recipient['email'] === $email) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $allFound = false;
                    break;
                }
            }
            if ($allFound) {
                $allCced = true;
                break;
            }
        }

        $this->assertTrue($allCced, "Expected email to be CC'd to all addresses: " . implode(', ', $emails));
        return $this;
    }

    public function assertMailBccAll(array $emails): self
    {
        $allBcced = false;
        foreach (Mail::getSentMails() as $mail) {
            $allFound = true;
            foreach ($emails as $email) {
                $found = false;
                foreach ($mail['bcc'] as $recipient) {
                    if ($recipient['email'] === $email) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $allFound = false;
                    break;
                }
            }
            if ($allFound) {
                $allBcced = true;
                break;
            }
        }

        $this->assertTrue($allBcced, "Expected email to be BCC'd to all addresses: " . implode(', ', $emails));
        return $this;
    }

    public function assertMailReplyToAll(array $emails): self
    {
        $allFound = false;
        foreach (Mail::getSentMails() as $mail) {
            $allMatched = true;
            foreach ($emails as $email) {
                $found = false;
                foreach ($mail['reply_to'] as $recipient) {
                    if ($recipient['email'] === $email) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $allMatched = false;
                    break;
                }
            }
            if ($allMatched) {
                $allFound = true;
                break;
            }
        }

        $this->assertTrue($allFound, "Expected email to have all reply-to addresses: " . implode(', ', $emails));
        return $this;
    }

    public function assertMailHasAttachments(array $filenames): self
    {
        $allFound = false;
        foreach (Mail::getSentMails() as $mail) {
            $allMatched = true;
            foreach ($filenames as $filename) {
                $fileFound = false;
                foreach ($mail['attachments'] as $attachment) {
                    if ($attachment['name'] === $filename) {
                        $fileFound = true;
                        break;
                    }
                }
                if (!$fileFound) {
                    $allMatched = false;
                    break;
                }
            }
            if ($allMatched) {
                $allFound = true;
                break;
            }
        }

        $this->assertTrue($allFound, "Expected email to have all attachments: " . implode(', ', $filenames));
        return $this;
    }
}
