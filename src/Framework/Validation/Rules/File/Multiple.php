<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules\File;

class Multiple
{
    private string $message;
    private ?int $min;
    private ?int $max;

    public function __construct(?int $min = null, ?int $max = null)
    {
        $this->min = $min;
        $this->max = $max;
        $this->message = $this->buildMessage();
    }

    public function __invoke($value, array $data = []): bool 
    {
        if (!is_array($value)) {
            return false;
        }

        // Handle non-multiple uploads
        if (isset($value['name']) && !is_array($value['name'])) {
            $count = 1;
        } else {
            $count = count($value['name'] ?? []);
        }

        if ($this->min !== null && $count < $this->min) {
            $this->message = sprintf('At least %d files must be uploaded', $this->min);
            return false;
        }

        if ($this->max !== null && $count > $this->max) {
            $this->message = sprintf('No more than %d files can be uploaded', $this->max);
            return false;
        }

        return true;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }

    private function buildMessage(): string
    {
        if ($this->min !== null && $this->max !== null) {
            return sprintf('Number of files must be between %d and %d', $this->min, $this->max);
        }

        if ($this->min !== null) {
            return sprintf('At least %d files must be uploaded', $this->min);
        }

        if ($this->max !== null) {
            return sprintf('No more than %d files can be uploaded', $this->max);
        }

        return 'Invalid number of files';
    }
}
