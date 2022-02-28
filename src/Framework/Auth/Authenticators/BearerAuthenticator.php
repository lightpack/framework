<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\AbstractAuthenticator;
use Lightpack\Auth\Identifier;

class BearerAuthenticator extends AbstractAuthenticator
{
    public function verify(Identifier $identifier, array $config): bool
    {
       $token = app('request')->bearerToken();
        
        if(null === $token) {
            return false;
        }

        $tokenHash = hash_hmac('sha1', $token, '');

        $this->identity = $identifier->findByAuthToken($tokenHash);

        return null !== $this->identity;
    }
}