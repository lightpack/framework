<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules\File;

class MultipleFileRule
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

        // For optional fields, no file is valid
        if (isset($value['error']) && $value['error'] === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        // Get count of files from the name array
        $count = 0;
        if (isset($value['name'])) {
            if (is_array($value['name'])) {
                $count = count($value['name']);
            } else {
                $count = 1;
            }
        }

        if ($this->min !== null && $count < $this->min) {
            $this->message = "Must upload at least {$this->min} files";
            return false;
        }

        if ($this->max !== null && $count > $this->max) {
            $this->message = "Cannot upload more than {$this->max} files";
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
