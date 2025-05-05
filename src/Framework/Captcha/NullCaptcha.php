<?php

namespace Lightpack\Captcha;

class NullCaptcha implements CaptchaInterface
{
    public function generate(): string
    {
        return '';
    }

    public function verify(string $input): bool
    {
        return true;
    }
}
