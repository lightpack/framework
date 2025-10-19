<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Utils\Arr;
use Lightpack\Validation\Traits\ValidationMessageTrait;
use Lightpack\Validation\Traits\FileUploadValidationTrait;

class RequiredUnlessRule
{
    use ValidationMessageTrait;
    use FileUploadValidationTrait;

    public function __construct(
        private string $field,
        private mixed $value,
        private Arr $arr
    ) {
        $this->message = "This field is required unless {$field} is {$value}";
    }

    public function __invoke($value, array $data = []): bool
    {
        $otherValue = $this->arr->get($this->field, $data);

        // If the condition is met (field equals value), this field is not required
        if ($otherValue === $this->value) {
            return true;
        }

        // Condition not met, so this field is required
        if (empty($value) && $value !== '0' && $value !== 0) {
            return false;
        }

        if (is_array($value) && ($this->isEmptySingleFileUpload($value) || $this->isEmptyMultiFileUpload($value))) {
            return false;
        }

        return true;
    }
}
