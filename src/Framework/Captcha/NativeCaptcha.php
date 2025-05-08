<?php

namespace Lightpack\Captcha;

use Lightpack\Session\Session;
use Lightpack\Http\Request;

class NativeCaptcha implements CaptchaInterface
{
    protected const SESSION_KEY = '_captcha_text';

    protected int $width = 150;
    protected int $height = 50;
    protected string $font;
    protected ?string $text = null;
    protected Request $request;
    protected Session $session;

    public function __construct(Request $request, Session $session)
    {
        $this->request = $request;
        $this->session = $session;
    }

    public function width(int $width): self
    {
        $this->width = $width;
        return $this;
    }

    public function height(int $height): self
    {
        $this->height = $height;
        return $this;
    }

    public function font(string $font): self
    {
        $this->font = $font;
        return $this;
    }

    public function text(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function generate(): string
    {
        if (!extension_loaded('gd')) {
            throw new \Exception('GD extension is required for CAPTCHA generation.');
        }
        if (empty($this->font)) {
            throw new \Exception('Font path is required for CAPTCHA generation.');
        }

        $text = $this->text ?? substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6);
        $this->session->set(self::SESSION_KEY, $text);

        $im = imagecreatetruecolor($this->width, $this->height);
        imagesavealpha($im, true);
        $bg = imagecolorallocatealpha($im, 255, 255, 255, 0);
        imagefill($im, 0, 0, $bg);
        for ($i = 0; $i < 6; $i++) {
            $lineColor = imagecolorallocate($im, rand(100,200), rand(100,200), rand(100,200));
            imageline($im, rand(0, $this->width), rand(0, $this->height), rand(0, $this->width), rand(0, $this->height), $lineColor);
        }
        for ($i = 0; $i < 150; $i++) {
            $dotColor = imagecolorallocate($im, rand(100,200), rand(100,200), rand(100,200));
            imagesetpixel($im, rand(0, $this->width-1), rand(0, $this->height-1), $dotColor);
        }
        for ($i = 0; $i < strlen($text); $i++) {
            $fontSize = rand(18, 24);
            $angle = rand(-25, 25);
            $x = (int) (10 + $i * ($this->width - 20) / strlen($text));
            $y = (int) rand($this->height - 15, $this->height - 5);
            $color = imagecolorallocate($im, rand(0,80), rand(0,80), rand(0,80));
            imagettftext($im, $fontSize, $angle, $x, $y, $color, $this->font, $text[$i]);
        }
        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);
        return 'data:image/png;base64,' . base64_encode($data);
    }

    /**
     * Verify the user input against the generated captcha text
     * stored in session or cache for statelessness.
     */
    public function verify(): bool
    {
        $input = $this->request->input('captcha');
        return $this->verifyInput($input);
    }

    protected function verifyInput($input): bool
    {
        $expected = $this->session->get(self::SESSION_KEY);
        if ($expected === null) {
            return false;
        }
        $this->session->delete(self::SESSION_KEY);
        return $input === $expected;
    }
}
