<?php

namespace Lightpack\Auth;

interface IdentifierInterface
{
    public function findById($id): ?IdentityInterface;
    public function findByRememberToken($id, string $token): ?IdentityInterface;
    public function findByCredentials(array $credentials): ?IdentityInterface;
    public function updateLogin($id, array $fields);
}
