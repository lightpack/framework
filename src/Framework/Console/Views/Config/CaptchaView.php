<?php

namespace Lightpack\Console\Views\Config;

class CaptchaView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'captcha' => [
        // Captcha settings
    ],
];
PHP;
    }
}
