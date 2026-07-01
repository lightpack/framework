<?php

namespace Lightpack\Lang;

class LangView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'default' => get_env('APP_LOCALE', 'en'),
    'fallback' => 'en',
    'supported' => ['en'],
    'path' => DIR_ROOT . '/app/Lang',
];
PHP;
    }
}
