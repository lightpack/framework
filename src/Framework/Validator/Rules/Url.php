<?php

namespace Lightpack\Validator\Rules;

use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Url implements RuleInterface
{   
    private $_validUrlPrefixes = array('http://', 'https://', 'ftp://');
    
    public function validate(array $dataSource, string $field, $param = null)
    {
        $data = (new Arr)->get($field, $dataSource);

        foreach ($this->_validUrlPrefixes as $prefix) {
            if (strpos($data, $prefix) !== false) {
                return filter_var($data, FILTER_VALIDATE_URL);
            }
        }

        return false;
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s appears to be invalid", $field);
    }
}