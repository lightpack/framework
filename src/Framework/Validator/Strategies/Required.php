<?php

namespace Lightpack\Validator\Strategies;

use Lightpack\Utils\Arr;
use Lightpack\Validator\IValidationStrategy;

class Required implements IValidationStrategy
{
    public function validate(array $dataSource, string $field, $param = null)
    {
        $data = Arr::get($field, $dataSource);

        return trim($data) !== '';
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s is required", $field);
    }
}