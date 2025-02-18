<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Utils\Arr;

class DifferentRule
{
    private string $message;

    public function __construct(
        private readonly string $field,
        private array &$data,
        private readonly Arr $arr
    ) {
        $this->message = "Must be different from {$field}";
    }

    public function __invoke($value): bool
    {
        return $value !== $this->arr->get($this->field, $this->data);
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
