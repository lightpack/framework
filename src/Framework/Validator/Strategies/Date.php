<?php

namespace Lightpack\Validator\Strategies;

use DateTime;
use Lightpack\Utils\Arr;
use Lightpack\Validator\IValidationStrategy;

class Date implements IValidationStrategy
{   
    public function validate(array $dataSource, string $field, $format)
    {
        $data = Arr::get($field, $dataSource);

        return (bool) DateTime::createFromFormat($format, $data);
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s format mismatch", $field);
    }
}