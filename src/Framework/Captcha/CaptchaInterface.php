<?php

namespace Lightpack\Captcha;

interface CaptchaInterface
{
    /**
     * Generate the CAPTCHA challenge (image, HTML, etc.)
     * @return string
     */
    public function generate(): string;

    /**
     * Verify the CAPTCHA response
     * @param mixed $input User input or request data
     * @return bool
     */
    public function verify(string $input): bool;
}
