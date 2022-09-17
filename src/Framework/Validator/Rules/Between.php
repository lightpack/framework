<?php

namespace Lightpack\Validator\Rules;


use Lightpack\Validator\StringTrait;use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Between implements RuleInterface
{
    use StringTrait;
    
    private $_min;
    private $_max;
    
    public function validate(array $dataSource, string $field, $range)
    {
        $data = (new Arr)->get($field, $dataSource);

        list($this->_min, $this->_max) = $this->explodeString($range, ',');
        
        return ($data >= (int) $this->_min && $data <= (int) $this->_max);
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s must be between %s and %s", $field, $this->_min, $this->_max);
    }
}