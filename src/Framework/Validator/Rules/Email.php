<?php

namespace Lightpack\Validator\Rules;

use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Email implements RuleInterface
{   
    public function validate(array $dataSource, string $field, $param = null)
    {
        $data = (new Arr)->get($field, $dataSource);

        return filter_var($data, FILTER_VALIDATE_EMAIL);
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s is invalid", $field);
    }
}