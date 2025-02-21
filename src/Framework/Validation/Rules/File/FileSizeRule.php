<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules\File;

class FileSizeRule
{
    private string $message;
    private int $maxBytes;

    public function __construct(string $size)
    {
        $this->maxBytes = $this->parseSize($size);
        $this->message = "File size must not exceed {$size}";
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
        if (isset($value['size']) && !is_array($value['size'])) {
            return $value['size'] <= $this->maxBytes;
        }

        // Multiple file upload
        if (isset($value['size']) && is_array($value['size'])) {
            foreach ($value['size'] as $size) {
                if ($size > $this->maxBytes) {
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

    private function parseSize(string $size): int 
    {
        $size = strtoupper(trim($size));
        
        // Define units with their byte multipliers
        $units = [
            'B' => 1,
            'KB' => 1024,
            'MB' => 1024 * 1024,
            'GB' => 1024 * 1024 * 1024,
        ];

        // Extract numeric value and unit
        if (preg_match('/^(\d+)\s*([KMGB]+)B?$/i', $size, $matches)) {
            $value = (int)$matches[1];
            $unit = $matches[2];
            return $value * ($units[$unit] ?? 1);
        }

        // If no unit specified or invalid format, assume bytes
        return (int)$size;
    }
}
