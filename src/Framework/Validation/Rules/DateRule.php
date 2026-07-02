<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class DateRule
{
    use ValidationMessageTrait;

    public function __construct(private readonly ?string $format = null)
    {
        if ($format) {
            $this->message = "Must be a valid date in format {$format}";
            $this->langKey = 'validation.date_format';
            $this->messageParams = ['format' => $format];
        } else {
            $this->message = 'Must be a valid date';
            $this->langKey = 'validation.date';
        }
    }

    public function __invoke($value): bool
    {
        if ($this->format) {
            $date = \DateTime::createFromFormat($this->format, $value);

            return $date && $date->format($this->format) === $value;
        }

        return strtotime($value) !== false;
    }
}
