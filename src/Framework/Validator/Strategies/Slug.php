<?php

namespace Lightpack\Validator\Strategies;

use Lightpack\Utils\Arr;
use Lightpack\Validator\IValidationStrategy;

class Slug implements IValidationStrategy
{   
    public function validate(array $dataSource, string $field, $param = null)
    {
        $data = Arr::get($field, $dataSource);

        return (bool) preg_match('/^([_-a-zA-Z0-9])+$/i', $data);
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s must contain only dashes, underscores, and alphanumeric characters", $field);
    }
}