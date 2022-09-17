<?php

namespace Lightpack\Validator\Rules;

use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Alnum implements RuleInterface
{   
    public function validate(array $dataSource, string $field, $param = null)
    {
        $data = (new Arr)->get($field, $dataSource);

        return ctype_alnum($data);
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s must contain only alphabets and numbers", $field);
    }
}