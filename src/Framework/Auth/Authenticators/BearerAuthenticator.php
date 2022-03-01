<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\AbstractAuthenticator;
use Lightpack\Auth\Result;

class BearerAuthenticator extends AbstractAuthenticator
{
    public function verify(): Result
    {
       $token = app('request')->bearerToken();
        
        if(null === $token) {
            return new Result;
        }

        $tokenHash = hash_hmac('sha1', $token, '');

        return new Result($this->identifier->findByAuthToken($tokenHash));
    }
}