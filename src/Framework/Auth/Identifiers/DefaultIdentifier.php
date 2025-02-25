<?php

namespace Lightpack\Auth\Identifiers;

use Lightpack\Auth\Identifier;
use Lightpack\Auth\Identity;
use Lightpack\Auth\Models\AuthUser;

class DefaultIdentifier implements Identifier
{
    public function __construct(protected AuthUser $user)
    {
        // ...
    }

    public function findById($id): ?Identity
    {
        $user = $this->user->find($id);

        if (!$user) {
            return null;
        }

        return $user;
    }

    public function findByRememberToken($id, string $token): ?Identity
    {
        $user = $this->user->query()->where('id', '=', $id)->one();

        if (!$user) {
            return null;
        }

        if ($user->remember_token !== $token) {
            return null;
        }

        return $user;
    }

    public function findByCredentials(array $credentials): ?Identity
    {
        $user = $this->user->query()->where('email', '=', $credentials['email'])->one();

        if (!$user) {
            return null;
        }

        if (!password()->verify($credentials['password'], $user->password)) {
            return null;
        }

        return $user;
    }

    public function updateLogin($id, array $fields)
    {
        $user = $this->user->find($id);

        foreach ($fields as $key => $value) {
            $user->$key = $value;
        }

        $user->save();
    }
}
