<?php

namespace Lightpack\Captcha;

class CloudflareTurnstile implements CaptchaInterface
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
        return '<div class="cf-turnstile" data-sitekey="' . htmlspecialchars($this->siteKey) . '"></div>';
    }

    protected function fetchVerifyResponse($data)
    {
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        $context = stream_context_create($options);
        return file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
    }

    public function verify(string $input): bool
    {
        if (empty($input)) {
            return false;
        }
        $data = [
            'secret' => $this->secretKey,
            'response' => $input,
        ];
        $result = $this->fetchVerifyResponse($data);
        $result = json_decode($result, true);
        return isset($result['success']) && $result['success'] === true;
    }
}
