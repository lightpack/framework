<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\AbstractAuthenticator;
use Lightpack\Auth\Identity;

class FormAuthenticator extends AbstractAuthenticator
{
    public function verify(): ?Identity
    {
        $credentials = request()->input();

        if (empty($credentials)) {
            return null;
        }

        return $this->identifier->findByCredentials($credentials);
    }
}
