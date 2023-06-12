<?php

namespace Lightpack\Http;

use Lightpack\Container\Container;
use Lightpack\Validator\Validator;

class FormRequest extends Request
{
    public function __boot(Container $container, Validator $validator)
    {
        $rules = $container->call($this, 'rules');

        return $validator->setRules($rules)->validate();
    }
}