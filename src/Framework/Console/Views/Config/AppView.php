<?php

namespace Lightpack\Console\Views\Config;

class AppView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'app' => [
        'env' => get_env('APP_ENV', 'development'),
        'url' => get_env('APP_URL', 'http://localhost'),
        'timezone' => 'UTC',
        'locale' => 'en',
        'key' => get_env('APP_KEY'),
    ]
];
PHP;
    }
}
