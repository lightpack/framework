<?php

namespace Lightpack\Validator;

/**
 * An interface to implemented by all our validation strategy classes.
 */
interface RuleInterface
{
    /**
     * The method to be called to perform validation on data.
     *
     * @param  array  $dataSource  The data source to be validated.
     * @param  string  $key  The field label in data source.
     * @param  string  $value  The value to validate against.
     */
    public function validate(array $dataSource, string $filed, string $param);

    /**
     * The method to be called to access the generated error message.
     *
     * @access public
     * @param   string  $field  The field for which the error message is required.
     * @return  string
     */
    public function getErrorMessage(string $key);
}
