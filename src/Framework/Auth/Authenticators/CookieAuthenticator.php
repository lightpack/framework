<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\Authenticator;
use Lightpack\Auth\Identifier;
use Lightpack\Auth\Identity;

class CookieAuthenticator implements Authenticator
{
    public function verify(Identifier $identifier, array $config): ?Identity
    {
        if (!cookie()->has('remember_me')) {
            return null;
        }

        $cookieFragments =  explode('|', cookie()->get('remember_me'));

        if(count($cookieFragments) !== 2) {
            return null;
        }

        list($userId, $cookie) = $cookieFragments;

        return $identifier->findByRememberToken($userId, $cookie);
    }
}