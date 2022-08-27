<?php

namespace Lightpack\Validator\Strategies;

use Lightpack\Utils\Arr;
use Lightpack\Validator\IValidationStrategy;

class Length implements IValidationStrategy
{
    private $_length;
    
    public function validate(array $dataSource, string $field, $num)
    {
        $data = Arr::get($field, $dataSource);

        $this->_length = (int) $num;
        
        return mb_strlen($data) === $this->_length;
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s must have length %s", $field, $this->_length);
    }
}