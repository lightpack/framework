<?php

namespace Lightpack\Utils;

class Password
{
    protected string $numericCharacters = '0123456789';
    protected string $specialCharacters = '!@#$%^&*()_-=+;:,.?[]{}';
    protected string $lowercaseCharacters = 'abcdefghijklmnopqrstuvwxyz';
    protected string $uppercaseCharacters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

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

    /**
     * Generates a random password string of specified length.
     * 
     * @param $length Length of password string.
     */
    public function generate(int $length = 8): string
    {
        if ($length < 6) {
            throw new \Exception('Password length must be at least 6 characters.');
        }

        $password = $this->uppercaseCharacters[rand(0, strlen($this->uppercaseCharacters) - 1)];
        $password .= $this->lowercaseCharacters[rand(0, strlen($this->lowercaseCharacters) - 1)];
        $password .= $this->numericCharacters[rand(0, strlen($this->numericCharacters) - 1)];
        $password .= $this->specialCharacters[rand(0, strlen($this->specialCharacters) - 1)];
        $password = str_shuffle($password);

        $chars = $this->numericCharacters . $this->specialCharacters . $this->uppercaseCharacters . $this->lowercaseCharacters;
        $count = strlen($chars);

        for ($i = 0; $i < $length - 4; $i++) {
            $password .= $chars[rand(0, $count - 1)];
        }

        return str_shuffle($password);
    }

    /**
     * Checks if supplied password is strong enough. It uses the following 
     * rules to check if password is strong:
     * 
     * 1. Password should be at least 8 characters long.
     * 2. Password should contain at least one uppercase letter.
     * 3. Password should contain at least one lowercase letter.
     * 4. Password should contain at least one number.
     * 5. Password should contain at least one special character.
     * 
     * @param $password Plain text password.
     * @return string One of the following values: 'weak', 'medium', 'strong'.
     */
    public function strength(string $password): string
    {
        $strength = 0;

        if (strlen($password) >= 8) {
            $strength++;
        }

        if (false !== strpbrk($password, $this->uppercaseCharacters)) {
            $strength++;
        }

        if (false !== strpbrk($password, $this->lowercaseCharacters)) {
            $strength++;
        }

        if (false !== strpbrk($password, $this->numericCharacters)) {
            $strength++;
        }

        if (false !== strpbrk($password, $this->specialCharacters)) {
            $strength++;
        }

        match ($strength) {
            1, 2 => $strength = 'weak',
            3, 4 => $strength = 'medium',
            5 => $strength = 'strong',
        };

        return $strength;
    }
}
