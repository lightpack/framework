<?php

namespace Lightpack\Validator;

use RuntimeException;
use Lightpack\Utils\Arr;

class AbstractValidator
{
    // use StringTrait;

    /**
     * Holds the input data for validation.
     *
     * @var array
     */
    protected $dataSource = [];

    /**
     * Holds the set of rules for validation.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Holds the set of validation errors
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Holds the set of custom error messages.
     *
     * @var array
     */
    protected $customErrors = [];

    /**
     * Holds the set of custom field labels.
     *
     * @var array
     */
    protected $customLabels = [];

    /**
     * This method has to be called by the extending class to add a rule for a filed.
     *
     * @param  string  $key    The field name or key in the data source to be validated
     * @param  string|array  $rules  The string of rules e.g. 'required|alpha|min:3|max:8'
     * @throws RuntimeException
     */
    protected function addRule($key, $rules)
    {
        if (is_string($rules) && trim($rules)) {
            $this->rules[$key] =  str_getcsv($rules, '|');
            return;
        }

        if (is_array($rules) && isset($rules['rules'])) {
            if(is_callable($rules['rules'])) {
                $this->rules[$key] =  $rules['rules'];
            } else {
                $this->rules[$key] =  str_getcsv($rules['rules'], '|');
            }

            $this->customErrors[$key] = $rules['error'] ?? null;
            $this->customLabels[$key] = $rules['label'] ?? null;
            
            return;
        }

        if (is_callable($rules)) {
            $this->rules[$key] =  $rules;
            return;
        }

        if(is_array($rules) && isset($rules['file'])) {
            $this->rules[$key] = $rules;
            return;
        }

        throw new RuntimeException(sprintf("Could not add the rules for key: %s", $key));
    }

    /**
     * This method has to be called by the extending class to process the rules.
     */
    protected function processRules()
    {
        if (empty($this->rules)) {
            return;
        }

        foreach ($this->rules as $field => $values) {
            // Rules for file uploads
            if(is_array($values) && isset($values['file'])) {
                $this->validate($field, 'upload', $values['file']);
                continue;
            }

            if (is_callable($values)) {
                $this->validate($field, 'callback');
                continue;
            }

            foreach ($values as $value) {
                if (
                    !in_array('required', $values, true) && // if current field is not required &&
                    empty((new Arr)->get($field, $this->dataSource)) // no data has been provided then
                ) {
                    continue; // skip the loop
                }

                $continue = true;

                if (strpos($value, ':') !== false) {
                    list($rule, $param) = str_getcsv($value, ':');
                    $continue = $this->validate($field, $rule, $param);
                } else {
                    $rule = $value;
                    $continue = $this->validate($field, $rule);
                }

                if (!$continue) { //break validating further the same field as soon as we break
                    break;
                }
            }
        }
    }

    /**
     * This acts as an internal method for this class. Its role is to call the
     * appropriate validation strategy class and return true on successful validation
     * else false.
     *
     * @param   string   $field     The name of the field to validate
     * @param   string   $rule      Rulename to be used
     * @param   mixed    $param     An extra parameter for the strategy (if required)
     * @return  boolean             Returns true if the field passes the rule else false
     */
    private function validate($field, $rule, $param = null)
    {
        $isValidFlag = true; // we are optimistic
        $factoryInstance = new RuleFactory($rule);
        $ruleInstance = $factoryInstance->getRule();

        if ($param) {
            $isValidFlag = $ruleInstance->validate($this->dataSource, $field, $param);
        } elseif ($rule === 'callback') {
            $isValidFlag = $ruleInstance->validate($this->dataSource, $field, $this->rules[$field]);
        } else {
            $isValidFlag = $ruleInstance->validate($this->dataSource, $field);
        }

        if ($isValidFlag === false) {
            $label = $this->customLabels[$field] ?? $field;
            $this->errors[$field] = $this->customErrors[$field] ?? $ruleInstance->getErrorMessage($label);
        }

        return $isValidFlag;
    }
}
