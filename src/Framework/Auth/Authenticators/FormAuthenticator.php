<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\Authenticator;
use Lightpack\Auth\IdentityInterface;

class FormAuthenticator extends Authenticator
{
    public function verify(): ?IdentityInterface
    {
        $credentials = request()->input();

        if (empty($credentials)) {
            return null;
        }

        return $this->identifier->findByCredentials($credentials);
    }
}
