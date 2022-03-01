<?php

namespace Lightpack\Auth\Authenticators;

use Lightpack\Auth\AbstractAuthenticator;
use Lightpack\Auth\Identifier;
use Lightpack\Auth\Result;

class FormAuthenticator extends AbstractAuthenticator
{
    public function verify(): Result
    {
        $credentials = request()->isJson() ? request()->json() : request()->post();

        $usernameField = $this->config['fields.username'];
        $passwordField = $this->config['fields.password'];

        $username = $credentials[$usernameField] ?? null;
        $password = $credentials[$passwordField] ?? null;

        if(!$username || !$password) {
            return new Result;
        }

        $identity = $this->identifier->findByCredentials([
            $usernameField => $username,
            $passwordField => $password
        ]);

        return new Result($identity);
    }
}