<?php

namespace Lightpack\Console\Views\Config;

class AuthView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'auth' => [
        // Authentication settings
    ],
];
PHP;
    }
}
