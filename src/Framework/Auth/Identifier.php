<?php

namespace Lightpack\Auth;

interface Identifier
{
    public function findByAuthToken(string $token): ?Identity;
    public function findByRememberToken($id, string $token): ?Identity;
    public function findByCredentials(array $credentials): ?Identity;
    public function updateLogin($id, array $fields);
}