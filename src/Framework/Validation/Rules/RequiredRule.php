<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\ValidationMessageTrait;
use Lightpack\Validation\Traits\FileUploadValidationTrait;

class RequiredRule
{
    use ValidationMessageTrait;
    use FileUploadValidationTrait;

    public function __construct()
    {
        $this->message = 'This field is required';
    }

    public function __invoke($value): bool
    {
        if (empty($value)) {
            return false;
        }

        if(is_array($value) && ($this->isEmptySingleFileUpload($value) || $this->isEmptyMultiFileUpload($value))) {
            return false;
        }

        return true;
    }
}
