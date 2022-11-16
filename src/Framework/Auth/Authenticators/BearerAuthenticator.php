<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\AbstractAuthenticator;
use Lightpack\Auth\Identity;

class BearerAuthenticator extends AbstractAuthenticator
{
    public function verify(): ?Identity
    {
        $token = request()->bearerToken();

        if (null === $token) {
            return null;
        }

        $tokenHash = hash_hmac('sha1', $token, '');

        return $this->identifier->findByAuthToken($tokenHash);
    }
}
