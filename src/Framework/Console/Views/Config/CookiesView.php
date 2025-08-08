<?php

namespace Lightpack\Console\Views\Config;

class CookiesView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'cookies' => [
        // Cookie settings
    ],
];
PHP;
    }
}
