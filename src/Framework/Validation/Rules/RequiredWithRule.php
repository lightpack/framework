<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Utils\Arr;
use Lightpack\Validation\Traits\ValidationMessageTrait;
use Lightpack\Validation\Traits\FileUploadValidationTrait;

class RequiredWithRule
{
    use ValidationMessageTrait;
    use FileUploadValidationTrait;

    private array $fields;

    public function __construct(
        string|array $fields,
        private Arr $arr
    ) {
        $this->fields = (array) $fields;
        $fieldList = implode(', ', $this->fields);
        $this->message = "This field is required when {$fieldList} is present";
    }

    public function __invoke($value, array $data = []): bool
    {
        // Check if any of the specified fields are present
        $anyFieldPresent = false;
        foreach ($this->fields as $field) {
            $fieldValue = $this->arr->get($field, $data);
            if (!empty($fieldValue) || $fieldValue === '0' || $fieldValue === 0) {
                $anyFieldPresent = true;
                break;
            }
        }

        // If none of the fields are present, this field is not required
        if (!$anyFieldPresent) {
            return true;
        }

        // At least one field is present, so this field is required
        if (empty($value) && $value !== '0' && $value !== 0) {
            return false;
        }

        if (is_array($value) && ($this->isEmptySingleFileUpload($value) || $this->isEmptyMultiFileUpload($value))) {
            return false;
        }

        return true;
    }
}
