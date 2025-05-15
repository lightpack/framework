<?php

namespace Lightpack\Mfa;

use Lightpack\Config\Config;

class Otp
{
    public function __construct(
        protected Config $config
    ) {}

    public function generate(string $context = 'email'): string
    {
        $length = $this->config->get("mfa.{$context}.code_length", 6);
        $type   = $this->config->get("mfa.{$context}.code_type", 'numeric'); // numeric|alpha|alnum

        if ($type === 'numeric') {
            $min = (int) str_pad('1', $length, '0');
            $max = (int) str_pad('', $length + 1, '9') - 1;
            return str_pad((string)random_int($min, $max), $length, '0', STR_PAD_LEFT);
        }

        if ($type === 'alpha') {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        } else {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        }

        return substr(str_shuffle(str_repeat($chars, $length)), 0, $length);
    }
}
