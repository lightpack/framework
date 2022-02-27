<?php

namespace Lightpack\Auth;

interface Authenticator
{
    public function verify(Identifier $identifier, array $config);
}