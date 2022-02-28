<?php

namespace Lightpack\Auth;

use Lightpack\Auth\Identity;

abstract class AbstractAuthenticator
{
    /** @var Identity */
    protected $identity;

    abstract public function verify(Identifier $identifier, array $config): bool;

    public function setIdentity(Identity $identity): void
    {
        $this->identity = $identity;
    }

    public function getIdentity(): Identity
    {
        return $this->identity;
    }
}