<?php

namespace Lightpack\Utils;

class Crypto
{
    protected string $cipher = 'AES-256-CBC';

    public function encrypt(string $value, string $key): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher));
        $encrypted = openssl_encrypt($value, $this->cipher, $key, 0, $iv);
        $encrypted = hash('sha256', $key).$iv.$encrypted;

        return base64_encode($encrypted);
    }

    public function decrypt(string $value, string $key): string|false
    {
        $value = base64_decode($value);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($value, 64, $ivLength);
        $encrypted = substr($value, $ivLength + 64);

        return openssl_decrypt($encrypted, $this->cipher, $key, 0, $iv);
    }
}
