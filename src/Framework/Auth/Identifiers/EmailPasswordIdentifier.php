<?php

namespace Lightpack\Auth\Identifiers;

use Lightpack\Auth\IdentifierInterface;
use Lightpack\Auth\IdentityInterface;
use Lightpack\Auth\Models\AuthUser;

class EmailPasswordIdentifier implements IdentifierInterface
{
    public function __construct(protected AuthUser $user)
    {
        // ...
    }

    public function findById($id): ?IdentityInterface
    {
        /** @var AuthUser */
        $user = $this->user->find($id);

        if (!$user) {
            return null;
        }

        return $user;
    }

    public function findByRememberToken($id, string $token): ?IdentityInterface
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

    public function findByCredentials(array $credentials): ?IdentityInterface
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
