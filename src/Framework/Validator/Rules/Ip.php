<?php

namespace Lightpack\Validator\Rules;

use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Ip implements RuleInterface
{   
    public function validate(array $dataSource, string $field, $param = null)
    {
        $data = (new Arr)->get($field, $dataSource);

        return (bool) filter_var($data, FILTER_VALIDATE_IP);
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("The %s is invalid.", $field);
    }
}