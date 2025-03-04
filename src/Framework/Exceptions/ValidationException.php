<?php

namespace Lightpack\Exceptions;

use Lightpack\Validation\Validator as ValidationValidator;
use Lightpack\Validator\Validator;

class ValidationException extends HttpException 
{
    public function __construct(protected ValidationValidator $validator) 
    {
        parent::__construct('Request validation failed.');
    }

    public function getErrors() : array
    {
        return $this->validator->getErrors();
    }
}