<?php

namespace Lightpack\Validator\Strategies;

use Lightpack\Utils\Arr;
use Lightpack\Validator\IValidationStrategy;

class Regex implements IValidationStrategy
{   
    public function validate(array $dataSource, string $field, $regex)
    {
        $data = Arr::get($field, $dataSource);

        return (bool) preg_match($regex, $data);
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s is invalid", $field);
    }
}