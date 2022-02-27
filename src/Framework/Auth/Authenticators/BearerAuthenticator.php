<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\Authenticator;
use Lightpack\Auth\Identifier;

class BearerAuthenticator implements Authenticator
{
    public function verify(Identifier $identifier, array $config)
    {
       $token = app('request')->bearerToken();
        
        if(null === $token) {
            return false;
        }

        $tokenHash = hash_hmac('sha1', $token, '');

        $user = $identifier->findByAuthToken($tokenHash);

        return $user ? $user->api_token : false;
    }
}