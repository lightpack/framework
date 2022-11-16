<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\Identity;
use Lightpack\Auth\AbstractAuthenticator;

class CookieAuthenticator extends AbstractAuthenticator
{
    public function verify(): ?Identity
    {
        $rememberTokenField = $this->config['fields.remember_token'];

        if (!cookie()->has($rememberTokenField)) {
            return null;
        }
        $cookieFragments =  explode('|', cookie()->get($rememberTokenField) ?? '');

        if (count($cookieFragments) !== 2) {
            return null;
        }

        list($userId, $cookie) = $cookieFragments;

        return $this->identifier->findByRememberToken($userId, $cookie);
    }
}
