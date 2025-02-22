<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules\File;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class FileExtensionRule
{
    use ValidationMessageTrait;

    private array $allowedExtensions;
    private array $extensionAliases = [
        'jpeg' => 'jpg',
        'tiff' => 'tif',
        'htm' => 'html',
    ];

    public function __construct(array|string $extensions)
    {
        $this->allowedExtensions = array_map('strtolower', (array)$extensions);
        $this->message = 'File extension must be: ' . implode(', ', $this->allowedExtensions);
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

        // Handle multiple files
        if (isset($value['name']) && is_array($value['name'])) {
            foreach ($value['name'] as $filename) {
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                // Check if it's an alias (e.g., 'jpeg' for 'jpg')
                if (isset($this->extensionAliases[$extension])) {
                    $extension = $this->extensionAliases[$extension];
                }
                
                if (!in_array($extension, $this->allowedExtensions)) {
                    return false;
                }
            }
            return true;
        }

        // Handle single file
        $extension = strtolower(pathinfo($value['name'], PATHINFO_EXTENSION));
        
        // Check if it's an alias (e.g., 'jpeg' for 'jpg')
        if (isset($this->extensionAliases[$extension])) {
            $extension = $this->extensionAliases[$extension];
        }
        
        return in_array($extension, $this->allowedExtensions);
    }
}
