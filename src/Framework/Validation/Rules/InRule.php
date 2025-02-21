<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class InRule
{
    private string $message;

    public function __construct(private readonly array $values) 
    {
        $this->message = "Must be one of: " . implode(', ', $values);
    }

    public function __invoke($value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (!in_array($item, $this->values, true)) {
                    return false;
                }
            }
            return true;
        }

        return in_array($value, $this->values, true);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
