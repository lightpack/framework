<?php

namespace Lightpack\Utils;

/**
 * Fluent OTP/code generator utility.
 */
class Otp
{
    protected int $length = 6;
    protected string $type = 'numeric'; // 'numeric', 'alpha', 'alnum', 'custom'
    protected ?string $charset = null;
    protected ?string $demo = null;

    public function length(int $length): self
    {
        $this->length = $length;
        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function charset(string $charset): self
    {
        $this->charset = $charset;
        return $this;
    }

    public function demo(string $code): self
    {
        $this->demo = $code;
        return $this;
    }

    /**
     * Generate the OTP/code.
     * @param bool $isDemo Optional: pass true to always use demo code
     * @return string
     */
    public function generate(bool $isDemo = false): string
    {
        if ($this->demo !== null && $isDemo) {
            return $this->demo;
        }

        $length = $this->length;
        if ($length < 1 || $length > 32) {
            $length = 6;
        }

        if ($this->type === 'numeric') {
            $max = (int) str_repeat('9', $length);
            $num = random_int(0, $max);
            return str_pad((string)$num, $length, '0', STR_PAD_LEFT);
        }

        if ($this->type === 'alpha') {
            $chars = $this->charset ?: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        } elseif ($this->type === 'alnum') {
            $chars = $this->charset ?: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        } elseif ($this->type === 'custom' && $this->charset) {
            $chars = $this->charset;
        } else {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        }

        $pool = str_repeat($chars, (int) ceil($length / strlen($chars)));
        return substr(str_shuffle($pool), 0, $length);
    }
}
