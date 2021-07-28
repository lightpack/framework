<?php

namespace Lightpack\Validator\Strategies;

use Lightpack\Validator\IValidationStrategy;

class Callback implements IValidationStrategy
{
    public function validate($data, $param = null)
    {
        return true;
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s is invalid", $field);
    }
}