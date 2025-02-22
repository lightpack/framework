<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Utils\Arr;
use Lightpack\Validation\Traits\ValidationMessageTrait;

class DifferentRule
{
    use ValidationMessageTrait;

    public function __construct(
        private readonly string $field,
        private readonly Arr $arr
    ) {
        $this->message = "Must be different from {$field}";
    }

    public function __invoke($value, array $data = []): bool
    {
        return $value !== $this->arr->get($this->field, $data);
    }
}
