<?php

namespace Lightpack\Validator\Strategies;

use Lightpack\Utils\Arr;
use Lightpack\Validator\StringTrait;
use Lightpack\Validator\IValidationStrategy;

class Same implements IValidationStrategy
{
    
    use StringTrait;
    
    private $_matchTo;
    
    public function validate(array $dataSource, string $field, $matchString)
    {
        $data = Arr::get($field, $dataSource);

        $this->_matchTo = $matchString;
        
        return $data === $dataSource[$matchString];
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s mismatch", $this->_matchTo);
    }
}