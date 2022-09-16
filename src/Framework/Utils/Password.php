<?php

namespace Lightpack\Utils;

class Password
{
    /**
     * Returns hashed string for supplied password.
     * 
     * @param $password User supplied password string.
     */
    public function hash(string $password): string
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
    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
