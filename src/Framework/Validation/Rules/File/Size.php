<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules\File;

class Size
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
        if (!is_array($value) || !isset($value['size'])) {
            return false;
        }

        return $value['size'] <= $this->maxBytes;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }

    private function parseSize(string $size): int 
    {
        $units = ['B' => 1, 'K' => 1024, 'M' => 1024 * 1024, 'G' => 1024 * 1024 * 1024];
        $unit = strtoupper(substr($size, -1));
        $value = (int)substr($size, 0, -1);

        return isset($units[$unit]) ? $value * $units[$unit] : $value;
    }
}
