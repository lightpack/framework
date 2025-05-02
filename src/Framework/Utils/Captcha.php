<?php

namespace Lightpack\Utils;

class Captcha
{
    public static function generate(int $width = 150, int $height = 50, string $font, ?string $text = null): string
    {
        if (!extension_loaded('gd')) {
            throw new \Exception('GD extension is required for CAPTCHA generation.');
        }

        $text = $text ?? substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6);
        $im = imagecreatetruecolor($width, $height);
        imagesavealpha($im, true);
        $bg = imagecolorallocatealpha($im, 255, 255, 255, 0);
        imagefill($im, 0, 0, $bg);

        // Add random lines for noise
        for ($i = 0; $i < 6; $i++) {
            $lineColor = imagecolorallocate($im, rand(100,200), rand(100,200), rand(100,200));
            imageline($im, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
        }

        // Add random dots for noise
        for ($i = 0; $i < 150; $i++) {
            $dotColor = imagecolorallocate($im, rand(100,200), rand(100,200), rand(100,200));
            imagesetpixel($im, rand(0, $width-1), rand(0, $height-1), $dotColor);
        }

        // Draw the text with random angle and position
        for ($i = 0; $i < strlen($text); $i++) {
            $fontSize = rand(18, 24);
            $angle = rand(-25, 25);
            $x = (int) (10 + $i * ($width - 20) / strlen($text));
            $y = (int) rand($height - 15, $height - 5);
            $color = imagecolorallocate($im, rand(0,80), rand(0,80), rand(0,80));
            imagettftext($im, $fontSize, $angle, $x, $y, $color, $font, $text[$i]);
        }

        // Output image to a string
        ob_start();
        imagepng($im);
        $data = ob_get_clean();
        imagedestroy($im);
        return 'data:image/png;base64,' . base64_encode($data);
    }
}
