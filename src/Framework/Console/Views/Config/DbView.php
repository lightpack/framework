<?php

namespace Lightpack\Console\Views\Config;

class DbView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

return [
    'db' => [
        // Database connection settings
    ],
];
PHP;
    }
}
