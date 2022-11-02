<?php

namespace Lightpack\Validator;

use Lightpack\Exceptions\ValidationException;

class Validator extends AbstractValidator
{
    /**
     * @param   array  $dataSource  Array to validate.
     */
    public function setInput(array $dataSource): self
    {
        $this->dataSource = $dataSource;

        return $this;
    }

    /**
     * This is the method to be called when setting a rule for a data field. For
     * an example, to validate field "username" with rules required, alpha, min,
     * and max, we could do it like:
     *
     * $validator->setRule('username', 'required|alpha|min:5|max:12');
     *
     * The rules are piped together and are processed in the order specified in the
     * rules string.
     * 
     * The key to validate is stored in the rules array only if it is a valid key
     * i.e. it has to be present as a key in the array of data to be validated.
     *
     * @param  string  $key    The name of the data key or field to validate.
     * @param  string|array|callable  $rules  The rules to apply to the data key.
     */
    public function setRule($key, $rules)
    {
        $this->addRule($key, $rules);

        return $this;
    }

    /**
     * This method provides a way to bunch a number of rules
     * together.
     * 
     * Example:
     * 
     * $validator->setRules([
     *      'email' => 'required|email',
     *      'name' => 'required|alpha:min:3',
     * ]);
     * 
     * Or, provide a nested array for custom field label and error messages. 
     * 
     * $validator->setRules([
     *      'email' => 'required|email',
     *      'name' => [
     *           'rules' => 'required|alpha:min:3', 
     *           'label' => 'Your name', 
     *           'error'=> 'Name is required'
     *      ],
     * ]);
     */
    public function setRules(array $config): self
    {
        foreach ($config as $key => $rules) {
            $this->setRule($key, $rules);
        }

        return $this;
    }

    /**
     * Runs the validation rules against the data source.
     */
    public function run(): self
    {
        $this->processRules();

        return $this;
    }

    /**
     * Runs the validation rules against the data source. If there are any errors,
     * it throws a ValidationException.
     *
     * @throws ValidationException
     */
    public function validate(): void
    {
        $this->run();

        if ($this->hasErrors()) {
            throw new ValidationException($this);
        }
    }

    /**
     * Check if validation has errors.
     * 
     * This method confirms the state of overall validation. Call this method to
     * ensure that the data source passes all the validation rules imposed.
     *
     * @access  public
     * @return  boolean  Return true if we have no validation errors else false.
     */
    public function hasErrors()
    {
        $this->processRules();

        return empty($this->errors) === false;
    }

    /**
     * This method returns the error message associated with a data field that failed
     * the validation. In case there is no error, it simply returns an empty string.
     *
     * @access  public
     * @return  string  The error message for a field.
     */
    public function getError($field)
    {
        return isset($this->errors[$field]) ? $this->errors[$field] : '';
    }

    /**
     * This method returns an array of all errors after validation.
     *
     * @access  public
     * @return  array  An array of error messages.
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
