<?php

namespace Lightpack\Validator\Rules;


use Lightpack\Validator\StringTrait;use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Same implements RuleInterface
{
    
    
    private $_matchTo;
    
    public function validate(array $dataSource, string $field, $matchString)
    {
        $data = (new Arr)->get($field, $dataSource);

        $this->_matchTo = $matchString;
        
        return $data === $dataSource[$matchString];
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s mismatch", $this->_matchTo);
    }
}