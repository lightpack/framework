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
        // Application settings
    ],
];
PHP;
    }
}
