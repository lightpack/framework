<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\Identity;
use Lightpack\Auth\AbstractAuthenticator;

class CookieAuthenticator extends AbstractAuthenticator
{
    public function verify(): ?Identity
    {
        if (!cookie()->has('remember_token')) {
            return null;
        }
        $cookieFragments =  explode('|', cookie()->get('remember_token') ?? '');

        if (count($cookieFragments) !== 2) {
            return null;
        }

        list($userId, $cookie) = $cookieFragments;

        return $this->identifier->findByRememberToken($userId, $cookie);
    }
}
