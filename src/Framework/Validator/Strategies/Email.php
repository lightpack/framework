<?php

namespace Lightpack\Validator\Strategies;

use Lightpack\Utils\Arr;
use Lightpack\Validator\IValidationStrategy;

class Email implements IValidationStrategy
{   
    public function validate(array $dataSource, string $field, $param = null)
    {
        $data = Arr::get($field, $dataSource);

        return filter_var($data, FILTER_VALIDATE_EMAIL);
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s is invalid", $field);
    }
}