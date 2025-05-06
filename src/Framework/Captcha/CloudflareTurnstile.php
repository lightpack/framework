<?php

namespace Lightpack\Captcha;

class CloudflareTurnstile implements CaptchaInterface
{
    private $request;
    private string $siteKey;
    private string $secretKey;

    public function __construct($request, string $siteKey, string $secretKey)
    {
        $this->request = $request;
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
    }

    public function generate(): string
    {
        return '<div class="cf-turnstile" data-sitekey="' . htmlspecialchars($this->siteKey) . '"></div>' .
               '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    }

    public function verify(): bool
    {
        $input = $this->request->input('cf-turnstile-response');
        return $this->verifyInput($input);
    }

    protected function verifyInput($input): bool
    {
        if (empty($input)) {
            return false;
        }
        $data = [
            'secret' => $this->secretKey,
            'response' => $input,
        ];
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        $result = $this->fetchVerifyResponse('https://challenges.cloudflare.com/turnstile/v0/siteverify', $options);
        $response = json_decode($result, true);
        return isset($response['success']) && $response['success'] === true;
    }

    protected function fetchVerifyResponse($url, $options = [])
    {
        return file_get_contents($url, false, $options ? stream_context_create($options) : null);
    }
}
