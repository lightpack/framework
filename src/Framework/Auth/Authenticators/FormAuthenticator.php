<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\AbstractAuthenticator;
use Lightpack\Auth\Identifier;

class FormAuthenticator extends AbstractAuthenticator
{
    public function verify(Identifier $identifier, array $config): bool
    {
        $credentials = request()->isJson() ? request()->json() : request()->post();

        $usernameField = $config['fields.username'];
        $passwordField = $config['fields.password'];

        $username = $credentials[$usernameField] ?? null;
        $password = $credentials[$passwordField] ?? null;

        if(!$username || !$password) {
            return false;
        }

        $this->identity = $identifier->findByCredentials([
            $usernameField => $username,
            $passwordField => $password
        ]);

        return null !== $this->identity;
    }
}