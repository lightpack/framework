<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\AbstractAuthenticator;
use Lightpack\Auth\Result;

class CookieAuthenticator extends AbstractAuthenticator
{
    public function verify(): Result
    {
        $rememberTokenField = $this->config['fields.remember_token'];

        if (!cookie()->has($rememberTokenField)) {
            return new Result;
        }

        $cookieFragments =  explode('|', cookie()->get($rememberTokenField));

        if(count($cookieFragments) !== 2) {
            return new Result;
        }

        list($userId, $cookie) = $cookieFragments;

        return new Result($this->identifier->findByRememberToken($userId, $cookie));
    }
}