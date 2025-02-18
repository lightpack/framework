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
        if (!is_array($value) || !isset($value['tmp_name'])) {
            return false;
        }

        return in_array($this->getMimeType($value['tmp_name']), $this->allowedTypes);
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
