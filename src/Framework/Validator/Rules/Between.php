<?php

namespace Lightpack\Validator\Rules;

use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Between implements RuleInterface
{
    private $_min;
    private $_max;
    
    public function validate(array $dataSource, string $field, $range)
    {
        $data = (new Arr)->get($field, $dataSource);

        list($this->_min, $this->_max) = str_getcsv($range, ',');
        
        return ($data >= (int) $this->_min && $data <= (int) $this->_max);
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("The %s must be between %s and %s.", $field, $this->_min, $this->_max);
    }
}