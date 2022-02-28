<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\Authenticator;
use Lightpack\Auth\Identifier;
use Lightpack\Auth\Identity;

class BearerAuthenticator implements Authenticator
{
    public function verify(Identifier $identifier, array $config): ?Identity
    {
       $token = app('request')->bearerToken();
        
        if(null === $token) {
            return null;
        }

        $tokenHash = hash_hmac('sha1', $token, '');

        return $identifier->findByAuthToken($tokenHash);
    }
}