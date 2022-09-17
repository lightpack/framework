<?php

namespace Lightpack\Validator\Rules;

use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Required implements RuleInterface
{
    public function validate(array $dataSource, string $field, $param = null)
    {
        $data = (new Arr)->get($field, $dataSource);

        return trim($data) !== '';
    }
    
    public function getErrorMessage($field)
    {
        return sprintf("%s is required", $field);
    }
}