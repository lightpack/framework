<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;

class RegexRule
{
    use ValidationMessageTrait;

    public function __construct(private readonly string $pattern) 
    {
        $this->message = "Must match pattern: {$pattern}";
    }

    public function __invoke($value): bool
    {
        return preg_match($this->pattern, $value) === 1;
    }
}
