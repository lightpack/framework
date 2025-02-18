<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Utils\Arr;

class RequiredIfRule
{
    private string $message;

    public function __construct(
        private readonly string $field,
        private array &$data,
        private readonly Arr $arr,
        private readonly mixed $value = null
    ) {
        $this->message = $value === null
            ? "Required when {$field} is true"
            : "Required when {$field} is {$value}";
    }

    public function __invoke($value): bool
    {
        $dependentValue = $this->arr->get($this->field, $this->data);
        
        // If no specific value is provided, check if dependent field is truthy
        if ($this->value === null) {
            $required = $dependentValue === true || $dependentValue === 1 || $dependentValue === '1';
        } else {
            $required = $dependentValue === $this->value;
        }

        // If not required, any value is valid
        if (!$required) {
            return true;
        }

        // If required, must have a non-null value
        return $value !== null && $value !== '';
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
