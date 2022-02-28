<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\Authenticator;
use Lightpack\Auth\Identifier;
use Lightpack\Auth\Identity;

class FormAuthenticator implements Authenticator
{
    public function verify(Identifier $identifier, array $config): ?Identity
    {
        $credentials = request()->isJson() ? request()->json() : request()->post();

        $usernameField = $config['fields.username'];
        $passwordField = $config['fields.password'];

        $username = $credentials[$usernameField] ?? null;
        $password = $credentials[$passwordField] ?? null;

        if(!$username || !$password) {
            return null;
        }

        return $identifier->findByCredentials([
            $usernameField => $username,
            $passwordField => $password
        ]);
    }
}