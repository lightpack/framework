<?php

namespace Lightpack\Validator\Rules;

use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Length implements RuleInterface
{
    private $_length;
    
    public function validate(array $dataSource, string $field, $num)
    {
        $data = (new Arr)->get($field, $dataSource);

        $this->_length = (int) $num;
        
        return mb_strlen($data) === $this->_length;
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("The %s must have length %s.", $field, $this->_length);
    }
}