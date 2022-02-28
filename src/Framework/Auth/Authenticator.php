<?php

namespace Lightpack\Auth;

use Lightpack\Auth\Identity;

interface Authenticator
{
    public function verify(Identifier $identifier, array $config): ?Identity;
}