<?php

namespace Lightpack\Auth\Identifiers;

use Lightpack\Auth\Identifier;
use Lightpack\Auth\Identity;
use Lightpack\Auth\Models\User;
use Lightpack\Crypto\Password;

class DefaultIdentifier implements Identifier
{
    public function findByAuthToken(string $token): ?Identity
    {
        $user = User::query()->where('api_token', '=', $token)->one();

        if (!$user) {
            return null;
        }

        return new Identity($user->toArray());
    }

    public function findByRememberToken($id, string $token): ?Identity
    {
        $user = User::query()->where('id', '=', $id)->one();

        if (!$user) {
            return null;
        }

        if ($user->remember_token !== $token) {
            return null;
        }

        return new Identity($user->toArray());
    }

    public function findByCredentials(array $credentials): ?Identity
    {
        $user = User::query()->where('email', '=', $credentials['email'])->one();

        if (!$user) {
            return null;
        }

        if (!Password::verify($credentials['password'], $user->password)) {
            return null;
        }

        return new Identity($user->toArray());
    }

    public function updateLogin($id, array $fields)
    {
        $user = new User($id);

        foreach ($fields as $key => $value) {
            $user->$key = $value;
        }

        $user->save();
    }
}
