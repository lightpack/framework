<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\IdentityInterface;
use Lightpack\Auth\Authenticator;

class CookieAuthenticator extends Authenticator
{
    public function verify(): ?IdentityInterface
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
