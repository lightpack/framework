<?php

namespace Lightpack\Exceptions;

use Lightpack\Validation\Validator;

class ValidationException extends HttpException 
{
    public function __construct(protected Validator $validator) 
    {
        parent::__construct('Request validation failed.');
    }

    public function getErrors() : array
    {
        return $this->validator->getErrors();
    }
}