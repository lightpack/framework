<?php

namespace Lightpack\Validator\Strategies;

use Lightpack\Validator\StringTrait;
use Lightpack\Validator\IValidationStrategy;

class Same implements IValidationStrategy
{
    
    use StringTrait;
    
    private $_matchTo;
    
    public function validate(array $dataSource, string $field, $matchString)
    {
        $data = $dataSource[$field];

        $this->_matchTo = $matchString;
        
        return $data === $dataSource[$matchString];
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s mismatch", $this->_matchTo);
    }
}