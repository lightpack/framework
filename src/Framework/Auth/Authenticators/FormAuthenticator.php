<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\Authenticator;
use Lightpack\Auth\Identifier;

class FormAuthenticator implements Authenticator
{
    public function verify(Identifier $identifier, array $config)
    {
        $credentials = request()->isJson() ? request()->json() : request()->post();

        $usernameField = $config['fields.username'];
        $passwordField = $config['fields.password'];

        $username = $credentials[$usernameField] ?? null;
        $password = $credentials[$passwordField] ?? null;

        if(!$username || !$password) {
            return false;
        }

        $user = $identifier->findByCredentials([
            $usernameField => $username,
            $passwordField => $password
        ]);

        return $user ?? false;
    }
}