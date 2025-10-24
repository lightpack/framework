<?php

namespace Lightpack\Mail;

interface DriverInterface
{
    /**
     * Send an email
     * 
     * @param array $data Normalized mail data containing:
     *   - to: array of recipients [['email' => 'user@example.com', 'name' => 'User']]
     *   - from: array ['email' => 'sender@example.com', 'name' => 'Sender']
     *   - subject: string
     *   - html_body: string
     *   - text_body: string|null
     *   - cc: array (optional)
     *   - bcc: array (optional)
     *   - reply_to: array (optional)
     *   - attachments: array (optional) [['path' => '/path/to/file', 'name' => 'filename.txt']]
     * 
     * @return bool True on success
     * @throws \Exception on failure
     */
    public function send(array $data): bool;
}
