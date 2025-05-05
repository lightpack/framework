<?php

namespace Lightpack\Captcha;

class GoogleReCaptcha implements CaptchaInterface
{
    private string $siteKey;
    private string $secretKey;

    public function __construct(string $siteKey, string $secretKey)
    {
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
    }

    public function generate(): string
    {
        return '<div class="g-recaptcha" data-sitekey="' . htmlspecialchars($this->siteKey) . '"></div>';
    }

    public function verify(string $input): bool
    {
        if (empty($input)) {
            return false;
        }
        $response = file_get_contents(
            'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($this->secretKey) . '&response=' . urlencode($input)
        );
        $result = json_decode($response, true);
        return isset($result['success']) && $result['success'] === true;
    }
}
