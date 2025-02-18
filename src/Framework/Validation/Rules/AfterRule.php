<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class AfterRule
{
    private string $message;

    public function __construct(
        private readonly string $date,
        private readonly ?string $format = null
    ) {
        $this->message = $format 
            ? "Must be a date after {$date} in format {$format}"
            : "Must be a date after {$date}";
    }

    public function __invoke($value): bool
    {
        if ($this->format) {
            $date = \DateTime::createFromFormat($this->format, $value);
            $compare = \DateTime::createFromFormat($this->format, $this->date);
            return $date && $compare && $date > $compare;
        }

        return strtotime($value) > strtotime($this->date);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
