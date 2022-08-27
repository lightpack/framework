<?php

namespace Lightpack\Validator\Strategies;

use Lightpack\Validator\StringTrait;
use Lightpack\Validator\IValidationStrategy;

class Between implements IValidationStrategy
{
    use StringTrait;
    
    private $_min;
    private $_max;
    
    public function validate(array $dataSource, string $field, $range)
    {
        $data = $dataSource[$field];

        list($this->_min, $this->_max) = $this->explodeString($range, ',');
        
        return ($data >= (int) $this->_min && $data <= (int) $this->_max);
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s must be between %s and %s", $field, $this->_min, $this->_max);
    }
}