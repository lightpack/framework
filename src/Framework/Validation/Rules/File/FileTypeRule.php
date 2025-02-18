<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules\File;

class FileTypeRule
{
    private string $message;
    private array $allowedTypes;

    public function __construct(array|string $types)
    {
        $this->allowedTypes = (array)$types;
        $this->message = 'File type must be: ' . implode(', ', $this->allowedTypes);
    }

    public function __invoke($value, array $data = []): bool 
    {
        if (!is_array($value)) {
            return false;
        }

        // For optional fields, no file is valid
        if (isset($value['error']) && $value['error'] === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        // Single file upload
        if (isset($value['tmp_name']) && !is_array($value['tmp_name'])) {
            return in_array($this->getMimeType($value['tmp_name']), $this->allowedTypes);
        }

        // Multiple file upload
        if (isset($value['tmp_name']) && is_array($value['tmp_name'])) {
            foreach ($value['tmp_name'] as $tmp_name) {
                if (!in_array($this->getMimeType($tmp_name), $this->allowedTypes)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }

    protected function getMimeType(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($path) ?: 'application/octet-stream';
    }
}
