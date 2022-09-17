<?php

namespace Lightpack\Validator\Rules;

use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Callback implements RuleInterface
{
    public function validate(array $dataSource, string $field, $callback)
    {
        $data = (new Arr)->get($field, $dataSource);

        if(is_callable($callback)) {
            return $callback($data);
        }

        return false;
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s is invalid", $field);
    }
}