<?php

namespace Lightpack\Auth;

interface Identifier
{
    public function findByAuthToken(string $token);
    public function findByRememberToken($id, string $token);
    public function findByCredentials(array $credentials);
    public function updateLogin($id, array $fields);
}