<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules\File;

class FileExtensionRule
{
    private string $message;
    private array $allowedExtensions;

    public function __construct(array|string $extensions)
    {
        $this->allowedExtensions = array_map('strtolower', (array)$extensions);
        $this->message = 'File extension must be: ' . implode(', ', $this->allowedExtensions);
    }

    public function __invoke($value, array $data = []): bool 
    {
        if (!is_array($value) || !isset($value['name'])) {
            return false;
        }

        $extension = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION));
        
        return in_array($extension, $this->allowedExtensions);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
