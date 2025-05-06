<?php

namespace Lightpack\Captcha;

use Lightpack\Http\Request;

class GoogleReCaptchaInvisible extends GoogleReCaptcha
{
    public function generate(): string
    {
        // Render an invisible reCAPTCHA button (the form should have onSubmit="return onSubmit(event)")
        return '<button class="g-recaptcha" data-sitekey="' . htmlspecialchars($this->siteKey) . '" data-callback="onSubmit" data-size="invisible">Submit</button>' .
               '<script src="https://www.google.com/recaptcha/api.js" async defer></script>' .
               '<script>function onSubmit(token) { document.querySelector("form").submit(); }</script>';
    }
}
