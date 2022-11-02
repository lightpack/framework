<?php

namespace Lightpack\Validator\Rules;

use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Alpha implements RuleInterface
{   
    public function validate(array $dataSource, string $field, $param = null)
    {
        $data = (new Arr)->get($field, $dataSource);

        return ctype_alpha($data);
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("The %s must contain only alphabets.", $field);
    }
}