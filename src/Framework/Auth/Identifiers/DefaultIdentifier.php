<?php

namespace Lightpack\Auth\Identifiers;

use Lightpack\Auth\Identifier;
use Lightpack\Auth\Models\User;
use Lightpack\Crypto\Password;

class DefaultIdentifier implements Identifier
{
    public function findByAuthToken(string $token)
    {
        $user = User::query()->where('api_token', '=', $token)->one();

        if (!$user) {
            return null;
        }

        return $user;
    }

    public function findByRememberToken($id, string $token)
    {
        $user = User::query()->where('id', '=', $id)->one();

        if (!$user) {
            return null;
        }

        if ($user->remember_token !== $token) {
            return null;
        }

        return $user;
    }

    public function findByCredentials(array $credentials)
    {
        $user = User::query()->where('email', '=', $credentials['email'])->one();

        if (!$user) {
            return null;
        }

        if (!Password::verify($credentials['password'], $user->password)) {
            return null;
        }

        return $user;
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
