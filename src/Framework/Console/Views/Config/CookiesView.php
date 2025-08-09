<?php

namespace Lightpack\Console\Views\Config;

class CookiesView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'cookie' => [
        'secret' => get_env('APP_KEY', 'secret'),
    ],
];
PHP;
    }
}
