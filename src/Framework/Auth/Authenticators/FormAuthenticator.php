<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\AbstractAuthenticator;
use Lightpack\Auth\Result;

class FormAuthenticator extends AbstractAuthenticator
{
    public function verify(): Result
    {
        $credentials = request()->isJson() ? request()->json() : request()->post();

        if(empty($credentials)) {
            return new Result;
        }

        $identity = $this->identifier->findByCredentials($credentials);

        return new Result($identity);
    }
}