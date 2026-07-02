<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class AfterRule
{
    use ValidationMessageTrait;

    public function __construct(
        private readonly string $date,
        private readonly ?string $format = null
    ) {
        if ($format) {
            $this->message = "Date must be after {$date} (format: {$format})";
            $this->langKey = 'validation.after_format';
            $this->messageParams = ['date' => $date, 'format' => $format];
        } else {
            $this->message = "Date must be after {$date}";
            $this->langKey = 'validation.after';
            $this->messageParams = ['date' => $date];
        }
    }

    public function __invoke($value): bool
    {
        if ($this->format) {
            $date = \DateTime::createFromFormat($this->format, $value);
            $compare = \DateTime::createFromFormat($this->format, $this->date);

            return $date && $compare && $date > $compare;
        }

        $date = strtotime($value);
        $compare = strtotime($this->date);

        return $date !== false && $compare !== false && $date > $compare;
    }
}
