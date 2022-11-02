<?php

namespace Lightpack\Validator\Rules;

use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Regex implements RuleInterface
{   
    public function validate(array $dataSource, string $field, $regex)
    {
        $data = (new Arr)->get($field, $dataSource);

        return (bool) preg_match($regex, $data);
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("The %s is invalid.", $field);
    }
}