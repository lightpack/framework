<?php

namespace Lightpack\Validator\Rules;

use DateTime;
use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Date implements RuleInterface
{   
    public function validate(array $dataSource, string $field, $format)
    {
        $data = (new Arr)->get($field, $dataSource);

        return (bool) DateTime::createFromFormat($format, $data);
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("The %s format mismatch.", $field);
    }
}