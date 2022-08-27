<?php

namespace Lightpack\Validator\Strategies;

use Lightpack\Validator\IValidationStrategy;

class Required implements IValidationStrategy
{
    public function validate(array $dataSource, string $field, $param = null)
    {
        $data = $dataSource[$field];

        return trim($data) !== '';
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s is required", $field);
    }
}