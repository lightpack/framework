<?php

namespace Lightpack\Auth\Identifiers;

use Lightpack\Auth\Identifier;

class CustomIdentifier implements Identifier
{
    public function findByAuthToken(string $token)
    {
        // nothing to do here
    }

    public function findByRememberToken($id, string $token)
    {
        // nothing to do here
    }

    public function findByCredentials(array $credentials)
    {
        if ($credentials['mobile'] !== '8073277296' || $credentials['otp'] !== '210388') {
            return null;
        }

        $user = new \stdClass;
        $user->id = 2;

        return $user;
    }

    public function updateLogin($id, array $fields)
    {
        // nothing to do here
    }
}
