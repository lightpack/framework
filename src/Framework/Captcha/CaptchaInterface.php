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
     * Verify the captcha response from the request.
     *
     * @return bool
     */
    public function verify(): bool;
}
