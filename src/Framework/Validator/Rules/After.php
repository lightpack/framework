<?php

namespace Lightpack\Validator\Rules;

use DateTime;

use Lightpack\Validator\StringTrait;use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class After implements RuleInterface
{
    
    private $_errorType = 'date';
    private $_afterDate;
    private $_dateFormat;
    
    public function validate(array $dataSource, string $field, $string)
    {
        $data = (new Arr)->get($field, $dataSource);

        list($this->_dateFormat, $this->_afterDate) = str_getcsv(str_replace('/', '', $string), ',');
    
        if(($data = DateTime::createFromFormat($this->_dateFormat, $data)) === false)
		{
            $this->_errorType = 'format';
			return false;
		}

		return ($data->getTimestamp() > DateTime::createFromFormat($this->_dateFormat, $this->_afterDate)->getTimestamp());
    }
    
    public function getErrorMessage($field)
    {
        if($this->_errorType === 'format') {
            $message = sprintf("%s must match format: %s", $field, $this->_dateFormat);
        } else {
            $message = sprintf("%s must be after %s", $field, $this->_afterDate);
        }
        return $message;
    }
}