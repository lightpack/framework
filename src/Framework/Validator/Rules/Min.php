<?php

namespace Lightpack\Validator\Rules;

use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Min implements RuleInterface
{
    private $_length;
    
    public function validate(array $dataSource, string $field, $num)
    {
        $data = (new Arr)->get($field, $dataSource);

        $this->_length = $num;
        
        return strlen($data) >= $num;  
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("The %s must be >= %s.", $field, $this->_length);
    }
}