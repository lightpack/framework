<?php

namespace Lightpack\Validator\Rules;

use DateTime;

use Lightpack\Utils\Arr;
use Lightpack\Validator\RuleInterface;

class Before implements RuleInterface
{
    private $_errorType = 'date';
    private $_beforeDate;
    private $_dateFormat;
    
    public function validate(array $dataSource, string $field, $string)
    {
        $data = (new Arr)->get($field, $dataSource);

        list($this->_dateFormat, $this->_beforeDate) = str_getcsv(str_replace('/', '', $string), ',');
    
        if(($data = DateTime::createFromFormat($this->_dateFormat, $data)) === false)
		{
            $this->_errorType = 'format';
			return false;
		}

		return ($data->getTimestamp() < DateTime::createFromFormat($this->_dateFormat, $this->_beforeDate)->getTimestamp());
    }
    
    public function getErrorMessage($field)
    {
        if($this->_errorType === 'format') {
            $message = sprintf("The %s must match format: %s.", $field, $this->_dateFormat);
        } else {
            $message = sprintf("The %s must be before %s.", $field, $this->_beforeDate);
        }
        return $message;
    }
}