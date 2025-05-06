<?php

namespace Lightpack\Captcha;

class NullCaptcha implements CaptchaInterface
{
    private $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function generate(): string
    {
        return '';
    }

    public function verify(): bool
    {
        return true;
    }
}
