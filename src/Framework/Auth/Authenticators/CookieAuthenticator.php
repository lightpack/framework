<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\Authenticator;
use Lightpack\Auth\Identifier;

class CookieAuthenticator implements Authenticator
{
    public function verify(Identifier $identifier, array $config)
    {
        if (!cookie()->has('remember_me')) {
            return false;
        }

        $cookieFragments =  explode('|', cookie()->get('remember_me'));

        if(count($cookieFragments) !== 2) {
            return false;
        }

        list($userId, $cookie) = $cookieFragments;

        $user = $identifier->findByRememberToken($userId, $cookie);

        return $user ?? false;
    }
}