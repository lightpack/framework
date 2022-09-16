<?php

namespace Lightpack\Crypto;

class Security
{
    /**
     * Returns hashed string for supplied password.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verifies if user supplied password matches hashed 
     * password value.
     * 
     * @param $password Plain text password.
     * @param $hash Hashed password.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
