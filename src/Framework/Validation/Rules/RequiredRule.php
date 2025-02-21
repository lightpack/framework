<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

use Lightpack\Validation\Traits\FileUploadValidationTrait;

class RequiredRule
{
    use FileUploadValidationTrait;

    private string $message = 'This field is required';

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

    public function getMessage(): string 
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
