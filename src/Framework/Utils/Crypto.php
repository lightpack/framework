<?php

namespace Lightpack\Utils;

class Crypto
{
    protected string $cipher = 'AES-256-CBC';

    public function __construct(protected string $key)
    {
    }

    /**
     * Encrypts a string.
     */
    public function encrypt(string $value): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher));
        $encrypted = openssl_encrypt($value, $this->cipher, $this->key, 0, $iv);
        $encrypted = hash('sha256', $this->key).$iv.$encrypted;

        return base64_encode($encrypted);
    }

    /**
     * Decrypts a string.
     */
    public function decrypt(string $value): string|false
    {
        $value = base64_decode($value);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($value, 64, $ivLength);
        $encrypted = substr($value, $ivLength + 64);

        return openssl_decrypt($encrypted, $this->cipher, $this->key, 0, $iv);
    }

    /**
     * This method returns a 64 characters long random token hashed 
     * using SHA-256. 
     */
    public function token(): string
    {
        return hash_hmac('sha256', bin2hex(random_bytes(16)), $this->key);
    }
}
