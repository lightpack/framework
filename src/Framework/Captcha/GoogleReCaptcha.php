<?php

namespace Lightpack\Captcha;

class GoogleReCaptcha implements CaptchaInterface
{
    protected $request;
    protected string $siteKey;
    protected string $secretKey;

    public function __construct($request, string $siteKey, string $secretKey)
    {
        $this->request = $request;
        $this->siteKey = $siteKey;
        $this->secretKey = $secretKey;
    }

    public function generate(): string
    {
        return '<div class="g-recaptcha" data-sitekey="' . htmlspecialchars($this->siteKey) . '"></div>' .
               '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
    }

    public function verify(): bool
    {
        $input = $this->request->input('g-recaptcha-response');
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
        $context  = stream_context_create($options);
        $result = $this->fetchVerifyResponse('https://www.google.com/recaptcha/api/siteverify', $options);
        $response = json_decode($result, true);
        return isset($response['success']) && $response['success'] === true;
    }

    protected function fetchVerifyResponse($url, $options = [])
    {
        return file_get_contents($url, false, $options ? stream_context_create($options) : null);
    }
}
