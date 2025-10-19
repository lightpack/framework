<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Utils\Arr;
use Lightpack\Validation\Traits\ValidationMessageTrait;
use Lightpack\Validation\Traits\FileUploadValidationTrait;

class RequiredIfRule
{
    use ValidationMessageTrait;
    use FileUploadValidationTrait;

    public function __construct(
        private readonly string $field,
        private readonly mixed $value,
        private readonly Arr $arr
    ) {
        $this->message = "This field is required when {$field} is {$value}";
    }

    public function __invoke($value, array $data = []): bool
    {
        $otherValue = $this->arr->get($this->field, $data);

        // If the condition is not met, field is not required
        if ($otherValue !== $this->value) {
            return true;
        }

        // Condition is met, so field is required
        if (empty($value)) {
            return false;
        }

        if(is_array($value) && ($this->isEmptySingleFileUpload($value) || $this->isEmptyMultiFileUpload($value))) {
            return false;
        }

        return true;
    }
}
