<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class DateRule
{
    private string $message;

    public function __construct(private readonly ?string $format = null) 
    {
        $this->message = $format 
            ? "Must be a valid date in format {$format}"
            : 'Must be a valid date';
    }

    public function __invoke($value): bool
    {
        if ($this->format) {
            $date = \DateTime::createFromFormat($this->format, $value);
            return $date && $date->format($this->format) === $value;
        }

        return strtotime($value) !== false;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
