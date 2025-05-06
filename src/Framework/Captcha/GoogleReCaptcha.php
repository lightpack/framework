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

    protected function fetchVerifyResponse($url)
    {
        return file_get_contents($url);
    }

    public function verify(string $input): bool
    {
        if (empty($input)) {
            return false;
        }
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($this->secretKey) . '&response=' . urlencode($input);
        $response = $this->fetchVerifyResponse($url);
        $result = json_decode($response, true);
        return isset($result['success']) && $result['success'] === true;
    }
}
