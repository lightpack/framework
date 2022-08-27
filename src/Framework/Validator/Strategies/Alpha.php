<?php

namespace Lightpack\Validator\Strategies;

use Lightpack\Validator\IValidationStrategy;

class Alpha implements IValidationStrategy
{   
    public function validate(array $dataSource, string $field, $param = null)
    {
        $data = $dataSource[$field];

        return ctype_alpha($data);
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s must contain only alphabets", $field);
    }
}