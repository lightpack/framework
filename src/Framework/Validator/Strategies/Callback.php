<?php

namespace Lightpack\Validator\Strategies;

use Lightpack\Validator\IValidationStrategy;

class Callback implements IValidationStrategy
{
    public function validate(array $dataSource, string $field, $callback)
    {
        $data = $dataSource[$field];

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