<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\AbstractAuthenticator;
use Lightpack\Auth\Identifier;

class CookieAuthenticator extends AbstractAuthenticator
{
    public function verify(Identifier $identifier, array $config): bool
    {
        if (!cookie()->has('remember_me')) {
            return false;
        }

        $cookieFragments =  explode('|', cookie()->get('remember_me'));

        if(count($cookieFragments) !== 2) {
            return false;
        }

        list($userId, $cookie) = $cookieFragments;

        $this->identity = $identifier->findByRememberToken($userId, $cookie);

        return null !== $this->identity;
    }
}